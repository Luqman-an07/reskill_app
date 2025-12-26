<?php

namespace App\Http\Middleware;

use App\Models\UserTaskSubmission;
use App\Models\Department; // Pastikan Model Department diimport
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'app';

    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    public function share(Request $request): array
    {
        $user = $request->user();
        $pendingCount = 0;

        if ($user) {
            // --- LOGIKA CEK ROLE (SAFE MODE) ---
            // Menggunakan query relasi manual agar tidak error 'undefined method hasRole'
            $isAdmin = $user->roles()->where('name', 'admin')->exists();
            $isMentor = $user->roles()->where('name', 'mentor')->exists();

            if ($isAdmin) {
                // ADMIN: Hitung semua tugas yang belum dinilai (is_graded = false)
                $pendingCount = UserTaskSubmission::where('is_graded', false)->count();
            } elseif ($isMentor) {
                // MENTOR: Hitung tugas HANYA dari kursus miliknya
                // Relasi: Submission -> Task -> Module -> Course -> Mentor
                $pendingCount = UserTaskSubmission::whereHas('task.module.course', function ($q) use ($user) {
                    $q->where('mentor_id', $user->id);
                })->where('is_graded', false)->count();
            }
        }

        return [
            ...parent::share($request),

            // Data Auth & Counters
            'auth' => [
                'user' => $user ? $user->load('roles', 'department') : null,
                'pendingSubmissionsCount' => $pendingCount, // Badge Merah Tugas
                'unread_notifications_count' => $user ? $user->unreadNotifications()->count() : 0, // Badge Lonceng
            ],

            // [BARU] Data Global untuk Onboarding Modal
            // Mengirim daftar departemen ke seluruh frontend jika user sedang login
            // Menggunakan fn() agar query hanya dijalankan saat data diminta (Lazy Evaluation)
            'departments_global' => fn() => $request->user()
                ? Department::select('id', 'department_name')
                ->orderBy('department_name', 'asc')
                ->get()
                : [],

            'flash' => [
                'success' => fn() => $request->session()->get('success'),
                'error' => fn() => $request->session()->get('error'),
                'new_badge' => fn() => $request->session()->get('new_badge'),
            ],
        ];
    }
}
