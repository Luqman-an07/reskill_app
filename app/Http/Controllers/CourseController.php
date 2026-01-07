<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\UserModuleProgress;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class CourseController extends Controller
{
    /**
     * Dashboard Siswa: Menampilkan kursus dengan Logika 3 Prioritas (Gatekeeper).
     */
    public function index()
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        // 1. Load Data User & Departemen
        $user->load('department');
        $userDeptId = $user->department_id;
        $userDeptName = $user->department ? $user->department->department_name : '';

        // 2. AMBIL SEMUA DATA KURSUS
        $allCourses = Course::with(['modules' => function ($q) {
            $q->orderBy('module_order');
        }, 'modules.progress' => function ($q) use ($user) {
            $q->where('user_id', $user->id);
        }])
            ->where('is_published', true)
            ->orderBy('created_at', 'desc')
            ->get();

        // 3. MAPPING DATA
        $coursesMapped = $allCourses->map(function ($course) {
            $totalModules = $course->modules->count();

            // Hitung modul selesai
            $completedModules = $course->modules->filter(fn($m) => $m->progress->isNotEmpty() && $m->progress->first()->status === 'completed')->count();
            $startedModules = $course->modules->filter(fn($m) => $m->progress->isNotEmpty() && $m->progress->first()->status === 'started')->count();

            $progressPercent = $totalModules > 0 ? round(($completedModules / $totalModules) * 100) : 0;
            $isEnrolled = $completedModules > 0 || $startedModules > 0;

            // [FIX] Status Selesai Strict Check
            $isCompleted = ($totalModules > 0 && $completedModules === $totalModules);

            $modulePoints = $course->modules->sum('completion_points');
            $totalXP = $modulePoints + $course->completion_points;

            return [
                'id' => $course->id,
                'department_id' => $course->department_id,
                'title' => $course->title,
                'description' => $course->description,
                'target_role' => $course->target_role,
                'completion_points' => $course->completion_points,
                'modules_count' => $totalModules,
                'module_points' => $modulePoints,
                'total_xp'      => $totalXP,
                'progress' => $progressPercent,
                'is_enrolled' => $isEnrolled,
                'is_completed' => $isCompleted,
                'is_locked' => false,
                'image_url' => $course->thumbnail ? asset('storage/' . $course->thumbnail) : null,
                'mentor' => $course->mentor,
            ];
        });

        // 4. PENGELOMPOKAN
        $generalCourses = $coursesMapped->filter(fn($c) => is_null($c['department_id']))->values();
        $deptCourses = $coursesMapped->filter(fn($c) => $c['department_id'] === $userDeptId)->values();
        $devCourses = $coursesMapped->filter(fn($c) => !is_null($c['department_id']) && $c['department_id'] !== $userDeptId)->values();

        // 5. LOGIKA GATEKEEPER
        $isGeneralDone = $generalCourses->isEmpty() || $generalCourses->every(fn($c) => $c['is_completed']);
        $isDeptDone = $deptCourses->isEmpty() || $deptCourses->every(fn($c) => $c['is_completed']);

        // 6. PENERAPAN STATUS VISUAL
        $p1 = $generalCourses->map(fn($c) => array_merge($c, ['is_locked' => false]));
        $p2 = $deptCourses->map(fn($c) => array_merge($c, ['is_locked' => !$isGeneralDone]));
        $p3 = $devCourses->map(fn($c) => array_merge($c, ['is_locked' => !($isGeneralDone && $isDeptDone)]));

        return Inertia::render('Course/Index', [
            'sections' => [
                'priority_1' => $p1,
                'priority_2' => $p2,
                'priority_3' => $p3,
            ],
            'status_gates' => [
                'general_done' => $isGeneralDone,
                'dept_done' => $isDeptDone,
            ],
            'userDept' => $userDeptName
        ]);
    }

    public function show($id)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        // 1. Ambil Kursus + Modul + Progress
        $course = Course::with(['modules' => function ($query) {
            $query->orderBy('module_order', 'asc');
        }, 'modules.progress' => function ($query) use ($user) {
            $query->where('user_id', $user->id);
        }, 'modules.quiz', 'modules.task', 'mentor'])->findOrFail($id);

        // 2. Cek Status Enrollment
        $isEnrolled = false;
        if ($user) {
            $isEnrolled = $user->courses()->where('course_id', $course->id)->exists();
        }

        // 3. Mapping Modul & Hitung Poin Modul
        $lastAccessedModule = null;
        $firstModuleId = null;

        // Variabel penampung poin
        $maxModulePoints = 0;   // Total poin modul yang tersedia
        $earnedModulePoints = 0; // Total poin modul yang sudah didapat user

        $modulesMapped = $course->modules->map(function ($module, $index) use (&$lastAccessedModule, &$firstModuleId, &$maxModulePoints, &$earnedModulePoints) {
            if ($index === 0) $firstModuleId = $module->id;

            $myProgress = $module->progress->first();
            $status = 'locked';

            // Hitung Max Poin Modul
            $maxModulePoints += $module->completion_points;

            if ($myProgress) {
                $status = $myProgress->status;
                if ($status === 'started') {
                    $lastAccessedModule = ['id' => $module->id, 'type' => $module->content_type];
                }

                // Hitung Poin Modul yang Didapat (Hanya jika completed)
                if ($status === 'completed') {
                    $earnedModulePoints += $module->completion_points;
                }
            }

            return [
                'id' => $module->id,
                'title' => $module->module_title,
                'type' => $module->content_type,
                'points' => $module->completion_points,
                'duration' => $module->required_time,
                'status' => $status,
                'quiz_id' => $module->quiz ? $module->quiz->id : null,
                'task_id' => $module->task ? $module->task->id : null,
            ];
        });

        // 4. Hitung Status Kelulusan (Strict Check)
        $totalModules = $course->modules->count();
        $completedCount = $course->modules->filter(fn($m) => $m->progress->isNotEmpty() && $m->progress->first()->status === 'completed')->count();

        $progressPercent = $totalModules > 0 ? round(($completedCount / $totalModules) * 100) : 0;
        $isCompleted = ($totalModules > 0 && $completedCount === $totalModules);

        // 5. Hitung Akumulasi Total
        $maxTotalPoints = $maxModulePoints + $course->completion_points;

        // Poin Total Didapat = Poin Modul + (Bonus jika selesai)
        $earnedTotalPoints = $earnedModulePoints;
        if ($isCompleted) {
            $earnedTotalPoints += $course->completion_points;
        }

        return Inertia::render('Course/Show', [
            'course' => [
                'id' => $course->id,
                'title' => $course->title,
                'description' => $course->description,
                'category' => $course->target_role,
                'mentor' => $course->mentor,
                'image_url' => $course->thumbnail ? asset('storage/' . $course->thumbnail) : null,
            ],
            'modules' => $modulesMapped,

            // [DATA GAMIFIKASI YANG SUDAH DIPERBAIKI]
            'gamification' => [
                'earned_module_points' => $earnedModulePoints, // Murni dari modul
                'earned_total_points'  => $earnedTotalPoints,  // Modul + Bonus (Current)
                'max_total_points'     => $maxTotalPoints,     // Poin maksimal kursus (Target)
                'bonus_points'         => $course->completion_points
            ],

            'isEnrolled' => $isEnrolled,
            'progressPercent' => $progressPercent,
            'isCompleted' => $isCompleted,
            'firstModuleId' => $firstModuleId,
            'resumeModule' => $lastAccessedModule,
        ]);
    }

    /**
     * Proses Enroll / Mulai Belajar Kursus.
     */
    public function enroll(Request $request, $id)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $course = Course::findOrFail($id);

        // Gunakan syncWithoutDetaching untuk mencegah duplikat enroll
        // (Lebih aman daripada cek exists manual)
        $user->courses()->syncWithoutDetaching([
            $id => ['status' => 'active', 'progress' => 0]
        ]);

        // Buka Modul Pertama
        $firstModule = $course->modules()->orderBy('module_order', 'asc')->first();
        if ($firstModule) {
            UserModuleProgress::firstOrCreate(
                ['user_id' => $user->id, 'module_id' => $firstModule->id],
                ['status' => 'started', 'time_spent' => 0, 'last_position' => 0]
            );
        }

        return redirect()->back()->with('success', 'Training dimulai! Selamat belajar.');
    }
}
