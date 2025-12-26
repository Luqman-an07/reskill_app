<?php

namespace App\Console\Commands;

use App\Models\Badge;
use App\Models\User;
use App\Models\UserModuleProgress;
use App\Models\UserQuizAttempt;
use Illuminate\Console\Command;

class SyncBadges extends Command
{
    /**
     * Nama perintah yang akan diketik di terminal
     */
    protected $signature = 'gamification:sync-badges';

    /**
     * Deskripsi perintah
     */
    protected $description = 'Memindai dan memberikan badge kepada user yang memenuhi syarat (Retroactive)';

    /**
     * Logika Eksekusi
     */
    public function handle()
    {
        $this->info('Memulai sinkronisasi Badge untuk semua user...');

        $users = User::with('badges')->get();
        $badges = Badge::all();
        $countAwarded = 0;

        foreach ($users as $user) {
            $this->line("Memproses user: {$user->name}...");

            // Ambil ID badge yang sudah dimiliki user ini agar tidak duplikat
            $ownedBadgeIds = $user->badges->pluck('id')->toArray();

            foreach ($badges as $badge) {
                // Skip jika sudah punya
                if (in_array($badge->id, $ownedBadgeIds)) continue;

                $shouldAward = false;

                // --- LOGIKA 1: TOTAL POIN ---
                if ($badge->trigger_type === 'POINTS_REACHED') {
                    if ($user->total_points >= (int)$badge->trigger_value) {
                        $shouldAward = true;
                    }
                }

                // --- LOGIKA 2: KUIS SEMPURNA ---
                if ($badge->trigger_type === 'QUIZ_PERFECT') {
                    // Cek apakah user PERNAH dapat nilai 100 di kuis mana saja
                    $hasPerfectScore = UserQuizAttempt::where('user_id', $user->id)
                        ->where('final_score', 100)
                        ->exists();

                    if ($hasPerfectScore) $shouldAward = true;
                }

                // --- LOGIKA 3: COURSE COMPLETE ---
                // (Contoh: Trigger value = Jumlah modul selesai)
                if ($badge->trigger_type === 'COURSE_COMPLETE') {
                    $completedCount = UserModuleProgress::where('user_id', $user->id)
                        ->where('status', 'completed')
                        ->count();

                    if ($completedCount >= (int)$badge->trigger_value) {
                        $shouldAward = true;
                    }
                }

                // --- EKSEKUSI PEMBERIAN BADGE ---
                if ($shouldAward) {
                    $user->badges()->attach($badge->id, ['achieved_at' => now()]);
                    $this->info("  -> Badge Diberikan: {$badge->badge_name}");
                    $countAwarded++;
                }
            }
        }

        $this->newLine();
        $this->info("Selesai! Total {$countAwarded} badge baru telah diberikan.");
    }
}
