<?php

namespace App\Events;

use App\Models\Booking;
use Carbon\Carbon;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class BookingCreated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public Booking $booking;

    /**
     * Create a new event instance.
     */
    public function __construct(Booking $booking)
    {
        $this->booking = $booking;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new Channel('court.' . $this->booking->court_id),
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'BookingCreated';
    }

    /**
     * Get the data to broadcast.
     *
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        $date = $this->booking->booking_date instanceof Carbon
            ? $this->booking->booking_date->format('Y-m-d')
            : Carbon::parse($this->booking->booking_date)->format('Y-m-d');

        return [
            'booking_id' => (int) $this->booking->id,
            'court_id' => (int) $this->booking->court_id,
            'booking_date' => $date,
            'start_time' => substr($this->booking->start_time, 0, 5),
            'end_time' => substr($this->booking->end_time, 0, 5),
            'user_id' => (int) $this->booking->user_id,
            'status' => $this->booking->status,
        ];
    }
}

