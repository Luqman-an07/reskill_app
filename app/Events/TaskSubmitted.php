<?php

namespace App\Events;

use App\Models\UserTaskSubmission;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast; // <--- Wajib Implements ini
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TaskSubmitted implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $submission;

    public function __construct(UserTaskSubmission $submission)
    {
        // Load relasi agar data lengkap dikirim ke frontend
        $this->submission = $submission->load(['user', 'task.module.course']);
    }

    /**
     * Tentukan kemana event ini dikirim.
     * Kita kirim ke Channel Pribadi Mentor pemilik kursus ini.
     */
    public function broadcastOn(): array
    {
        $mentorId = $this->submission->task->module->course->mentor_id;
        return [
            new PrivateChannel('App.Models.User.' . $mentorId),
        ];
    }

    public function broadcastAs(): string
    {
        return 'TaskSubmitted';
    }
}
