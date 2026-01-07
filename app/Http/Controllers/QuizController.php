<?php

namespace App\Http\Controllers;

use App\Models\Quiz;
use App\Models\UserModuleProgress;
use App\Models\UserQuizAttempt;
use App\Models\GamificationLedger;
use App\Services\GamificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Carbon\Carbon;

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
        $user = Auth::user();

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

        // ---------------------------------------------------------------
        // 1. Cek Limit Attempt & Perfect Score
        // ---------------------------------------------------------------
        // Cek apakah ada attempt yang MASIH BERJALAN (belum submit)?
        // Jika ada, kita abaikan limit dulu untuk membiarkan user menyelesaikan attempt tsb.
        $ongoingAttempt = $userAttempts->whereNull('end_time')->first();

        if (!$ongoingAttempt && ($attemptsCount >= $quiz->max_attempts || $hasPerfectScore)) {

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

        // ---------------------------------------------------------------
        // 2. Resume atau Buat Attempt Baru
        // ---------------------------------------------------------------
        if ($ongoingAttempt) {
            // RESUME: Gunakan attempt yang sedang berjalan
            $attempt = $ongoingAttempt;
        } else {
            // START BARU: Buat attempt baru
            // LOGIKA CARRY OVER (Ambil jawaban sebelumnya jika ada)
            $previousAttempt = $userAttempts->first(); // Ambil yang terakhir selesai
            $previousAnswers = $previousAttempt ? $previousAttempt->answers : [];

            $attempt = UserQuizAttempt::create([
                'user_id' => $user->id,
                'quiz_id' => $quiz->id,
                'attempt_number' => $attemptsCount + 1,
                'start_time' => now(),
                'answers' => $previousAnswers, // Pre-fill jawaban
            ]);
        }

        // ---------------------------------------------------------------
        // 3. Hitung Sisa Waktu (Timer Server-Side)
        // ---------------------------------------------------------------
        // Kapan waktu harusnya habis?
        $endTime = Carbon::parse($attempt->start_time)->addMinutes($quiz->duration_minutes);

        // Berapa detik lagi dari sekarang?
        $remainingSeconds = now()->diffInSeconds($endTime, false); // false = return negatif jika lewat

        // Jika waktu sudah habis tapi belum submit, set ke 0 (Frontend akan auto-submit)
        if ($remainingSeconds < 0) {
            $remainingSeconds = 0;
        }

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
                // 'duration' => ... (Tidak dikirim lagi, pakai remaining_seconds)
                'questions' => $questions,
                'attempt_id' => $attempt->id,
                'course_id' => $quiz->module->course_id
            ],
            'previousAnswers' => $attempt->answers ?? [],
            'remaining_seconds' => (int) $remainingSeconds, // [UPDATED] Kirim sisa detik
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

        // 1. Hitung Skor
        $totalWeightEarned = 0;
        $maxPossibleWeight = 0;

        foreach ($quiz->questions as $question) {
            $maxPossibleWeight += $question->score_per_question;
            if (isset($answers[$question->id]) && $answers[$question->id] === $question->correct_answer) {
                $totalWeightEarned += $question->score_per_question;
            }
        }

        // 2. Konversi ke 0-100
        $finalScore = ($maxPossibleWeight > 0)
            ? round(($totalWeightEarned / $maxPossibleWeight) * 100)
            : 0;

        $isPassed = $finalScore >= $quiz->passing_score;

        // 3. Update Attempt
        $attempt = UserQuizAttempt::findOrFail($attemptId);
        $attempt->update([
            'end_time' => now(),
            'final_score' => $finalScore,
            'is_passed' => $isPassed,
            'answers' => $answers
        ]);

        // 4. Logika Kelulusan & Gamifikasi
        if ($isPassed) {

            // A. Update Status Modul
            UserModuleProgress::updateOrCreate(
                ['user_id' => $user->id, 'module_id' => $quiz->module_id],
                ['status' => 'completed', 'completion_date' => now()]
            );

            // B. Cek & Beri Poin (Hanya Sekali)
            $alreadyRewarded = GamificationLedger::where('user_id', $user->id)
                ->where('related_type', 'App\Models\Quiz')
                ->where('related_id', $quiz->id)
                ->where('reason_code', 'QUIZ_PASS')
                ->exists();

            // Variabel untuk menampung badge baru
            $newBadges = [];

            if (!$alreadyRewarded && $quiz->points_reward > 0) {
                // Jika dapat poin, cek badge otomatis dilakukan di dalam awardPoints
                $newBadges = $this->gamificationService->awardPoints(
                    $user,
                    $quiz->points_reward,
                    'QUIZ_PASS',
                    $quiz
                );
            } else {
                // [PERBAIKAN BUG] Jika tidak dapat poin (Retake/Sudah Lulus),
                // Tetap panggil checkBadges secara manual agar Perfect Score terdeteksi
                $newBadges = $this->gamificationService->checkBadges(
                    $user,
                    'QUIZ_PASS',
                    $quiz
                );
            }

            // C. Cek Penyelesaian Kursus (Bonus Poin)
            $courseCompleted = $this->gamificationService->tryFinishCourse($user, $quiz->module->course_id);

            // D. Prioritas Notifikasi Flash Message
            if ($courseCompleted) {
                session()->flash('success', 'Selamat! Anda lulus kuis dan menyelesaikan seluruh materi kursus!');
            } elseif (!empty($newBadges)) {
                // Jika dapat badge (misal: Perfect Score), tampilkan notifikasi badge
                session()->flash('new_badge', $newBadges[0]);
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
