<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Role;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Role::create(['name' => 'admin', 'description' => 'Administrator Sistem']);
        Role::create(['name' => 'mentor', 'description' => 'Mentor / Instruktur']);
        Role::create(['name' => 'peserta', 'description' => 'Intern / Karyawan Baru']);
    }
}
