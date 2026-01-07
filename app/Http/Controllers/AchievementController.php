<?php

namespace App\Http\Controllers;

use App\Models\Badge;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Carbon\Carbon;

class AchievementController extends Controller
{
    public function index()
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        // --- 1. LOGIKA RANKING ---
        $rank = '-';
        if ($user->roles()->where('name', 'peserta')->exists()) {
            $usersBetterThanMe = User::whereHas('roles', function ($q) {
                $q->where('name', 'peserta');
            })
                ->where(function ($query) use ($user) {
                    $query->where('total_points', '>', $user->total_points)
                        ->orWhere(function ($subQuery) use ($user) {
                            $subQuery->where('total_points', $user->total_points)
                                ->where('updated_at', '<', $user->updated_at);
                        });
                })
                ->count();

            $rank = $usersBetterThanMe + 1;
        }

        // --- 2. LOGIKA STREAK ---
        $activityDates = $user->pointLogs()
            ->select(DB::raw('DATE(transaction_date) as date'))
            ->distinct()
            ->orderBy('date', 'desc')
            ->pluck('date')
            ->toArray();

        $streak = 0;
        $checkDate = Carbon::now();
        $today = $checkDate->format('Y-m-d');
        $hasActivityToday = in_array($today, $activityDates);

        if (!$hasActivityToday) {
            $checkDate->subDay();
        }

        while (in_array($checkDate->format('Y-m-d'), $activityDates)) {
            $streak++;
            $checkDate->subDay();
        }

        // --- 3. AMBIL BADGE ---
        $earnedBadges = $user->badges()
            ->orderByPivot('achieved_at', 'desc')
            ->get();

        $earnedBadgeIds = $earnedBadges->pluck('id')->toArray();
        $lockedBadges = Badge::whereNotIn('id', $earnedBadgeIds)->get();

        // --- 4. BREAKDOWN XP ---
        // Ambil logs sekali saja untuk efisiensi
        $logs = $user->pointLogs()->get();

        $xpBreakdown = [
            [
                'label' => 'Kursus Selesai',
                // [NOTE] Ini biasanya untuk poin per modul (Text/Video)
                'amount' => $logs->where('reason_code', 'MODULE_COMPLETE')->sum('point_amount'),
                'icon_type' => 'book'
            ],
            [
                'label' => 'Performa Kuis',
                'amount' => $logs->where('reason_code', 'QUIZ_PASS')->sum('point_amount'),
                'icon_type' => 'star'
            ],
            [
                'label' => 'Pengiriman Tugas',
                'amount' => $logs->where('reason_code', 'TASK_PASS')->sum('point_amount'),
                'icon_type' => 'target'
            ],
            [
                'label' => 'Bonus Kursus',
                // [FIX] Ubah COURSE_BONUS menjadi COURSE_COMPLETE
                'amount' => $logs->where('reason_code', 'COURSE_COMPLETE')->sum('point_amount'),
                'icon_type' => 'zap'
            ]
        ];

        return Inertia::render('Achievement/Index', [
            'stats' => [
                'total_xp' => $user->total_points,
                'badges_count' => $earnedBadges->count(),
                'rank' => $rank,
                'streak' => $streak,
            ],
            'earnedBadges' => $earnedBadges,
            'lockedBadges' => $lockedBadges,
            'xpBreakdown' => $xpBreakdown,
        ]);
    }
}
