<?php

namespace App\Http\Controllers;

use App\Events\BookingCancelled;
use App\Models\RecurringBooking;
use App\Notifications\BookingCancelledNotification;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class RecurringBookingController extends Controller
{
    /**
     * Display logged-in user's recurring bookings list.
     */
    public function index(Request $request): Response
    {
        $statusFilter = $request->query('status', 'all');

        $query = $request->user()->recurringBookings()
            ->with(['court', 'bookings.payment'])
            ->latest();

        if ($statusFilter === 'active') {
            $query->where('status', 'active');
        } elseif ($statusFilter === 'cancelled') {
            $query->where('status', 'cancelled');
        }

        $recurringBookings = $query->get()->map(function (RecurringBooking $rb) {
            $court = $rb->court;
            $allBookings = $rb->bookings;
            $today = now()->format('Y-m-d');

            $upcomingCount = $allBookings->filter(function ($b) use ($today) {
                return $b->booking_date->format('Y-m-d') >= $today && $b->status !== 'cancelled';
            })->count();

            $completedCount = $allBookings->filter(function ($b) use ($today) {
                return $b->booking_date->format('Y-m-d') < $today || $b->status === 'completed';
            })->count();

            $totalPrice = (float) $allBookings->sum('total_price');

            // Find first pending payment if any
            $pendingPaymentId = null;
            foreach ($allBookings as $b) {
                if ($b->payment && $b->payment->status === 'pending') {
                    $pendingPaymentId = $b->payment->id;
                    break;
                }
            }

            $isAllPaid = $allBookings->isNotEmpty() && $allBookings->every(fn ($b) => $b->payment && $b->payment->status === 'paid');

            return [
                'id' => $rb->id,
                'court' => [
                    'id' => $court->id,
                    'name' => $court->name,
                    'image_url' => $court->image_path ? Storage::url($court->image_path) : null,
                ],
                'day_of_week' => $rb->day_of_week,
                'day_name' => $rb->day_name,
                'start_time' => substr($rb->start_time, 0, 5),
                'end_time' => substr($rb->end_time, 0, 5),
                'start_date' => $rb->start_date->format('Y-m-d'),
                'start_date_formatted' => $rb->start_date->translatedFormat('d M Y'),
                'end_date' => $rb->end_date->format('Y-m-d'),
                'end_date_formatted' => $rb->end_date->translatedFormat('d M Y'),
                'status' => $rb->status,
                'total_price' => $totalPrice,
                'is_all_paid' => $isAllPaid,
                'pending_payment_id' => $pendingPaymentId,
                'total_sessions_count' => $allBookings->count(),
                'upcoming_sessions_count' => $upcomingCount,
                'completed_sessions_count' => $completedCount,
                'sessions' => $allBookings->map(function ($b) {
                    return [
                        'id' => $b->id,
                        'booking_date' => $b->booking_date->format('Y-m-d'),
                        'booking_date_formatted' => $b->booking_date->translatedFormat('l, d F Y'),
                        'start_time' => substr($b->start_time, 0, 5),
                        'end_time' => substr($b->end_time, 0, 5),
                        'status' => $b->status,
                        'total_price' => (float) $b->total_price,
                        'payment_status' => $b->payment?->status ?? 'unpaid',
                        'payment_id' => $b->payment?->id,
                    ];
                }),
            ];
        });

        return Inertia::render('Bookings/MyRecurringBookings', [
            'recurringBookings' => $recurringBookings,
            'filters' => [
                'status' => $statusFilter,
            ],
        ]);
    }

    /**
     * Cancel an entire recurring booking series (all upcoming unpassed sessions).
     */
    public function cancel(Request $request, RecurringBooking $recurringBooking): RedirectResponse
    {
        $user = $request->user();

        if ($recurringBooking->user_id !== $user->id && ! $user->hasRole('admin')) {
            abort(403, 'Anda tidak memiliki hak untuk membatalkan booking rutin ini.');
        }

        if ($recurringBooking->status === 'cancelled') {
            return back()->with('info', 'Rangkaian booking rutin ini sudah dibatalkan sebelumnya.');
        }

        $today = now()->format('Y-m-d');

        $cancelledBookings = DB::transaction(function () use ($recurringBooking, $today) {
            $recurringBooking->update(['status' => 'cancelled']);

            // Find all future / unpassed sessions that are pending or confirmed
            $upcomingBookings = $recurringBooking->bookings()
                ->whereIn('status', ['pending', 'confirmed'])
                ->where('booking_date', '>=', $today)
                ->with('payment')
                ->get();

            foreach ($upcomingBookings as $b) {
                $b->update(['status' => 'cancelled']);

                if ($b->payment && $b->payment->status === 'pending') {
                    $b->payment->update(['status' => 'failed']);
                }
            }

            return $upcomingBookings;
        });

        // Broadcast real-time slot release for each cancelled session
        foreach ($cancelledBookings as $b) {
            event(new BookingCancelled($b));
        }

        // Notify user
        try {
            $user->notify(new BookingCancelledNotification(
                $recurringBooking->bookings()->first() ?? new \App\Models\Booking(['court_id' => $recurringBooking->court_id]),
                "Seluruh rangkaian booking rutin (setiap {$recurringBooking->day_name}) telah dibatalkan."
            ));
        } catch (\Exception $e) {
            Log::error("Gagal mengirim email pembatalan recurring booking #{$recurringBooking->id}: " . $e->getMessage());
        }

        $count = $cancelledBookings->count();

        return back()->with('success', "Rangkaian booking rutin berhasil dibatalkan. {$count} sesi mendatang telah dibatalkan dan slotnya dibebaskan.");
    }
}

