<?php

namespace Database\Factories;

use App\Models\Membership;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Membership>
 */
class MembershipFactory extends Factory
{
    protected $model = Membership::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $points = fake()->numberBetween(0, 500);

        return [
            'user_id' => User::factory(),
            'points' => $points,
            'tier' => match (true) {
                $points >= 300 => 'gold',
                $points >= 100 => 'silver',
                default => 'bronze',
            },
        ];
    }
}

