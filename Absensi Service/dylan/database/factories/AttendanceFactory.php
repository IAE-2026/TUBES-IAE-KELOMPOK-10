<?php

namespace Database\Factories;

use App\Models\Attendance;
use Illuminate\Database\Eloquent\Factories\Factory;

class AttendanceFactory extends Factory
{
    protected $model = Attendance::class;

    public function definition(): array
    {
        return [
            'employee_id' => 'EMP-' . str_pad($this->faker->numberBetween(1, 99), 3, '0', STR_PAD_LEFT),
            'date' => $this->faker->unique()->dateTimeBetween('-1 month', 'now')->format('Y-m-d'),
            'status' => $this->faker->randomElement(['hadir', 'izin', 'sakit', 'alpha']),
            'note' => $this->faker->optional()->sentence(),
        ];
    }
}
