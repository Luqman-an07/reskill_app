<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class AchievementUnlocked extends Notification
{
    use Queueable;

    protected $badge;

    public function __construct($badge)
    {
        $this->badge = $badge;
    }

    public function via($notifiable): array
    {
        return ['database']; // Simpan ke DB agar muncul di lonceng
    }

    public function toArray($notifiable): array
    {
        return [
            'title' => 'Lencana Baru Didapat!',
            'message' => "Selamat! Anda telah membuka lencana: {$this->badge->badge_name}",
            'icon' => $this->badge->icon_url ? $this->badge->icon_url : null,
            'link' => route('dashboard'), // Atau link ke halaman achievement
            'type' => 'achievement' // Penanda tipe
        ];
    }
}
