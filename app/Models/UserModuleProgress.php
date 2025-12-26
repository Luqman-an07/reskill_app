<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserModuleProgress extends Model
{
    use HasFactory;

    // PENTING: Definisikan nama tabel secara eksplisit
    // Agar Laravel tidak mencari tabel 'user_module_progresses' (plural)
    protected $table = 'user_module_progress';

    protected $fillable = [
        'user_id',
        'module_id',
        'status',          // 'locked', 'started', 'completed'
        'time_spent',      // Dalam detik
        'last_position',   // Posisi terakhir (video timestamp / scroll)
        'last_access_at',
        'completion_date'
    ];

    protected $casts = [
        'last_access_at' => 'datetime',
        'completion_date' => 'datetime',
        'time_spent' => 'integer',    // Pastikan angka
        'last_position' => 'integer', // Pastikan angka
    ];

    /**
     * Relasi balik ke User
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relasi balik ke Module
     */
    public function module(): BelongsTo
    {
        return $this->belongsTo(Module::class);
    }
}
