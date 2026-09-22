<?php

namespace Tests\Feature;

use App\Events\BookingCancelled;
use App\Events\PaymentCompleted;
use App\Models\Booking;
use App\Models\Court;
use App\Models\Membership;
use App\Models\Payment;
use App\Models\PointHistory;
use App\Models\User;
use App\Notifications\BookingCancelledNotification;
use App\Notifications\PaymentSuccessfulNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class PaymentSimulationTest extends TestCase
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
            'name' => 'Lapangan 1 Test',
            'description' => 'Karpet Vinyl Test',
            'price_per_hour' => 40000,
            'is_active' => true,
        ]);
    }

    public function test_user_is_redirected_to_payment_page_after_booking_creation(): void
    {
        $response = $this->actingAs($this->user)->post(route('bookings.store'), [
            'court_id' => $this->court->id,
            'booking_date' => now()->addDay()->format('Y-m-d'),
            'start_time' => '09:00',
            'end_time' => '11:00',
            'notes' => 'Catatan booking test',
        ]);

        $booking = Booking::first();
        $this->assertNotNull($booking);
        $this->assertEquals('pending', $booking->status);
        $this->assertEquals(80000, (float) $booking->total_price);

        $payment = Payment::where('booking_id', $booking->id)->first();
        $this->assertNotNull($payment);
        $this->assertEquals('pending', $payment->status);
        $this->assertEquals(80000, (float) $payment->amount);
        $this->assertStringStartsWith('INV-' . now()->format('Ymd') . '-', $payment->invoice_number);

        $response->assertRedirect(route('payments.show', $payment->id));
    }

    public function test_user_can_view_payment_selection_page(): void
    {
        $booking = Booking::create([
            'user_id' => $this->user->id,
            'court_id' => $this->court->id,
            'booking_date' => now()->addDay()->format('Y-m-d'),
            'start_time' => '10:00:00',
            'end_time' => '11:00:00',
            'status' => 'pending',
            'total_price' => 40000,
        ]);

        $payment = Payment::create([
            'booking_id' => $booking->id,
            'amount' => 40000,
            'method' => 'simulasi_transfer',
            'status' => 'pending',
            'invoice_number' => Payment::generateInvoiceNumber(),
        ]);

        $response = $this->actingAs($this->user)->get(route('payments.show', $payment->id));
        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Payments/Show')
            ->has('payment')
            ->where('payment.invoice_number', $payment->invoice_number)
            ->where('payment.amount', 40000)
        );
    }

    public function test_user_cannot_view_or_pay_other_users_payment(): void
    {
        $booking = Booking::create([
            'user_id' => $this->user->id,
            'court_id' => $this->court->id,
            'booking_date' => now()->addDay()->format('Y-m-d'),
            'start_time' => '10:00:00',
            'end_time' => '11:00:00',
            'status' => 'pending',
            'total_price' => 40000,
        ]);

        $payment = Payment::create([
            'booking_id' => $booking->id,
            'amount' => 40000,
            'method' => 'simulasi_transfer',
            'status' => 'pending',
            'invoice_number' => Payment::generateInvoiceNumber(),
        ]);

        $response = $this->actingAs($this->otherUser)->get(route('payments.show', $payment->id));
        $response->assertStatus(403);
    }

    public function test_user_can_select_method_and_proceed_to_waiting_page(): void
    {
        $booking = Booking::create([
            'user_id' => $this->user->id,
            'court_id' => $this->court->id,
            'booking_date' => now()->addDay()->format('Y-m-d'),
            'start_time' => '14:00:00',
            'end_time' => '15:00:00',
            'status' => 'pending',
            'total_price' => 40000,
        ]);

        $payment = Payment::create([
            'booking_id' => $booking->id,
            'amount' => 40000,
            'method' => 'simulasi_transfer',
            'status' => 'pending',
            'invoice_number' => Payment::generateInvoiceNumber(),
        ]);

        $response = $this->actingAs($this->user)->post(route('payments.pay', $payment->id), [
            'method' => 'simulasi_ewallet',
        ]);

        $response->assertRedirect(route('payments.waiting', $payment->id));
        $this->assertEquals('simulasi_ewallet', $payment->fresh()->method);

        $waitingResponse = $this->actingAs($this->user)->get(route('payments.waiting', $payment->id));
        $waitingResponse->assertStatus(200);
        $waitingResponse->assertInertia(fn ($page) => $page->component('Payments/Waiting'));
    }

    public function test_simulate_successful_payment_marks_paid_confirms_booking_and_awards_loyalty_points(): void
    {
        Notification::fake();

        $booking = Booking::create([
            'user_id' => $this->user->id,
            'court_id' => $this->court->id,
            'booking_date' => now()->addDay()->format('Y-m-d'),
            'start_time' => '16:00:00',
            'end_time' => '18:00:00',
            'status' => 'pending',
            'total_price' => 80000,
        ]);

        $payment = Payment::create([
            'booking_id' => $booking->id,
            'amount' => 80000,
            'method' => 'simulasi_transfer',
            'status' => 'pending',
            'invoice_number' => Payment::generateInvoiceNumber(),
        ]);

        $response = $this->actingAs($this->user)->post(route('payments.simulate-success', $payment->id));

        $response->assertRedirect(route('payments.invoice', $payment->id));

        $freshPayment = $payment->fresh();
        $freshBooking = $booking->fresh();

        $this->assertEquals('paid', $freshPayment->status);
        $this->assertNotNull($freshPayment->paid_at);
        $this->assertEquals('confirmed', $freshBooking->status);

        // Verify loyalty points awarded: 80.000 / 10.000 = 8 points
        $membership = Membership::where('user_id', $this->user->id)->first();
        $this->assertNotNull($membership);
        $this->assertEquals(8, $membership->points);
        $this->assertEquals('bronze', $membership->tier);

        $history = PointHistory::where('booking_id', $booking->id)->first();
        $this->assertNotNull($history);
        $this->assertEquals(8, $history->points_earned);

        // Verify notification was sent
        Notification::assertSentTo($this->user, PaymentSuccessfulNotification::class);
    }

    public function test_simulate_failed_payment_cancels_booking_and_broadcasts_slot_release(): void
    {
        Event::fake([BookingCancelled::class]);
        Notification::fake();

        $booking = Booking::create([
            'user_id' => $this->user->id,
            'court_id' => $this->court->id,
            'booking_date' => now()->addDay()->format('Y-m-d'),
            'start_time' => '18:00:00',
            'end_time' => '19:00:00',
            'status' => 'pending',
            'total_price' => 40000,
        ]);

        $payment = Payment::create([
            'booking_id' => $booking->id,
            'amount' => 40000,
            'method' => 'simulasi_ewallet',
            'status' => 'pending',
            'invoice_number' => Payment::generateInvoiceNumber(),
        ]);

        $response = $this->actingAs($this->user)->post(route('payments.simulate-failed', $payment->id));

        $response->assertRedirect(route('my-bookings.index'));

        $this->assertEquals('failed', $payment->fresh()->status);
        $this->assertEquals('cancelled', $booking->fresh()->status);

        Event::assertDispatched(BookingCancelled::class);
        Notification::assertSentTo($this->user, BookingCancelledNotification::class);
    }

    public function test_expire_pending_bookings_command_auto_cancels_stale_bookings(): void
    {
        Event::fake([BookingCancelled::class]);
        Notification::fake();

        $staleBooking = Booking::create([
            'user_id' => $this->user->id,
            'court_id' => $this->court->id,
            'booking_date' => now()->addDay()->format('Y-m-d'),
            'start_time' => '12:00:00',
            'end_time' => '13:00:00',
            'status' => 'pending',
            'total_price' => 40000,
        ]);
        // Set created_at to 16 minutes ago
        $staleBooking->created_at = now()->subMinutes(16);
        $staleBooking->save();

        $payment = Payment::create([
            'booking_id' => $staleBooking->id,
            'amount' => 40000,
            'method' => 'simulasi_transfer',
            'status' => 'pending',
            'invoice_number' => Payment::generateInvoiceNumber(),
        ]);

        $this->artisan('bookings:expire-pending')
            ->expectsOutputToContain('Berhasil membatalkan 1 booking')
            ->assertExitCode(0);

        $this->assertEquals('cancelled', $staleBooking->fresh()->status);
        $this->assertEquals('expired', $payment->fresh()->status);

        Event::assertDispatched(BookingCancelled::class);
        Notification::assertSentTo($this->user, BookingCancelledNotification::class);
    }

    public function test_user_can_view_invoice_page_for_paid_booking(): void
    {
        $booking = Booking::create([
            'user_id' => $this->user->id,
            'court_id' => $this->court->id,
            'booking_date' => now()->addDay()->format('Y-m-d'),
            'start_time' => '15:00:00',
            'end_time' => '16:00:00',
            'status' => 'confirmed',
            'total_price' => 40000,
        ]);

        $payment = Payment::create([
            'booking_id' => $booking->id,
            'amount' => 40000,
            'method' => 'simulasi_transfer',
            'status' => 'paid',
            'paid_at' => now(),
            'invoice_number' => Payment::generateInvoiceNumber(),
        ]);

        $response = $this->actingAs($this->user)->get(route('payments.invoice', $payment->id));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Payments/Invoice')
            ->has('payment')
            ->where('payment.status', 'paid')
            ->where('payment.invoice_number', $payment->invoice_number)
        );
    }
}

