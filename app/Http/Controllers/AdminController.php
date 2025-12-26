<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\GamificationLedger;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class AdminController extends Controller
{
    public function dashboard()
    {
        // --- 1. KARTU STATISTIK UTAMA ---
        $totalUsers = User::count();

        // User baru minggu ini (created_at >= 7 hari lalu)
        $newUsersThisWeek = User::where('created_at', '>=', now()->subDays(7))->count();

        $activeCourses = Course::where('is_published', true)->count();
        $pendingCourses = Course::where('is_published', false)->count();

        // Hitung mentor (User yang punya role 'mentor')
        $activeMentors = User::whereHas('roles', function ($q) {
            $q->where('name', 'mentor');
        })->count();

        // Total Seasonal Points seluruh user
        $seasonPoints = User::sum('seasonal_points');

        // --- 2. GRAFIK: DAILY ACTIVE USERS (7 Hari Terakhir) ---
        // Kita anggap 'Active' jika user mendapatkan poin/beraktivitas di ledger pada hari itu
        $dailyActivity = GamificationLedger::select(
            DB::raw('DATE(transaction_date) as date'),
            DB::raw('COUNT(DISTINCT user_id) as active_count')
        )
            ->where('transaction_date', '>=', now()->subDays(6)) // 7 hari termasuk hari ini
            ->groupBy('date')
            ->orderBy('date', 'asc')
            ->get();

        // Format data untuk Chart.js
        $chartLabels = [];
        $chartData = [];

        // Loop 7 hari terakhir untuk memastikan tanggal kosong tetap ada (nilai 0)
        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i)->format('Y-m-d');
            $dayName = Carbon::now()->subDays($i)->format('D'); // Mon, Tue

            $record = $dailyActivity->firstWhere('date', $date);

            $chartLabels[] = $dayName;
            $chartData[] = $record ? $record->active_count : 0;
        }

        // --- 3. RECENT ACTIVITY (Log Terbaru) ---
        $recentActivities = GamificationLedger::with('user')
            ->latest('transaction_date')
            ->take(5)
            ->get()
            ->map(function ($log) {
                // Format pesan agar lebih mudah dibaca manusia
                $action = match ($log->reason_code) {
                    'MODULE_COMPLETE' => 'completed a module',
                    'QUIZ_PASS' => 'passed a quiz',
                    'TASK_PASS' => 'completed a task',
                    'LEVEL_UP' => 'leveled up',
                    default => 'earned points',
                };
                $date = \Carbon\Carbon::parse($log->transaction_date)->timezone(config('app.timezone'));

                return [
                    'id' => $log->id,
                    'user_name' => $log->user->name,
                    'user_avatar' => $log->user->profile_picture,
                    'action' => $action,
                    'points' => $log->point_amount,
                    'time_ago' => $date->diffForHumans(),
                    'exact_time' => $date->format('d M Y, H:i'),
                ];
            });

        return Inertia::render('Admin/Dashboard', [
            'stats' => [
                'total_users' => $totalUsers,
                'new_users_week' => $newUsersThisWeek,
                'active_courses' => $activeCourses,
                'pending_courses' => $pendingCourses,
                'active_mentors' => $activeMentors,
                'season_points' => $seasonPoints,
            ],
            'activityChart' => [
                'labels' => $chartLabels,
                'data' => $chartData,
            ],
            'recentActivities' => $recentActivities,
        ]);
    }
}
