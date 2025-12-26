<?php

namespace App\Http\Controllers;

use App\Models\Quiz;
use App\Models\UserModuleProgress;
use App\Models\UserQuizAttempt;
use App\Services\GamificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class QuizController extends Controller
{
    protected $gamificationService;

    public function __construct(GamificationService $gamificationService)
    {
        $this->gamificationService = $gamificationService;
    }

    /**
     * Halaman Pengerjaan Kuis (Player).
     * Menerima parameter $id sebagai MODULE ID.
     */
    public function show($moduleId)
    {
        /** @var \App\Models\User $user */
        $user = \Illuminate\Support\Facades\Auth::user();

        $quiz = Quiz::with('questions', 'module')
            ->where('module_id', $moduleId)
            ->firstOrFail();

        // Ambil semua riwayat percobaan user untuk kuis ini
        $userAttempts = $user->quizAttempts()
            ->where('quiz_id', $quiz->id)
            ->orderBy('created_at', 'desc')
            ->get();

        $attemptsCount = $userAttempts->count();

        // LOGIKA BARU: Cek apakah sudah pernah dapat nilai 100?
        $hasPerfectScore = $userAttempts->where('final_score', 100)->isNotEmpty();

        // JIKA: (Limit Habis) ATAU (Sudah Nilai 100) -> Masuk REVIEW MODE
        if ($attemptsCount >= $quiz->max_attempts || $hasPerfectScore) {

            $lastAttempt = $userAttempts->first();
            $questions = $quiz->questions->map(function ($q) {
                return [
                    'id' => $q->id,
                    'text' => $q->question_text,
                    'options' => [
                        ['key' => 'a', 'label' => $q->option_a],
                        ['key' => 'b', 'label' => $q->option_b],
                        ['key' => 'c', 'label' => $q->option_c],
                        ['key' => 'd', 'label' => $q->option_d],
                    ],
                    'correct_answer' => $q->correct_answer
                ];
            });

            $message = $hasPerfectScore
                ? 'Selamat! Anda sudah mencapai nilai sempurna.'
                : 'Kesempatan mencoba kuis ini sudah habis.';

            return Inertia::render('Quiz/Review', [
                'quiz' => [
                    'title' => $quiz->quiz_title,
                    'questions' => $questions,
                    'course_id' => $quiz->module->course_id
                ],
                'attempt' => $lastAttempt,
                'flashMessage' => $message
            ]);
        }

        // LOGIKA CARRY OVER
        $previousAttempt = $userAttempts->first();
        $previousAnswers = $previousAttempt ? $previousAttempt->answers : [];

        $attempt = UserQuizAttempt::create([
            'user_id' => $user->id,
            'quiz_id' => $quiz->id,
            'attempt_number' => $attemptsCount + 1,
            'start_time' => now(),
            'answers' => $previousAnswers,
        ]);

        // Transform Data Soal (TANPA Kunci Jawaban)
        $questions = $quiz->questions->map(function ($q) {
            return [
                'id' => $q->id,
                'text' => $q->question_text,
                'options' => [
                    ['key' => 'a', 'label' => $q->option_a],
                    ['key' => 'b', 'label' => $q->option_b],
                    ['key' => 'c', 'label' => $q->option_c],
                    ['key' => 'd', 'label' => $q->option_d],
                ]
            ];
        });

        return Inertia::render('Quiz/Player', [
            'quiz' => [
                'id' => $quiz->id,
                'title' => $quiz->quiz_title,
                'duration' => $quiz->duration_minutes * 60,
                'questions' => $questions,
                'attempt_id' => $attempt->id,
                'course_id' => $quiz->module->course_id
            ],
            'previousAnswers' => $previousAnswers
        ]);
    }

    /**
     * Proses Submit Jawaban & Penilaian.
     */
    public function submit(Request $request, $id)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $quiz = Quiz::with('questions', 'module')->findOrFail($id);

        $answers = $request->input('answers', []);
        $attemptId = $request->input('attempt_id');

        // 1. Hitung Skor (Bobot)
        $totalWeightEarned = 0;
        $maxPossibleWeight = 0;

        foreach ($quiz->questions as $question) {
            $maxPossibleWeight += $question->score_per_question;

            // Cek jawaban user
            if (isset($answers[$question->id]) && $answers[$question->id] === $question->correct_answer) {
                $totalWeightEarned += $question->score_per_question;
            }
        }

        // 2. Konversi ke Skala 0-100 (Auto-Scale)
        // Rumus: (Bobot Didapat / Total Bobot) * 100
        if ($maxPossibleWeight > 0) {
            $finalScore = round(($totalWeightEarned / $maxPossibleWeight) * 100);
        } else {
            $finalScore = 0;
        }
        $isPassed = $finalScore >= $quiz->passing_score;

        // 3. Update Attempt Record
        $attempt = UserQuizAttempt::findOrFail($attemptId);
        $attempt->update([
            'end_time' => now(),
            'final_score' => $finalScore,
            'is_passed' => $isPassed,
            'answers' => $answers
        ]);

        // 4. Update Module Progress & Gamifikasi (Jika Lulus)
        if ($isPassed) {
            UserModuleProgress::updateOrCreate(
                ['user_id' => $user->id, 'module_id' => $quiz->module_id],
                [
                    'status' => 'completed',
                    'completion_date' => now(),
                ]
            );

            // Cek apakah sudah pernah dapat reward?
            $alreadyRewarded = $user->pointLogs()
                ->where('related_type', 'App\Models\Quiz')
                ->where('related_id', $quiz->id)
                ->exists();

            if (!$alreadyRewarded && $quiz->points_reward > 0) {
                $this->gamificationService->awardPoints(
                    $user,
                    $quiz->points_reward,
                    'QUIZ_PASS',
                    $quiz
                );

                // Cek Badge Baru
                $newBadge = $user->badges()
                    ->wherePivot('achieved_at', '>=', now()->subSeconds(5))
                    ->first();

                if ($newBadge) {
                    return redirect()->route('quiz.result', ['id' => $quiz->id, 'attemptId' => $attempt->id])
                        ->with('new_badge', $newBadge);
                }
            }
        }

        return redirect()->route('quiz.result', ['id' => $quiz->id, 'attemptId' => $attempt->id]);
    }

    /**
     * Halaman Hasil Kuis (Result Page).
     */
    public function result(Request $request, $id)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $quiz = Quiz::with('questions', 'module')->findOrFail($id);
        $attemptId = $request->input('attemptId');

        $attempt = UserQuizAttempt::where('user_id', $user->id)
            ->where('quiz_id', $quiz->id)
            ->when($attemptId, fn($q) => $q->where('id', $attemptId))
            ->latest()
            ->firstOrFail();

        return Inertia::render('Quiz/Result', [
            'quiz' => [
                'id' => $quiz->id,
                'title' => $quiz->quiz_title,
                'course_id' => $quiz->module->course_id,
                'module_id' => $quiz->module_id,
                'passing_score' => $quiz->passing_score,
                'points_reward' => $quiz->points_reward,
            ],
            'attempt' => [
                'score' => $attempt->final_score,
                'is_passed' => $attempt->is_passed,
                'finished_at' => $attempt->end_time,
            ],
            'stats' => [
                'total_questions' => $quiz->questions->count(),
            ]
        ]);
    }
}
