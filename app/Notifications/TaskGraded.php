<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class TaskGraded extends Notification
{
    use Queueable;

    protected $submission;
    protected $customMessage; // Variabel baru

    // Terima parameter pesan opsional
    public function __construct($submission, $customMessage = null)
    {
        $this->submission = $submission;
        $this->customMessage = $customMessage;
    }

    public function via($notifiable)
    {
        return ['database'];
    }

    public function toArray($notifiable)
    {
        // Gunakan pesan kustom jika ada, jika tidak gunakan default
        $msg = $this->customMessage ?? "Tugas '{$this->submission->task->task_title}' telah dinilai. Skor: {$this->submission->score_mentor}/100";

        return [
            'title' => 'Task Grade Update', // Judul umum
            'message' => $msg,
            'icon' => null,
            'link' => route('task.show', $this->submission->task->module_id),
        ];
    }
}
