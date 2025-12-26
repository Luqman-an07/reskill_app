<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ModuleAttachment extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'module_id', // Foreign Key ke tabel modules
        'file_name', // Nama asli file (untuk ditampilkan ke user)
        'file_path', // Path penyimpanan di storage (public/...)
        'file_type', // Ekstensi file (pdf, pptx, docx)
    ];

    /**
     * Relasi: Attachment ini milik satu Module.
     */
    public function module(): BelongsTo
    {
        return $this->belongsTo(Module::class);
    }
}
