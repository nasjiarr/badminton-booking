<?php

namespace App\Console\Commands;

use App\Events\BookingCancelled;
use App\Models\Booking;
use App\Notifications\BookingCancelledNotification;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ExpirePendingBookings extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'bookings:expire-pending';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Auto-cancel pending bookings that exceeded the 15-minute payment window';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $cutoffTime = now()->subMinutes(15);

        $expiredBookings = Booking::where('status', 'pending')
            ->where('created_at', '<', $cutoffTime)
            ->with(['payment', 'user', 'court'])
            ->get();

        $count = $expiredBookings->count();

        if ($count === 0) {
            $this->info('Tidak ada booking pending yang kedaluwarsa.');
            return Command::SUCCESS;
        }

        $this->info("Ditemukan {$count} booking pending yang kedaluwarsa. Memproses pembatalan otomatis...");

        foreach ($expiredBookings as $booking) {
            DB::transaction(function () use ($booking) {
                $booking->update(['status' => 'cancelled']);

                if ($booking->payment) {
                    $booking->payment->update(['status' => 'expired']);
                }
            });

            // Broadcast real-time cancellation event to free court slots
            event(new BookingCancelled($booking));

            // Notify customer
            if ($booking->user) {
                try {
                    $booking->user->notify(new BookingCancelledNotification(
                        $booking,
                        'Batas waktu pembayaran (15 menit) telah habis. Booking dibatalkan otomatis oleh sistem.'
                    ));
                } catch (\Exception $e) {
                    Log::error("Gagal mengirim notifikasi pembatalan booking #{$booking->id}: " . $e->getMessage());
                }
            }

            $this->line("- Booking #{$booking->id} dibatalkan (Invoice: {$booking->payment?->invoice_number}).");
        }

        $this->info("Berhasil membatalkan {$count} booking.");

        return Command::SUCCESS;
    }
}

