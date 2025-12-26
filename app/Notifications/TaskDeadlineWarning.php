<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\BroadcastMessage;

class TaskDeadlineWarning extends Notification
{
    use Queueable;

    public $task;
    public $daysLeft;

    public function __construct($task, $daysLeft)
    {
        $this->task = $task;
        $this->daysLeft = $daysLeft;
    }

    public function via($notifiable)
    {
        return ['database', 'broadcast'];
    }

    public function toDatabase($notifiable)
    {
        $timeStr = $this->daysLeft === 0 ? "Hari Ini" : "dalam {$this->daysLeft} Hari";

        return [
            'title' => "⏳ Deadline Mendekati!",
            'message' => "Tugas '{$this->task->task_title}' jatuh tempo {$timeStr}. Segera kumpulkan!",
            'url' => route('task.show', $this->task->module_id), // Arahkan langsung ke player tugas
            'type' => 'warning'
        ];
    }

    public function toBroadcast($notifiable)
    {
        return new BroadcastMessage([
            'title' => "Warning Deadline",
            'message' => "Tugas '{$this->task->task_title}' deadline sebentar lagi!",
            'type' => 'warning'
        ]);
    }
}
