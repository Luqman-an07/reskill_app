<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\User;
use App\Models\UserModuleProgress;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index()
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        // 1. CEK ROLE & REDIRECT
        if ($user->roles()->where('name', 'admin')->exists()) return redirect()->route('admin.dashboard');
        if ($user->roles()->where('name', 'mentor')->exists()) return redirect()->route('mentor.dashboard');

        // --- 2. DATA KURSUS SAYA (ENROLLED COURSES) ---
        // Ambil ID semua kursus yang pernah diikuti (progress minimal 1 modul)
        $enrolledCourseIds = \App\Models\UserModuleProgress::where('user_id', $user->id)
            ->join('modules', 'user_module_progress.module_id', '=', 'modules.id')
            ->pluck('modules.course_id')
            ->unique();

        // Ambil Data Kursus Lengkap
        $myCourses = \App\Models\Course::whereIn('id', $enrolledCourseIds)
            ->with(['modules' => function ($q) {
                $q->select('id', 'course_id', 'completion_points');
            }, 'mentor'])
            ->withCount('modules')
            ->get()
            ->map(function ($course) use ($user) {

                // A. Logika Split Poin (Konsistensi UI)
                $course->module_total_points = $course->modules->sum('completion_points');
                $course->course_bonus_points = $course->completion_points;

                // B. Hitung Progress
                // Ambil semua modul di kursus ini yang user sudah 'completed'
                $completedCount = \App\Models\UserModuleProgress::where('user_id', $user->id)
                    ->whereIn('module_id', $course->modules->pluck('id'))
                    ->where('status', 'completed')
                    ->count();

                $total = $course->modules_count;
                $course->progress = ($total > 0) ? round(($completedCount / $total) * 100) : 0;

                // C. Image & Cosmetic
                $course->image_path = $course->thumbnail ? asset('storage/' . $course->thumbnail) : null;

                return $course;
            })
            // Filter: Hanya tampilkan yang belum 100% (Sedang Berjalan)
            // Jika Anda ingin menampilkan yang selesai juga, hapus filter ini
            ->filter(fn($c) => $c->progress < 100)
            ->values();

        // --- 3. TAMBAHAN: REKOMENDASI (JIKA BELUM ADA KURSUS / TAMBAHAN) ---
        // Logika: Ambil Kursus 'General' yang TIDAK ada di enrolledCourseIds
        $recommendedCourses = \App\Models\Course::where('is_published', true)
            ->whereNotIn('id', $enrolledCourseIds) // Kecualikan yang sudah diambil
            ->where(function ($q) {
                $q->where('target_role', 'General')
                    ->orWhere('target_role', 'All')
                    ->orWhereNull('target_role')
                    ->orWhere('target_role', '');
            })
            ->with(['modules' => function ($q) {
                $q->select('id', 'course_id', 'completion_points');
            }, 'mentor'])
            ->withCount('modules')
            ->take(3) // Batasi 3 rekomendasi
            ->get()
            ->map(function ($course) {
                // Mapping juga diperlukan agar UI Split Badge tidak error
                $course->module_total_points = $course->modules->sum('completion_points');
                $course->course_bonus_points = $course->completion_points;
                $course->progress = 0; // Karena rekomendasi, progress pasti 0
                $course->image_path = $course->thumbnail ? asset('storage/' . $course->thumbnail) : null;
                return $course;
            });


        // --- 4. DATA STATISTIK USER ---
        // Menghitung statistik manual agar akurat
        // (Bisa juga pakai query count seperti di atas jika performa jadi isu)
        $allInteractedCourses = \App\Models\Course::whereIn('id', $enrolledCourseIds)->withCount('modules')->get();

        $statsCompletedCount = 0;
        $statsInProgressCount = 0;

        foreach ($allInteractedCourses as $c) {
            $userDone = \App\Models\UserModuleProgress::where('user_id', $user->id)
                ->whereHas('module', fn($q) => $q->where('course_id', $c->id))
                ->where('status', 'completed')
                ->count();

            if ($c->modules_count > 0 && $userDone >= $c->modules_count) {
                $statsCompletedCount++;
            } else {
                $statsInProgressCount++;
            }
        }

        // --- 4. RANKING & LEADERBOARD (Tetap Sama) ---
        $usersBetterThanMe = \App\Models\User::whereHas('roles', fn($q) => $q->where('name', 'peserta'))
            ->where(function ($query) use ($user) {
                $query->where('total_points', '>', $user->total_points)
                    ->orWhere(function ($subQuery) use ($user) {
                        $subQuery->where('total_points', $user->total_points)
                            ->where('updated_at', '<', $user->updated_at);
                    });
            })
            ->count();
        $myRank = $usersBetterThanMe + 1;

        $seasonalPoints = $user->pointLogs()
            ->where('created_at', '>=', \Carbon\Carbon::now()->startOfMonth())
            ->sum('point_amount');

        $userStats = [
            'total_xp' => $user->total_points,
            'seasonal_points' => $seasonalPoints,
            'rank' => $myRank,
            'badges_count' => $user->badges()->count(),
            'completed_courses' => $statsCompletedCount,
            'in_progress_courses' => $statsInProgressCount,
        ];

        // --- 5. LEADERBOARD QUERY ---
        $leaderboardGlobal = \App\Models\User::whereHas('roles', fn($q) => $q->where('name', 'peserta'))
            ->with('department')
            ->orderBy('total_points', 'desc')
            ->orderBy('updated_at', 'asc')
            ->take(5)->get()->map(function ($u, $index) use ($user) {
                return [
                    'id' => $u->id,
                    'name' => $u->name,
                    'profile_picture' => $u->profile_picture,
                    'department' => $u->department,
                    'total_points' => $u->total_points,
                    'rank' => $index + 1,
                    'is_me' => $u->id === $user->id
                ];
            });

        $leaderboardDept = \App\Models\User::whereHas('roles', fn($q) => $q->where('name', 'peserta'))
            ->where('department_id', $user->department_id)
            ->with('department')
            ->orderBy('total_points', 'desc')
            ->orderBy('updated_at', 'asc')
            ->take(5)->get()->map(function ($u, $index) use ($user) {
                return [
                    'id' => $u->id,
                    'name' => $u->name,
                    'profile_picture' => $u->profile_picture,
                    'department' => $u->department,
                    'total_points' => $u->total_points,
                    'rank' => $index + 1,
                    'is_me' => $u->id === $user->id
                ];
            });

        $latestBadges = $user->badges()->orderByPivot('achieved_at', 'desc')->take(3)->get();

        return Inertia::render('Dashboard', [
            'myCourses' => $myCourses,
            'recommendedCourses' => $recommendedCourses,
            'leaderboardGlobal' => $leaderboardGlobal,
            'leaderboardDept' => $leaderboardDept,
            'userStats' => $userStats,
            'latestBadges' => $latestBadges,
        ]);
    }
}
