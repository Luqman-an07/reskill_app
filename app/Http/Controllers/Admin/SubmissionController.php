<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\UserTaskSubmission;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class SubmissionController extends Controller
{
    public function index(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $isMentor = $user->roles()->where('name', 'mentor')->exists();

        // --- 1. AMBIL PARAMETER FILTER & SORT ---
        $search = $request->input('search');
        $status = $request->input('status');
        $mentorId = $request->input('mentor_id');

        $sortField = $request->input('sort', 'submission_date'); // Default: Tanggal
        $sortDirection = $request->input('direction', 'desc');   // Default: Terbaru

        // --- 2. BUILD QUERY ---
        // Kita perlu JOIN tabel users dan tasks agar bisa sort by name/title
        $query = UserTaskSubmission::query()
            ->select('user_task_submissions.*') // Ambil kolom utama saja agar id tidak bentrok
            ->join('users', 'user_task_submissions.user_id', '=', 'users.id')
            ->join('tasks', 'user_task_submissions.task_id', '=', 'tasks.id')
            // Eager load relasi untuk tampilan (Course, Module)
            ->with(['user', 'task.module.course.mentor']);

        // --- 3. APPLY SECURITY FILTER ---
        if ($isMentor) {
            $query->whereHas('task.module.course', function ($q) use ($user) {
                $q->where('mentor_id', $user->id);
            });
        }

        // --- 4. APPLY SEARCH & FILTERS ---
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('users.name', 'like', "%{$search}%")
                    ->orWhere('tasks.task_title', 'like', "%{$search}%");
            });
        }

        if ($mentorId) {
            $query->whereHas('task.module.course', function ($q) use ($mentorId) {
                $q->where('mentor_id', $mentorId);
            });
        }

        if ($status === 'pending') {
            $query->where('is_graded', false);
        } elseif ($status === 'graded') {
            $query->where('is_graded', true);
        }

        // --- 5. APPLY SORTING ---
        // Mapping field frontend ke kolom database
        switch ($sortField) {
            case 'student_name':
                $query->orderBy('users.name', $sortDirection);
                break;
            case 'task_title':
                $query->orderBy('tasks.task_title', $sortDirection);
                break;
            case 'status':
                $query->orderBy('is_graded', $sortDirection);
                break;
            default: // submission_date
                $query->orderBy('user_task_submissions.submission_date', $sortDirection);
                break;
        }

        // --- 6. PAGINATION & MAPPING ---
        $submissions = $query->paginate(10)
            ->withQueryString()
            ->through(function ($sub) {
                return [
                    'id' => $sub->id,
                    'student_name' => $sub->user->name,
                    'student_avatar' => $sub->user->profile_picture,
                    'task_title' => $sub->task->task_title,
                    'course_title' => $sub->task->module->course->title ?? '-',
                    'mentor_name' => $sub->task->module->course->mentor->name ?? 'Unknown',
                    'file_url' => $sub->submission_file_url ? Storage::url($sub->submission_file_url) : null,
                    'submitted_at' => $sub->submission_date->diffForHumans(),
                    'status' => $sub->is_graded ? 'Graded' : 'Pending',
                    'score' => $sub->score_mentor,
                    'feedback' => $sub->feedback_mentor,
                ];
            });

        // Data Mentor untuk Dropdown Admin
        $mentors = [];
        if (!$isMentor) {
            $mentors = User::whereHas('roles', fn($q) => $q->where('name', 'mentor'))->get();
        }

        return Inertia::render('Admin/Submissions/Index', [
            'submissions' => $submissions,
            'filters' => $request->only(['search', 'status', 'mentor_id', 'sort', 'direction']),
            'mentors' => $mentors,
            'is_admin' => !$isMentor
        ]);
    }
}
