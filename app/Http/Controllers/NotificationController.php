<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    // Ambil 5 notifikasi terakhir + jumlah unread
    public function index()
    {
        $user = Auth::user();

        return response()->json([
            'notifications' => $user->notifications()->take(5)->get(),
            'unread_count' => $user->unreadNotifications->count()
        ]);
    }

    /**
     * Tandai satu notifikasi sebagai 'read'
     */
    public function markOneAsRead($id)
    {
        $notification = auth()->user()->notifications()->findOrFail($id);
        $notification->markAsRead();

        return response()->json(['message' => 'Notification read']);
    }

    public function markAllRead()
    {
        // Fitur bawaan Laravel untuk menandai semua notifikasi user ini sbg 'read'
        auth()->user()->unreadNotifications->markAsRead();

        return response()->json(['message' => 'All notifications marked as read']);
    }
}
