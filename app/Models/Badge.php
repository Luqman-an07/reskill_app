<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute; // <--- PENTING: Import ini
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Badge extends Model
{
    use HasFactory;

    protected $fillable = [
        'badge_name',
        'description',
        'icon_emoji',
        'icon_url',
        'bonus_points',
        'trigger_type',
        'trigger_value'
    ];

    /**
     * Accessor: Otomatis mengubah path database menjadi Full URL.
     * Contoh DB: "badges/gold.png"
     * Output JSON: "http://domain.com/storage/badges/gold.png"
     */
    protected function iconUrl(): Attribute
    {
        return Attribute::make(
            get: function ($value) {
                // Jika kosong, kembalikan null
                if (!$value) return null;

                // Jika sudah berupa link eksternal (http/https), biarkan
                if (str_starts_with($value, 'http')) {
                    return $value;
                }

                // Jika path lokal, bungkus dengan asset storage
                return asset('storage/' . $value);
            }
        );
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_badges')->withPivot('achieved_at');
    }
}
