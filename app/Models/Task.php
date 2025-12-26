<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Carbon\Carbon; // PENTING: Import Carbon untuk manipulasi tanggal

class Task extends Model
{
    use HasFactory;

    protected $fillable = [
        'module_id',
        'task_title',
        'description',
        'max_score',
        'due_date',      // Tanggal Pasti (Fixed)
        'duration_days', // Durasi (Relative) -> [BARU]
        'points_reward'
    ];

    protected $casts = [
        'due_date' => 'datetime',
        'duration_days' => 'integer',
    ];

    /**
     * ACCESSOR: Menghitung Deadline Personal untuk User yang sedang login.
     * Cara panggil: $task->personal_deadline
     */
    public function getPersonalDeadlineAttribute()
    {
        // SKENARIO 1: Jika Admin set Tanggal Pasti (Fixed Deadline)
        // Maka semua user deadline-nya sama (tanggal kalender).
        if ($this->due_date) {
            return Carbon::parse($this->due_date);
        }

        // SKENARIO 2: Jika Admin set Durasi Hari (Relative Deadline)
        if ($this->duration_days) {
            $user = auth()->user();

            // Jika tidak ada user login, kembalikan null
            if (!$user) return null;

            // Kita harus cari kapan user ini Enroll (Bergabung) ke Kursus.
            // Alurnya: Task -> Module -> Course -> Cek Pivot Table User

            // Mengambil data enrollment user di course terkait
            $enrollment = $user->courses()
                ->where('course_id', $this->module->course_id) // Akses via relasi module
                ->withPivot('created_at') // Pastikan ambil timestamp dari pivot
                ->first();

            // Jika data enrollment ketemu
            if ($enrollment && $enrollment->pivot->created_at) {
                // Rumus: Tanggal Join + Durasi Hari
                // Contoh: Join tgl 1 Jan + Durasi 3 Hari = Deadline tgl 4 Jan (23:59:59)
                return Carbon::parse($enrollment->pivot->created_at)
                    ->addDays($this->duration_days)
                    ->endOfDay();
            }
        }

        // Jika tidak ada due_date DAN tidak ada duration_days
        return null;
    }

    // --- RELASI ---

    public function module(): BelongsTo
    {
        return $this->belongsTo(Module::class);
    }

    public function submissions(): HasMany
    {
        return $this->hasMany(UserTaskSubmission::class);
    }
}
