<?php

namespace App\Notifications;

use App\Models\Payment;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PaymentSuccessfulNotification extends Notification
{
    use Queueable;

    public Payment $payment;
    public int $pointsEarned;

    /**
     * Create a new notification instance.
     */
    public function __construct(Payment $payment, int $pointsEarned = 0)
    {
        $this->payment = $payment;
        $this->pointsEarned = $pointsEarned;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $booking = $this->payment->booking;
        $courtName = $booking?->court?->name ?? 'Lapangan Badminton';
        $formattedDate = $booking?->booking_date ? $booking->booking_date->format('d M Y') : '-';
        $timeSlot = ($booking?->start_time ? substr($booking->start_time, 0, 5) : '-') . ' - ' . ($booking?->end_time ? substr($booking->end_time, 0, 5) : '-');
        $formattedPrice = 'Rp ' . number_format((float) $this->payment->amount, 0, ',', '.');

        return (new MailMessage)
            ->subject("Pembayaran Berhasil — Invoice {$this->payment->invoice_number}")
            ->greeting("Halo, {$notifiable->name}!")
            ->line("Pembayaran untuk pemesanan lapangan Anda telah berhasil dikonfirmasi.")
            ->line("**Detail Pemesanan:**")
            ->line("- **Nomor Invoice:** {$this->payment->invoice_number}")
            ->line("- **Lapangan:** {$courtName}")
            ->line("- **Tanggal:** {$formattedDate}")
            ->line("- **Jam:** {$timeSlot}")
            ->line("- **Total Bayar:** {$formattedPrice}")
            ->line("- **Poin Loyalty Didapat:** +{$this->pointsEarned} Poin")
            ->action('Lihat Invoice Resmi', url(route('payments.invoice', $this->payment->id)))
            ->line('Terima kasih telah memesan di Smash Arena! Selamat bertanding!');
    }
}

