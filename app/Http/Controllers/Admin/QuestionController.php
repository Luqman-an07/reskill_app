<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Quiz;
use App\Models\QuizQuestion;
use Illuminate\Http\Request;
use Inertia\Inertia;

class QuestionController extends Controller
{
    /**
     * Menampilkan Halaman Quiz Builder
     */
    public function index($quizId)
    {
        $quiz = Quiz::with(['questions', 'module.course'])->findOrFail($quizId);
        
        // 👇 PERBAIKAN: Tambahkan Type Hinting agar garis merah hilang
        /** @var \App\Models\User $user */
        $user = \Illuminate\Support\Facades\Auth::user();

        // Logika Pengecekan Role
        $isMentor = $user->roles()->where('name', 'mentor')->exists();

        // Tentukan Route Kembali secara Dinamis
        $backRoute = $isMentor ? 'mentor.courses.edit' : 'admin.courses.edit';

        return Inertia::render('Admin/Quiz/Builder', [
            'quiz' => $quiz,
            'questions' => $quiz->questions,
            'courseId' => $quiz->module->course_id,
            'backRoute' => $backRoute, // Kirim nama route ke Vue
        ]);
    }

    /**
     * Menyimpan Soal Baru
     */
    public function store(Request $request, Quiz $quiz)
    {
        $validated = $request->validate([
            'question_text' => 'required|string',
            'option_a' => 'required|string',
            'option_b' => 'required|string',
            'option_c' => 'required|string',
            'option_d' => 'required|string',
            'correct_answer' => 'required|in:a,b,c,d',
            'score_per_question' => 'required|integer|min:1',
        ]);

        $quiz->questions()->create($validated);

        return back()->with('success', 'Question added successfully.');
    }

    /**
     * Update Soal
     */
    public function update(Request $request, QuizQuestion $question)
    {
        $validated = $request->validate([
            'question_text' => 'required|string',
            'option_a' => 'required|string',
            'option_b' => 'required|string',
            'option_c' => 'required|string',
            'option_d' => 'required|string',
            'correct_answer' => 'required|in:a,b,c,d',
            'score_per_question' => 'required|integer|min:1',
        ]);

        $question->update($validated);

        return back()->with('success', 'Question updated.');
    }

    /**
     * Hapus Soal
     */
    public function destroy(QuizQuestion $question)
    {
        $question->delete();
        return back()->with('success', 'Question deleted.');
    }
}
