<?php

namespace Tests\Feature;

use App\Events\BookingCancelled;
use App\Events\BookingCreated;
use App\Models\Booking;
use App\Models\Court;
use App\Models\CourtSchedule;
use App\Models\User;
use Illuminate\Broadcasting\Channel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class BookingRealtimeEventTest extends TestCase
{
    use RefreshDatabase;

    protected Court $court;
    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();

        $this->court = Court::create([
            'name' => 'Lapangan A',
            'description' => 'Lapangan badminton standar dengan fasilitas lengkap.',
            'price_per_hour' => 40000,
            'is_active' => true,
        ]);

        for ($day = 0; $day <= 6; $day++) {
            CourtSchedule::create([
                'court_id' => $this->court->id,
                'day_of_week' => $day,
                'open_time' => '06:00:00',
                'close_time' => '22:00:00',
            ]);
        }
    }

    public function test_booking_created_event_is_dispatched_with_correct_payload(): void
    {
        Event::fake([BookingCreated::class]);

        $targetDate = now()->addDays(2)->format('Y-m-d');

        $response = $this->actingAs($this->user)->post(route('bookings.store'), [
            'court_id' => $this->court->id,
            'booking_date' => $targetDate,
            'start_time' => '09:00',
            'end_time' => '11:00',
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect(route('my-bookings.index'));

        Event::assertDispatched(BookingCreated::class, function (BookingCreated $event) use ($targetDate) {
            $channels = $event->broadcastOn();
            $this->assertCount(1, $channels);
            $this->assertInstanceOf(Channel::class, $channels[0]);
            $this->assertEquals("court.{$this->court->id}", $channels[0]->name);
            $this->assertEquals('BookingCreated', $event->broadcastAs());

            $payload = $event->broadcastWith();
            $this->assertEquals($this->court->id, $payload['court_id']);
            $this->assertEquals($targetDate, $payload['booking_date']);
            $this->assertEquals('09:00', $payload['start_time']);
            $this->assertEquals('11:00', $payload['end_time']);
            $this->assertEquals($this->user->id, $payload['user_id']);
            $this->assertEquals('pending', $payload['status']);

            return true;
        });
    }

    public function test_booking_cancelled_event_is_dispatched_with_correct_payload(): void
    {
        Event::fake([BookingCancelled::class]);

        $targetDate = now()->addDays(2)->format('Y-m-d');

        $booking = Booking::create([
            'user_id' => $this->user->id,
            'court_id' => $this->court->id,
            'booking_date' => $targetDate,
            'start_time' => '14:00:00',
            'end_time' => '16:00:00',
            'status' => 'pending',
            'total_price' => 80000,
            'is_recurring' => false,
        ]);

        $response = $this->actingAs($this->user)->patch(route('bookings.cancel', $booking->id));

        $response->assertSessionHasNoErrors();

        Event::assertDispatched(BookingCancelled::class, function (BookingCancelled $event) use ($targetDate, $booking) {
            $channels = $event->broadcastOn();
            $this->assertCount(1, $channels);
            $this->assertInstanceOf(Channel::class, $channels[0]);
            $this->assertEquals("court.{$this->court->id}", $channels[0]->name);
            $this->assertEquals('BookingCancelled', $event->broadcastAs());

            $payload = $event->broadcastWith();
            $this->assertEquals($booking->id, $payload['booking_id']);
            $this->assertEquals($this->court->id, $payload['court_id']);
            $this->assertEquals($targetDate, $payload['booking_date']);
            $this->assertEquals('14:00', $payload['start_time']);
            $this->assertEquals('16:00', $payload['end_time']);
            $this->assertEquals($this->user->id, $payload['user_id']);
            $this->assertEquals('cancelled', $payload['status']);

            return true;
        });
    }
}

