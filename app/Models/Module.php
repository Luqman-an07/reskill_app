<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Module extends Model
{
    use HasFactory;

    protected $fillable = [
        'course_id',
        'module_title',
        'content_type', // TEXT, VIDEO, QUIZ, TASK, PDF, PPT
        'content_url',
        'module_order',
        'prerequisite_module_id',
        'completion_points',
        'required_time'
    ];

    /**
     * Relasi ke Course (Milik satu kursus)
     */
    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    /**
     * Relasi ke Quiz (Punya satu kuis - Opsional)
     */
    public function quiz(): HasOne
    {
        return $this->hasOne(Quiz::class);
    }

    /**
     * Relasi ke Task (Punya satu tugas - Opsional)
     */
    public function task(): HasOne
    {
        return $this->hasOne(Task::class);
    }

    /**
     * Relasi ke Attachments (File lampiran)
     */
    public function attachments(): HasMany
    {
        return $this->hasMany(ModuleAttachment::class);
    }

    /**
     * [BARU] Relasi ke UserModuleProgress
     * Penting untuk memantau status 'completed' atau 'started' per user.
     */
    public function progress(): HasMany
    {
        return $this->hasMany(UserModuleProgress::class, 'module_id');
    }
}
