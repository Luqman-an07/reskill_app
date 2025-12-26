<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany; // <--- PENTING: Tambah ini

class Course extends Model
{
    use HasFactory;

    protected $fillable = [
        'mentor_id',
        'department_id',
        'title',
        'description',
        'target_role',
        'completion_points',
        'is_published'
    ];

    protected $casts = [
        'is_published' => 'boolean',
    ];

    // --- RELASI UTAMA ---

    // 1. Relasi ke Module (One to Many)
    public function modules(): HasMany
    {
        return $this->hasMany(Module::class);
    }

    // 2. Relasi ke Mentor/Pembuat (One to Many Inverse)
    public function mentor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'mentor_id');
    }

    // 3. [BARU & WAJIB] Relasi ke Peserta (Many to Many)
    // Digunakan untuk mengambil daftar siswa yang harus dinotifikasi
    public function students(): BelongsToMany
    {
        // Asumsi nama tabel pivot adalah 'course_user'
        return $this->belongsToMany(User::class, 'course_user')
            ->withPivot('status', 'progress') // Sesuaikan dengan kolom pivot Anda
            ->withTimestamps();
    }

    // --- RELASI PENDUKUNG ---

    /**
     * Relasi ke Progress Peserta (Lewat Module)
     * Digunakan untuk menghitung jumlah siswa secara efisien via progress.
     */
    public function progress()
    {
        return $this->hasManyThrough(
            UserModuleProgress::class, // Target (Progress)
            Module::class,             // Perantara (Module)
            'course_id',               // FK di table modules
            'module_id',               // FK di table user_module_progress
            'id',                      // PK di table courses
            'id'                       // PK di table modules
        );
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }
}
