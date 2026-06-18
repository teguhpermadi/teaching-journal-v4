<?php

namespace Database\Factories;

use App\AcademicStatusCalendarEnum;
use App\Models\AcademicCalendar;
use App\Models\AcademicYear;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class AcademicCalendarFactory extends Factory
{
    protected $model = AcademicCalendar::class;

    public function definition(): array
    {
        return [
            'title' => fake()->sentence(3),
            'date' => fake()->date(),
            'description' => fake()->paragraph(),
            'status' => fake()->randomElement(AcademicStatusCalendarEnum::cases()),
            'color' => null,
            'user_id' => User::factory(),
            'academic_year_id' => AcademicYear::factory(),
        ];
    }

    public function effective(): static
    {
        return $this->state(fn () => ['status' => AcademicStatusCalendarEnum::EFFECTIVE]);
    }

    public function notEffective(): static
    {
        return $this->state(fn () => ['status' => AcademicStatusCalendarEnum::NOT_EFFECTIVE]);
    }
}
