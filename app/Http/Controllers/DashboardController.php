<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\User;
use App\Models\UserModuleProgress;
use App\Models\GamificationLedger; // [PENTING] Tambahkan Model ini
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

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
        $enrolledCourseIds = UserModuleProgress::where('user_id', $user->id)
            ->join('modules', 'user_module_progress.module_id', '=', 'modules.id')
            ->pluck('modules.course_id')
            ->unique();

        $myCourses = Course::whereIn('id', $enrolledCourseIds)
            ->with(['modules' => function ($q) {
                $q->select('id', 'course_id', 'completion_points');
            }, 'mentor'])
            ->withCount('modules')
            ->get()
            ->map(function ($course) use ($user) {
                $course->module_total_points = $course->modules->sum('completion_points');
                $course->course_bonus_points = $course->completion_points;

                $completedCount = UserModuleProgress::where('user_id', $user->id)
                    ->whereIn('module_id', $course->modules->pluck('id'))
                    ->where('status', 'completed')
                    ->count();

                $total = $course->modules_count;
                $course->progress = ($total > 0) ? round(($completedCount / $total) * 100) : 0;
                $course->image_path = $course->thumbnail ? asset('storage/' . $course->thumbnail) : null;

                return $course;
            })
            ->filter(fn($c) => $c->progress < 100)
            ->values();

        // --- 3. REKOMENDASI ---
        $recommendedCourses = Course::where('is_published', true)
            ->whereNotIn('id', $enrolledCourseIds)
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
            ->take(3)
            ->get()
            ->map(function ($course) {
                $course->module_total_points = $course->modules->sum('completion_points');
                $course->course_bonus_points = $course->completion_points;
                $course->progress = 0;
                $course->image_path = $course->thumbnail ? asset('storage/' . $course->thumbnail) : null;
                return $course;
            });

        // --- 4. DATA STATISTIK USER ---
        $allInteractedCourses = Course::whereIn('id', $enrolledCourseIds)->withCount('modules')->get();
        $statsCompletedCount = 0;
        $statsInProgressCount = 0;

        foreach ($allInteractedCourses as $c) {
            $userDone = UserModuleProgress::where('user_id', $user->id)
                ->whereHas('module', fn($q) => $q->where('course_id', $c->id))
                ->where('status', 'completed')
                ->count();

            if ($c->modules_count > 0 && $userDone >= $c->modules_count) {
                $statsCompletedCount++;
            } else {
                $statsInProgressCount++;
            }
        }

        // Hitung Ranking
        $usersBetterThanMe = User::whereHas('roles', fn($q) => $q->where('name', 'peserta'))
            ->where(function ($query) use ($user) {
                $query->where('total_points', '>', $user->total_points)
                    ->orWhere(function ($subQuery) use ($user) {
                        $subQuery->where('total_points', $user->total_points)
                            ->where('updated_at', '<', $user->updated_at);
                    });
            })
            ->count();
        $myRank = $usersBetterThanMe + 1;

        // [PERBAIKAN ERROR SQL]
        // Menggunakan Model GamificationLedger agar tabelnya benar (gamification_ledgers)
        // Dan menggunakan 'transaction_date' agar konsisten dengan LeaderboardController
        $seasonalPoints = GamificationLedger::where('user_id', $user->id)
            ->where('transaction_date', '>=', Carbon::now()->startOfMonth())
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

        // GLOBAL LEADERBOARD
        $leaderboardGlobal = User::whereHas('roles', fn($q) => $q->where('name', 'peserta'))
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
                    'is_me' => $u->id === $user->id,
                    'is_online' => $u->last_activity_at && $u->last_activity_at->gt(Carbon::now()->subMinutes(5)),
                ];
            });

        // DEPARTMENT LEADERBOARD
        $leaderboardDept = User::whereHas('roles', fn($q) => $q->where('name', 'peserta'))
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
                    'is_me' => $u->id === $user->id,
                    'is_online' => $u->last_activity_at && $u->last_activity_at->gt(Carbon::now()->subMinutes(5)),
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
