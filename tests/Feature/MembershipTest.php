<?php

namespace Tests\Feature;

use App\Events\PaymentCompleted;
use App\Models\Booking;
use App\Models\Court;
use App\Models\Membership;
use App\Models\Payment;
use App\Models\PointHistory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class MembershipTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Court $court;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->court = Court::create([
            'name' => 'Lapangan Test VIP',
            'description' => 'Karpet Vinyl PBSI',
            'price_per_hour' => 50000,
            'is_active' => true,
        ]);
    }

    public function test_user_registration_automatically_creates_bronze_membership(): void
    {
        $response = $this->post('/register', [
            'name' => 'Member Baru',
            'email' => 'memberbaru@test.com',
            'phone' => '081299998888',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertRedirect(route('dashboard', absolute: false));

        $newUser = User::where('email', 'memberbaru@test.com')->first();
        $this->assertNotNull($newUser);

        $this->assertDatabaseHas('memberships', [
            'user_id' => $newUser->id,
            'points' => 0,
            'tier' => 'bronze',
        ]);

        $this->assertEquals('bronze', $newUser->membership->tier);
        $this->assertEquals(0, $newUser->membership->points);
        $this->assertEquals(0, $newUser->membership->discount_percentage);
    }

    public function test_payment_awards_points_based_on_price_after_discount_with_idempotency(): void
    {
        $booking = Booking::create([
            'user_id' => $this->user->id,
            'court_id' => $this->court->id,
            'booking_date' => now()->addDay()->format('Y-m-d'),
            'start_time' => '10:00',
            'end_time' => '12:00',
            'total_price' => 100000,
            'status' => 'pending',
        ]);

        $payment = Payment::create([
            'booking_id' => $booking->id,
            'invoice_number' => 'INV-TEST-0001',
            'payment_method' => 'bank_transfer',
            'amount' => 100000,
            'status' => 'pending',
        ]);

        // Trigger payment completed event
        event(new PaymentCompleted($payment));

        $this->user->refresh();
        $this->assertEquals(10, $this->user->membership->points);
        $this->assertEquals('bronze', $this->user->membership->tier);

        $this->assertDatabaseHas('point_histories', [
            'user_id' => $this->user->id,
            'booking_id' => $booking->id,
            'points_earned' => 10,
        ]);

        // Test idempotency: triggering again should not duplicate points
        event(new PaymentCompleted($payment));
        $this->user->refresh();
        $this->assertEquals(10, $this->user->membership->points);
        $this->assertEquals(1, PointHistory::where('booking_id', $booking->id)->count());
    }

    public function test_user_tier_automatically_upgrades_to_silver_and_gold(): void
    {
        $membership = $this->user->membership;
        $this->assertEquals('bronze', $membership->tier);

        // Helper method calculateTier verification
        $this->assertEquals('bronze', Membership::calculateTier(0));
        $this->assertEquals('bronze', Membership::calculateTier(99));
        $this->assertEquals('silver', Membership::calculateTier(100));
        $this->assertEquals('silver', Membership::calculateTier(299));
        $this->assertEquals('gold', Membership::calculateTier(300));
        $this->assertEquals('gold', Membership::calculateTier(1000));

        // Create booking & payment for 1,000,000 IDR (awards 100 points -> unlocks Silver)
        $booking = Booking::create([
            'user_id' => $this->user->id,
            'court_id' => $this->court->id,
            'booking_date' => now()->addDays(2)->format('Y-m-d'),
            'start_time' => '14:00',
            'end_time' => '16:00',
            'total_price' => 1000000,
            'status' => 'pending',
        ]);

        $payment = Payment::create([
            'booking_id' => $booking->id,
            'invoice_number' => 'INV-TEST-SILVER',
            'payment_method' => 'bank_transfer',
            'amount' => 1000000,
            'status' => 'pending',
        ]);

        event(new PaymentCompleted($payment));

        $this->user->refresh();
        $this->assertEquals(100, $this->user->membership->points);
        $this->assertEquals('silver', $this->user->membership->tier);
        $this->assertEquals(5, $this->user->membership->discount_percentage);

        // Another payment for 2,000,000 IDR (awards 200 points -> total 300 points -> unlocks Gold)
        $bookingGold = Booking::create([
            'user_id' => $this->user->id,
            'court_id' => $this->court->id,
            'booking_date' => now()->addDays(3)->format('Y-m-d'),
            'start_time' => '16:00',
            'end_time' => '18:00',
            'total_price' => 2000000,
            'status' => 'pending',
        ]);

        $paymentGold = Payment::create([
            'booking_id' => $bookingGold->id,
            'invoice_number' => 'INV-TEST-GOLD',
            'payment_method' => 'bank_transfer',
            'amount' => 2000000,
            'status' => 'pending',
        ]);

        event(new PaymentCompleted($paymentGold));

        $this->user->refresh();
        $this->assertEquals(300, $this->user->membership->points);
        $this->assertEquals('gold', $this->user->membership->tier);
        $this->assertEquals(10, $this->user->membership->discount_percentage);
    }

    public function test_silver_member_receives_five_percent_discount_on_booking(): void
    {
        // Set user to silver
        $this->user->membership->update([
            'tier' => 'silver',
            'points' => 150,
        ]);

        // Court price is 50,000/hr, 2 hours = 100,000 raw
        // 5% discount = 5,000 -> final total = 95,000
        $response = $this->actingAs($this->user)->post(route('bookings.store'), [
            'court_id' => $this->court->id,
            'booking_date' => now()->addDays(5)->format('Y-m-d'),
            'start_time' => '08:00',
            'end_time' => '10:00',
            'notes' => 'Booking Silver Member',
        ]);

        $booking = Booking::where('user_id', $this->user->id)
            ->where('start_time', '08:00:00')
            ->first();

        $this->assertNotNull($booking);
        $this->assertEquals(95000, (float) $booking->total_price);

        $payment = Payment::where('booking_id', $booking->id)->first();
        $this->assertNotNull($payment);
        $this->assertEquals(95000, (float) $payment->amount);

        // When payment completes, points awarded from discounted amount: floor(95000 / 10000) = 9
        event(new PaymentCompleted($payment));
        $this->user->refresh();
        $this->assertEquals(159, $this->user->membership->points);
    }

    public function test_gold_member_receives_ten_percent_discount_on_booking(): void
    {
        // Set user to gold
        $this->user->membership->update([
            'tier' => 'gold',
            'points' => 350,
        ]);

        // Court price is 50,000/hr, 2 hours = 100,000 raw
        // 10% discount = 10,000 -> final total = 90,000
        $response = $this->actingAs($this->user)->post(route('bookings.store'), [
            'court_id' => $this->court->id,
            'booking_date' => now()->addDays(6)->format('Y-m-d'),
            'start_time' => '10:00',
            'end_time' => '12:00',
            'notes' => 'Booking Gold Member',
        ]);

        $booking = Booking::where('user_id', $this->user->id)
            ->where('start_time', '10:00:00')
            ->first();

        $this->assertNotNull($booking);
        $this->assertEquals(90000, (float) $booking->total_price);

        $payment = Payment::where('booking_id', $booking->id)->first();
        $this->assertNotNull($payment);
        $this->assertEquals(90000, (float) $payment->amount);

        // Points from discounted amount: floor(90000 / 10000) = 9
        event(new PaymentCompleted($payment));
        $this->user->refresh();
        $this->assertEquals(359, $this->user->membership->points);
    }

    public function test_guest_cannot_access_membership_page(): void
    {
        $response = $this->get(route('membership.index'));
        $response->assertRedirect(route('login'));
    }

    public function test_authenticated_user_can_view_membership_page(): void
    {
        $this->user->membership->update([
            'tier' => 'silver',
            'points' => 150,
        ]);

        // Create point history
        PointHistory::create([
            'user_id' => $this->user->id,
            'points_earned' => 15,
            'points_used' => 0,
            'description' => 'Poin booking test',
        ]);

        $response = $this->actingAs($this->user)->get(route('membership.index'));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Membership/Index')
            ->has('membership', fn (Assert $membership) => $membership
                ->where('tier', 'silver')
                ->where('points', 150)
                ->where('discount_percentage', 5)
                ->where('next_tier', 'gold')
                ->where('target_points', 300)
                ->where('points_needed', 150)
                ->where('progress_percentage', 25) // (150 - 100) / 200 = 25%
            )
            ->has('pointHistories', 1)
            ->has('tierBenefits', 3)
        );
    }

    public function test_shared_inertia_auth_includes_user_membership(): void
    {
        $this->user->membership->update([
            'tier' => 'gold',
            'points' => 320,
        ]);

        $response = $this->actingAs($this->user)->get(route('dashboard'));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->has('auth.user', fn (Assert $user) => $user
                ->has('membership', fn (Assert $membership) => $membership
                    ->where('tier', 'gold')
                    ->where('points', 320)
                    ->where('discount_percentage', 10)
                )
                ->etc()
            )
        );
    }
}

