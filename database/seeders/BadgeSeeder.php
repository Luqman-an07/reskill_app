<?php

namespace Database\Seeders;

use App\Models\Badge;
use Illuminate\Database\Seeder;

class BadgeSeeder extends Seeder
{
    public function run()
    {
        Badge::create([
            'name' => 'Si Paling Awal',
            'description' => 'Menyelesaikan modul pertama kali.',
            'icon_path' => 'badges/first_step.png', // Pastikan ada file dummy atau biarkan null
            'trigger_type' => 'FIRST_MODULE',
            'trigger_value' => 1,
            'xp_bonus' => 50
        ]);

        Badge::create([
            'name' => 'Quiz Master',
            'description' => 'Mendapatkan nilai 100 pada kuis apa saja.',
            'icon_path' => 'badges/quiz_master.png',
            'trigger_type' => 'QUIZ_PERFECT', // Sesuai diskusi GamificationService
            'trigger_value' => 100,
            'xp_bonus' => 100
        ]);

        Badge::create([
            'name' => 'Course Finisher',
            'description' => 'Menyelesaikan 1 Kursus Penuh.',
            'icon_path' => 'badges/finisher.png',
            'trigger_type' => 'COURSE_COMPLETE',
            'trigger_value' => 1,
            'xp_bonus' => 200
        ]);
    }
}
