<?php

namespace Database\Seeders;

use App\Models\Course;
use App\Models\Module;
use App\Models\Quiz;
use App\Models\QuizQuestion;
use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Seeder;

class CourseSeeder extends Seeder
{
    public function run()
    {
        $mentor = User::where('email', 'mentor.hr@reskill.test')->first();

        // --- KURSUS 1: ONBOARDING (WAJIB) ---
        $course = Course::create([
            'title' => 'Onboarding: Budaya Perusahaan & SOP',
            'description' => 'Materi wajib untuk seluruh karyawan baru.',
            'thumbnail' => 'thumbnails/onboarding.jpg',
            'mentor_id' => $mentor->id,
            'target_role' => 'All', // Gatekeeper Logic
            'status' => 'published',
            'completion_points' => 500, // Bonus akhir
        ]);

        // MODUL 1: TEKS (Visi Misi)
        $mod1 = Module::create([
            'course_id' => $course->id,
            'module_title' => 'Bab 1: Visi & Misi (Rongga Tiga)',
            'content_type' => 'TEXT',
            'content_url' => 'Selamat datang di PT AMK. Visi kami adalah...',
            'module_order' => 1,
            'completion_points' => 50,
            'required_time' => 60, // 1 menit estimasi
        ]);

        // MODUL 2: KUIS (Tes Timer & Logic Score)
        $mod2 = Module::create([
            'course_id' => $course->id,
            'module_title' => 'Bab 2: Kuis Pemahaman Visi',
            'content_type' => 'QUIZ',
            'module_order' => 2,
            'prerequisite_module_id' => $mod1->id, // Harus baca Bab 1 dulu
            'completion_points' => 100,
            'required_time' => 300, // 5 menit (display only)
        ]);

        // Create Quiz Data
        $quiz = Quiz::create([
            'module_id' => $mod2->id,
            'quiz_title' => 'Kuis Pemahaman Visi',
            'description' => 'Jawab dengan teliti.',
            'duration_minutes' => 5, // [PENTING] Ini untuk Timer Player
            'passing_score' => 70,
            'max_attempts' => 3,
            'points_reward' => 100
        ]);

        // Create Questions (3 Soal)
        QuizQuestion::insert([
            [
                'quiz_id' => $quiz->id,
                'question_text' => 'Apa fokus utama dari "Rongga Kepala" dalam visi perusahaan?',
                'option_a' => 'Kesejahteraan Finansial',
                'option_b' => 'Peningkatan Kapasitas & Inovasi', // Benar
                'option_c' => 'Penghargaan Tim',
                'option_d' => 'Liburan',
                'correct_answer' => 'b',
                'score_per_question' => 34
            ],
            [
                'quiz_id' => $quiz->id,
                'question_text' => 'Divisi apa yang bertanggung jawab atas pengembangan talenta?',
                'option_a' => 'Human Growth', // Benar
                'option_b' => 'Commerce',
                'option_c' => 'Finance',
                'option_d' => 'IT Support',
                'correct_answer' => 'a',
                'score_per_question' => 33
            ],
            [
                'quiz_id' => $quiz->id,
                'question_text' => 'Berapa jumlah pilar utama dalam filosofi perusahaan?',
                'option_a' => 'Dua',
                'option_b' => 'Tiga', // Benar
                'option_c' => 'Empat',
                'option_d' => 'Lima',
                'correct_answer' => 'b',
                'score_per_question' => 33
            ]
        ]);

        // MODUL 3: TUGAS (Tes Upload)
        $mod3 = Module::create([
            'course_id' => $course->id,
            'module_title' => 'Bab 3: Tugas Refleksi Diri',
            'content_type' => 'TASK',
            'module_order' => 3,
            'prerequisite_module_id' => $mod2->id,
            'completion_points' => 150,
            'required_time' => 0,
        ]);

        Task::create([
            'module_id' => $mod3->id,
            'task_title' => 'Refleksi Nilai Perusahaan',
            'description' => 'Buatlah esai singkat (PDF) tentang bagaimana kamu akan menerapkan nilai Jujur & Amanah.',
            'max_score' => 100,
            'points_reward' => 150,
            'due_date' => now()->addDays(7), // Deadline seminggu lagi
        ]);
    }
}
