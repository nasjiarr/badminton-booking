<?php

namespace App\Notifications;

use App\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class BookingCancelledNotification extends Notification
{
    use Queueable;

    public Booking $booking;
    public string $reason;

    /**
     * Create a new notification instance.
     */
    public function __construct(Booking $booking, string $reason = 'Pembayaran dibatalkan atau waktu pembayaran telah habis.')
    {
        $this->booking = $booking;
        $this->reason = $reason;
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
        $courtName = $this->booking->court?->name ?? 'Lapangan Badminton';
        $formattedDate = $this->booking->booking_date ? $this->booking->booking_date->format('d M Y') : '-';
        $timeSlot = ($this->booking->start_time ? substr($this->booking->start_time, 0, 5) : '-') . ' - ' . ($this->booking->end_time ? substr($this->booking->end_time, 0, 5) : '-');

        return (new MailMessage)
            ->subject("Pemesanan Lapangan Dibatalkan — #{$this->booking->id}")
            ->greeting("Halo, {$notifiable->name}!")
            ->line("Pemesanan lapangan Anda telah dibatalkan.")
            ->line("**Alasan:** {$this->reason}")
            ->line("**Detail Pemesanan:**")
            ->line("- **Nomor Booking:** #{$this->booking->id}")
            ->line("- **Lapangan:** {$courtName}")
            ->line("- **Tanggal:** {$formattedDate}")
            ->line("- **Jam:** {$timeSlot}")
            ->action('Pesan Jadwal Baru', url(route('bookings.create')))
            ->line('Jika Anda memiliki pertanyaan, silakan hubungi tim dukungan kami di Smash Arena.');
    }
}

