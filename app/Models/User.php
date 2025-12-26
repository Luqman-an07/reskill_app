<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'employee_id',
        'name',
        'email',
        'password',
        'department_id',
        'profile_picture',
        'total_points',
        'seasonal_points',
        'current_level',
        'is_active',
        'last_activity_at',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'last_activity_at' => 'datetime',
        ];
    }

    // --- RELASI ---

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function courses()
    {
        // Pastikan ada ->withTimestamps();
        return $this->belongsToMany(Course::class, 'course_user') // atau nama tabel pivot Anda
            ->withPivot('status', 'progress') // kolom tambahan lain
            ->withTimestamps(); // <--- INI WAJIB ADA
    }

    // Relasi ke Course yang diajar (Jika dia Mentor)
    public function coursesMentored(): HasMany
    {
        return $this->hasMany(Course::class, 'mentor_id');
    }

    // Relasi Tracking Belajar
    public function moduleProgress(): HasMany
    {
        return $this->hasMany(UserModuleProgress::class);
    }

    // Relasi Hasil Ujian/Tugas
    public function quizAttempts(): HasMany
    {
        return $this->hasMany(UserQuizAttempt::class);
    }

    public function taskSubmissions(): HasMany
    {
        return $this->hasMany(UserTaskSubmission::class);
    }

    // Relasi Gamifikasi
    public function pointLogs(): HasMany
    {
        return $this->hasMany(GamificationLedger::class);
    }

    public function badges(): BelongsToMany
    {
        return $this->belongsToMany(Badge::class, 'user_badges')->withPivot('achieved_at');
    }
}
