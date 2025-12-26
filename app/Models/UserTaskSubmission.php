<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserTaskSubmission extends Model
{
    use HasFactory;

    protected $table = 'user_task_submissions';

    protected $fillable = [
        'task_id',
        'user_id',
        'submission_file_url',
        'score_mentor',
        'feedback_mentor',
        'is_graded',
        'submission_date',
        'is_late'
    ];

    protected $casts = [
        'submission_date' => 'datetime',
        'is_late' => 'boolean',
    ];

    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
