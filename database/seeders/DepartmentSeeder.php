<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Department;

class DepartmentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Department::create(['department_code' => 'HR', 'department_name' => 'Human Growth']);
        Department::create(['department_code' => 'IT', 'department_name' => 'Information Technology']);
        Department::create(['department_code' => 'COM', 'department_name' => 'Commerce']);
        Department::create(['department_code' => 'FIN', 'department_name' => 'Finance']);
    }
}
