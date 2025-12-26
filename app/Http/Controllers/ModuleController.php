<?php

namespace App\Http\Controllers;

use App\Models\Module;
use App\Models\UserModuleProgress;
use App\Services\GamificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class ModuleController extends Controller
{
    protected $gamificationService;

    public function __construct(GamificationService $gamificationService)
    {
        $this->gamificationService = $gamificationService;
    }

    /**
     * Halaman Player Modul (Text/Video/PDF)
     */
    public function show($id)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        $module = Module::with(['course', 'attachments'])->findOrFail($id);

        // CEK PRASYARAT (Chain Locking)
        // Modul ini hanya bisa dibuka jika Prerequisite-nya sudah 'completed'
        if ($module->prerequisite_module_id) {
            $prerequisite = UserModuleProgress::where('user_id', $user->id)
                ->where('module_id', $module->prerequisite_module_id)
                ->first();

            if (!$prerequisite || $prerequisite->status !== 'completed') {
                return redirect()->route('course.show', $module->course_id)
                    ->with('error', 'Silakan selesaikan modul sebelumnya terlebih dahulu.');
            }
        }

        // Catat progress 'started' saat pertama kali buka
        $progress = UserModuleProgress::firstOrCreate(
            ['user_id' => $user->id, 'module_id' => $module->id],
            ['status' => 'started', 'time_spent' => 0, 'last_position' => 0]
        );

        return Inertia::render('Course/TextPlayer', [
            'module' => $module,
            'course' => $module->course,
            'currentProgress' => $progress,
        ]);
    }

    /**
     * Update Progress Real-time (Tracking waktu baca/nonton)
     */
    public function updateProgress(Request $request, $id)
    {
        $user = Auth::user();
        $request->validate([
            'time_spent' => 'required|integer',
            'last_position' => 'required|numeric',
        ]);

        $progress = UserModuleProgress::where('user_id', $user->id)
            ->where('module_id', $id)
            ->firstOrFail();

        $progress->update([
            'time_spent' => $request->time_spent,
            'last_position' => $request->last_position,
            'last_access_at' => now(),
        ]);

        return response()->json(['status' => 'ok']);
    }

    /**
     * Menandai Modul Selesai & Membuka Modul Berikutnya
     */
    public function markAsComplete(Request $request, $moduleId)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $module = Module::with('course')->findOrFail($moduleId);

        // Ambil atau buat record progress
        $progress = UserModuleProgress::firstOrCreate(
            ['user_id' => $user->id, 'module_id' => $module->id]
        );

        // Hanya proses logika penyelesaian jika status belum 'completed'
        // (Mencegah double point / spamming)
        if ($progress->status !== 'completed') {

            // 1. UPDATE STATUS MODUL SAAT INI
            $progress->update([
                'status' => 'completed',
                'completion_date' => now(),
            ]);

            // 2. BERI POIN GAMIFIKASI MODUL
            if ($module->completion_points > 0) {
                $newBadges = $this->gamificationService->awardPoints(
                    $user,
                    $module->completion_points,
                    'MODULE_COMPLETE',
                    $module
                );
                if (!empty($newBadges)) session()->flash('new_badge', $newBadges[0]);
            }

            // 3. CEK KELULUSAN KURSUS & BERI BONUS
            // Service ini akan mengecek apakah semua modul sudah selesai
            $this->gamificationService->tryFinishCourse($user, $module->course_id);
        }

        // ---------------------------------------------------------
        // 4. [FITUR BARU] BUKA MODUL SELANJUTNYA (UNLOCKING)
        // ---------------------------------------------------------

        // Cari modul berikutnya berdasarkan urutan
        $nextModule = Module::where('course_id', $module->course_id)
            ->where('module_order', '>', $module->module_order)
            ->orderBy('module_order', 'asc')
            ->first();

        if ($nextModule) {
            // Buka kunci modul selanjutnya (Set status: 'started')
            UserModuleProgress::firstOrCreate(
                ['user_id' => $user->id, 'module_id' => $nextModule->id],
                [
                    'status' => 'started',
                    'time_spent' => 0,
                    'last_position' => 0
                ]
            );

            // Redirect otomatis ke modul selanjutnya
            return redirect()->route('module.show', $nextModule->id)
                ->with('success', 'Modul selesai! Melanjutkan ke materi berikutnya.');
        }

        // Jika tidak ada modul lagi (Kursus Selesai)
        return redirect()->route('course.show', $module->course_id)
            ->with('success', 'Selamat! Anda telah menyelesaikan seluruh materi kursus ini.');
    }
}
