<?php

namespace App\Policies;

use App\Models\Booking;
use App\Models\User;
use Carbon\Carbon;

class BookingPolicy
{
    /**
     * Determine whether the user can view the booking.
     */
    public function view(User $user, Booking $booking): bool
    {
        return $user->id === $booking->user_id;
    }

    /**
     * Determine whether the user can cancel the booking.
     *
     * Rules:
     * - Booking must belong to the user.
     * - Status cannot already be 'cancelled' or 'completed'.
     * - Permitted if status is still 'pending', OR if at least 2 hours before start time.
     */
    public function cancel(User $user, Booking $booking): bool
    {
        if ($user->id !== $booking->user_id) {
            return false;
        }

        if (in_array($booking->status, ['cancelled', 'completed'])) {
            return false;
        }

        // Pending bookings can always be cancelled
        if ($booking->status === 'pending') {
            return true;
        }

        // For other active statuses (e.g. confirmed), allow cancellation only if at least 2 hours before start
        $bookingStartString = $booking->booking_date instanceof Carbon 
            ? $booking->booking_date->format('Y-m-d') . ' ' . $booking->start_time
            : Carbon::parse($booking->booking_date)->format('Y-m-d') . ' ' . $booking->start_time;

        $bookingStart = Carbon::parse($bookingStartString);

        return now()->diffInMinutes($bookingStart, false) >= 120;
    }
}

