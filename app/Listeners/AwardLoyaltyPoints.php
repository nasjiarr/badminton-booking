<?php

namespace App\Listeners;

use App\Events\PaymentCompleted;
use App\Models\Membership;
use App\Models\PointHistory;
use Illuminate\Support\Facades\DB;

class AwardLoyaltyPoints
{
    /**
     * Handle the event.
     */
    public function handle(PaymentCompleted $event): void
    {
        $payment = $event->payment;
        $booking = $payment->booking;

        if (! $booking || ! $booking->user_id) {
            return;
        }

        // Calculate points: 1 point for every Rp 10.000
        $pointsEarned = (int) floor((float) $payment->amount / 10000);

        if ($pointsEarned <= 0) {
            return;
        }

        // Idempotency: Do not award points more than once for the same booking
        $alreadyAwarded = PointHistory::where('booking_id', $booking->id)
            ->where('points_earned', '>', 0)
            ->exists();

        if ($alreadyAwarded) {
            return;
        }

        DB::transaction(function () use ($booking, $payment, $pointsEarned) {
            $membership = Membership::firstOrCreate(
                ['user_id' => $booking->user_id],
                ['points' => 0, 'tier' => 'bronze']
            );

            $newTotalPoints = $membership->points + $pointsEarned;
            $tier = Membership::calculateTier($newTotalPoints);

            $membership->update([
                'points' => $newTotalPoints,
                'tier' => $tier,
            ]);

            PointHistory::create([
                'user_id' => $booking->user_id,
                'booking_id' => $booking->id,
                'points_earned' => $pointsEarned,
                'points_used' => 0,
                'description' => "Poin reward pembayaran booking #{$booking->id} ({$payment->invoice_number})",
            ]);
        });
    }
}
