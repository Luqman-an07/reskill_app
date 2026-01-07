<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;
use App\Models\User;

class UserActivity
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check()) {
            /** @var User $user */
            $user = Auth::user();
            
            // Cek apakah kolom last_activity_at sudah ada isinya atau sudah lebih dari 1 menit yang lalu
            // Ini untuk mencegah update database setiap detik (efisiensi performa)
            if (!$user->last_activity_at || $user->last_activity_at->diffInMinutes(now()) > 0) {
                $user->update(['last_activity_at' => now()]);
            }
        }

        return $next($request);
    }
}