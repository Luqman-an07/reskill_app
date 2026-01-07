<?php

namespace App\Http\Controllers;

use App\Models\Module;
use App\Models\UserModuleProgress;
use App\Models\GamificationLedger;
use App\Services\GamificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Carbon\Carbon;

class ModuleController extends Controller
{
    protected $gamificationService;

    public function __construct(GamificationService $gamificationService)
    {
        $this->gamificationService = $gamificationService;
    }

    public function show($id)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $module = Module::with(['course', 'attachments'])->findOrFail($id);

        // CEK PRASYARAT
        if ($module->prerequisite_module_id) {
            $prerequisite = UserModuleProgress::where('user_id', $user->id)
                ->where('module_id', $module->prerequisite_module_id)
                ->first();

            if (!$prerequisite || $prerequisite->status !== 'completed') {
                return redirect()->route('course.show', $module->course_id)
                    ->with('error', 'Silakan selesaikan modul sebelumnya terlebih dahulu.');
            }
        }

        UserModuleProgress::firstOrCreate(
            ['user_id' => $user->id, 'module_id' => $module->id],
            ['status' => 'started', 'time_spent' => 0, 'last_position' => 0]
        );

        return Inertia::render('Course/TextPlayer', [
            'module' => $module,
            'course' => $module->course,
            'currentProgress' => UserModuleProgress::where('user_id', $user->id)->where('module_id', $module->id)->first(),
        ]);
    }

    public function updateProgress(Request $request, $id)
    {
        $user = Auth::user();
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
     * Menandai Modul Selesai
     */
    public function markAsComplete(Request $request, $moduleId)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $module = Module::with('course')->findOrFail($moduleId);

        // Gunakan Transaction agar data konsisten
        $courseCompleted = DB::transaction(function () use ($user, $module) {

            // 1. Update/Create Progress
            $progress = UserModuleProgress::firstOrCreate(
                ['user_id' => $user->id, 'module_id' => $module->id]
            );

            // Jika belum completed, proses poin modul
            if ($progress->status !== 'completed') {
                $progress->update([
                    'status' => 'completed',
                    'completion_date' => now(),
                ]);

                // Poin Modul via Service
                if ($module->completion_points > 0) {
                    $this->gamificationService->awardPoints(
                        $user,
                        $module->completion_points,
                        'MODULE_COMPLETE',
                        $module
                    );
                }
            }

            // 2. [UPDATED] Cek Kelulusan Kursus Menggunakan Service Pusat
            // Menggunakan method yang sudah kita pindahkan ke GamificationService
            return $this->gamificationService->tryFinishCourse($user, $module->course_id);
        });

        // Feedback Flash Message jika Kursus Selesai
        if ($courseCompleted) {
            session()->flash('success', 'Selamat! Kursus selesai dan Bonus Poin telah ditambahkan!');
        } else {
            session()->flash('success', 'Modul selesai! Melanjutkan ke materi berikutnya.');
        }

        // 3. Logika Redirect (Next Module / Finish)
        $nextModule = Module::where('course_id', $module->course_id)
            ->where('module_order', '>', $module->module_order)
            ->orderBy('module_order', 'asc')
            ->first();

        if ($nextModule) {
            UserModuleProgress::firstOrCreate(
                ['user_id' => $user->id, 'module_id' => $nextModule->id],
                ['status' => 'started', 'time_spent' => 0, 'last_position' => 0]
            );
            return redirect()->route('module.show', $nextModule->id);
        }

        return redirect()->route('course.show', $module->course_id);
    }
}
