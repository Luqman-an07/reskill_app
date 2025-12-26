<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Course;
use App\Models\Module;
use App\Models\User;
use App\Models\Quiz;
use App\Models\QuizQuestion;
use App\Models\Task;

class CourseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Ambil Mentor
        $mentor = User::where('email', 'mentor@reskill.test')->first();

        // 1. Buat Course
        $course = Course::create([
            'mentor_id' => $mentor->id,
            'title' => 'Onboarding: Budaya Perusahaan & SOP',
            'description' => 'Pelajari nilai-nilai inti PT. Ayo Menebar Kebaikan dan SOP dasar.',
            'target_role' => 'All Interns',
            'completion_points' => 500,
            'is_published' => true,
        ]);

        // 2. Buat Modul 1 (Materi Teks)
        $module1 = Module::create([
            'course_id' => $course->id,
            'module_title' => 'Bab 1: Visi & Misi (Rongga Tiga)',
            'content_type' => 'TEXT',
            'content_url' => 'Materi teks tentang Rongga Kepala, Dada, dan Perut...',
            'module_order' => 1,
            'completion_points' => 50,
            'required_time' => 300, // 5 menit
        ]);

        // 3. Buat Modul 2 (Kuis)
        $module2 = Module::create([
            'course_id' => $course->id,
            'module_title' => 'Bab 2: Kuis Pemahaman Visi',
            'content_type' => 'QUIZ',
            'module_order' => 2,
            'prerequisite_module_id' => $module1->id, // Harus selesai Bab 1 dulu
            'completion_points' => 100,
            'required_time' => 600,
        ]);

        // Buat Kuis untuk Modul 2
        $quiz = Quiz::create([
            'module_id' => $module2->id,
            'quiz_title' => 'Kuis Evaluasi Visi Misi',
            'duration_minutes' => 10,
            'passing_score' => 70,
            'points_reward' => 100,
        ]);

        // Buat Soal Kuis
        QuizQuestion::create([
            'quiz_id' => $quiz->id,
            'question_text' => 'Apa fokus utama dari "Rongga Kepala" dalam visi perusahaan?',
            'option_a' => 'Kesejahteraan Finansial',
            'option_b' => 'Peningkatan Kapasitas & Inovasi',
            'option_c' => 'Penghargaan Tim',
            'option_d' => 'Liburan',
            'correct_answer' => 'b',
            'score_per_question' => 50,
        ]);

        QuizQuestion::create([
            'quiz_id' => $quiz->id,
            'question_text' => 'Apa misi utama PT. Ayo Menebar Kebaikan?',
            'option_a' => 'Mencari keuntungan sebesar-besarnya',
            'option_b' => 'Menjadi perusahaan global',
            'option_c' => 'Wasilah Kebermanfaatan dan Penerapan Syariat Islam',
            'option_d' => 'Menjual parfum termurah',
            'correct_answer' => 'c',
            'score_per_question' => 50,
        ]);

        // 4. Buat Modul 3 (Task Praktik)
        $module3 = Module::create([
            'course_id' => $course->id,
            'module_title' => 'Bab 3: Tugas Refleksi Diri',
            'content_type' => 'TASK',
            'module_order' => 3,
            'prerequisite_module_id' => $module2->id,
            'completion_points' => 200,
            'required_time' => 0,
        ]);

        // Buat Task untuk Modul 3
        Task::create([
            'module_id' => $module3->id,
            'task_title' => 'Esai Implementasi Values',
            'description' => 'Buatlah esai singkat (PDF) tentang bagaimana kamu akan menerapkan nilai "Jujur & Amanah" saat magang.',
            'max_score' => 100,
            'due_date' => now()->addDays(7),
            'points_reward' => 200,
        ]);
    }
}
