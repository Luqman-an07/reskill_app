<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Department;
use App\Models\User;
use App\Models\UserModuleProgress;
use App\Models\UserQuizAttempt;
use App\Models\GamificationLedger; // Pastikan Model ini diimport
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon; // Import Carbon

class ReportController extends Controller
{
    /**
     * Menampilkan Halaman Laporan Utama (Dashboard + Tabel + Grafik).
     */
    public function index(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        // --- 1. SETUP SCOPE & PERMISSIONS ---
        $isMentor = $user->roles()->where('name', 'mentor')->exists();
        $myCourseIds = collect();

        if ($isMentor) {
            $myCourseIds = Course::where('mentor_id', $user->id)->pluck('id');
        }

        // --- 2. AMBIL PARAMETER FILTER & SORT ---
        $deptId = $request->input('department_id');
        $courseId = $request->input('course_id');
        $sortField = $request->input('sort', 'name');
        $sortDirection = $request->input('direction', 'asc');

        // --- 3. BUILD QUERY PESERTA ---
        $query = User::select('users.*')
            ->whereHas('roles', fn($q) => $q->where('name', 'peserta'))
            ->with(['department', 'moduleProgress.module', 'quizAttempts', 'badges']);

        // A. FILTER SCOPE MENTOR
        if ($isMentor) {
            $query->whereHas('moduleProgress.module', function ($q) use ($myCourseIds) {
                $q->whereIn('course_id', $myCourseIds);
            });
        }

        // B. FILTER INPUT
        if ($deptId) {
            $query->where('department_id', $deptId);
        }

        if ($courseId) {
            if ($isMentor && !$myCourseIds->contains($courseId)) {
                abort(403, 'Anda tidak memiliki akses ke laporan kursus ini.');
            }
            $query->whereHas('moduleProgress.module', fn($q) => $q->where('course_id', $courseId));
        }

        // --- 4. APPLY SERVER-SIDE SORTING ---
        switch ($sortField) {
            case 'name':
                $query->orderBy('name', $sortDirection);
                break;
            case 'total_points':
                $query->orderBy('total_points', $sortDirection);
                break;
            case 'last_active':
                $query->orderBy('last_activity_at', $sortDirection);
                break;
            case 'avg_score':
                $query->withAvg('quizAttempts', 'final_score')
                    ->orderBy('quiz_attempts_avg_final_score', $sortDirection);
                break;
            default:
                $query->orderBy('name', 'asc');
                break;
        }

        // --- 5. DATA TABEL (PAGINATION) ---
        $participants = $query->paginate(10)->withQueryString()
            ->through(function ($student) use ($isMentor, $myCourseIds) {

                $progressQuery = $student->moduleProgress;
                $quizQuery = $student->quizAttempts;

                if ($isMentor) {
                    $progressQuery = $progressQuery->whereIn('module.course_id', $myCourseIds);
                    $quizQuery = $quizQuery->filter(function ($attempt) use ($myCourseIds) {
                        return $myCourseIds->contains($attempt->quiz->module->course_id ?? 0);
                    });
                }

                $enrolledCount = $progressQuery->pluck('module.course_id')->unique()->count();
                $completedCount = $progressQuery->where('status', 'completed')->count();
                $avgScore = $quizQuery->avg('final_score') ?? 0;

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

                    // [UPDATED] Tambahkan Status Online
                    'is_online' => $student->last_activity_at && $student->last_activity_at->gt(Carbon::now()->subMinutes(5)),
                ];
            });


        // --- 6. DATA STATISTIK (HEADER & GRAFIK) ---
        $userIds = $query->reorder()->pluck('users.id');

        $baseProgressQuery = UserModuleProgress::whereIn('user_id', $userIds);
        $baseQuizQuery = UserQuizAttempt::whereIn('user_id', $userIds);

        if ($isMentor) {
            $baseProgressQuery->whereHas('module', fn($q) => $q->whereIn('course_id', $myCourseIds));
            $baseQuizQuery->whereHas('quiz.module', fn($q) => $q->whereIn('course_id', $myCourseIds));
        }

        // A. Header Stats
        $totalCompletedModules = (clone $baseProgressQuery)->where('status', 'completed')->count();
        $totalActiveModules = (clone $baseProgressQuery)->count();
        $avgProgress = $totalActiveModules > 0 ? round(($totalCompletedModules / $totalActiveModules) * 100) : 0;

        $globalAvgScore = $baseQuizQuery->avg('final_score') ?? 0;

        $activeCount = User::whereIn('id', $userIds)
            ->where('last_activity_at', '>=', now()->subDays(7))
            ->count();

        $stats = [
            'total' => $userIds->count(),
            'avg_progress' => $avgProgress,
            'avg_score' => round($globalAvgScore),
            'active' => $activeCount
        ];

        // B. Chart: Weekly Trend
        $weeklyTrend = ['labels' => [], 'completed' => [], 'started' => [], 'not_started' => []];
        for ($i = 3; $i >= 0; $i--) {
            $start = now()->subWeeks($i)->startOfWeek();
            $end = now()->subWeeks($i)->endOfWeek();

            $wCompleted = (clone $baseProgressQuery)
                ->where('status', 'completed')
                ->whereBetween('updated_at', [$start, $end])
                ->count();

            $wStarted = (clone $baseProgressQuery)
                ->where('status', 'started')
                ->whereBetween('updated_at', [$start, $end])
                ->count();

            $weeklyTrend['labels'][] = 'Minggu ' . (4 - $i);
            $weeklyTrend['completed'][] = $wCompleted;
            $weeklyTrend['started'][] = $wStarted;
            $weeklyTrend['not_started'][] = 0;
        }

        // C. Chart: Course Completion (Top 5)
        $relevantCourses = Course::where('is_published', true)
            ->when($isMentor, fn($q) => $q->whereIn('id', $myCourseIds))
            ->take(5)->get();

        $courseStats = $relevantCourses->map(function ($c) use ($userIds) {
            $usersInCourse = UserModuleProgress::whereIn('user_id', $userIds)
                ->whereIn('module_id', $c->modules->pluck('id'))
                ->distinct('user_id')->count();

            if ($usersInCourse == 0) return null;

            $totalPotential = $c->modules()->count() * $usersInCourse;
            $completedReal = UserModuleProgress::whereIn('user_id', $userIds)
                ->whereIn('module_id', $c->modules->pluck('id'))
                ->where('status', 'completed')
                ->count();

            return [
                'title' => $c->title,
                'rate' => $totalPotential > 0 ? round(($completedReal / $totalPotential) * 100) : 0
            ];
        })->filter()->sortByDesc('rate')->values();

        // D. Chart: Performance Distribution
        $quizScores = $baseQuizQuery->pluck('final_score');
        $performanceDist = [
            '90-100' => $quizScores->filter(fn($s) => $s >= 90)->count(),
            '80-89'  => $quizScores->filter(fn($s) => $s >= 80 && $s < 90)->count(),
            '70-79'  => $quizScores->filter(fn($s) => $s >= 70 && $s < 80)->count(),
            '60-69'  => $quizScores->filter(fn($s) => $s >= 60 && $s < 70)->count(),
            '< 60'   => $quizScores->filter(fn($s) => $s < 60)->count(),
        ];

        // --- 7. PREPARE PROPS FOR VIEW ---
        $deptOptions = $isMentor ? [] : Department::all();
        $courseOptions = $isMentor
            ? Course::where('mentor_id', $user->id)->select('id', 'title')->get()
            : Course::select('id', 'title')->get();

        $baseRoute = $isMentor ? 'mentor.reports.index' : 'admin.reports.index';

        return Inertia::render('Admin/Reports/Index', [
            'participants' => $participants,
            'departments' => $deptOptions,
            'courses' => $courseOptions,
            'stats' => $stats,
            'filters' => $request->only(['department_id', 'course_id', 'sort', 'direction']),
            'charts' => [
                'weekly' => $weeklyTrend,
                'completion' => $courseStats,
                'performance' => $performanceDist
            ],
            'baseRoute' => $baseRoute,
            'exportRoute' => 'admin.reports.export',
            'detailRouteName' => $isMentor ? 'mentor.reports.show' : null, // Opsi untuk Mentor View
        ]);
    }

    /**
     * Method Export PDF (Global Report).
     */
    public function export(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $isMentor = $user->roles()->where('name', 'mentor')->exists();
        $myCourseIds = $isMentor ? Course::where('mentor_id', $user->id)->pluck('id') : collect();

        $deptId = $request->input('department_id');
        $courseId = $request->input('course_id');
        $sortField = $request->input('sort', 'name');
        $sortDirection = $request->input('direction', 'asc');

        $query = User::select('users.*')
            ->whereHas('roles', fn($q) => $q->where('name', 'peserta'))
            ->with(['department', 'moduleProgress', 'quizAttempts']);

        // Scope Mentor
        if ($isMentor) {
            $query->whereHas('moduleProgress.module', function ($q) use ($myCourseIds) {
                $q->whereIn('course_id', $myCourseIds);
            });
        }

        // Filters
        if ($deptId) $query->where('department_id', $deptId);

        if ($courseId) {
            if ($isMentor && !$myCourseIds->contains($courseId)) abort(403);
            $query->whereHas('moduleProgress.module', fn($q) => $q->where('course_id', $courseId));
        }

        // Sorting
        if ($sortField === 'name') $query->orderBy('name', $sortDirection);
        elseif ($sortField === 'total_points') $query->orderBy('total_points', $sortDirection);
        else $query->orderBy('name', 'asc');

        // Get Data
        $users = $query->get()->map(function ($student) use ($isMentor, $myCourseIds) {
            $progressQuery = $student->moduleProgress;
            $quizQuery = $student->quizAttempts;

            if ($isMentor) {
                $progressQuery = $progressQuery->whereIn('module.course_id', $myCourseIds);
                $quizQuery = $quizQuery->filter(function ($attempt) use ($myCourseIds) {
                    return $myCourseIds->contains($attempt->quiz->module->course_id ?? 0);
                });
            }

            return [
                'name' => $student->name,
                'department' => $student->department ? $student->department->department_name : '-',
                'completed_modules' => $progressQuery->where('status', 'completed')->count(),
                'avg_score' => round($quizQuery->avg('final_score') ?? 0),
                'total_xp' => $student->total_points,
            ];
        });

        $pdf = Pdf::loadView('pdf.global_report', [
            'users' => $users,
            'date' => now()->format('d F Y'),
            'filter' => $deptId ? Department::find($deptId)->department_name : ($isMentor ? 'Mentor Scope' : 'All Departments')
        ]);

        return $pdf->download('Laporan_Peserta_RESKILL.pdf');
    }

    // Method exportUser tetap sama
    public function exportUser($userId)
    {
        $user = User::with(['department', 'badges', 'quizAttempts.quiz', 'moduleProgress.module.course'])
            ->findOrFail($userId);

        $courseProgress = $user->moduleProgress
            ->groupBy('module.course.title')
            ->map(function ($modules, $courseTitle) {
                return [
                    'title' => $courseTitle,
                    'completed' => $modules->where('status', 'completed')->count(),
                    'total' => $modules->count(),
                ];
            });

        $pdf = Pdf::loadView('pdf.individual_report', [
            'user' => $user,
            'date' => now()->format('d F Y'),
            'courseProgress' => $courseProgress
        ]);

        return $pdf->download("Laporan_{$user->name}.pdf");
    }

    /**
     * Menampilkan Detail User (Modal / JSON Response).
     */
    public function showUser($id)
    {
        $user = User::with(['department', 'badges', 'quizAttempts'])->findOrFail($id);

        $enrolledCourseIds = $user->moduleProgress->pluck('module.course_id')->unique();

        $courseProgress = Course::whereIn('id', $enrolledCourseIds)
            ->withCount('modules')
            ->get()
            ->map(function ($c) use ($user) {
                $total = $c->modules_count;
                $completed = $user->moduleProgress()
                    ->whereIn('module_id', $c->modules()->pluck('id'))
                    ->where('status', 'completed')
                    ->count();

                $progress = $total > 0 ? round(($completed / $total) * 100) : 0;
                $quizScore = $user->quizAttempts()
                    ->whereHas('quiz.module', fn($q) => $q->where('course_id', $c->id))
                    ->avg('final_score');

                return [
                    'id' => $c->id,
                    'title' => $c->title,
                    'progress' => $progress,
                    'status' => $progress == 100 ? 'Completed' : 'In Progress',
                    'score' => $quizScore ? round($quizScore) : null,
                    'last_access' => $user->moduleProgress()
                        ->whereIn('module_id', $c->modules()->pluck('id'))
                        ->latest('updated_at')
                        ->value('updated_at')?->format('Y-m-d'),
                ];
            });

        // [PERBAIKAN] Menggunakan GamificationLedger agar tidak error table 'point_logs'
        $activities = GamificationLedger::where('user_id', $user->id)
            ->latest('transaction_date')
            ->take(10)
            ->get()
            ->map(function ($log) {
                $title = match ($log->reason_code) {
                    'MODULE_COMPLETE' => 'Menyelesaikan modul',
                    'QUIZ_PASS' => 'Lulus kuis',
                    'TASK_PASS' => 'Mengumpulkan tugas',
                    'COURSE_COMPLETE' => 'Menyelesaikan kursus',
                    default => 'Mendapat poin',
                };
                return [
                    'id' => $log->id,
                    'title' => $title,
                    'description' => $log->description ?? "Dapat {$log->point_amount} XP",
                    'date' => $log->transaction_date->format('Y-m-d H:i'),
                    'type' => $log->reason_code
                ];
            });

        $stats = [
            'enrolled' => $courseProgress->count(),
            'completed' => $courseProgress->where('status', 'Completed')->count(),
            'avg_score' => round($user->quizAttempts->avg('final_score') ?? 0),
            'total_points' => $user->total_points,
        ];

        return response()->json([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'avatar' => $user->profile_picture,
                'department' => $user->department->department_name ?? '-',
                'join_date' => $user->created_at->format('Y-m-d'),
                'last_active' => $user->last_activity_at ? $user->last_activity_at->diffForHumans() : 'Never',

                // [UPDATED] Status Online untuk Modal
                'is_online' => $user->last_activity_at && $user->last_activity_at->gt(Carbon::now()->subMinutes(5)),
            ],
            'stats' => $stats,
            'courses' => $courseProgress,
            'badges' => $user->badges,
            'activities' => $activities
        ]);
    }
}
