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

        // Points earned from this specific booking
        $pointHistory = $payment->booking->pointHistories()->first();
        $pointsEarned = $pointHistory ? $pointHistory->points_earned : (int) floor((float) $payment->amount / 10000);

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

        return [
            'id' => $payment->id,
            'invoice_number' => $payment->invoice_number,
            'amount' => (float) $payment->amount,
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

