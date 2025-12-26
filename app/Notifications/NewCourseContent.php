<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\BroadcastMessage;

class NewCourseContent extends Notification
{
    use Queueable;

    public $content; // Object Module/Task/Quiz
    public $type;    // String: 'Modul', 'Tugas', 'Kuis'
    public $courseTitle;

    public function __construct($content, $type, $courseTitle)
    {
        $this->content = $content;
        $this->type = $type;
        $this->courseTitle = $courseTitle;
    }

    public function via($notifiable)
    {
        return ['database', 'broadcast']; // Simpan DB + Kirim Realtime
    }

    // 1. Simpan ke Database (Untuk List Lonceng)
    public function toDatabase($notifiable)
    {
        // Tentukan URL berdasarkan tipe konten
        // Asumsi: Kita arahkan ke module show atau task show
        $url = route('course.show', $this->content->course_id ?? $this->content->module->course_id);

        return [
            'title' => "{$this->type} Baru!",
            'message' => "{$this->type} '{$this->content->title}' baru saja ditambahkan di kursus {$this->courseTitle}.",
            'url' => $url,
            'type' => 'info' // Untuk warna icon di frontend
        ];
    }

    // 2. Kirim Realtime (Untuk Toast Popup)
    public function toBroadcast($notifiable)
    {
        return new BroadcastMessage([
            'title' => "{$this->type} Baru Tersedia",
            'message' => "Cek {$this->type} baru: {$this->content->title}",
            'type' => 'info'
        ]);
    }
}
