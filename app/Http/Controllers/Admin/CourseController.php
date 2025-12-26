<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\User;
use App\Models\Department;
use App\Models\UserModuleProgress;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class CourseController extends Controller
{
    /**
     * Menampilkan daftar kursus dengan Filter & Sorting Server-Side.
     */
    public function index(Request $request)
    {
        // 1. Ambil Parameter
        $search = $request->input('search');
        $status = $request->input('status');

        $sortField = $request->input('sort', 'created_at'); // Default: Tanggal
        $sortDirection = $request->input('direction', 'desc'); // Default: Terbaru

        // 2. Query Builder Dasar
        $query = Course::select('courses.*')
            ->with(['mentor', 'modules', 'department']); // Include relasi department

        // 3. Logic Filter Search
        if ($search) {
            $query->where('title', 'like', "%{$search}%");
        }

        // 4. Logic Filter Status
        if ($status === 'published') {
            $query->where('is_published', true);
        } elseif ($status === 'draft') {
            $query->where('is_published', false);
        }

        // 5. Logic Sorting Server-Side
        switch ($sortField) {
            case 'title':
                $query->orderBy('title', $sortDirection);
                break;

            case 'target_role':
                $query->orderBy('target_role', $sortDirection);
                break;

            case 'is_published':
                $query->orderBy('is_published', $sortDirection);
                break;

            case 'mentor_name':
                $query->leftJoin('users', 'courses.mentor_id', '=', 'users.id')
                    ->orderBy('users.name', $sortDirection);
                break;

            case 'students_count':
                $query->withCount(['progress as students_count' => function ($q) {
                    $q->select(DB::raw('count(distinct user_id)'));
                }])->orderBy('students_count', $sortDirection);
                break;

            default:
                $query->orderBy('courses.created_at', $sortDirection);
                break;
        }

        // 6. Pagination & Mapping Data
        $courses = $query->paginate(10)
            ->withQueryString()
            ->through(function ($course) {
                // Hitung ulang untuk akurasi tampilan
                $studentCount = $course->students_count ?? UserModuleProgress::whereIn('module_id', $course->modules->pluck('id'))
                    ->distinct('user_id')
                    ->count('user_id');

                $assessmentCount = $course->modules->whereIn('content_type', ['QUIZ', 'TASK'])->count();

                return [
                    'id' => $course->id,
                    'title' => $course->title,
                    // Tampilkan nama departemen dari relasi ID, atau fallback ke string target_role
                    'target_role' => $course->department ? $course->department->department_name : ($course->target_role ?? 'General'),
                    'is_published' => (bool) $course->is_published,
                    'mentor_name' => $course->mentor ? $course->mentor->name : 'Unassigned',
                    'students_count' => $studentCount,
                    'assessments_count' => $assessmentCount,
                    'created_at' => $course->created_at->format('d M Y'),
                    'updated_at_diff' => $course->updated_at->diffForHumans(),
                ];
            });

        $mentors = User::whereHas('roles', function ($q) {
            $q->whereIn('name', ['mentor', 'admin']); // Ambil user dengan role mentor atau admin
        })->orderBy('name')->get();

        return Inertia::render('Admin/Courses/Index', [
            'courses' => $courses,
            'mentors' => $mentors,
            'departments' => Department::orderBy('department_name')->get(),
            'filters' => $request->only(['search', 'status', 'sort', 'direction']),
        ]);
    }

    /**
     * Halaman Tambah Kursus.
     */
    public function create()
    {
        // Kirim daftar departemen untuk dropdown
        $departments = Department::orderBy('department_name', 'asc')->get();

        // Kirim daftar mentor (User dengan role mentor/admin)
        $mentors = User::whereHas('roles', function ($q) {
            $q->whereIn('name', ['mentor', 'admin']);
        })->orderBy('name')->get();

        return Inertia::render('Admin/Courses/Create', [
            'departments' => $departments,
            'mentors' => $mentors
        ]);
    }

    /**
     * Menyimpan Kursus Baru.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',

            // [BARU] Validasi department_id (Boleh Null untuk General)
            'department_id' => 'nullable|exists:departments,id',

            'completion_points' => 'required|integer|min:0',
            'mentor_id' => 'nullable|exists:users,id',
            'thumbnail' => 'nullable|image|max:2048',
            'is_published' => 'boolean'
        ]);

        // [LOGIKA FALLBACK] Isi target_role string otomatis
        if ($request->department_id) {
            $dept = Department::find($request->department_id);
            $validated['target_role'] = $dept->department_name;
        } else {
            $validated['target_role'] = 'General';
        }

        // Upload Thumbnail
        if ($request->hasFile('thumbnail')) {
            $path = $request->file('thumbnail')->store('thumbnails', 'public');
            $validated['thumbnail'] = $path;
        }

        // Default Mentor: Auth User jika kosong
        $validated['mentor_id'] = $validated['mentor_id'] ?? Auth::id();

        $course = Course::create($validated);

        // Redirect ke Index
        return redirect()->route('admin.courses.index')->with('success', 'Kursus berhasil dibuat.');
    }

    /**
     * Halaman Edit Kursus.
     */
    public function edit(Course $course)
    {
        $course->load(['mentor', 'modules.quiz', 'modules.task']);

        $departments = Department::orderBy('department_name', 'asc')->get();
        $mentors = User::whereHas('roles', function ($q) {
            $q->whereIn('name', ['mentor', 'admin']);
        })->orderBy('name')->get();

        return Inertia::render('Admin/Courses/Edit', [
            'course' => $course,
            'departments' => $departments,
            'mentors' => $mentors,
            // Mengirim list modul agar bisa di-reorder/edit di halaman yang sama jika desainnya SPA
            'modules' => $course->modules->sortBy('module_order')->values()->all(),
        ]);
    }

    /**
     * Update Kursus.
     */
    public function update(Request $request, Course $course)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',

            // [BARU] Validasi department_id
            'department_id' => 'nullable|exists:departments,id',

            'completion_points' => 'required|integer|min:0',
            'mentor_id' => 'nullable|exists:users,id',
            'thumbnail' => 'nullable|image|max:2048',
            'is_published' => 'boolean'
        ]);

        // [LOGIKA FALLBACK] Update target_role string otomatis
        if ($request->department_id) {
            $dept = Department::find($request->department_id);
            $validated['target_role'] = $dept->department_name;
        } else {
            $validated['target_role'] = 'General';
        }

        // Update Thumbnail
        if ($request->hasFile('thumbnail')) {
            if ($course->thumbnail) {
                Storage::disk('public')->delete($course->thumbnail);
            }
            $path = $request->file('thumbnail')->store('thumbnails', 'public');
            $validated['thumbnail'] = $path;
        }

        $course->update($validated);

        return redirect()->route('admin.courses.index')->with('success', 'Kursus berhasil diperbarui.');
    }

    /**
     * Hapus Kursus.
     */
    public function destroy(Course $course)
    {
        // Hapus thumbnail fisik jika ada
        if ($course->thumbnail) {
            Storage::disk('public')->delete($course->thumbnail);
        }

        $course->delete();
        return back()->with('success', 'Kursus berhasil dihapus.');
    }
}
