<?php

namespace Database\Factories;

use App\Models\Court;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Court>
 */
class CourtFactory extends Factory
{
    protected $model = Court::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => 'Lapangan ' . fake()->unique()->randomLetter(),
            'description' => fake()->sentence(),
            'price_per_hour' => fake()->randomElement([65000, 75000, 85000, 100000, 120000]),
            'image_path' => null,
            'is_active' => true,
        ];
    }

    /**
     * Indicate that the court is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}

