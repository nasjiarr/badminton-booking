<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Court;
use App\Models\CourtSchedule;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookingTest extends TestCase
{
    use RefreshDatabase;

    protected Court $court;
    protected User $user;
    protected User $otherUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->otherUser = User::factory()->create();

        $this->court = Court::create([
            'name' => 'Lapangan A',
            'description' => 'Lapangan badminton standar dengan fasilitas lengkap.',
            'price_per_hour' => 40000,
            'is_active' => true,
        ]);

        // Create schedules for all days
        for ($day = 0; $day <= 6; $day++) {
            CourtSchedule::create([
                'court_id' => $this->court->id,
                'day_of_week' => $day,
                'open_time' => '06:00:00',
                'close_time' => '22:00:00',
            ]);
        }
    }

    public function test_user_can_view_booking_page_with_active_courts(): void
    {
        $response = $this->actingAs($this->user)->get(route('bookings.create'));

        $response->assertOk();
    }

    public function test_user_can_get_court_hourly_slot_availability(): void
    {
        $targetDate = now()->addDays(2)->format('Y-m-d');

        // Create an existing booking for 08:00 - 10:00 by otherUser
        Booking::create([
            'user_id' => $this->otherUser->id,
            'court_id' => $this->court->id,
            'booking_date' => $targetDate,
            'start_time' => '08:00:00',
            'end_time' => '10:00:00',
            'status' => 'confirmed',
            'total_price' => 80000,
            'is_recurring' => false,
        ]);

        // Create a booking for 14:00 - 15:00 by this->user
        Booking::create([
            'user_id' => $this->user->id,
            'court_id' => $this->court->id,
            'booking_date' => $targetDate,
            'start_time' => '14:00:00',
            'end_time' => '15:00:00',
            'status' => 'pending',
            'total_price' => 40000,
            'is_recurring' => false,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson(route('courts.availability', $this->court->id) . "?date={$targetDate}");

        $response->assertOk()
            ->assertJsonStructure([
                'court_id',
                'court_name',
                'price_per_hour',
                'date',
                'slots' => [
                    '*' => ['start_time', 'end_time', 'status', 'booking_id'],
                ],
            ]);

        $slots = collect($response->json('slots'));

        // 06:00 - 07:00 should be available
        $slot06 = $slots->firstWhere('start_time', '06:00');
        $this->assertEquals('available', $slot06['status']);

        // 08:00 - 09:00 should be booked (by other user)
        $slot08 = $slots->firstWhere('start_time', '08:00');
        $this->assertEquals('booked', $slot08['status']);

        // 09:00 - 10:00 should be booked (by other user)
        $slot09 = $slots->firstWhere('start_time', '09:00');
        $this->assertEquals('booked', $slot09['status']);

        // 14:00 - 15:00 should be mine (by current user)
        $slot14 = $slots->firstWhere('start_time', '14:00');
        $this->assertEquals('mine', $slot14['status']);
    }

    public function test_user_can_successfully_book_available_slots(): void
    {
        $targetDate = now()->addDays(2)->format('Y-m-d');

        $response = $this->actingAs($this->user)->post(route('bookings.store'), [
            'court_id' => $this->court->id,
            'booking_date' => $targetDate,
            'start_time' => '08:00',
            'end_time' => '10:00',
            'notes' => 'Tolong sediakan net cadangan',
        ]);

        $response->assertSessionHasNoErrors();
        $createdBooking = \App\Models\Booking::first();
        $response->assertRedirect(route('payments.show', $createdBooking->payment->id));

        $this->assertDatabaseHas('bookings', [
            'user_id' => $this->user->id,
            'court_id' => $this->court->id,
            'booking_date' => $targetDate,
            'start_time' => '08:00:00',
            'end_time' => '10:00:00',
            'status' => 'pending',
            'total_price' => 80000.00, // 2 hours x 40,000
        ]);
    }

    /**
     * Test Anti-Bentrok concurrency locking.
     */
    public function test_anti_bentrok_concurrent_booking_to_same_slot_only_one_succeeds(): void
    {
        $targetDate = now()->addDays(3)->format('Y-m-d');
        $startTime = '10:00';
        $endTime = '12:00';

        // Request 1: User 1 submits booking for 10:00 - 12:00
        $response1 = $this->actingAs($this->user)->post(route('bookings.store'), [
            'court_id' => $this->court->id,
            'booking_date' => $targetDate,
            'start_time' => $startTime,
            'end_time' => $endTime,
        ]);

        $response1->assertSessionHasNoErrors();
        $createdBooking1 = \App\Models\Booking::latest('id')->first();
        $response1->assertRedirect(route('payments.show', $createdBooking1->payment->id));

        // Request 2: User 2 immediately attempts to book the EXACT SAME slot
        $response2 = $this->actingAs($this->otherUser)->post(route('bookings.store'), [
            'court_id' => $this->court->id,
            'booking_date' => $targetDate,
            'start_time' => $startTime,
            'end_time' => $endTime,
        ]);

        // Request 2 must be rejected with validation error on slots
        $response2->assertSessionHasErrors(['slots']);

        // Request 3: User 2 tries partial overlapping slot (e.g. 11:00 - 13:00)
        $response3 = $this->actingAs($this->otherUser)->post(route('bookings.store'), [
            'court_id' => $this->court->id,
            'booking_date' => $targetDate,
            'start_time' => '11:00',
            'end_time' => '13:00',
        ]);

        $response3->assertSessionHasErrors(['slots']);

        // Assert in database: ONLY EXACTLY 1 booking was created
        $count = Booking::where('court_id', $this->court->id)
            ->where('booking_date', $targetDate)
            ->count();

        $this->assertEquals(1, $count, 'Hanya tepat 1 booking yang boleh berhasil dibuat untuk slot yang sama.');
    }

    public function test_user_can_view_their_bookings_history(): void
    {
        $targetDate = now()->addDays(2)->format('Y-m-d');

        $myBooking = Booking::create([
            'user_id' => $this->user->id,
            'court_id' => $this->court->id,
            'booking_date' => $targetDate,
            'start_time' => '08:00:00',
            'end_time' => '10:00:00',
            'status' => 'pending',
            'total_price' => 80000,
            'is_recurring' => false,
        ]);

        $otherBooking = Booking::create([
            'user_id' => $this->otherUser->id,
            'court_id' => $this->court->id,
            'booking_date' => $targetDate,
            'start_time' => '14:00:00',
            'end_time' => '15:00:00',
            'status' => 'confirmed',
            'total_price' => 40000,
            'is_recurring' => false,
        ]);

        $response = $this->actingAs($this->user)->get(route('my-bookings.index'));

        $response->assertOk();
    }

    public function test_user_can_cancel_pending_booking(): void
    {
        $targetDate = now()->addDays(1)->format('Y-m-d');

        $booking = Booking::create([
            'user_id' => $this->user->id,
            'court_id' => $this->court->id,
            'booking_date' => $targetDate,
            'start_time' => '08:00:00',
            'end_time' => '10:00:00',
            'status' => 'pending',
            'total_price' => 80000,
            'is_recurring' => false,
        ]);

        $response = $this->actingAs($this->user)->patch(route('bookings.cancel', $booking->id));

        $response->assertSessionHasNoErrors();
        $this->assertEquals('cancelled', $booking->fresh()->status);
    }

    public function test_user_can_cancel_confirmed_booking_more_than_2_hours_before_start(): void
    {
        // Booking is tomorrow, more than 24 hours away
        $targetDate = now()->addDays(1)->format('Y-m-d');

        $booking = Booking::create([
            'user_id' => $this->user->id,
            'court_id' => $this->court->id,
            'booking_date' => $targetDate,
            'start_time' => '18:00:00',
            'end_time' => '20:00:00',
            'status' => 'confirmed',
            'total_price' => 80000,
            'is_recurring' => false,
        ]);

        $response = $this->actingAs($this->user)->patch(route('bookings.cancel', $booking->id));

        $response->assertSessionHasNoErrors();
        $this->assertEquals('cancelled', $booking->fresh()->status);
    }

    public function test_user_cannot_cancel_confirmed_booking_less_than_2_hours_before_start(): void
    {
        // Booking is today, starts in 1 hour
        $targetDate = now()->format('Y-m-d');
        $startTime = now()->addHour()->format('H:i:00');
        $endTime = now()->addHours(2)->format('H:i:00');

        $booking = Booking::create([
            'user_id' => $this->user->id,
            'court_id' => $this->court->id,
            'booking_date' => $targetDate,
            'start_time' => $startTime,
            'end_time' => $endTime,
            'status' => 'confirmed',
            'total_price' => 40000,
            'is_recurring' => false,
        ]);

        $response = $this->actingAs($this->user)->patch(route('bookings.cancel', $booking->id));

        $response->assertForbidden();
        $this->assertEquals('confirmed', $booking->fresh()->status);
    }

    public function test_user_cannot_cancel_other_users_booking(): void
    {
        $targetDate = now()->addDays(2)->format('Y-m-d');

        $booking = Booking::create([
            'user_id' => $this->otherUser->id,
            'court_id' => $this->court->id,
            'booking_date' => $targetDate,
            'start_time' => '08:00:00',
            'end_time' => '10:00:00',
            'status' => 'pending',
            'total_price' => 80000,
            'is_recurring' => false,
        ]);

        $response = $this->actingAs($this->user)->patch(route('bookings.cancel', $booking->id));

        $response->assertForbidden();
        $this->assertEquals('pending', $booking->fresh()->status);
    }
}

