<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\UserTaskSubmission;
use App\Models\UserModuleProgress;
use App\Models\Task;
use App\Services\GamificationService;
use App\Events\TaskGraded;
use App\Notifications\TaskGraded as TaskGradedNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Inertia\Inertia;

class TaskSubmissionController extends Controller
{
    protected $gamificationService;

    public function __construct(GamificationService $gamificationService)
    {
        $this->gamificationService = $gamificationService;
    }

    /**
     * Menampilkan daftar tugas yang perlu dinilai.
     */
    public function index(Request $request)
    {
        $user = Auth::user();

        // --- 1. CEK AKSES MANUAL (TANPA hasAnyRole) ---
        $isAuthorized = $user->roles()->whereIn('name', ['admin', 'mentor'])->exists();

        if (!$isAuthorized) {
            abort(403, 'Unauthorized Access: Role mismatch');
        }

        // 2. Query Utama
        $query = UserTaskSubmission::query()
            ->with(['user', 'task.module.course']);

        // 3. Filter Pencarian
        if ($request->search) {
            $query->where(function ($q) use ($request) {
                $q->whereHas('user', fn($sq) => $sq->where('name', 'like', "%{$request->search}%"))
                    ->orWhereHas('task', fn($sq) => $sq->where('task_title', 'like', "%{$request->search}%"));
            });
        }

        // 4. Filter Status
        if ($request->status) {
            if ($request->status === 'pending') {
                $query->where('is_graded', false);
            } elseif ($request->status === 'graded') {
                $query->where('is_graded', true);
            }
        }

        // 5. Filter Kursus
        if ($request->course_id) {
            $query->whereHas('task.module', fn($q) => $q->where('course_id', $request->course_id));
        }

        // 6. Sorting
        $sortField = $request->input('sort', 'submission_date');
        $sortDirection = $request->input('direction', 'desc');
        $query->orderBy($sortField, $sortDirection);

        // 7. Pagination & Mapping
        $submissions = $query->paginate(10)
            ->withQueryString()
            ->through(function ($sub) {
                return [
                    'id' => $sub->id,
                    'student_name' => $sub->user->name ?? 'Unknown',
                    'student_avatar' => $sub->user->profile_photo_url,
                    'task_title' => $sub->task->task_title ?? 'Untitled',
                    'course_title' => $sub->task->module->course->title ?? '-',
                    'is_graded' => (bool) $sub->is_graded,
                    'is_late' => (bool) $sub->is_late, // Status Terlambat
                    'score' => $sub->score_mentor,

                    // Format Tanggal
                    'submitted_at' => $sub->submission_date ? Carbon::parse($sub->submission_date)->diffForHumans() : '-',
                    'submitted_date_full' => $sub->submission_date ? Carbon::parse($sub->submission_date)->format('d M Y H:i') : '-',

                    'file_url' => $sub->submission_file_url ? asset('storage/' . $sub->submission_file_url) : null,
                    'feedback' => $sub->feedback_mentor,
                ];
            });

        return Inertia::render('Mentor/Tasks/Index', [
            'submissions' => $submissions,
            'filters' => $request->only(['search', 'course_id', 'status', 'sort', 'direction']),
            'courses' => \App\Models\Course::where('is_published', true)->orderBy('title')->select('id', 'title')->get()
        ]);
    }

    /**
     * Proses Upload Tugas oleh Siswa (Store).
     * [UPDATED] MENGGUNAKAN LOGIKA RELATIVE DEADLINE
     */
    public function store(Request $request, $taskId)
    {
        $request->validate([
            'file' => 'required|file|mimes:pdf,doc,docx,ppt,pptx,zip|max:10240',
        ]);

        $user = Auth::user();

        // PENTING: Load relasi module.course agar Accessor di Model bisa menghitung tanggal enrollment
        $task = Task::with('module.course')->findOrFail($taskId);

        // ---------------------------------------------------------
        // LOGIKA SOFT DEADLINE (HYBRID: FIXED & RELATIVE)
        // ---------------------------------------------------------
        $isLate = false;

        // Panggil Accessor 'personal_deadline' dari Model Task
        // Ini akan otomatis menghitung berdasarkan enrollment user (jika relative)
        // atau tanggal kalender (jika fixed).
        $deadline = $task->personal_deadline;

        // Jika deadline ada (tidak null) DAN waktu sekarang melewatinya
        if ($deadline && Carbon::now()->greaterThan($deadline)) {
            $isLate = true;
        }
        // ---------------------------------------------------------

        // Simpan File
        $path = $request->file('file')->store('submissions', 'public');

        // Simpan ke Database
        UserTaskSubmission::updateOrCreate(
            [
                'user_id' => $user->id,
                'task_id' => $task->id
            ],
            [
                'submission_file_url' => $path,
                'submission_date'     => now(),
                'is_late'             => $isLate, // Simpan status hasil hitungan di atas
                'is_graded'           => false,   // Reset status penilaian
                'score_mentor'        => null,    // Reset nilai
            ]
        );

        // Update Progress Modul (Agar modul dianggap selesai secara akses)
        UserModuleProgress::updateOrCreate(
            ['user_id' => $user->id, 'module_id' => $task->module_id],
            [
                'status' => 'completed',
                'completion_date' => now(),
                'last_access_at' => now()
            ]
        );

        // Feedback Pesan
        $msg = $isLate
            ? 'Tugas berhasil dikumpulkan (Tercatat Terlambat). Menunggu penilaian.'
            : 'Tugas berhasil dikumpulkan tepat waktu. Menunggu penilaian.';

        return back()->with('success', $msg);
    }

    /**
     * Proses Penilaian oleh Mentor (Update).
     */
    public function update(Request $request, $id)
    {
        $user = Auth::user();

        // --- CEK AKSES MANUAL (TANPA hasAnyRole) ---
        $isAuthorized = $user->roles()->whereIn('name', ['admin', 'mentor'])->exists();
        if (!$isAuthorized) {
            abort(403, 'Unauthorized');
        }

        $request->validate([
            'score_mentor' => 'required|integer|min:0|max:100',
            'feedback_mentor' => 'nullable|string',
        ]);

        $submission = UserTaskSubmission::with(['task', 'user', 'task.module'])->findOrFail($id);

        // Update Penilaian
        $submission->update([
            'score_mentor' => $request->score_mentor,
            'feedback_mentor' => $request->feedback_mentor,
            'is_graded' => true,
        ]);

        // Logika Kelulusan (KKM 70)
        $passingScore = 70;

        if ($request->score_mentor >= $passingScore) {

            // A. Pastikan Modul Completed
            UserModuleProgress::updateOrCreate(
                ['user_id' => $submission->user_id, 'module_id' => $submission->task->module_id],
                ['status' => 'completed', 'completion_date' => now()]
            );

            // B. Beri Poin (Cek Duplikasi dulu)
            $alreadyRewarded = $submission->user->pointLogs()
                ->where('related_type', get_class($submission->task))
                ->where('related_id', $submission->task->id)
                ->exists();

            if (!$alreadyRewarded && $submission->task->points_reward > 0) {
                $this->gamificationService->awardPoints(
                    $submission->user,
                    $submission->task->points_reward,
                    'TASK_PASS',
                    $submission->task
                );
            }

            // C. Cek Finish Course
            $this->gamificationService->tryFinishCourse(
                $submission->user,
                $submission->task->module->course_id
            );
        }

        // Notifikasi
        try {
            $statusMsg = $request->score_mentor >= $passingScore ? "LULUS" : "PERLU PERBAIKAN";
            $message = "Nilai tugas '{$submission->task->task_title}' diperbarui: {$submission->score_mentor}/100 ({$statusMsg}).";

            $submission->user->notify(new TaskGradedNotification($submission, $message));
            TaskGraded::dispatch($submission);
        } catch (\Exception $e) {
            Log::error("Notification Error: " . $e->getMessage());
        }

        return back()->with('success', 'Tugas berhasil dinilai.');
    }
}
