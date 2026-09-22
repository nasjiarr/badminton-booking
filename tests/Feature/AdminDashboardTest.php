<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Court;
use App\Models\Payment;
use App\Models\User;
use App\Services\DashboardStatsService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminDashboardTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $customer;
    protected array $courts;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => 'admin']);
        Role::firstOrCreate(['name' => 'user']);

        $this->admin = User::factory()->create();
        $this->admin->assignRole('admin');

        $this->customer = User::factory()->create();
        $this->customer->assignRole('user');

        // Seed 4 active courts
        $this->courts = [];
        foreach (['A', 'B', 'C', 'D'] as $letter) {
            $this->courts[$letter] = Court::create([
                'name' => "Lapangan $letter",
                'description' => "Lapangan Badminton PBSI $letter",
                'price_per_hour' => 40000,
                'is_active' => true,
            ]);
        }
    }

    public function test_unauthenticated_user_cannot_access_admin_dashboard(): void
    {
        $response = $this->get(route('admin.dashboard'));
        $response->assertRedirect(route('login'));
    }

    public function test_regular_customer_cannot_access_admin_dashboard(): void
    {
        $response = $this->actingAs($this->customer)->get(route('admin.dashboard'));
        $response->assertForbidden();
    }

    public function test_admin_can_access_admin_dashboard(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.dashboard'));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Admin/Dashboard')
            ->has('summary')
            ->has('charts.revenue')
            ->has('charts.occupancy_trend')
            ->has('charts.court_comparison')
            ->has('filters')
        );
    }

    public function test_dashboard_summary_cards_calculate_accurate_metrics(): void
    {
        $today = now()->format('Y-m-d');

        // Create booking for today (2 hours)
        $booking = Booking::create([
            'user_id' => $this->customer->id,
            'court_id' => $this->courts['A']->id,
            'booking_date' => $today,
            'start_time' => '10:00:00',
            'end_time' => '12:00:00',
            'total_price' => 80000,
            'status' => 'confirmed',
        ]);

        // Create payment paid today
        Payment::create([
            'booking_id' => $booking->id,
            'invoice_number' => 'INV-TEST-DASH-1',
            'amount' => 80000,
            'payment_method' => 'bank_transfer',
            'status' => 'paid',
            'paid_at' => now(),
        ]);

        // Cancelled booking should NOT count
        Booking::create([
            'user_id' => $this->customer->id,
            'court_id' => $this->courts['B']->id,
            'booking_date' => $today,
            'start_time' => '14:00:00',
            'end_time' => '16:00:00',
            'total_price' => 80000,
            'status' => 'cancelled',
        ]);

        $service = app(DashboardStatsService::class);
        $summary = $service->getSummaryCards();

        $this->assertEquals(1, $summary['total_bookings_today']);
        $this->assertEquals(80000, $summary['total_revenue_today']);
        $this->assertEquals(2.0, $summary['occupied_hours_today']);
        $this->assertEquals(64, $summary['total_capacity_today']); // 4 courts * 16 hours
        $this->assertEquals(3.1, $summary['occupancy_rate_today']); // round(2 / 64 * 100, 1) = 3.1
        $this->assertGreaterThanOrEqual(2, $summary['new_members_this_month']); // admin + customer
    }

    public function test_dashboard_revenue_chart_aggregates_paid_amounts(): void
    {
        $date1 = now()->subDays(2)->startOfDay();
        $date2 = now()->subDay()->startOfDay();

        $b1 = Booking::create([
            'user_id' => $this->customer->id,
            'court_id' => $this->courts['A']->id,
            'booking_date' => $date1->format('Y-m-d'),
            'start_time' => '10:00:00',
            'end_time' => '11:00:00',
            'total_price' => 40000,
            'status' => 'confirmed',
        ]);

        Payment::create([
            'booking_id' => $b1->id,
            'invoice_number' => 'INV-REV-1',
            'amount' => 40000,
            'payment_method' => 'bank_transfer',
            'status' => 'paid',
            'paid_at' => $date1->copy()->addHours(11),
        ]);

        $b2 = Booking::create([
            'user_id' => $this->customer->id,
            'court_id' => $this->courts['B']->id,
            'booking_date' => $date2->format('Y-m-d'),
            'start_time' => '15:00:00',
            'end_time' => '17:00:00',
            'total_price' => 80000,
            'status' => 'confirmed',
        ]);

        Payment::create([
            'booking_id' => $b2->id,
            'invoice_number' => 'INV-REV-2',
            'amount' => 80000,
            'payment_method' => 'bank_transfer',
            'status' => 'paid',
            'paid_at' => $date2->copy()->addHours(16),
        ]);

        $service = app(DashboardStatsService::class);
        $chart = $service->getRevenueChart(now()->subDays(6), now());

        $this->assertCount(7, $chart['labels']);
        $this->assertEquals(120000, $chart['total_in_period']);
        $this->assertEquals('#CCFF00', $chart['datasets'][0]['backgroundColor']);
    }

    public function test_dashboard_occupancy_chart_calculates_daily_percentages(): void
    {
        $date = now()->subDays(3)->format('Y-m-d');

        // Book 8 hours across courts on $date -> 8 / 64 = 12.5%
        Booking::create([
            'user_id' => $this->customer->id,
            'court_id' => $this->courts['A']->id,
            'booking_date' => $date,
            'start_time' => '08:00:00',
            'end_time' => '12:00:00',
            'total_price' => 160000,
            'status' => 'confirmed',
        ]);

        Booking::create([
            'user_id' => $this->customer->id,
            'court_id' => $this->courts['B']->id,
            'booking_date' => $date,
            'start_time' => '14:00:00',
            'end_time' => '18:00:00',
            'total_price' => 160000,
            'status' => 'confirmed',
        ]);

        $service = app(DashboardStatsService::class);
        $chart = $service->getOccupancyTrendChart(now()->subDays(5), now());

        $this->assertCount(6, $chart['labels']);
        $this->assertGreaterThan(0, $chart['average_rate']);
        $this->assertEquals('#FF5500', $chart['datasets'][0]['borderColor']);
    }

    public function test_dashboard_court_comparison_chart_returns_all_active_courts(): void
    {
        $today = now()->format('Y-m-d');

        // Lapangan A: 3 jam
        Booking::create([
            'user_id' => $this->customer->id,
            'court_id' => $this->courts['A']->id,
            'booking_date' => $today,
            'start_time' => '08:00:00',
            'end_time' => '11:00:00',
            'total_price' => 120000,
            'status' => 'confirmed',
        ]);

        // Lapangan B: 1 jam
        Booking::create([
            'user_id' => $this->customer->id,
            'court_id' => $this->courts['B']->id,
            'booking_date' => $today,
            'start_time' => '13:00:00',
            'end_time' => '14:00:00',
            'total_price' => 40000,
            'status' => 'confirmed',
        ]);

        $service = app(DashboardStatsService::class);
        $chart = $service->getCourtOccupancyComparison(now()->startOfMonth(), now()->endOfMonth());

        $this->assertCount(4, $chart['labels']);
        $this->assertEquals(4.0, $chart['total_hours']);
        $this->assertEquals('Lapangan A', $chart['courts_detail'][0]['name']);
        $this->assertEquals(3.0, $chart['courts_detail'][0]['hours']);
        $this->assertEquals(75.0, $chart['courts_detail'][0]['percentage']); // 3 / 4 * 100 = 75%
    }

    public function test_admin_dashboard_controller_respects_custom_date_range_filter(): void
    {
        $start = now()->subDays(10)->format('Y-m-d');
        $end = now()->subDays(2)->format('Y-m-d');

        $response = $this->actingAs($this->admin)->get(route('admin.dashboard', [
            'start_date' => $start,
            'end_date' => $end,
        ]));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Admin/Dashboard')
            ->where('filters.mode', 'custom')
            ->where('filters.start_date', $start)
            ->where('filters.end_date', $end)
            ->has('charts.revenue.labels', 9) // 10 to 2 inclusive = 9 days
        );
    }
}

