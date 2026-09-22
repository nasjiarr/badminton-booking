<?php

namespace Database\Factories;

use App\Models\Booking;
use App\Models\PointHistory;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\PointHistory>
 */
class PointHistoryFactory extends Factory
{
    protected $model = PointHistory::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $isEarning = fake()->boolean(70);

        return [
            'user_id' => User::factory(),
            'booking_id' => Booking::factory(),
            'points_earned' => $isEarning ? fake()->numberBetween(5, 50) : 0,
            'points_used' => $isEarning ? 0 : fake()->numberBetween(10, 100),
            'description' => $isEarning
                ? 'Poin dari booking lapangan'
                : 'Penukaran poin untuk diskon',
        ];
    }
}

