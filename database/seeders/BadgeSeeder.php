<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Badge;

class BadgeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Badge::create([
            'badge_name' => 'Si Paling Awal',
            'description' => 'Menyelesaikan Course Onboarding dalam 1 hari.',
            'icon_url' => 'badges/early_bird.png',
            'trigger_type' => 'COURSE_COMPLETE',
        ]);

        Badge::create([
            'badge_name' => 'Quiz Master',
            'description' => 'Mendapatkan nilai 100 pada kuis apa saja.',
            'icon_url' => 'badges/quiz_master.png',
            'trigger_type' => 'QUIZ_PERFECT',
        ]);
    }
}
