<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class UpdateLastActivity
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // 1. Cek apakah user sedang login?
        if (Auth::check()) {
            /** @var \App\Models\User $user */
            $user = Auth::user();

            // 2. Update kolom last_activity_at
            // Kita gunakan updateQuietly agar tidak memicu event 'updated' (untuk performa)
            // Atau update() biasa jika ingin timestamp 'updated_at' juga berubah.

            // Agar efisien: Hanya update jika selisih waktu > 1 menit dari terakhir update
            // (Supaya tidak update database setiap milidetik jika user klik cepat)
            if (! $user->last_activity_at || $user->last_activity_at->diffInMinutes(now()) > 1) {
                $user->forceFill([
                    'last_activity_at' => now(),
                ])->save();
            }
        }

        return $next($request);
    }
}
