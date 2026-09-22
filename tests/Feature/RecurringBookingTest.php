<?php

namespace Tests\Feature;

use App\Events\BookingCancelled;
use App\Events\BookingCreated;
use App\Models\Booking;
use App\Models\Court;
use App\Models\CourtSchedule;
use App\Models\Membership;
use App\Models\Payment;
use App\Models\RecurringBooking;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class RecurringBookingTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected User $otherUser;
    protected Court $court;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->otherUser = User::factory()->create();

        $this->court = Court::create([
            'name' => 'Lapangan A',
            'description' => 'Lapangan standar BWF.',
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

    public function test_user_can_create_recurring_booking_across_multiple_weeks(): void
    {
        $startDate = now()->addDays(2)->format('Y-m-d'); // Minggu ke-1
        $endDate = Carbon::parse($startDate)->addWeeks(3)->format('Y-m-d'); // 4 minggu total (minggu 0, 1, 2, 3)

        $response = $this->actingAs($this->user)->post(route('bookings.store'), [
            'court_id' => $this->court->id,
            'booking_date' => $startDate,
            'start_time' => '19:00',
            'end_time' => '20:00',
            'is_recurring' => true,
            'recurring_end_date' => $endDate,
            'notes' => 'Booking latihan mingguan',
        ]);

        $this->assertDatabaseCount('recurring_bookings', 1);
        $recurringBooking = RecurringBooking::first();
        $this->assertEquals($this->user->id, $recurringBooking->user_id);
        $this->assertEquals($this->court->id, $recurringBooking->court_id);
        $this->assertEquals('active', $recurringBooking->status);

        // 4 sessions created
        $this->assertDatabaseCount('bookings', 4);
        $bookings = Booking::where('recurring_booking_id', $recurringBooking->id)->get();
        $this->assertCount(4, $bookings);

        foreach ($bookings as $b) {
            $this->assertTrue($b->is_recurring);
            $this->assertEquals('pending', $b->status);
            $this->assertEquals(40000, $b->total_price);
            $this->assertNotNull($b->payment);
            $this->assertEquals('pending', $b->payment->status);
        }

        $firstPayment = $bookings->first()->payment;
        $response->assertRedirect(route('payments.show', $firstPayment->id));
    }

    public function test_recurring_booking_skips_conflicted_week_and_successfully_books_non_conflicting_weeks(): void
    {
        $startDate = now()->addDays(3)->format('Y-m-d'); // Minggu ke-1
        $conflictDate = Carbon::parse($startDate)->addWeek()->format('Y-m-d'); // Minggu ke-2 bentrok!
        $endDate = Carbon::parse($startDate)->addWeeks(2)->format('Y-m-d'); // Minggu ke-3 (total 3 minggu dicoba)

        // Buat booking bentrok oleh user lain di minggu ke-2
        Booking::create([
            'user_id' => $this->otherUser->id,
            'court_id' => $this->court->id,
            'booking_date' => $conflictDate,
            'start_time' => '19:00:00',
            'end_time' => '20:00:00',
            'status' => 'confirmed',
            'total_price' => 40000,
        ]);

        $response = $this->actingAs($this->user)->post(route('bookings.store'), [
            'court_id' => $this->court->id,
            'booking_date' => $startDate,
            'start_time' => '19:00',
            'end_time' => '20:00',
            'is_recurring' => true,
            'recurring_end_date' => $endDate,
        ]);

        $this->assertDatabaseCount('recurring_bookings', 1);
        $recurring = RecurringBooking::first();

        // 2 sesi yang berhasil (minggu ke-1 dan minggu ke-3), minggu ke-2 di-skip
        $userBookings = Booking::where('recurring_booking_id', $recurring->id)->get();
        $this->assertCount(2, $userBookings);

        $datesBooked = $userBookings->pluck('booking_date')->map(fn ($d) => $d->format('Y-m-d'))->toArray();
        $this->assertContains($startDate, $datesBooked);
        $this->assertContains($endDate, $datesBooked);
        $this->assertNotContains($conflictDate, $datesBooked);

        // Flash session must notify user about skipped week
        $response->assertSessionHas('success');
        $this->assertStringContainsString('dilewati', session('success'));
    }

    public function test_recurring_booking_fails_when_all_weeks_conflict(): void
    {
        $startDate = now()->addDays(3)->format('Y-m-d');
        $week2 = Carbon::parse($startDate)->addWeek()->format('Y-m-d');

        // Bentrok di kedua minggu
        Booking::create([
            'user_id' => $this->otherUser->id,
            'court_id' => $this->court->id,
            'booking_date' => $startDate,
            'start_time' => '19:00:00',
            'end_time' => '20:00:00',
            'status' => 'confirmed',
            'total_price' => 40000,
        ]);

        Booking::create([
            'user_id' => $this->otherUser->id,
            'court_id' => $this->court->id,
            'booking_date' => $week2,
            'start_time' => '19:00:00',
            'end_time' => '20:00:00',
            'status' => 'confirmed',
            'total_price' => 40000,
        ]);

        $response = $this->actingAs($this->user)->post(route('bookings.store'), [
            'court_id' => $this->court->id,
            'booking_date' => $startDate,
            'start_time' => '19:00',
            'end_time' => '20:00',
            'is_recurring' => true,
            'recurring_end_date' => $week2,
        ]);

        $response->assertSessionHasErrors('slots');
        $this->assertDatabaseCount('recurring_bookings', 0);
    }

    public function test_recurring_booking_validates_maximum_three_months_range(): void
    {
        $startDate = now()->addDay()->format('Y-m-d');
        $invalidEndDate = Carbon::parse($startDate)->addMonths(4)->format('Y-m-d');

        $response = $this->actingAs($this->user)->post(route('bookings.store'), [
            'court_id' => $this->court->id,
            'booking_date' => $startDate,
            'start_time' => '19:00',
            'end_time' => '20:00',
            'is_recurring' => true,
            'recurring_end_date' => $invalidEndDate,
        ]);

        $response->assertSessionHasErrors('recurring_end_date');
    }

    public function test_recurring_booking_broadcasts_realtime_event_for_each_created_session(): void
    {
        Event::fake([BookingCreated::class]);

        $startDate = now()->addDays(2)->format('Y-m-d');
        $endDate = Carbon::parse($startDate)->addWeeks(2)->format('Y-m-d'); // 3 sesi

        $this->actingAs($this->user)->post(route('bookings.store'), [
            'court_id' => $this->court->id,
            'booking_date' => $startDate,
            'start_time' => '18:00',
            'end_time' => '19:00',
            'is_recurring' => true,
            'recurring_end_date' => $endDate,
        ]);

        Event::assertDispatched(BookingCreated::class, 3);
    }

    public function test_user_can_view_my_recurring_bookings_page(): void
    {
        $startDate = now()->addDays(2)->format('Y-m-d');
        $endDate = Carbon::parse($startDate)->addWeeks(2)->format('Y-m-d');

        $this->actingAs($this->user)->post(route('bookings.store'), [
            'court_id' => $this->court->id,
            'booking_date' => $startDate,
            'start_time' => '18:00',
            'end_time' => '19:00',
            'is_recurring' => true,
            'recurring_end_date' => $endDate,
        ]);

        $response = $this->actingAs($this->user)->get(route('recurring-bookings.index'));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Bookings/MyRecurringBookings')
            ->has('recurringBookings', 1)
            ->where('recurringBookings.0.total_sessions_count', 3)
        );
    }

    public function test_user_can_cancel_recurring_booking_and_broadcasts_cancelled_events(): void
    {
        Event::fake([BookingCancelled::class]);
        Notification::fake();

        $startDate = now()->addDays(2)->format('Y-m-d');
        $endDate = Carbon::parse($startDate)->addWeeks(2)->format('Y-m-d');

        $this->actingAs($this->user)->post(route('bookings.store'), [
            'court_id' => $this->court->id,
            'booking_date' => $startDate,
            'start_time' => '18:00',
            'end_time' => '19:00',
            'is_recurring' => true,
            'recurring_end_date' => $endDate,
        ]);

        $recurring = RecurringBooking::first();
        $this->assertEquals('active', $recurring->status);

        $response = $this->actingAs($this->user)->patch(route('recurring-bookings.cancel', $recurring->id));

        $response->assertSessionHas('success');

        $this->assertEquals('cancelled', $recurring->fresh()->status);
        $sessions = Booking::where('recurring_booking_id', $recurring->id)->get();
        foreach ($sessions as $s) {
            $this->assertEquals('cancelled', $s->status);
        }

        Event::assertDispatched(BookingCancelled::class, 3);
    }

    public function test_user_cannot_cancel_other_users_recurring_booking(): void
    {
        $startDate = now()->addDays(2)->format('Y-m-d');
        $endDate = Carbon::parse($startDate)->addWeeks(2)->format('Y-m-d');

        $this->actingAs($this->user)->post(route('bookings.store'), [
            'court_id' => $this->court->id,
            'booking_date' => $startDate,
            'start_time' => '18:00',
            'end_time' => '19:00',
            'is_recurring' => true,
            'recurring_end_date' => $endDate,
        ]);

        $recurring = RecurringBooking::first();

        // Other user attempts cancel
        $response = $this->actingAs($this->otherUser)->patch(route('recurring-bookings.cancel', $recurring->id));

        $response->assertForbidden();
        $this->assertEquals('active', $recurring->fresh()->status);
    }

    public function test_simulating_successful_payment_confirms_all_recurring_sessions_and_awards_points(): void
    {
        Notification::fake();

        $startDate = now()->addDays(2)->format('Y-m-d');
        $endDate = Carbon::parse($startDate)->addWeeks(2)->format('Y-m-d'); // 3 sessions = 3 * 40.000 = 120.000 (12 points)

        $this->actingAs($this->user)->post(route('bookings.store'), [
            'court_id' => $this->court->id,
            'booking_date' => $startDate,
            'start_time' => '18:00',
            'end_time' => '19:00',
            'is_recurring' => true,
            'recurring_end_date' => $endDate,
        ]);

        $firstBooking = Booking::first();
        $payment = $firstBooking->payment;

        $response = $this->actingAs($this->user)->post(route('payments.simulate-success', $payment->id));

        $response->assertRedirect(route('payments.invoice', $payment->id));

        // Assert all 3 sessions are confirmed and paid
        $allBookings = Booking::where('recurring_booking_id', $firstBooking->recurring_booking_id)->get();
        $this->assertCount(3, $allBookings);

        foreach ($allBookings as $b) {
            $this->assertEquals('confirmed', $b->status);
            $this->assertEquals('paid', $b->payment->status);
            $this->assertNotNull($b->payment->paid_at);
        }

        // Assert loyalty points: 3 sessions * 4 points = 12 points total
        $membership = Membership::where('user_id', $this->user->id)->first();
        $this->assertNotNull($membership);
        $this->assertEquals(12, $membership->points);
    }
}

