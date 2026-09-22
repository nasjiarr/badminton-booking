<?php

namespace Database\Factories;

use App\Models\Court;
use App\Models\RecurringBooking;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\RecurringBooking>
 */
class RecurringBookingFactory extends Factory
{
    protected $model = RecurringBooking::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $startHour = fake()->numberBetween(6, 20);
        $duration = fake()->numberBetween(1, 2);

        return [
            'user_id' => User::factory(),
            'court_id' => Court::factory(),
            'day_of_week' => fake()->numberBetween(0, 6),
            'start_time' => sprintf('%02d:00', $startHour),
            'end_time' => sprintf('%02d:00', $startHour + $duration),
            'start_date' => now()->toDateString(),
            'end_date' => now()->addMonths(fake()->numberBetween(1, 3))->toDateString(),
            'status' => 'active',
        ];
    }

    /**
     * Indicate that the recurring booking is cancelled.
     */
    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'cancelled',
        ]);
    }
}

