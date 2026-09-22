<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Court;
use App\Models\CourtSchedule;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class CourtSearchTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Court $courtA;
    protected Court $courtB;
    protected Court $courtC;
    protected Court $courtD;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();

        // Create 4 active courts with varying prices
        $this->courtA = Court::create([
            'name' => 'Lapangan A',
            'description' => 'Lapangan A karpet vinil.',
            'price_per_hour' => 40000,
            'is_active' => true,
        ]);

        $this->courtB = Court::create([
            'name' => 'Lapangan B',
            'description' => 'Lapangan B karpet vinil.',
            'price_per_hour' => 50000,
            'is_active' => true,
        ]);

        $this->courtC = Court::create([
            'name' => 'Lapangan C',
            'description' => 'Lapangan C standar BWF.',
            'price_per_hour' => 60000,
            'is_active' => true,
        ]);

        $this->courtD = Court::create([
            'name' => 'Lapangan D',
            'description' => 'Lapangan D standar BWF.',
            'price_per_hour' => 70000,
            'is_active' => true,
        ]);

        // Create standard schedules 06:00 - 22:00 for all courts and days
        foreach ([$this->courtA, $this->courtB, $this->courtC, $this->courtD] as $c) {
            for ($day = 0; $day <= 6; $day++) {
                CourtSchedule::create([
                    'court_id' => $c->id,
                    'day_of_week' => $day,
                    'open_time' => '06:00:00',
                    'close_time' => '22:00:00',
                ]);
            }
        }
    }

    public function test_guest_is_redirected_to_login_when_accessing_court_search(): void
    {
        $response = $this->get(route('courts.search'));
        $response->assertRedirect(route('login'));
    }

    public function test_authenticated_user_can_access_court_search_page(): void
    {
        $response = $this->actingAs($this->user)->get(route('courts.search'));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Courts/Search')
            ->has('courts')
            ->has('filters')
        );
    }

    public function test_search_finds_all_courts_when_no_bookings_exist(): void
    {
        $targetDate = now()->addDays(2)->format('Y-m-d');

        $response = $this->actingAs($this->user)->get(route('courts.search', [
            'date' => $targetDate,
            'start_time' => '08:00',
            'end_time' => '10:00',
        ]));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Courts/Search')
            ->has('courts', 4)
            ->where('courts.0.duration_hours', 2)
            ->where('courts.0.total_price', 80000) // Lapangan A: 40.000 * 2
        );
    }

    public function test_search_excludes_court_with_overlapping_confirmed_booking(): void
    {
        $targetDate = now()->addDays(2)->format('Y-m-d');

        // Book Court A from 08:00 to 09:00 (overlapping with 08:00 - 10:00)
        Booking::create([
            'user_id' => $this->user->id,
            'court_id' => $this->courtA->id,
            'booking_date' => $targetDate,
            'start_time' => '08:00:00',
            'end_time' => '09:00:00',
            'status' => 'confirmed',
            'total_price' => 40000,
        ]);

        $response = $this->actingAs($this->user)->get(route('courts.search', [
            'date' => $targetDate,
            'start_time' => '08:00',
            'end_time' => '10:00',
        ]));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Courts/Search')
            ->has('courts', 3) // Lapangan B, C, D available, A excluded
            ->where('courts.0.name', 'Lapangan B')
        );
    }

    public function test_search_excludes_court_with_overlapping_pending_booking(): void
    {
        $targetDate = now()->addDays(2)->format('Y-m-d');

        // Pending booking on Court B from 09:00 to 10:00
        Booking::create([
            'user_id' => $this->user->id,
            'court_id' => $this->courtB->id,
            'booking_date' => $targetDate,
            'start_time' => '09:00:00',
            'end_time' => '10:00:00',
            'status' => 'pending',
            'total_price' => 50000,
        ]);

        $response = $this->actingAs($this->user)->get(route('courts.search', [
            'date' => $targetDate,
            'start_time' => '08:00',
            'end_time' => '10:00',
        ]));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Courts/Search')
            ->has('courts', 3)
            ->where('courts.0.name', 'Lapangan A')
            ->where('courts.1.name', 'Lapangan C')
            ->where('courts.2.name', 'Lapangan D')
        );
    }

    public function test_search_includes_court_with_cancelled_booking(): void
    {
        $targetDate = now()->addDays(2)->format('Y-m-d');

        // Cancelled booking on Court C
        Booking::create([
            'user_id' => $this->user->id,
            'court_id' => $this->courtC->id,
            'booking_date' => $targetDate,
            'start_time' => '08:00:00',
            'end_time' => '10:00:00',
            'status' => 'cancelled',
            'total_price' => 120000,
        ]);

        $response = $this->actingAs($this->user)->get(route('courts.search', [
            'date' => $targetDate,
            'start_time' => '08:00',
            'end_time' => '10:00',
        ]));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Courts/Search')
            ->has('courts', 4) // All 4 including C
        );
    }

    public function test_search_respects_price_filter(): void
    {
        $targetDate = now()->addDays(2)->format('Y-m-d');

        // Filter: min_price = 55000 (should only return Court C [60k] & Court D [70k])
        $response = $this->actingAs($this->user)->get(route('courts.search', [
            'date' => $targetDate,
            'start_time' => '08:00',
            'end_time' => '10:00',
            'min_price' => 55000,
        ]));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Courts/Search')
            ->has('courts', 2)
            ->where('courts.0.name', 'Lapangan C')
            ->where('courts.1.name', 'Lapangan D')
        );
    }

    public function test_search_sorts_by_price_asc_and_desc(): void
    {
        $targetDate = now()->addDays(2)->format('Y-m-d');

        // Sort price_desc
        $responseDesc = $this->actingAs($this->user)->get(route('courts.search', [
            'date' => $targetDate,
            'start_time' => '08:00',
            'end_time' => '10:00',
            'sort_by' => 'price_desc',
        ]));

        $responseDesc->assertOk();
        $responseDesc->assertInertia(fn (Assert $page) => $page
            ->component('Courts/Search')
            ->where('courts.0.name', 'Lapangan D') // 70k
            ->where('courts.3.name', 'Lapangan A') // 40k
        );

        // Sort price_asc
        $responseAsc = $this->actingAs($this->user)->get(route('courts.search', [
            'date' => $targetDate,
            'start_time' => '08:00',
            'end_time' => '10:00',
            'sort_by' => 'price_asc',
        ]));

        $responseAsc->assertOk();
        $responseAsc->assertInertia(fn (Assert $page) => $page
            ->component('Courts/Search')
            ->where('courts.0.name', 'Lapangan A') // 40k
            ->where('courts.3.name', 'Lapangan D') // 70k
        );
    }

    public function test_search_suggests_alternative_times_when_slot_is_fully_booked(): void
    {
        $targetDate = now()->addDays(2)->format('Y-m-d');

        // Fully book all 4 courts from 08:00 to 10:00
        foreach ([$this->courtA, $this->courtB, $this->courtC, $this->courtD] as $court) {
            Booking::create([
                'user_id' => $this->user->id,
                'court_id' => $court->id,
                'booking_date' => $targetDate,
                'start_time' => '08:00:00',
                'end_time' => '10:00:00',
                'status' => 'confirmed',
                'total_price' => $court->price_per_hour * 2,
            ]);
        }

        $response = $this->actingAs($this->user)->get(route('courts.search', [
            'date' => $targetDate,
            'start_time' => '08:00',
            'end_time' => '10:00',
        ]));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Courts/Search')
            ->has('courts', 0)
            ->has('suggestions')
            ->where('suggestions.0.date', $targetDate)
        );
    }

    public function test_booking_create_page_prefills_parameters_from_search(): void
    {
        $targetDate = now()->addDays(2)->format('Y-m-d');

        $response = $this->actingAs($this->user)->get(route('bookings.create', [
            'court_id' => $this->courtB->id,
            'date' => $targetDate,
            'start_time' => '08:00',
            'end_time' => '10:00',
        ]));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Bookings/Create')
            ->where('initialCourtId', $this->courtB->id)
            ->where('initialDate', $targetDate)
            ->where('initialStartTime', '08:00')
            ->where('initialEndTime', '10:00')
        );
    }
}

