<?php

namespace App\Events;

use App\Models\UserTaskSubmission;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TaskGraded implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $submission;

    public function __construct(UserTaskSubmission $submission)
    {
        $this->submission = $submission->load('task');
    }

    /**
     * Kirim ke Channel Pribadi Siswa yang punya tugas ini.
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('App.Models.User.' . $this->submission->user_id),
        ];
    }

    public function broadcastAs(): string
    {
        return 'TaskGraded';
    }
}
