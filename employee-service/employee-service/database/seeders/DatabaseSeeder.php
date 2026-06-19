<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        \App\Models\Role::query()->upsert([
            ['name' => 'hr_admin', 'description' => 'Mengelola master data karyawan'],
            ['name' => 'payroll_admin', 'description' => 'Membaca data untuk proses penggajian'],
            ['name' => 'viewer', 'description' => 'Akses baca terbatas'],
        ], ['name'], ['description']);

        \App\Models\Employee::updateOrCreate(
            ['employee_id' => 'EMP-002'],
            [
                'nik' => '100504005509393939',
                'name' => 'jokoanwar',
                'email' => 'burhan@gmail.com',
                'position' => 'Staff',
                'department' => 'IT',
                'base_salary' => 5000000,
                'fixed_allowance' => 1000000,
                'status' => 'active',
            ]
        );

    }
}
