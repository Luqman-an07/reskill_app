<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\User;
use App\Models\UserModuleProgress;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Carbon\Carbon;

class ProgressController extends Controller
{
    public function index()
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        // --- 1. DATA KARTU STATISTIK ---

        // A. Modules Completed
        $modulesCompleted = $user->moduleProgress()->where('status', 'completed')->count();
        $totalModulesAvailable = DB::table('modules')
            ->join('courses', 'modules.course_id', '=', 'courses.id')
            ->where('courses.is_published', true)
            ->count();
        $completionRate = $totalModulesAvailable > 0 ? round(($modulesCompleted / $totalModulesAvailable) * 100) : 0;

        // B. Avg Quiz Score
        $currentAvgScore = $user->quizAttempts()->avg('final_score') ?? 0;

        // Rata-rata Bulan Lalu (Untuk komparasi)
        $lastMonthAvgScore = $user->quizAttempts()
            ->whereMonth('created_at', now()->subMonth()->month)
            ->avg('final_score');

        // Hitung Selisih (Trend)
        $scoreTrend = 0;
        if ($lastMonthAvgScore) {
            $scoreTrend = round($currentAvgScore - $lastMonthAvgScore);
        } else {
            // Jika bulan lalu belum ada nilai, anggap trend 0 atau sama dengan skor saat ini
            $scoreTrend = 0;
        }
        // C. Weekly Hours (Total minggu ini)
        $startOfWeek = now()->startOfWeek();
        $secondsThisWeek = $user->moduleProgress()
            ->where('updated_at', '>=', $startOfWeek)
            ->sum('time_spent');

        $hoursThisWeek = round($secondsThisWeek / 3600, 1);
        $daysPassed = now()->dayOfWeekIso; // 1 (Senin) - 7 (Minggu)
        $dailyAverage = $daysPassed > 0 ? round($hoursThisWeek / $daysPassed, 1) : 0;

        // D. Streak 
        // Ambil tanggal unik aktivitas dari ledger
        $activityDates = $user->pointLogs()
            ->select(DB::raw('DATE(transaction_date) as date'))
            ->distinct()
            ->orderBy('date', 'desc')
            ->pluck('date')
            ->toArray();

        $streak = 0;
        $today = now()->format('Y-m-d');
        $yesterday = now()->subDay()->format('Y-m-d');

        // Cek apakah hari ini atau kemarin ada aktivitas?
        if (!in_array($today, $activityDates) && !in_array($yesterday, $activityDates)) {
            $streak = 0; // Streak putus
        } else {
            // Hitung mundur
            $checkDate = in_array($today, $activityDates) ? now() : now()->subDay();

            foreach ($activityDates as $date) {
                if ($date === $checkDate->format('Y-m-d')) {
                    $streak++;
                    $checkDate->subDay(); // Mundur 1 hari
                } else {
                    break; // Urutan putus
                }
            }
        }

        // --- 2. CHART 1: WEEKLY ACTIVITY (7 HARI TERAKHIR) ---
        $weeklyLabels = [];
        $weeklyData = [];

        // Loop 7 hari ke belakang (dari hari ini mundur ke -6 hari)
        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i);
            $dateString = $date->format('Y-m-d');

            // Ambil sum time_spent pada tanggal tersebut
            // Catatan: updated_at digunakan sebagai penanda kapan terakhir belajar
            $dailySeconds = $user->moduleProgress()
                ->whereDate('updated_at', $dateString)
                ->sum('time_spent');

            $weeklyLabels[] = $date->format('D'); // Mon, Tue, Wed
            $weeklyData[] = round($dailySeconds / 3600, 1); // Konversi ke Jam
        }


        // --- 3. CHART 2: TIME DISTRIBUTION (BY TYPE) ---
        // Join ke tabel modules untuk tahu content_type
        $distributionStats = DB::table('user_module_progress')
            ->join('modules', 'user_module_progress.module_id', '=', 'modules.id')
            ->where('user_module_progress.user_id', $user->id)
            ->select('modules.content_type', DB::raw('SUM(user_module_progress.time_spent) as total_seconds'))
            ->groupBy('modules.content_type')
            ->pluck('total_seconds', 'content_type');

        // Mapping data agar urutannya konsisten di Chart
        // (VIDEO, TEXT/PDF, QUIZ, TASK)
        $distData = [
            round(($distributionStats['VIDEO'] ?? 0) / 60),    // Menit
            round((($distributionStats['TEXT'] ?? 0) + ($distributionStats['PDF'] ?? 0)) / 60), // Gabung Text & PDF
            round(($distributionStats['QUIZ'] ?? 0) / 60),
            round(($distributionStats['TASK'] ?? 0) / 60),
        ];


        // --- 4. CHART 3: PERFORMANCE (MONTHLY COMPLETED) ---
        // Hitung modul selesai per bulan (6 bulan terakhir)
        $monthlyLabels = [];
        $monthlyData = [];

        for ($i = 5; $i >= 0; $i--) {
            $month = Carbon::now()->subMonths($i);
            $monthName = $month->format('M');
            $monthNum = $month->month;
            $yearNum = $month->year;

            $count = $user->moduleProgress()
                ->where('status', 'completed')
                ->whereMonth('completion_date', $monthNum)
                ->whereYear('completion_date', $yearNum)
                ->count();

            $monthlyLabels[] = $monthName;
            $monthlyData[] = $count;
        }


        // --- 5. DATA TAB: COURSES ---
        $myActiveCourseIds = $user->moduleProgress()
            ->join('modules', 'user_module_progress.module_id', '=', 'modules.id')
            ->pluck('modules.course_id')
            ->unique();
        $coursesProgress = Course::whereIn('id', $myActiveCourseIds)
            ->withCount('modules')
            ->get()
            ->map(function ($course) use ($user) {
                $completed = $user->moduleProgress()
                    ->whereIn('module_id', $course->modules()->pluck('id'))
                    ->where('status', 'completed')
                    ->count();

                $total = $course->modules_count;
                $percent = $total > 0 ? round(($completed / $total) * 100) : 0;

                return [
                    'id' => $course->id,
                    'title' => $course->title,
                    'completed_modules' => $completed,
                    'total_modules' => $total,
                    'percent' => $percent,
                ];
            });

        return Inertia::render('Progress/Index', [
            'stats' => [
                'hours_week' => $hoursThisWeek,
                'modules_completed' => $modulesCompleted,
                'total_modules' => $totalModulesAvailable,
                'completion_rate' => $completionRate,
                'daily_average' => $dailyAverage,
                'avg_score' => round($currentAvgScore),
                'score_trend' => $scoreTrend,
                'streak' => $streak
            ],
            'coursesProgress' => $coursesProgress,
            'chartData' => [
                'weekly' => [
                    'labels' => $weeklyLabels,
                    'data' => $weeklyData
                ],
                'distribution' => $distData,
                'monthly' => [
                    'labels' => $monthlyLabels,
                    'data' => $monthlyData
                ]
            ]
        ]);
    }
}
