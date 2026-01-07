<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use App\Models\Course;
use App\Models\User;
use App\Models\Department;
use App\Models\UserModuleProgress;
use App\Models\UserQuizAttempt;
use App\Models\UserTaskSubmission;
use Inertia\Inertia;

class MentorController extends Controller
{
    /**
     * Dashboard Mentor (Human Growth Team)
     * Menampilkan statistik global untuk SEMUA KURSUS.
     */
    public function dashboard()
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        // 1. Ambil SEMUA Kursus (Kolaborasi Tim)
        // Tidak ada lagi filter 'mentor_id'
        $allCourses = Course::with('modules')
            ->withCount('modules')
            ->get();

        $courseIds = $allCourses->pluck('id');

        // 2. Hitung Statistik Global

        // A. Active Participants (User unik di sistem)
        $activeParticipantsCount = UserModuleProgress::distinct('user_id')->count('user_id');

        // B. Pending Reviews (Tugas yang perlu dinilai)
        $pendingReviewsQuery = UserTaskSubmission::where('is_graded', false);
        $pendingReviewsCount = $pendingReviewsQuery->count();

        // C. Avg Completion Global
        $totalProgressSum = 0;
        $activeCoursesCount = 0;

        // 3. Data List Kursus (Detail per Item)
        $coursesList = $allCourses->map(function ($c) use (&$totalProgressSum, &$activeCoursesCount) {
            // a. Hitung Pending Reviews per course
            $pending = UserTaskSubmission::whereHas('task.module', function ($q) use ($c) {
                $q->where('course_id', $c->id);
            })->where('is_graded', false)->count();

            // b. Hitung Participants per course
            $participantsCount = UserModuleProgress::whereIn('module_id', $c->modules->pluck('id'))
                ->distinct('user_id')
                ->count('user_id');

            // c. Hitung Progress Kelas
            $progress = 0;
            if ($participantsCount > 0 && $c->modules_count > 0) {
                $targetTotalCompleted = $participantsCount * $c->modules_count;
                $actualCompleted = UserModuleProgress::whereIn('module_id', $c->modules->pluck('id'))
                    ->where('status', 'completed')
                    ->count();

                $progress = round(($actualCompleted / $targetTotalCompleted) * 100);

                $totalProgressSum += $progress;
                $activeCoursesCount++;
            }

            return [
                'id' => $c->id,
                'title' => $c->title,
                'participants' => $participantsCount,
                'pending_reviews' => $pending,
                'progress' => $progress,
                'mentor_name' => $c->mentor ? $c->mentor->name : null,
            ];
        });

        // Hitung rata-rata global
        $avgCompletion = $activeCoursesCount > 0 ? round($totalProgressSum / $activeCoursesCount) : 0;

        // 4. List Tugas Pending (Sidebar)
        $recentPendingReviews = $pendingReviewsQuery
            ->with(['user', 'task'])
            ->latest('submission_date')
            ->take(5)
            ->get()
            ->map(function ($sub) {
                return [
                    'id' => $sub->id,
                    'student_name' => $sub->user->name,
                    'task_title' => $sub->task->task_title,
                    'time_ago' => $sub->submission_date->diffForHumans(),
                    'type' => 'Task'
                ];
            });

        return Inertia::render('Mentor/Dashboard', [
            'stats' => [
                'courses_managed' => $allCourses->count(),
                'active_participants' => $activeParticipantsCount,
                'pending_reviews' => $pendingReviewsCount,
                'avg_completion' => $avgCompletion,
            ],
            'courses' => $coursesList,
            'pendingReviews' => $recentPendingReviews
        ]);
    }

    public function myCourses(Request $request)
    {
        $sortField = $request->input('sort', 'created_at');
        $sortDirection = $request->input('direction', 'desc');
        $allowedSorts = ['title', 'is_published', 'modules_count', 'created_at', 'students_count'];

        if (!in_array($sortField, $allowedSorts)) {
            $sortField = 'created_at';
        }

        // QUERY SPESIFIK MENTOR (Hanya lihat kursus buatan sendiri untuk diedit)
        $query = Course::query()
            ->withCount('modules')
            ->with(['mentor', 'department']) // Load relasi department & mentor
            ->withCount(['progress as students_count' => function ($q) {
                $q->select(DB::raw('count(distinct user_id)'));
            }])
            ->where('mentor_id', Auth::id()); // <--- KUNCI: Filter kepemilikan

        // Filter Search
        $query->when($request->search, function ($q, $search) {
            $q->where('title', 'like', "%{$search}%");
        });

        // Filter Status
        $query->when($request->status, function ($q, $status) {
            if ($status === 'published') $q->where('is_published', true);
            if ($status === 'draft') $q->where('is_published', false);
        });

        // Sorting
        $query->orderBy($sortField, $sortDirection);

        $courses = $query->paginate(10)
            ->withQueryString()
            ->through(function ($course) {
                return [
                    'id' => $course->id,
                    'title' => $course->title,
                    // [UPDATED] Fallback nama departemen
                    'target_role' => $course->department ? $course->department->department_name : ($course->target_role ?? 'General'),
                    'status' => $course->is_published ? 'Published' : 'Draft',
                    'is_published' => (bool)$course->is_published, // Untuk konsistensi frontend
                    'modules_count' => $course->modules_count,
                    'students_count' => $course->students_count,
                    'created_at' => $course->created_at->format('d M Y'),

                    // Info PIC
                    'mentor_name' => $course->mentor ? $course->mentor->name : 'Unknown',
                    'mentor_avatar' => $course->mentor ? $course->mentor->profile_photo_url : null,
                ];
            });

        return Inertia::render('Mentor/Courses/Index', [
            'courses' => $courses,
            // [BARU] Kirim data department untuk dropdown create
            'departments' => Department::orderBy('department_name')->get(),
            'filters' => $request->only(['search', 'status', 'sort', 'direction']),
        ]);
    }

    /**
     * Menyimpan Kursus Baru.
     * [UPDATED: Support Department ID Dropdown]
     */
    public function storeCourse(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'completion_points' => 'required|integer|min:0',
            // [BARU] Validasi input dropdown
            'department_id' => 'nullable|exists:departments,id',
        ]);

        $user = Auth::user();

        // [LOGIKA BARU] Sinkronisasi nama target_role dari department_id
        if ($request->department_id) {
            $dept = Department::find($request->department_id);
            $targetRoleString = $dept->department_name;
        } else {
            $targetRoleString = 'General';
        }

        $course = Course::create([
            'title' => $validated['title'],
            'department_id' => $request->department_id, // Simpan ID
            'target_role' => $targetRoleString,         // Simpan String (Fallback)
            'mentor_id' => $user->id,                   // PIC tetap User Login
            'description' => 'Course description...',
            'is_published' => false,
            'completion_points' => $validated['completion_points']
        ]);

        return redirect()->route('mentor.courses.edit', $course->id)
            ->with('success', "Course created successfully.");
    }

    /**
     * Halaman Edit.
     * [UPDATED: Auth Check & Department Data]
     */
    public function editCourse($id)
    {
        $course = Course::with(['modules.quiz', 'modules.task'])
            ->findOrFail($id);

        // [KEAMANAN] Pastikan mentor hanya mengedit kursus miliknya
        if ($course->mentor_id !== Auth::id()) {
            abort(403, 'Anda tidak memiliki akses untuk mengedit kursus ini.');
        }

        return Inertia::render('Admin/Courses/Edit', [
            'course' => $course,
            'departments' => Department::orderBy('department_name')->get(), // [BARU]
            'modules' => $course->modules->sortBy('module_order')->values()->all(),
            'isMentorView' => true,
        ]);
    }

    /**
     * Update Kursus.
     * [UPDATED: Support Department ID & Auth Check]
     */
    public function updateCourse(Request $request, $id)
    {
        $course = Course::findOrFail($id);

        // [KEAMANAN] Cek Kepemilikan
        if ($course->mentor_id !== Auth::id()) {
            abort(403);
        }

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            // [BARU] Validasi department_id
            'department_id' => 'nullable|exists:departments,id',
            'completion_points' => 'required|integer|min:0',
            'is_published' => 'boolean'
        ]);

        // [LOGIKA BARU] Update string target_role otomatis
        if ($request->department_id) {
            $dept = Department::find($request->department_id);
            $validated['target_role'] = $dept->department_name;
        } else {
            $validated['target_role'] = 'General';
        }

        $course->update($validated);

        return back()->with('success', 'Course updated successfully.');
    }

    /**
     * Hapus Kursus.
     * [UPDATED: Auth Check & Thumbnail Cleanup]
     */
    public function destroyCourse($id)
    {
        $course = Course::findOrFail($id);

        // [KEAMANAN] Cek Kepemilikan
        if ($course->mentor_id !== Auth::id()) {
            abort(403);
        }

        // Hapus fisik thumbnail jika ada
        if ($course->thumbnail) {
            Storage::disk('public')->delete($course->thumbnail);
        }

        $course->delete();

        return back()->with('success', 'Course deleted successfully.');
    }

    /**
     * Laporan Global untuk Tim Human Growth.
     */
    public function reports(Request $request)
    {
        // 1. AMBIL SEMUA KURSUS
        $allCourseIds = Course::pluck('id');

        if ($allCourseIds->isEmpty()) {
            return Inertia::render('Admin/Reports/Index', [
                'participants' => ['data' => [], 'links' => []],
                'departments' => [],
                'courses' => [],
                'stats' => ['total' => 0, 'avg_progress' => 0, 'avg_score' => 0, 'active' => 0],
                'filters' => [],
                'charts' => null,
                'baseRoute' => 'mentor.reports.index',
                'exportRoute' => 'admin.reports.export',
                'detailRouteName' => 'mentor.reports.show', // Default route
            ]);
        }

        // 2. PARAMETER FILTER
        $deptId = $request->input('department_id');
        $courseId = $request->input('course_id');
        $sortField = $request->input('sort', 'name');
        $sortDirection = $request->input('direction', 'asc');

        // 3. QUERY DATA PESERTA
        $query = User::select('users.*')
            ->whereHas('roles', fn($q) => $q->where('name', 'peserta'))
            ->whereHas('moduleProgress')
            ->with(['department', 'moduleProgress.module', 'quizAttempts.quiz.module', 'badges']);

        if ($deptId) $query->where('department_id', $deptId);

        // Filter Level Query (Untuk membatasi jumlah row yang diambil)
        if ($courseId) {
            $query->whereHas('moduleProgress.module', fn($q) => $q->where('course_id', $courseId));
        }

        // 4. SORTING
        switch ($sortField) {
            case 'name':
                $query->orderBy('users.name', $sortDirection);
                break;
            case 'total_points':
                $query->orderBy('users.total_points', $sortDirection);
                break;
            case 'last_active':
                $query->orderBy('users.last_activity_at', $sortDirection);
                break;
            default:
                $query->orderBy('users.name', 'asc');
                break;
        }

        // 5. PAGINATION & MAPPING
        $participants = $query->paginate(10)->withQueryString()
            ->through(function ($student) use ($courseId) {

                // Menggunakan Collection (Data Memory)
                $progressData = $student->moduleProgress;
                $quizData = $student->quizAttempts;

                // Jika ada filter course_id, filter Collection di sini
                if ($courseId) {
                    $progressData = $progressData->filter(function ($p) use ($courseId) {
                        return $p->module && $p->module->course_id == $courseId;
                    });

                    $quizData = $quizData->filter(function ($q) use ($courseId) {
                        return $q->quiz && $q->quiz->module && $q->quiz->module->course_id == $courseId;
                    });
                }

                // Hitung dari Collection
                $enrolledCount = $progressData->pluck('module.course_id')->unique()->count();
                $completedCount = $progressData->where('status', 'completed')->count();
                $avgScore = $quizData->avg('final_score') ?? 0;

                return [
                    'id' => $student->id,
                    'name' => $student->name,
                    'email' => $student->email,
                    'avatar' => $student->profile_picture,
                    'department' => $student->department ? $student->department->department_name : '-',
                    'enrolled' => $enrolledCount,
                    'completed' => $completedCount,
                    'avg_score' => round($avgScore),
                    'total_xp' => $student->total_points,
                    'badges_count' => $student->badges->count(),
                    'last_active' => $student->last_activity_at ? $student->last_activity_at->diffForHumans() : '-',

                    // [BARU] Status Online (5 menit terakhir)
                    'is_online' => $student->last_activity_at && $student->last_activity_at->gt(now()->subMinutes(5)),
                ];
            });

        // 6. STATS GLOBAL
        $totalParticipants = User::whereHas('roles', fn($q) => $q->where('name', 'peserta'))->count();
        $avgScoreGlobal = UserQuizAttempt::avg('final_score') ?? 0;

        $stats = [
            'total' => $totalParticipants,
            'avg_progress' => 0,
            'avg_score' => round($avgScoreGlobal),
            'active' => User::where('last_activity_at', '>=', now()->subDays(7))->count()
        ];

        // 7. CHART DUMMY
        $charts = [
            'weekly' => ['labels' => [], 'completed' => [], 'started' => [], 'not_started' => []],
            'completion' => [],
            'performance' => []
        ];

        return Inertia::render('Admin/Reports/Index', [
            'participants' => $participants,
            'departments' => \App\Models\Department::all(),
            'courses' => Course::select('id', 'title')->get(),
            'stats' => $stats,
            'filters' => $request->only(['department_id', 'course_id', 'sort', 'direction']),
            'charts' => $charts,
            'baseRoute' => 'mentor.reports.index',
            'exportRoute' => 'admin.reports.export',

            // [PENTING] Ini memberitahu Vue untuk membuka halaman detail (bukan modal) saat diklik
            'detailRouteName' => 'mentor.reports.show',
        ]);
    }

    /**
     * Menampilkan Detail Laporan Satu Siswa (Global View).
     */
    public function showStudentReport($userId)
    {
        // 1. Ambil Data Siswa (Model)
        $studentModel = User::with(['department', 'badges'])->findOrFail($userId);

        // [BARU] Konversi ke Array agar bisa inject 'is_online'
        // Ini diperlukan karena kita tidak ingin mengubah struktur database
        $student = $studentModel->toArray();
        $student['department'] = $studentModel->department; // Pastikan relasi terbawa
        $student['badges'] = $studentModel->badges;         // Pastikan relasi terbawa

        // Logika Online: Aktif dalam 5 menit terakhir
        $student['is_online'] = $studentModel->last_activity_at
            && $studentModel->last_activity_at->gt(\Carbon\Carbon::now()->subMinutes(5));

        // 2. Ambil Kursus yang diambil siswa ini (Berdasarkan Progress Modul)
        // (Logika Query Tetap Sama Persis)
        $enrolledCourses = Course::whereHas('modules.progress', function ($q) use ($userId) {
            $q->where('user_id', $userId);
        })
            ->with(['modules' => function ($q) use ($userId) {
                // Eager Load Progress & Quiz Attempt KHUSUS user ini
                $q->with(['progress' => fn($sq) => $sq->where('user_id', $userId)]);
                $q->with(['quiz.attempts' => fn($sq) => $sq->where('user_id', $userId)->orderBy('final_score', 'desc')]);
            }])
            ->get()
            ->map(function ($course) {
                $totalModules = $course->modules->count();

                // Hitung modul yang statusnya 'completed' oleh user ini
                $completedModules = $course->modules->filter(function ($module) {
                    return $module->progress->isNotEmpty() && $module->progress->first()->status === 'completed';
                })->count();

                // Hitung rata-rata nilai kuis di kursus ini
                $quizScores = $course->modules->map(function ($module) {
                    if ($module->content_type === 'QUIZ' && $module->quiz && $module->quiz->attempts->isNotEmpty()) {
                        return $module->quiz->attempts->first()->final_score; // Ambil nilai terbaik/terakhir
                    }
                    return null;
                })->filter(fn($score) => !is_null($score));

                $avgScore = $quizScores->isNotEmpty() ? round($quizScores->avg()) : 0;
                $progressPercent = $totalModules > 0 ? round(($completedModules / $totalModules) * 100) : 0;

                return [
                    'id' => $course->id,
                    'title' => $course->title,
                    'total_modules' => $totalModules,
                    'completed_modules' => $completedModules,
                    'progress' => $progressPercent,
                    'avg_score' => $avgScore,
                    'status' => $progressPercent >= 100 ? 'Selesai' : 'Berjalan',
                    'last_activity' => $course->modules->flatMap->progress->max('updated_at')?->diffForHumans() ?? '-',
                ];
            });

        // 3. Statistik Ringkasan
        $stats = [
            'total_courses' => $enrolledCourses->count(),
            'completed_courses' => $enrolledCourses->where('progress', 100)->count(),
            'avg_global_score' => round($enrolledCourses->avg('avg_score')),
            'total_xp' => $studentModel->total_points // Ambil dari Model asli agar aman
        ];

        return Inertia::render('Mentor/Reports/Show', [
            'student' => $student, // [UPDATED] Array dengan is_online
            'courses' => $enrolledCourses,
            'stats' => $stats
        ]);
    }
}
