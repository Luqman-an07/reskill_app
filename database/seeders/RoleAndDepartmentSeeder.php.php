<?php

namespace Database\Seeders;

use App\Models\Department;
use App\Models\Role; // Asumsi pakai Spatie/Role model sendiri
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class RoleAndDepartmentSeeder extends Seeder
{
    public function run()
    {
        // 1. Buat Role
        $roleAdmin = Role::firstOrCreate(['name' => 'admin']);
        $roleMentor = Role::firstOrCreate(['name' => 'mentor']);
        $rolePeserta = Role::firstOrCreate(['name' => 'peserta']);

        // 2. Buat Departemen (Sesuai Laporan Magang)
        $deptHR = Department::create(['code' => 'HR', 'name' => 'Human Growth']);
        $deptIT = Department::create(['code' => 'IT', 'name' => 'Information Technology']);
        $deptCom = Department::create(['code' => 'COM', 'name' => 'Commerce']);

        // 3. Buat User Admin
        $admin = User::create([
            'name' => 'Super Admin',
            'email' => 'admin@reskill.test',
            'password' => Hash::make('password'),
            'department_id' => $deptIT->id,
        ]);
        $admin->assignRole($roleAdmin);

        // 4. Buat User Mentor (HR)
        $mentorHR = User::create([
            'name' => 'Arif (Mentor HR)',
            'email' => 'mentor.hr@reskill.test',
            'password' => Hash::make('password'),
            'department_id' => $deptHR->id,
        ]);
        $mentorHR->assignRole($roleMentor);

        // 5. Buat User Peserta (Budi - IT)
        $pesertaIT = User::create([
            'name' => 'Budi Intern',
            'email' => 'peserta@reskill.test',
            'password' => Hash::make('password'),
            'department_id' => $deptIT->id, // Penting untuk Gatekeeper
            'total_points' => 0,
        ]);
        $pesertaIT->assignRole($rolePeserta);
    }
}
