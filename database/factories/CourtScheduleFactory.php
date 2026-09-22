<?php

namespace Database\Factories;

use App\Models\Court;
use App\Models\CourtSchedule;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\CourtSchedule>
 */
class CourtScheduleFactory extends Factory
{
    protected $model = CourtSchedule::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'court_id' => Court::factory(),
            'day_of_week' => fake()->numberBetween(0, 6),
            'open_time' => '06:00',
            'close_time' => '22:00',
        ];
    }
}

