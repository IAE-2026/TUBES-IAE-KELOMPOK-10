<?php

namespace Database\Seeders;

use App\Models\Attendance;
use Illuminate\Database\Seeder;

class AttendanceSeeder extends Seeder
{
    public function run(): void
    {
        $employees = ['EMP-001', 'EMP-002', 'EMP-003'];
        $statuses = ['hadir', 'hadir', 'hadir', 'hadir', 'izin', 'sakit', 'alpha'];

        foreach ($employees as $empId) {
            for ($day = 1; $day <= 20; $day++) {
                $date = sprintf('2025-05-%02d', $day);
                // skip weekends (Saturday=6, Sunday=0)
                $dayOfWeek = date('N', strtotime($date));
                if ($dayOfWeek >= 6) continue;

                Attendance::firstOrCreate(
                    ['employee_id' => $empId, 'date' => $date],
                    [
                        'status' => $statuses[array_rand($statuses)],
                        'note' => null,
                    ]
                );
            }
        }
    }
}

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(AttendanceSeeder::class);
    }
}
