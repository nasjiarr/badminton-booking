<?php

namespace App\Http\Controllers;

use App\Events\BookingCancelled;
use App\Events\PaymentCompleted;
use App\Models\Payment;
use App\Notifications\BookingCancelledNotification;
use App\Notifications\PaymentSuccessfulNotification;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class PaymentController extends Controller
{
    /**
     * Show payment method selection page.
     */
    public function show(Request $request, Payment $payment): Response|RedirectResponse
    {
        $this->authorizeAccess($request, $payment);

        if ($payment->isPaid()) {
            return redirect()->route('payments.invoice', $payment->id)
                ->with('info', 'Pembayaran untuk booking ini sudah lunas.');
        }

        if ($payment->isExpired() || $payment->isFailed() || $payment->booking->status === 'cancelled') {
            return redirect()->route('my-bookings.index')
                ->with('error', 'Waktu pembayaran telah habis atau booking telah dibatalkan.');
        }

        $paymentData = $this->formatPaymentData($payment);

        return Inertia::render('Payments/Show', [
            'payment' => $paymentData,
        ]);
    }

    /**
     * Process method selection and forward to waiting confirmation.
     */
    /**
     * Process method selection and forward to waiting confirmation.
     */
    public function pay(Request $request, Payment $payment): RedirectResponse
    {
        $this->authorizeAccess($request, $payment);

        if ($payment->isPaid()) {
            return redirect()->route('payments.invoice', $payment->id);
        }

        if ($payment->isExpired() || $payment->isFailed() || $payment->booking->status === 'cancelled') {
            return redirect()->route('my-bookings.index')
                ->with('error', 'Waktu pembayaran telah habis atau booking telah dibatalkan.');
        }

        $validated = $request->validate([
            'method' => 'required|in:simulasi_transfer,simulasi_ewallet',
        ]);

        $payment->update([
            'method' => $validated['method'],
            'status' => 'pending',
        ]);

        // If part of recurring booking, sync method to all sibling payments
        if ($payment->booking->is_recurring && $payment->booking->recurring_booking_id) {
            $recurring = $payment->booking->recurringBooking;
            if ($recurring) {
                foreach ($recurring->bookings as $sibling) {
                    if ($sibling->payment && $sibling->payment->id !== $payment->id) {
                        $sibling->payment->update([
                            'method' => $validated['method'],
                            'status' => 'pending',
                        ]);
                    }
                }
            }
        }

        return redirect()->route('payments.waiting', $payment->id);
    }

    /**
     * Show waiting confirmation page with Dev Simulation Panel.
     */
    public function waiting(Request $request, Payment $payment): Response|RedirectResponse
    {
        $this->authorizeAccess($request, $payment);

        if ($payment->isPaid()) {
            return redirect()->route('payments.invoice', $payment->id)
                ->with('success', 'Pembayaran telah berhasil diverifikasi.');
        }

        if ($payment->isExpired() || $payment->isFailed() || $payment->booking->status === 'cancelled') {
            return redirect()->route('my-bookings.index')
                ->with('error', 'Waktu pembayaran telah habis atau booking telah dibatalkan.');
        }

        $paymentData = $this->formatPaymentData($payment);

        return Inertia::render('Payments/Waiting', [
            'payment' => $paymentData,
        ]);
    }

    /**
     * Dev action: simulate successful payment.
     */
    public function simulateSuccess(Request $request, Payment $payment): RedirectResponse
    {
        $this->authorizeAccess($request, $payment);

        if ($payment->isPaid()) {
            return redirect()->route('payments.invoice', $payment->id);
        }

        if ($payment->isExpired() || $payment->isFailed() || $payment->booking->status === 'cancelled') {
            return redirect()->route('my-bookings.index')
                ->with('error', 'Booking ini sudah tidak dapat dibayar.');
        }

        $booking = $payment->booking;

        if ($booking->is_recurring && $booking->recurring_booking_id) {
            // Confirm all pending sessions in recurring booking bundle
            $recurring = $booking->recurringBooking;
            $allBookings = $recurring ? $recurring->bookings()->with('payment')->get() : collect([$booking]);

            DB::transaction(function () use ($allBookings) {
                foreach ($allBookings as $b) {
                    $b->update(['status' => 'confirmed']);
                    if ($b->payment) {
                        $b->payment->update([
                            'status' => 'paid',
                            'paid_at' => now(),
                        ]);
                    }
                }
            });

            // Trigger loyalty points for each session in the bundle
            foreach ($allBookings as $b) {
                if ($b->payment) {
                    event(new PaymentCompleted($b->payment));
                }
            }

            $totalAmount = $allBookings->sum('total_price');
            $pointsEarned = (int) floor((float) $totalAmount / 10000);

            if ($booking->user) {
                try {
                    $booking->user->notify(new PaymentSuccessfulNotification($payment, $pointsEarned));
                } catch (\Exception $e) {
                    Log::error("Gagal mengirim notifikasi pembayaran invoice {$payment->invoice_number}: " . $e->getMessage());
                }
            }

            return redirect()->route('payments.invoice', $payment->id)
                ->with('success', "Simulasi Pembayaran Berhasil! Seluruh {$allBookings->count()} sesi booking rutin Anda telah terkonfirmasi.");
        }

        // Single Booking flow
        DB::transaction(function () use ($payment) {
            $payment->update([
                'status' => 'paid',
                'paid_at' => now(),
            ]);

            $payment->booking->update([
                'status' => 'confirmed',
            ]);
        });

        // Trigger loyalty points awarding via event
        event(new PaymentCompleted($payment));

        // Calculate points earned for notification
        $pointsEarned = (int) floor((float) $payment->amount / 10000);

        // Send payment successful notification
        if ($payment->booking->user) {
            try {
                $payment->booking->user->notify(new PaymentSuccessfulNotification($payment, $pointsEarned));
            } catch (\Exception $e) {
                Log::error("Gagal mengirim notifikasi pembayaran invoice {$payment->invoice_number}: " . $e->getMessage());
            }
        }

        return redirect()->route('payments.invoice', $payment->id)
            ->with('success', 'Simulasi Pembayaran Berhasil! Booking Anda telah terkonfirmasi.');
    }

    /**
     * Dev action: simulate failed payment.
     */
    public function simulateFailed(Request $request, Payment $payment): RedirectResponse
    {
        $this->authorizeAccess($request, $payment);

        if ($payment->isPaid()) {
            return redirect()->route('payments.invoice', $payment->id);
        }

        $booking = $payment->booking;

        if ($booking->is_recurring && $booking->recurring_booking_id) {
            $recurring = $booking->recurringBooking;
            $allBookings = $recurring ? $recurring->bookings()->with('payment')->get() : collect([$booking]);

            DB::transaction(function () use ($recurring, $allBookings) {
                if ($recurring) {
                    $recurring->update(['status' => 'cancelled']);
                }

                foreach ($allBookings as $b) {
                    $b->update(['status' => 'cancelled']);
                    if ($b->payment) {
                        $b->payment->update(['status' => 'failed']);
                    }
                }
            });

            // Broadcast real-time slot release for each session
            foreach ($allBookings as $b) {
                event(new BookingCancelled($b));
            }

            if ($booking->user) {
                try {
                    $booking->user->notify(new BookingCancelledNotification(
                        $booking,
                        'Simulasi pembayaran paket booking rutin gagal atau ditolak.'
                    ));
                } catch (\Exception $e) {
                    Log::error("Gagal mengirim notifikasi kegagalan bayar recurring #{$booking->recurring_booking_id}: " . $e->getMessage());
                }
            }

            return redirect()->route('recurring-bookings.index')
                ->with('error', 'Simulasi Pembayaran Gagal. Seluruh sesi booking rutin dibatalkan.');
        }

        DB::transaction(function () use ($payment) {
            $payment->update([
                'status' => 'failed',
            ]);

            $payment->booking->update([
                'status' => 'cancelled',
            ]);
        });

        // Broadcast real-time slot release
        event(new BookingCancelled($payment->booking));

        // Send cancellation notification
        if ($payment->booking->user) {
            try {
                $payment->booking->user->notify(new BookingCancelledNotification(
                    $payment->booking,
                    'Simulasi pembayaran gagal atau transaksi ditolak.'
                ));
            } catch (\Exception $e) {
                Log::error("Gagal mengirim notifikasi kegagalan bayar booking #{$payment->booking_id}: " . $e->getMessage());
            }
        }

        return redirect()->route('my-bookings.index')
            ->with('error', 'Simulasi Pembayaran Gagal. Booking otomatis dibatalkan.');
    }

    /**
     * View official athletic invoice.
     */
    public function invoice(Request $request, Payment $payment): Response
    {
        $this->authorizeAccess($request, $payment);

        $paymentData = $this->formatPaymentData($payment);

        // Points earned from this specific booking or recurring bundle
        $booking = $payment->booking;
        if ($booking->is_recurring && $booking->recurring_booking_id && $booking->recurringBooking) {
            $bookingIds = $booking->recurringBooking->bookings()->pluck('id');
            $pointsEarned = (int) \App\Models\PointHistory::whereIn('booking_id', $bookingIds)->sum('points_earned');
            if ($pointsEarned <= 0) {
                $pointsEarned = (int) floor((float) $paymentData['amount'] / 10000);
            }
        } else {
            $pointHistory = $booking->pointHistories()->first();
            $pointsEarned = $pointHistory ? $pointHistory->points_earned : (int) floor((float) $payment->amount / 10000);
        }

        return Inertia::render('Payments/Invoice', [
            'payment' => $paymentData,
            'pointsEarned' => $pointsEarned,
        ]);
    }

    /**
     * Authorize that the current logged in user owns the booking or is an admin.
     */
    protected function authorizeAccess(Request $request, Payment $payment): void
    {
        $user = $request->user();
        if (! $user) {
            abort(401);
        }

        if ($user->hasRole('admin')) {
            return;
        }

        if ($payment->booking->user_id !== $user->id) {
            abort(403, 'Anda tidak memiliki hak akses ke transaksi ini.');
        }
    }

    /**
     * Format payment and booking data for frontend views.
     */
    protected function formatPaymentData(Payment $payment): array
    {
        $payment->loadMissing(['booking.court', 'booking.user']);
        $booking = $payment->booking;
        $court = $booking->court;

        $expiresAt = $booking->created_at->copy()->addMinutes(15);
        $remainingSeconds = max(0, (int) now()->diffInSeconds($expiresAt, false));

        // Simulated Virtual Account and QR Code info
        $simulatedVa = '8808' . str_pad((string) $booking->user_id, 4, '0', STR_PAD_LEFT) . str_pad((string) $booking->id, 4, '0', STR_PAD_LEFT);
        $simulatedEwalletPhone = '0812-3456-' . str_pad((string) $booking->id, 4, '0', STR_PAD_LEFT);

        $startCarbon = Carbon::createFromFormat('H:i:s', $booking->start_time);
        $endCarbon = Carbon::createFromFormat('H:i:s', $booking->end_time);
        $durationHours = max(1, $startCarbon->diffInHours($endCarbon));

        $isRecurring = (bool) ($booking->is_recurring && $booking->recurring_booking_id);
        $recurringData = null;
        $totalAmount = (float) $payment->amount;

        if ($isRecurring && $booking->recurringBooking) {
            $recurring = $booking->recurringBooking()->with('bookings')->first();
            if ($recurring) {
                $totalAmount = (float) $recurring->bookings->sum('total_price');
                $recurringData = [
                    'id' => $recurring->id,
                    'day_name' => $recurring->day_name,
                    'total_sessions' => $recurring->bookings->count(),
                    'total_bundle_price' => $totalAmount,
                    'start_date' => $recurring->start_date->format('Y-m-d'),
                    'end_date' => $recurring->end_date->format('Y-m-d'),
                    'sessions' => $recurring->bookings->map(fn ($b) => [
                        'id' => $b->id,
                        'booking_date' => $b->booking_date->format('Y-m-d'),
                        'booking_date_formatted' => $b->booking_date->translatedFormat('l, d F Y'),
                        'start_time' => substr($b->start_time, 0, 5),
                        'end_time' => substr($b->end_time, 0, 5),
                        'status' => $b->status,
                        'total_price' => (float) $b->total_price,
                    ]),
                ];
            }
        }

        return [
            'id' => $payment->id,
            'invoice_number' => $payment->invoice_number,
            'amount' => $totalAmount,
            'is_recurring' => $isRecurring,
            'recurring' => $recurringData,
            'method' => $payment->method,
            'status' => $payment->status,
            'paid_at' => $payment->paid_at ? $payment->paid_at->format('d M Y H:i:s') : null,
            'expires_at' => $expiresAt->toIso8601String(),
            'remaining_seconds' => $remainingSeconds,
            'simulated_va' => $simulatedVa,
            'simulated_ewallet_phone' => $simulatedEwalletPhone,
            'customer' => [
                'name' => $booking->user?->name ?? 'Pelanggan',
                'email' => $booking->user?->email ?? '-',
            ],
            'booking' => [
                'id' => $booking->id,
                'booking_date' => $booking->booking_date->format('Y-m-d'),
                'booking_date_formatted' => $booking->booking_date->translatedFormat('d F Y'),
                'start_time' => substr($booking->start_time, 0, 5),
                'end_time' => substr($booking->end_time, 0, 5),
                'duration_hours' => $durationHours,
                'status' => $booking->status,
                'notes' => $booking->notes,
                'created_at' => $booking->created_at->format('d M Y H:i'),
            ],
            'court' => [
                'id' => $court->id,
                'name' => $court->name,
                'price_per_hour' => (float) $court->price_per_hour,
                'image_url' => $court->image_path ? Storage::url($court->image_path) : null,
            ],
        ];
    }
}

