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
        // Penting: Kita butuh department_id milik user
        $user->load('department');
        $userDeptId = $user->department_id; // Ambil ID, bukan Namanya
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

            // ... (Logika hitung progress sama seperti sebelumnya) ...
            $completedModules = $course->modules->filter(fn($m) => $m->progress->isNotEmpty() && $m->progress->first()->status === 'completed')->count();
            $startedModules = $course->modules->filter(fn($m) => $m->progress->isNotEmpty() && $m->progress->first()->status === 'started')->count();

            $progressPercent = $totalModules > 0 ? round(($completedModules / $totalModules) * 100) : 0;
            $isEnrolled = $completedModules > 0 || $startedModules > 0;
            $modulePoints = $course->modules->sum('completion_points');
            $totalXP = $modulePoints + $course->completion_points;

            return [
                'id' => $course->id,
                // [PENTING] Masukkan department_id ke dalam array map
                'department_id' => $course->department_id,

                'title' => $course->title,
                'description' => $course->description,
                'target_role' => $course->target_role, // Masih boleh dikirim untuk fallback
                'completion_points' => $course->completion_points,
                'modules_count' => $totalModules,
                'module_points' => $modulePoints,
                'total_xp'      => $totalXP,
                'progress' => $progressPercent,
                'is_enrolled' => $isEnrolled,
                'is_completed' => $progressPercent === 100,
                'is_locked' => false,
                'image_url' => $course->thumbnail ? asset('storage/' . $course->thumbnail) : null,
                'mentor' => $course->mentor,
            ];
        });

        // -----------------------------------------------------------
        // 4. PENGELOMPOKAN BARU (BERBASIS ID - AKURAT 100%)
        // -----------------------------------------------------------

        // Bucket 1: General (Kursus yang department_id-nya NULL)
        $generalCourses = $coursesMapped->filter(function ($c) {
            return is_null($c['department_id']);
        })->values();

        // Bucket 2: Kompetensi (Kursus yang department_id SAMA DENGAN user department_id)
        $deptCourses = $coursesMapped->filter(function ($c) use ($userDeptId) {
            return $c['department_id'] === $userDeptId;
        })->values();

        // Bucket 3: Pengembangan (Kursus milik departemen LAIN)
        // Syarat: Tidak NULL (bukan general) DAN Tidak sama dengan ID user
        $devCourses = $coursesMapped->filter(function ($c) use ($userDeptId) {
            return !is_null($c['department_id']) && $c['department_id'] !== $userDeptId;
        })->values();

        // 5. LOGIKA GATEKEEPER (Tetap Sama)
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

    /**
     * Halaman Detail Kursus (Syllabus & Player).
     */
    public function show($id)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        // 1. Ambil Kursus + Modul + Progress User + Relasi Lain
        $course = Course::with(['modules' => function ($query) {
            $query->orderBy('module_order', 'asc');
        }, 'modules.progress' => function ($query) use ($user) {
            $query->where('user_id', $user->id);
        }, 'modules.quiz', 'modules.task', 'mentor'])->findOrFail($id);

        // 2. [FITUR BARU] Cek Status Enrollment User dari Tabel Pivot
        // Ini memperbaiki bug tombol "Mulai Training" tidak berubah
        $isEnrolled = false;
        if ($user) {
            $isEnrolled = $user->courses()->where('course_id', $course->id)->exists();
        }

        // 3. Mapping Modul untuk Frontend
        $lastAccessedModule = null; // Untuk fitur "Resume"
        $firstModuleId = null;

        $modulesMapped = $course->modules->map(function ($module, $index) use (&$lastAccessedModule, &$firstModuleId) {
            // Set modul pertama
            if ($index === 0) $firstModuleId = $module->id;

            $myProgress = $module->progress->first();

            // Status default: locked
            $status = 'locked';
            if ($myProgress) {
                $status = $myProgress->status; // 'started' atau 'completed'

                // Logika Resume: Cari yang terakhir diakses ('started')
                if ($status === 'started') {
                    $lastAccessedModule = [
                        'id' => $module->id,
                        'type' => $module->content_type
                    ];
                }
            }

            return [
                'id' => $module->id,
                'title' => $module->module_title,
                'type' => $module->content_type,
                'points' => $module->completion_points,
                'duration' => $module->required_time,
                'status' => $status,
                // Kirim ID Task/Quiz jika perlu untuk linking langsung
                'quiz_id' => $module->quiz ? $module->quiz->id : null,
                'task_id' => $module->task ? $module->task->id : null,
            ];
        });

        // 4. Hitung Data Gamifikasi (Tetap dipertahankan)
        $totalPoints = $course->modules->sum('completion_points') + $course->completion_points;
        $earnedPoints = $course->modules->flatMap->progress
            ->where('status', 'completed')
            ->sum(function ($p) use ($course) {
                return $course->modules->find($p->module_id)->completion_points ?? 0;
            });

        // Hitung Persentase Progress Kursus ini untuk frontend
        $completedCount = $course->modules->filter(fn($m) => $m->progress->isNotEmpty() && $m->progress->first()->status === 'completed')->count();
        $progressPercent = $course->modules->count() > 0
            ? round(($completedCount / $course->modules->count()) * 100)
            : 0;

        return Inertia::render('Course/Show', [
            'course' => [
                'id' => $course->id,
                'title' => $course->title,
                'description' => $course->description,
                'category' => $course->target_role,
                'mentor' => $course->mentor,
                'image_url' => $course->thumbnail ? asset('storage/' . $course->thumbnail) : null,
            ],
            'modules' => $modulesMapped, // List silabus
            'gamification' => [
                'total_points' => $totalPoints,
                'earned_points' => $earnedPoints,
                'bonus_points' => $course->completion_points
            ],
            // [DATA TAMBAHAN UNTUK LOGIKA BARU]
            'isEnrolled' => $isEnrolled,
            'progressPercent' => $progressPercent,
            'firstModuleId' => $firstModuleId,
            'resumeModule' => $lastAccessedModule, // Modul terakhir yang dibuka user
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

        // 1. [UPDATED] Attach User ke Course (Isi Tabel Pivot course_user)
        // Cek agar tidak double enroll
        if (!$user->courses()->where('course_id', $id)->exists()) {

            // Simpan ke tabel pivot course_user
            // 'role' => 'student' (default)
            $user->courses()->attach($id, ['status' => 'active', 'progress' => 0]);

            // 2. Buat record progress awal untuk Modul Pertama (Opsional, agar langsung 'started')
            $firstModule = $course->modules()->orderBy('module_order', 'asc')->first();
            if ($firstModule) {
                UserModuleProgress::firstOrCreate(
                    ['user_id' => $user->id, 'module_id' => $firstModule->id],
                    ['status' => 'started', 'time_spent' => 0, 'last_position' => 0]
                );
            }
        }

        // 3. [UPDATED] Redirect Back
        // Menggunakan back() agar halaman Show.vue direfresh dan props 'isEnrolled' terupdate
        return redirect()->back()->with('success', 'Training dimulai! Selamat belajar.');
    }
}
