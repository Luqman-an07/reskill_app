<?php

namespace App\Policies;

use App\Models\Course;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class CoursePolicy
{
    use HandlesAuthorization;

    /**
     * Tentukan apakah user boleh melihat daftar semua kursus (Halaman Manage/Admin).
     */
    public function viewAny(User $user): bool
    {
        // Admin dan Mentor (Human Growth) boleh masuk dashboard pengelolaan
        return in_array($user->role, ['admin', 'mentor']);
    }

    /**
     * Tentukan apakah user boleh melihat detail kursus.
     */
    public function view(User $user, Course $course): bool
    {
        // Admin & Mentor boleh lihat semua detail (termasuk draft)
        if (in_array($user->role, ['admin', 'mentor'])) {
            return true;
        }

        // Peserta hanya boleh lihat jika kursus sudah dipublish
        return $course->is_published;
    }

    /**
     * Tentukan apakah user boleh membuat kursus baru.
     */
    public function create(User $user): bool
    {
        // Hanya Admin dan Tim Human Growth (Mentor)
        return in_array($user->role, ['admin', 'mentor']);
    }

    /**
     * Tentukan apakah user boleh mengupdate/edit kursus.
     */
    public function update(User $user, Course $course): bool
    {
        // Admin Boleh
        if ($user->role === 'admin') {
            return true;
        }

        // Mentor (Human Growth) Boleh edit SEMUA kursus.
        // (Kita HAPUS batasan '$user->id === $course->mentor_id' agar bisa kerja tim)
        if ($user->role === 'mentor') {
            return true;
        }

        return false;
    }

    /**
     * Tentukan apakah user boleh menghapus kursus.
     */
    public function delete(User $user, Course $course): bool
    {
        // Admin & Mentor boleh menghapus
        return in_array($user->role, ['admin', 'mentor']);
    }

    /**
     * Tentukan apakah user boleh me-restore kursus (jika pakai SoftDeletes).
     */
    public function restore(User $user, Course $course): bool
    {
        return in_array($user->role, ['admin', 'mentor']);
    }

    /**
     * Tentukan apakah user boleh menghapus permanen.
     */
    public function forceDelete(User $user, Course $course): bool
    {
        // Biasanya hanya Admin yang boleh force delete, tapi Mentor juga boleh jika kebijakan membolehkan
        return $user->role === 'admin';
    }
}
