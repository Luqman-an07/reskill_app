<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class GamificationLedger extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'point_amount',
        'reason_code',
        'related_id',
        'related_type',
        'description',
        'transaction_date'
    ];

    protected $casts = [
        'transaction_date' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // Relasi Polymorphic (Bisa connect ke Module, Quiz, atau Task)
    public function related(): MorphTo
    {
        return $this->morphTo();
    }
}
