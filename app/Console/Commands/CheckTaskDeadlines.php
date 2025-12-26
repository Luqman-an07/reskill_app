<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Task;
use App\Models\UserTaskSubmission;
use App\Notifications\TaskDeadlineWarning;
use Carbon\Carbon;

class CheckTaskDeadlines extends Command
{
    protected $signature = 'lms:check-deadlines';
    protected $description = 'Cek tugas yang deadline H-1 dan kirim notifikasi';

    public function handle()
    {
        $this->info('Memulai pengecekan deadline...');

        // ---------------------------------------------------------
        // 1. CEK FIXED DEADLINE (Tanggal Pasti)
        // ---------------------------------------------------------
        // Cari tugas yang due_date-nya BESOK
        $fixedTasks = Task::whereDate('due_date', Carbon::tomorrow())->with('module.course')->get();

        foreach ($fixedTasks as $task) {
            // Ambil siswa di kursus ini yang BELUM submit
            $students = $task->module->course->students; // Pakai relasi students yg tadi

            foreach ($students as $student) {
                $hasSubmitted = UserTaskSubmission::where('user_id', $student->id)
                    ->where('task_id', $task->id)
                    ->exists();

                if (!$hasSubmitted) {
                    $student->notify(new TaskDeadlineWarning($task, 1));
                    $this->info("Notif Fixed Deadline dikirim ke: {$student->name}");
                }
            }
        }

        // ---------------------------------------------------------
        // 2. CEK RELATIVE DEADLINE (Durasi)
        // ---------------------------------------------------------
        // Cari tugas yang menggunakan sistem durasi
        $relativeTasks = Task::whereNotNull('duration_days')->with('module.course')->get();

        foreach ($relativeTasks as $task) {
            // Kita harus iterasi per siswa karena tanggal deadlinenya beda-beda
            $students = $task->module->course->students;

            foreach ($students as $student) {
                // Skip jika sudah submit
                $hasSubmitted = UserTaskSubmission::where('user_id', $student->id)
                    ->where('task_id', $task->id)
                    ->exists();

                if ($hasSubmitted) continue;

                // Hitung Deadline Personal User
                // Kita perlu manual trigger accessor atau hitung ulang disini karena context console
                // Asumsi kita pakai logika yg sama dengan Model Task
                $enrollment = $student->courses()
                    ->where('course_id', $task->module->course_id)
                    ->first();

                if ($enrollment && $enrollment->pivot->created_at) {
                    $deadline = Carbon::parse($enrollment->pivot->created_at)
                        ->addDays($task->duration_days)
                        ->endOfDay();

                    // Cek apakah deadline BESOK?
                    if ($deadline->isTomorrow()) {
                        $student->notify(new TaskDeadlineWarning($task, 1));
                        $this->info("Notif Relative Deadline dikirim ke: {$student->name}");
                    }
                }
            }
        }

        $this->info('Selesai.');
    }
}
