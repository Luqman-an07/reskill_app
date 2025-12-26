<?php

namespace App\Services;

use App\Models\Badge;
use App\Models\GamificationLedger;
use App\Models\User;
use App\Models\Module;
use App\Models\Course;
use App\Models\UserModuleProgress;
use App\Notifications\AchievementUnlocked;
use App\Events\BadgeWon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

class GamificationService
{
    /**
     * Memberikan poin, mencatat ledger, cek level up, dan cek badge.
     */
    public function awardPoints(User $user, int $amount, string $reason, $relatedModel = null): array
    {
        if ($amount <= 0) return [];

        $newBadges = [];

        DB::transaction(function () use ($user, $amount, $reason, $relatedModel, &$newBadges) {
            // 1. Catat Ledger (Riwayat Poin)
            $ledger = new GamificationLedger();
            $ledger->user_id = $user->id;
            $ledger->point_amount = $amount;
            $ledger->reason_code = $reason;
            $ledger->transaction_date = now();

            if ($relatedModel) {
                try {
                    $ledger->related()->associate($relatedModel);
                } catch (\Exception $e) {
                    Log::warning("Gamification: Cannot associate related model. " . $e->getMessage());
                }
            }
            $ledger->save();

            // 2. Update User Points
            $user->increment('total_points', $amount);
            $user->increment('seasonal_points', $amount);

            // 3. Cek Level Up
            $newLevel = floor($user->total_points / 1000) + 1;
            if ($newLevel > $user->current_level) {
                $user->update(['current_level' => $newLevel]);
            }

            // 4. CEK BADGE & FLASH SESSION DI DALAMNYA
            $newBadges = $this->checkBadges($user, $reason, $relatedModel);
        });

        return $newBadges;
    }

    /**
     * Cek apakah user berhak mendapatkan badge baru berdasarkan event trigger.
     */
    public function checkBadges(User $user, string $triggerEvent, $relatedModel = null): array
    {
        $awardedBadges = [];

        // Ambil badge yang BELUM dimiliki user
        $unearnedBadges = Badge::whereDoesntHave('users', function ($q) use ($user) {
            $q->where('user_id', $user->id);
        })->get();

        foreach ($unearnedBadges as $badge) {
            $awarded = false;

            // --- LOGIKA PENGECEKAN SYARAT BADGE ---

            // 1. Syarat: Mencapai Poin Tertentu
            if ($badge->trigger_type === 'POINTS_REACHED') {
                if ($user->total_points >= (int)$badge->trigger_value) {
                    $awarded = true;
                }
            }

            // 2. Syarat: Kuis Nilai Sempurna (100)
            if ($badge->trigger_type === 'QUIZ_PERFECT' && $triggerEvent === 'QUIZ_PASS') {
                if ($relatedModel) {
                    $lastScore = $user->quizAttempts()
                        ->where('quiz_id', $relatedModel->id)
                        ->latest()
                        ->value('final_score');

                    if ($lastScore == 100) $awarded = true;
                }
            }

            // 3. Syarat: Menyelesaikan Jumlah Kursus/Modul Tertentu
            // Digunakan untuk badge seperti "Menyelesaikan 10 Modul"
            if ($badge->trigger_type === 'COURSE_COMPLETE' && $triggerEvent === 'MODULE_COMPLETE') {
                $completedCount = $user->moduleProgress()->where('status', 'completed')->count();
                if ($completedCount >= (int)$badge->trigger_value) {
                    $awarded = true;
                }
            }

            // 4. Syarat: Rajin Mengumpulkan Tugas (Task Count)
            if ($badge->trigger_type === 'TASK_COUNT' && $triggerEvent === 'TASK_PASS') {
                $taskCount = $user->taskSubmissions()->where('is_graded', true)->count();
                if ($taskCount >= (int)$badge->trigger_value) {
                    $awarded = true;
                }
            }

            // 5. Syarat: Bonus Kursus (Menyelesaikan 1 Kursus Penuh)
            // Digunakan untuk badge seperti "Menyelesaikan 1 Kursus Penuh"
            if ($badge->trigger_type === 'COURSE_BONUS' && $triggerEvent === 'COURSE_BONUS') {
                // Karena trigger eventnya spesifik, langsung berikan jika jumlah kursus selesai sesuai
                // Logic hitung jumlah kursus selesai bisa ditambahkan di sini jika badge-nya butuh > 1 kursus
                $awarded = true;
            }

            // --- JIKA SYARAT TERPENUHI ---
            if ($awarded) {
                // A. Simpan ke Database
                $user->badges()->attach($badge->id, ['achieved_at' => now()]);

                // B. Kirim Notifikasi Database (Agar masuk Dropdown Lonceng)
                try {
                    $user->notify(new AchievementUnlocked($badge));
                } catch (\Exception $e) {
                    Log::error("Notification Error: " . $e->getMessage());
                }

                // C. TRIGGER REAL-TIME EVENT (PENTING: Kirim OBJECT $badge, bukan string nama)
                // Ini trigger untuk Asynchronous (misal Mentor menilai tugas) -> Modal muncul Real-time
                try {
                    BadgeWon::dispatch($badge, $user->id);
                } catch (\Exception $e) {
                    Log::error("Broadcasting Error: " . $e->getMessage());
                }

                // D. FLASH MESSAGE (SOLUSI UTAMA BUG ANDA)
                // Cek apakah user yang sedang login adalah penerima badge?
                // Jika YA (Synchronous Action seperti Kuis/Modul), pasang session flash.
                // Modal akan muncul setelah halaman reload/redirect.
                if (Auth::id() === $user->id) {
                    session()->flash('new_badge', $badge);
                }

                $awardedBadges[] = $badge;
            }
        }

        return $awardedBadges;
    }

    /**
     * Helper Baru: Cek apakah user sudah menyelesaikan SELURUH modul dalam kursus.
     * Dipanggil di ModuleController (Selesai Baca) & TaskSubmissionController (Tugas Lulus).
     */
    public function tryFinishCourse(User $user, $courseId)
    {
        // 1. Hitung Total Modul di Kursus Ini
        $totalModules = Module::where('course_id', $courseId)->count();

        // 2. Hitung Modul yang Selesai oleh User
        $completedModules = UserModuleProgress::where('user_id', $user->id)
            ->whereHas('module', fn($q) => $q->where('course_id', $courseId))
            ->where('status', 'completed')
            ->count();

        // 3. Jika Selesai Semua
        if ($totalModules > 0 && $completedModules >= $totalModules) {

            // Ambil Data Kursus
            $course = Course::find($courseId);

            if (!$course || $course->completion_points <= 0) return;

            // 4. Cek Duplikasi (Jangan kasih bonus 2x)
            $alreadyClaimed = GamificationLedger::where('user_id', $user->id)
                ->where('reason_code', 'COURSE_BONUS')
                ->where('related_type', 'App\Models\Course')
                ->where('related_id', $courseId)
                ->exists();

            if (!$alreadyClaimed) {
                // 5. BERI BONUS POIN
                $this->awardPoints(
                    $user,
                    $course->completion_points,
                    'COURSE_BONUS', // Reason Code Khusus
                    $course
                );
            }
        }
    }
}
