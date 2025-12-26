<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Module;
use App\Models\Quiz;
use App\Models\Task;
use App\Models\ModuleAttachment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use App\Notifications\NewCourseContent; // Import Class Notifikasi
use Illuminate\Support\Facades\Notification; // Import Facade Notification

class CourseContentController extends Controller
{
    public function store(Request $request, Course $course)
    {
        // 1. VALIDASI INPUT
        $validated = $request->validate([
            'type' => 'required|in:TEXT,VIDEO,PDF,PPT,QUIZ,TASK',
            'title' => 'required|string|max:255',
            'completion_points' => 'required|integer|min:0',
            'required_time' => 'nullable|integer|min:0', // Dalam menit

            // Konten Utama
            'content_url' => 'nullable',
            'pdf_file' => 'nullable|file|mimes:pdf|max:10240',
            'ppt_file' => 'nullable|file|mimes:ppt,pptx,doc,docx|max:10240',
            'video_file' => 'nullable|file|mimes:mp4,mov,ogg,qt|max:51200',

            // Lampiran
            'attachments' => 'nullable|array',
            'attachments.*' => 'file|max:10240',

            // Field Quiz
            'duration_minutes' => 'nullable|required_if:type,QUIZ|integer|min:1',
            'passing_score' => 'nullable|required_if:type,QUIZ|integer|min:0|max:100',
            'max_attempts' => 'nullable|required_if:type,QUIZ|integer|min:1',

            // Field Task (UPDATED FOR RELATIVE DEADLINE)
            'description' => 'nullable|required_if:type,TASK|string',
            'max_score' => 'nullable|required_if:type,TASK|integer|min:1',

            // Logika Validasi Hybrid Deadline
            'deadline_type' => 'nullable|required_if:type,TASK|in:fixed,relative,none',
            'due_date' => 'nullable|required_if:deadline_type,fixed|date',
            'duration_days' => 'nullable|required_if:deadline_type,relative|integer|min:1',
        ]);

        // 2. PROSES UPLOAD KONTEN UTAMA
        $finalContentUrl = $validated['content_url'] ?? null;

        if ($request->hasFile('pdf_file') && $validated['type'] === 'PDF') {
            $path = $request->file('pdf_file')->store('modules/pdfs', 'public');
            $finalContentUrl = '/storage/' . $path;
        } elseif ($request->hasFile('ppt_file') && $validated['type'] === 'PPT') {
            $path = $request->file('ppt_file')->store('modules/docs', 'public');
            $finalContentUrl = '/storage/' . $path;
        } elseif ($request->hasFile('video_file') && $validated['type'] === 'VIDEO') {
            $path = $request->file('video_file')->store('modules/videos', 'public');
            $finalContentUrl = '/storage/' . $path;
        } elseif ($validated['type'] === 'TEXT') {
            $finalContentUrl = $validated['content_url'] ?? 'Content here...';
        }

        DB::transaction(function () use ($request, $course, $validated, $finalContentUrl) {

            // 3. LOGIKA URUTAN
            $lastModule = $course->modules()->orderBy('module_order', 'desc')->first();
            $newOrder = $lastModule ? $lastModule->module_order + 1 : 1;
            $prerequisiteId = $lastModule ? $lastModule->id : null;

            // 4. BUAT MODUL UTAMA
            $module = Module::create([
                'course_id' => $course->id,
                'module_title' => $validated['title'],
                'content_type' => $validated['type'],
                'content_url' => $finalContentUrl,
                'module_order' => $newOrder,
                'prerequisite_module_id' => $prerequisiteId,
                'completion_points' => $validated['completion_points'],
                'required_time' => ($validated['required_time'] ?? 0) * 60,
            ]);

            // 5. BUAT DATA SPESIFIK (QUIZ / TASK)
            if ($validated['type'] === 'QUIZ') {
                Quiz::create([
                    'module_id' => $module->id,
                    'quiz_title' => $validated['title'],
                    'duration_minutes' => $validated['duration_minutes'],
                    'passing_score' => $validated['passing_score'],
                    'max_attempts' => $validated['max_attempts'],
                    'points_reward' => $validated['completion_points'],
                ]);
            }

            if ($validated['type'] === 'TASK') {
                // Siapkan data dasar Task
                $taskData = [
                    'module_id' => $module->id,
                    'task_title' => $validated['title'],
                    'description' => $validated['description'],
                    'max_score' => $validated['max_score'],
                    'points_reward' => $validated['completion_points'],
                ];

                // [UPDATED] Logika Sanitasi Deadline Hybrid
                $deadlineType = $request->input('deadline_type', 'none');

                if ($deadlineType === 'fixed') {
                    $taskData['due_date'] = $validated['due_date'];
                    $taskData['duration_days'] = null;
                } elseif ($deadlineType === 'relative') {
                    $taskData['due_date'] = null;
                    $taskData['duration_days'] = $validated['duration_days'];
                } else {
                    // None
                    $taskData['due_date'] = null;
                    $taskData['duration_days'] = null;
                }

                Task::create($taskData);
            }

            // 6. SIMPAN LAMPIRAN
            if ($request->hasFile('attachments')) {
                foreach ($request->file('attachments') as $file) {
                    $path = $file->store('modules/attachments', 'public');
                    ModuleAttachment::create([
                        'module_id' => $module->id,
                        'file_name' => $file->getClientOriginalName(),
                        'file_path' => '/storage/' . $path,
                        'file_type' => $file->getClientOriginalExtension(),
                    ]);
                }
            }

            // ---------------------------------------------------------
            // 7. [BARU] KIRIM NOTIFIKASI KE PESERTA
            // ---------------------------------------------------------
            // Asumsi relasi 'students' sudah ada di Model Course
            $participants = $course->students;

            if ($participants && $participants->count() > 0) {
                // Tentukan Label Tipe Konten
                $typeLabel = match ($validated['type']) {
                    'QUIZ' => 'Kuis',
                    'TASK' => 'Tugas',
                    default => 'Materi'
                };

                // Siapkan Objek Data untuk Notifikasi
                $contentObject = (object) [
                    'title' => $validated['title'],
                    'course_id' => $course->id
                ];

                // Kirim Notifikasi Massal (Via Database & Broadcast)
                Notification::send($participants, new NewCourseContent($contentObject, $typeLabel, $course->title));
            }
            // ---------------------------------------------------------
        });

        return back()->with('success', 'Konten berhasil ditambahkan dan notifikasi dikirim ke peserta.');
    }

    public function reorder(Request $request, Course $course)
    {
        $request->validate([
            'modules' => 'required|array',
            'modules.*' => 'exists:modules,id',
        ]);

        $orderedIds = $request->modules;

        DB::transaction(function () use ($orderedIds, $course) {
            $course->modules()->update(['prerequisite_module_id' => null]);

            $previousModuleId = null;
            foreach ($orderedIds as $index => $moduleId) {
                $module = Module::where('course_id', $course->id)->where('id', $moduleId)->first();
                if ($module) {
                    $module->update([
                        'module_order' => $index + 1,
                        'prerequisite_module_id' => $previousModuleId
                    ]);
                    $previousModuleId = $module->id;
                }
            }
        });

        return back()->with('success', 'Urutan modul berhasil disimpan.');
    }

    public function update(Request $request, Module $module)
    {
        // 1. VALIDASI INPUT
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'completion_points' => 'required|integer|min:0',
            'required_time' => 'nullable|integer|min:0',

            'content_url' => 'nullable',
            'pdf_file' => 'nullable|file|mimes:pdf|max:10240',
            'ppt_file' => 'nullable|file|mimes:ppt,pptx,doc,docx|max:10240',
            'video_file' => 'nullable|file|mimes:mp4,mov,ogg,qt|max:51200',
            'attachments' => 'nullable|array',
            'attachments.*' => 'file|max:10240',

            // Quiz
            'duration_minutes' => 'nullable|integer',
            'passing_score' => 'nullable|integer',
            'max_attempts' => 'nullable|integer',

            // Task (UPDATED VALIDATION)
            'description' => 'nullable|string',
            'max_score' => 'nullable|integer',

            // Validasi Update Deadline
            'deadline_type' => 'nullable|in:fixed,relative,none',
            'due_date' => 'nullable|required_if:deadline_type,fixed|date',
            'duration_days' => 'nullable|required_if:deadline_type,relative|integer|min:1',
        ]);

        // 2. CEK FILE BARU
        $finalContentUrl = $module->content_url;

        if ($request->hasFile('pdf_file') && $module->content_type === 'PDF') {
            if ($module->content_url) Storage::disk('public')->delete(str_replace('/storage/', '', $module->content_url));
            $path = $request->file('pdf_file')->store('modules/pdfs', 'public');
            $finalContentUrl = '/storage/' . $path;
        } elseif ($request->hasFile('ppt_file') && $module->content_type === 'PPT') {
            if ($module->content_url) Storage::disk('public')->delete(str_replace('/storage/', '', $module->content_url));
            $path = $request->file('ppt_file')->store('modules/docs', 'public');
            $finalContentUrl = '/storage/' . $path;
        } elseif ($request->hasFile('video_file') && $module->content_type === 'VIDEO') {
            if ($module->content_url) Storage::disk('public')->delete(str_replace('/storage/', '', $module->content_url));
            $path = $request->file('video_file')->store('modules/videos', 'public');
            $finalContentUrl = '/storage/' . $path;
        } elseif ($request->filled('content_url') && in_array($module->content_type, ['TEXT', 'VIDEO'])) {
            $finalContentUrl = $validated['content_url'];
        }

        DB::transaction(function () use ($module, $validated, $finalContentUrl, $request) {
            // 3. UPDATE MODUL UTAMA
            $module->update([
                'module_title' => $validated['title'],
                'completion_points' => $validated['completion_points'],
                'required_time' => ($validated['required_time'] ?? 0) * 60,
                'content_url' => $finalContentUrl,
            ]);

            // 4. UPDATE DATA SPESIFIK
            if ($module->content_type === 'QUIZ' && $module->quiz) {
                $module->quiz->update([
                    'quiz_title' => $validated['title'],
                    'duration_minutes' => $validated['duration_minutes'],
                    'passing_score' => $validated['passing_score'],
                    'max_attempts' => $validated['max_attempts'],
                    'points_reward' => $validated['completion_points'],
                ]);
            }

            if ($module->content_type === 'TASK' && $module->task) {

                $updateData = [
                    'task_title' => $validated['title'],
                    'description' => $validated['description'],
                    'max_score' => $validated['max_score'],
                    'points_reward' => $validated['completion_points'],
                ];

                // [UPDATED] Logika Sanitasi Update Deadline
                $deadlineType = $request->input('deadline_type');

                if ($deadlineType) {
                    if ($deadlineType === 'fixed') {
                        $updateData['due_date'] = $validated['due_date'];
                        $updateData['duration_days'] = null;
                    } elseif ($deadlineType === 'relative') {
                        $updateData['due_date'] = null;
                        $updateData['duration_days'] = $validated['duration_days'];
                    } else {
                        $updateData['due_date'] = null;
                        $updateData['duration_days'] = null;
                    }
                }

                $module->task->update($updateData);
            }

            // 5. TAMBAH LAMPIRAN BARU
            if ($request->hasFile('attachments')) {
                foreach ($request->file('attachments') as $file) {
                    $path = $file->store('modules/attachments', 'public');
                    ModuleAttachment::create([
                        'module_id' => $module->id,
                        'file_name' => $file->getClientOriginalName(),
                        'file_path' => '/storage/' . $path,
                        'file_type' => $file->getClientOriginalExtension(),
                    ]);
                }
            }
        });

        return back()->with('success', 'Module updated successfully.');
    }

    public function destroy(Module $module)
    {
        DB::transaction(function () use ($module) {
            if ($module->content_type === 'PDF' && $module->content_url) {
                $path = str_replace('/storage/', '', $module->content_url);
                Storage::disk('public')->delete($path);
            }
            $module->delete();
        });

        return back()->with('success', 'Module deleted successfully.');
    }
}
