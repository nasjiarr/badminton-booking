<?php

namespace Database\Factories;

use App\Models\Booking;
use App\Models\Payment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Payment>
 */
class PaymentFactory extends Factory
{
    protected $model = Payment::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $status = fake()->randomElement(['pending', 'paid', 'failed', 'expired']);

        return [
            'booking_id' => Booking::factory(),
            'amount' => fake()->randomElement([65000, 75000, 85000, 100000, 150000, 200000]),
            'method' => fake()->randomElement(['simulasi_transfer', 'simulasi_ewallet']),
            'status' => $status,
            'paid_at' => $status === 'paid' ? now() : null,
            'invoice_number' => 'INV-' . now()->format('Ymd') . '-' . strtoupper(fake()->unique()->bothify('?????')),
        ];
    }

    /**
     * Indicate that the payment is paid.
     */
    public function paid(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'paid',
            'paid_at' => now(),
        ]);
    }
}

