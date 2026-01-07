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
use Carbon\Carbon;

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

            // Simpan deskripsi manual jika diperlukan, atau generate otomatis
            if ($reason === 'COURSE_COMPLETE' && $relatedModel instanceof Course) {
                $ledger->description = "Menyelesaikan kursus: {$relatedModel->title}";
            } elseif ($reason === 'MODULE_COMPLETE' && $relatedModel instanceof Module) {
                $ledger->description = "Menyelesaikan modul: {$relatedModel->module_title}";
            } else {
                $ledger->description = "Mendapatkan {$amount} poin";
            }

            $ledger->transaction_date = now();

            if ($relatedModel) {
                try {
                    // Pastikan relatedModel memiliki Morph Map atau class name yang valid
                    // Jika relatedModel adalah Course, tapi ledger table tidak punya polymorphic relation,
                    // Anda mungkin perlu menyesuaikan kolomnya (misal: reference_id & reference_type).
                    // Asumsi: GamificationLedger menggunakan Polymorphic Relation standar Laravel.
                    // $ledger->related()->associate($relatedModel); 

                    // Alternatif manual jika tidak pakai morph:
                    // $ledger->reference_id = $relatedModel->id;
                    // $ledger->reference_type = get_class($relatedModel);
                } catch (\Exception $e) {
                    Log::warning("Gamification: Cannot associate related model. " . $e->getMessage());
                }
            }
            $ledger->save();

            // 2. Update User Points
            $user->increment('total_points', $amount);

            // Update seasonal points jika ada kolomnya
            // $user->increment('seasonal_points', $amount); 

            // 3. Cek Level Up (Contoh: setiap 1000 poin naik level)
            // $newLevel = floor($user->total_points / 1000) + 1;
            // if ($newLevel > $user->current_level) {
            //     $user->update(['current_level' => $newLevel]);
            // }

            // 4. CEK BADGE & FLASH SESSION
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
                    // Ambil attempt terakhir untuk kuis ini
                    $lastAttempt = $user->quizAttempts()
                        ->where('quiz_id', $relatedModel->id)
                        ->latest('updated_at') // [OPTIMASI] Gunakan updated_at agar menangkap update terakhir
                        ->first();

                    // Pastikan attempt ditemukan dan nilainya 100
                    if ($lastAttempt && $lastAttempt->final_score == 100) {
                        $awarded = true;
                    }
                }
            }

            // 3. Syarat: Menyelesaikan Jumlah Kursus/Modul Tertentu
            if ($badge->trigger_type === 'COURSE_COMPLETE' && $triggerEvent === 'COURSE_COMPLETE') {
                // Hitung total kursus selesai dari Ledger (lebih akurat karena ledger mencatat penyelesaian)
                $completedCoursesCount = GamificationLedger::where('user_id', $user->id)
                    ->where('reason_code', 'COURSE_COMPLETE')
                    ->count();

                if ($completedCoursesCount >= (int)$badge->trigger_value) {
                    $awarded = true;
                }
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

                // C. TRIGGER REAL-TIME EVENT
                try {
                    BadgeWon::dispatch($badge, $user->id);
                } catch (\Exception $e) {
                    Log::error("Broadcasting Error: " . $e->getMessage());
                }

                // D. FLASH MESSAGE (Jika Synchronous)
                if (Auth::id() === $user->id) {
                    session()->flash('new_badge', $badge);
                }

                $awardedBadges[] = $badge;
            }
        }

        return $awardedBadges;
    }

    /**
     * LOGIKA PUSAT: Cek apakah user sudah menyelesaikan SELURUH modul dalam kursus.
     * Dipanggil di ModuleController, QuizController, & TaskController.
     * Mengembalikan true jika bonus diberikan, false jika tidak.
     */
    public function tryFinishCourse(User $user, $courseId)
    {
        $course = Course::with('modules')->find($courseId);
        if (!$course) return false;

        // 1. Hitung Total Modul vs Modul Selesai
        $totalModules = $course->modules->count();

        $completedModules = UserModuleProgress::where('user_id', $user->id)
            ->whereIn('module_id', $course->modules->pluck('id'))
            ->where('status', 'completed')
            ->count();

        // Jika belum 100%, berhenti.
        if ($completedModules < $totalModules) {
            return false;
        }

        // 2. Cek Duplikat (Exact Match Description) untuk mencegah Double Points
        // Kita gunakan deskripsi unik sebagai kunci idempotency sederhana
        $description = "Menyelesaikan kursus: {$course->title}";

        $alreadyAwarded = GamificationLedger::where('user_id', $user->id)
            ->where('reason_code', 'COURSE_COMPLETE')
            ->where('description', $description)
            ->exists();

        if ($alreadyAwarded) {
            return false;
        }

        // 3. Eksekusi Pemberian Poin Bonus via awardPoints()
        // Ini akan otomatis mencatat ledger, update poin user, dan cek badge.
        $bonusPoints = $course->completion_points ?? 0;

        if ($bonusPoints > 0) {
            $this->awardPoints(
                $user,
                $bonusPoints,
                'COURSE_COMPLETE',
                $course
            );
            return true; // Berhasil memberikan bonus
        }

        // Jika poin 0 tapi kursus selesai, tetap catat ledger (opsional) atau return false
        return false;
    }
}
