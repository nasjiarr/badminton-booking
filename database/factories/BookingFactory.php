<?php

namespace Database\Factories;

use App\Models\Booking;
use App\Models\Court;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Booking>
 */
class BookingFactory extends Factory
{
    protected $model = Booking::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $startHour = fake()->numberBetween(6, 20);
        $duration = fake()->numberBetween(1, 2);
        $pricePerHour = fake()->randomElement([65000, 75000, 85000, 100000]);

        return [
            'user_id' => User::factory(),
            'court_id' => Court::factory(),
            'booking_date' => fake()->dateTimeBetween('now', '+30 days')->format('Y-m-d'),
            'start_time' => sprintf('%02d:00', $startHour),
            'end_time' => sprintf('%02d:00', $startHour + $duration),
            'status' => fake()->randomElement(['pending', 'confirmed', 'cancelled', 'completed']),
            'total_price' => $pricePerHour * $duration,
            'notes' => fake()->optional(0.3)->sentence(),
            'is_recurring' => false,
            'recurring_booking_id' => null,
        ];
    }

    /**
     * Indicate that the booking is confirmed.
     */
    public function confirmed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'confirmed',
        ]);
    }

    /**
     * Indicate that the booking is pending.
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'pending',
        ]);
    }
}

