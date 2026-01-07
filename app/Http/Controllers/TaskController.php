<?php

namespace App\Http\Controllers;

use App\Events\TaskSubmitted;
use App\Models\Task;
use App\Models\UserModuleProgress;
use App\Models\UserTaskSubmission;
use App\Services\GamificationService; // [PENTING] Import Service
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log; // Import Log Facade
use Carbon\Carbon;
use Inertia\Inertia;

class TaskController extends Controller
{
    protected $gamificationService;

    // Inject GamificationService
    public function __construct(GamificationService $gamificationService)
    {
        $this->gamificationService = $gamificationService;
    }

    /**
     * Halaman Instruksi & Upload Tugas.
     * Parameter $moduleId (Konsisten dengan Quiz/Text).
     */
    public function show($moduleId)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        // 1. Ambil Task dengan Relasi 'module.course'
        $task = Task::with('module.course')
            ->where('module_id', $moduleId)
            ->firstOrFail();

        // 2. Cek Submission Terakhir
        $submission = UserTaskSubmission::where('user_id', $user->id)
            ->where('task_id', $task->id)
            ->latest('submission_date')
            ->first();

        // 3. Render Inertia
        return Inertia::render('Task/Player', [
            'task' => [
                'id' => $task->id,
                'title' => $task->task_title,
                'description' => $task->description,
                'points' => $task->points_reward,
                'course_id' => $task->module->course_id,
                'module_title' => $task->module->module_title,
                'max_score' => $task->max_score,

                // Kirim Deadline Personal (Fixed atau Relative)
                'deadline' => $task->personal_deadline
                    ? $task->personal_deadline->toIso8601String()
                    : null,
            ],
            'submission' => $submission ? [
                'status' => $submission->is_graded ? 'Graded' : 'Pending Review',
                'file_url' => $submission->submission_file_url ? Storage::url($submission->submission_file_url) : null,
                'submitted_at' => $submission->submission_date,
                'is_late' => (bool) $submission->is_late,
                'score' => $submission->score_mentor,
                'feedback' => $submission->feedback_mentor
            ] : null
        ]);
    }

    /**
     * Proses Upload File Tugas
     * Memicu Event Real-time ke Mentor & Cek Deadline.
     */
    public function submit(Request $request, $id)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        // Load relasi module.course agar Accessor bisa hitung durasi enrollment
        $task = Task::with('module.course')->findOrFail($id);

        // 1. Validasi File
        $request->validate([
            'file' => 'required|file|mimes:pdf,zip,doc,docx,png,jpg,ppt,pptx|max:10240', // Max 10MB
            'notes' => 'nullable|string|max:1000'
        ]);

        // ---------------------------------------------------------
        // 2. LOGIKA SOFT DEADLINE (HYBRID)
        // ---------------------------------------------------------
        $isLate = false;
        $deadline = $task->personal_deadline;

        if ($deadline && Carbon::now()->greaterThan($deadline)) {
            $isLate = true;
        }
        // ---------------------------------------------------------

        $path = $request->file('file')->store('tasks', 'public');

        // 3. Simpan/Update Submission
        $submission = UserTaskSubmission::updateOrCreate(
            ['user_id' => $user->id, 'task_id' => $task->id],
            [
                'submission_file_url' => $path,
                'submission_date' => now(),
                'status' => 'submitted',

                'is_late' => $isLate,
                'is_graded' => false,
                'score_mentor' => null,
                // 'notes' => $request->notes 
            ]
        );

        // 4. Update Progress Modul (Soft Blocking)
        // Langsung set 'completed' agar modul selanjutnya terbuka
        UserModuleProgress::updateOrCreate(
            ['user_id' => $user->id, 'module_id' => $task->module_id],
            [
                'status' => 'completed',
                'completion_date' => now(),
                'last_access_at' => now()
            ]
        );

        // 5. Trigger Real-time Event ke Mentor
        try {
            TaskSubmitted::dispatch($submission);
        } catch (\Exception $e) {
            Log::error("Broadcasting Error: " . $e->getMessage());
        }

        // 6. [LOGIKA BARU] CEK PENYELESAIAN KURSUS (BONUS POIN)
        // Panggil service untuk mengecek apakah ini modul terakhir.
        // Catatan: Biasanya tugas harus dinilai dulu untuk lulus kursus,
        // tapi jika sistem Anda "Submit = Selesai Modul", maka kita cek di sini.
        // Jika sistem Anda "Nilai >= KKM = Selesai", pindahkan logika ini ke controller penilaian Mentor.

        $courseCompleted = $this->gamificationService->tryFinishCourse($user, $task->module->course_id);

        // 7. Feedback Pesan
        if ($courseCompleted) {
            $msg = 'Selamat! Tugas terkirim dan Anda telah menyelesaikan seluruh materi kursus!';
        } elseif ($isLate) {
            $msg = 'Tugas berhasil dikirim (Tercatat Terlambat). Menunggu pemeriksaan Mentor.';
        } else {
            $msg = 'Tugas berhasil dikirim tepat waktu! Silakan lanjut ke materi berikutnya.';
        }

        return redirect()->back()->with('success', $msg);
    }

    /**
     * Halaman Riwayat Tugas (My Submissions)
     * Menampilkan semua tugas yang pernah dikerjakan user.
     */
    public function history(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        $query = UserTaskSubmission::with(['task.module.course'])
            ->where('user_id', $user->id);

        // Filter sederhana
        if ($request->status === 'graded') {
            $query->where('is_graded', true);
        } elseif ($request->status === 'pending') {
            $query->where('is_graded', false);
        }

        $submissions = $query->latest('submission_date')
            ->paginate(10)
            ->withQueryString()
            ->through(function ($sub) {
                return [
                    'id' => $sub->id,
                    'task_title' => $sub->task->task_title,
                    'course_title' => $sub->task->module->course->title ?? 'Kursus Dihapus',
                    'submission_date' => Carbon::parse($sub->submission_date)->diffForHumans(),
                    'full_date' => Carbon::parse($sub->submission_date)->format('d M Y, H:i'),
                    'status' => $sub->is_graded ? 'Dinilai' : 'Menunggu',
                    'is_late' => $sub->is_late,
                    'score' => $sub->score_mentor,
                    'feedback' => $sub->feedback_mentor,
                    'link' => route('task.show', $sub->task->module_id), // Link ke detail tugas
                ];
            });

        return Inertia::render('Task/History', [
            'submissions' => $submissions,
            'filters' => $request->only(['status'])
        ]);
    }
}
