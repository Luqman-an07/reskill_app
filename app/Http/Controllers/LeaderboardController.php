<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\GamificationLedger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Carbon\Carbon;

class LeaderboardController extends Controller
{
    public function index(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $filter = $request->input('filter', 'global'); // global / department

        // 1. AMBIL DATA USER (PESERTA SAJA)
        $query = User::whereHas('roles', function ($q) {
            $q->where('name', 'peserta');
        })->with('department');

        if ($filter === 'department') {
            $query->where('department_id', $user->department_id);
        }

        $allParticipants = $query->get();

        // 2. HITUNG POIN KEMARIN (REAL LOGIC)
        // Ambil semua transaksi poin HARI INI dari ledger untuk dikurangi
        $todayLogs = GamificationLedger::whereDate('transaction_date', now())
            ->get();

        // Buat Collection untuk perhitungan
        $stats = $allParticipants->map(function ($u) use ($todayLogs) {
            $pointsToday = $todayLogs->where('user_id', $u->id)->sum('point_amount');
            $pointsYesterday = $u->total_points - $pointsToday; // Poin sebelum hari ini

            return [
                'user' => $u,
                'current_points' => $u->total_points,
                'yesterday_points' => $pointsYesterday,
                'updated_at' => $u->updated_at, // PENTING: Untuk Tie-Breaker
                'last_activity_at' => $u->last_activity_at, // [BARU] Untuk Status Online
            ];
        });

        // 3. HITUNG RANKING SEKARANG vs KEMARIN (CONSISTENT SORTING)
        // Aturan: Poin Tertinggi (DESC), Jika Seri -> Waktu Update Terlama (ASC)

        $rankedCurrent = $stats->sortBy([
            ['current_points', 'desc'],
            ['updated_at', 'asc'] // Konsisten dengan DashboardController
        ])->values();

        $rankedYesterday = $stats->sortBy([
            ['yesterday_points', 'desc'],
            ['updated_at', 'asc'] // Konsisten Tie-Breaker untuk data kemarin
        ])->values();

        // 4. MAPPING DATA FINAL (TOP 50)
        // Kita ambil Top 50 dari ranking saat ini
        $leaderboard = $rankedCurrent->take(50)->map(function ($item, $index) use ($rankedYesterday) {
            $currentRank = $index + 1;
            $userId = $item['user']->id;

            // Cari rank kemarin user ini untuk hitung trend
            $prevIndex = $rankedYesterday->search(function ($prevItem) use ($userId) {
                return $prevItem['user']->id === $userId;
            });

            $prevRank = ($prevIndex !== false) ? $prevIndex + 1 : $currentRank;

            // Hitung Trend
            $trend = $prevRank - $currentRank;

            // [LOGIKA BARU] Cek Status Online (5 Menit Terakhir)
            $isOnline = false;
            if ($item['last_activity_at']) {
                $lastActive = Carbon::parse($item['last_activity_at']);
                $isOnline = $lastActive->gt(Carbon::now()->subMinutes(5));
            }

            return [
                'id' => $userId,
                'name' => $item['user']->name,
                'profile_picture' => $item['user']->profile_picture,
                'department' => $item['user']->department,
                'total_points' => $item['current_points'],
                'rank' => $currentRank,
                'trend' => $trend,
                'is_online' => $isOnline, // [BARU] Kirim ke Frontend
            ];
        });

        // 5. RANKING USER LOGIN
        // Cari posisi saya di array yang sudah diurutkan dengan benar tadi
        $myRankData = $rankedCurrent->search(fn($item) => $item['user']->id === $user->id);
        $myRank = ($myRankData !== false) ? $myRankData + 1 : '-';

        // --- 6. LOGIKA STREAK (AKTIVITAS HARIAN) ---
        $activityDates = $user->pointLogs()
            ->select(DB::raw('DATE(transaction_date) as date'))
            ->distinct()
            ->orderBy('date', 'desc')
            ->pluck('date')
            ->toArray();

        $streak = 0;
        $checkDate = Carbon::now();

        // Cek hari ini (jika belum ada aktivitas hari ini, cek kemarin untuk start streak)
        $today = $checkDate->format('Y-m-d');
        $hasActivityToday = in_array($today, $activityDates);

        if (!$hasActivityToday) {
            $checkDate->subDay();
        }

        // Loop mundur
        while (in_array($checkDate->format('Y-m-d'), $activityDates)) {
            $streak++;
            $checkDate->subDay();
        }

        $userStats = [
            'total_xp' => $user->total_points,
            'badges' => $user->badges()->count(),
            'streak' => $streak,
            'completed' => $user->moduleProgress()->where('status', 'completed')->count(),
        ];

        return Inertia::render('Leaderboard/Index', [
            'leaderboard' => $leaderboard,
            'myRank' => $myRank,
            'currentFilter' => $filter,
            'userStats' => $userStats,
        ]);
    }
}
