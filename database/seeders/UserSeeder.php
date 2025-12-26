<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Role;
use App\Models\Department;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Ambil ID Department
        $hrDept = Department::where('department_code', 'HR')->first();
        $itDept = Department::where('department_code', 'IT')->first();

        // Ambil ID Role
        $adminRole = Role::where('name', 'admin')->first();
        $mentorRole = Role::where('name', 'mentor')->first();
        $pesertaRole = Role::where('name', 'peserta')->first();

        // 1. Buat Akun Admin
        $admin = User::create([
            'employee_id' => 'ADM001',
            'name' => 'Super Admin',
            'email' => 'admin@reskill.test',
            'password' => Hash::make('password'),
            'department_id' => $itDept->id,
            'total_points' => 0,
        ]);
        $admin->roles()->attach($adminRole);

        // 2. Buat Akun Mentor (Bapak Arif - HR)
        $mentor = User::create([
            'employee_id' => 'MNT001',
            'name' => 'Arif (Mentor HR)',
            'email' => 'mentor@reskill.test',
            'password' => Hash::make('password'),
            'department_id' => $hrDept->id,
            'total_points' => 500,
        ]);
        $mentor->roles()->attach($mentorRole);

        // 3. Buat Akun Peserta (Intern)
        $peserta = User::create([
            'employee_id' => 'INT001',
            'name' => 'Budi Intern',
            'email' => 'peserta@reskill.test',
            'password' => Hash::make('password'),
            'department_id' => $itDept->id,
            'total_points' => 150,
            'seasonal_points' => 50,
            'current_level' => 2,
        ]);
        $peserta->roles()->attach($pesertaRole);
    }
}
