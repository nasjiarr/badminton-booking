<?php

namespace Tests\Feature;

use App\Exports\BookingsExport;
use App\Models\Booking;
use App\Models\Court;
use App\Models\Payment;
use App\Models\User;
use App\Reports\BookingReportPdf;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Maatwebsite\Excel\Facades\Excel;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminReportExportTest extends TestCase
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

    public function test_unauthenticated_user_cannot_access_reports_or_exports(): void
    {
        $this->get(route('admin.reports.index'))->assertRedirect(route('login'));
        $this->get(route('admin.reports.export.excel'))->assertRedirect(route('login'));
        $this->get(route('admin.reports.export.pdf'))->assertRedirect(route('login'));
    }

    public function test_regular_customer_cannot_access_reports_or_exports(): void
    {
        $this->actingAs($this->customer)->get(route('admin.reports.index'))->assertForbidden();
        $this->actingAs($this->customer)->get(route('admin.reports.export.excel'))->assertForbidden();
        $this->actingAs($this->customer)->get(route('admin.reports.export.pdf'))->assertForbidden();
    }

    public function test_admin_can_view_reports_page(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.reports.index'));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Admin/Reports/Index')
            ->has('summary')
            ->has('bookings')
            ->has('filters')
        );
    }

    public function test_admin_can_export_excel_report_for_one_week(): void
    {
        Excel::fake();

        $start = now()->subDays(6)->format('Y-m-d');
        $end = now()->format('Y-m-d');

        $response = $this->actingAs($this->admin)->get(route('admin.reports.export.excel', [
            'start_date' => $start,
            'end_date' => $end,
        ]));

        $response->assertSuccessful();

        Excel::assertDownloaded("Laporan-Booking-SmashArena-" . str_replace('-', '', $start) . "-sd-" . str_replace('-', '', $end) . ".xlsx", function (BookingsExport $export) use ($start, $end) {
            return true;
        });
    }

    public function test_admin_can_export_excel_report_for_one_month(): void
    {
        Excel::fake();

        $start = now()->subDays(29)->format('Y-m-d');
        $end = now()->format('Y-m-d');

        $response = $this->actingAs($this->admin)->get(route('admin.reports.export.excel', [
            'start_date' => $start,
            'end_date' => $end,
        ]));

        $response->assertSuccessful();

        Excel::assertDownloaded("Laporan-Booking-SmashArena-" . str_replace('-', '', $start) . "-sd-" . str_replace('-', '', $end) . ".xlsx");
    }

    public function test_admin_can_export_pdf_report_for_one_week(): void
    {
        $start = now()->subDays(6)->format('Y-m-d');
        $end = now()->format('Y-m-d');

        // Create booking & payment in this range
        $booking = Booking::create([
            'user_id' => $this->customer->id,
            'court_id' => $this->courts['A']->id,
            'booking_date' => now()->subDays(2)->format('Y-m-d'),
            'start_time' => '10:00:00',
            'end_time' => '12:00:00',
            'total_price' => 80000,
            'status' => 'confirmed',
        ]);

        Payment::create([
            'booking_id' => $booking->id,
            'invoice_number' => 'INV-WEEK-1',
            'amount' => 80000,
            'payment_method' => 'bank_transfer',
            'status' => 'paid',
            'paid_at' => now()->subDays(2),
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.reports.export.pdf', [
            'start_date' => $start,
            'end_date' => $end,
        ]));

        $response->assertOk();
        $this->assertEquals('application/pdf', $response->headers->get('Content-Type'));
        $this->assertStringContainsString('.pdf', $response->headers->get('Content-Disposition'));
    }

    public function test_admin_can_export_pdf_report_for_one_month(): void
    {
        $start = now()->subDays(29)->format('Y-m-d');
        $end = now()->format('Y-m-d');

        // Create bookings across different weeks
        $b1 = Booking::create([
            'user_id' => $this->customer->id,
            'court_id' => $this->courts['B']->id,
            'booking_date' => now()->subDays(20)->format('Y-m-d'),
            'start_time' => '14:00:00',
            'end_time' => '16:00:00',
            'total_price' => 80000,
            'status' => 'confirmed',
        ]);

        Payment::create([
            'booking_id' => $b1->id,
            'invoice_number' => 'INV-MONTH-1',
            'amount' => 80000,
            'payment_method' => 'simulasi_transfer',
            'status' => 'paid',
            'paid_at' => now()->subDays(20),
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.reports.export.pdf', [
            'start_date' => $start,
            'end_date' => $end,
        ]));

        $response->assertOk();
        $this->assertEquals('application/pdf', $response->headers->get('Content-Type'));
        $this->assertStringContainsString('.pdf', $response->headers->get('Content-Disposition'));
    }

    public function test_report_data_accurately_reflects_database_bookings_and_revenue(): void
    {
        $date = now()->subDays(3);

        $booking = Booking::create([
            'user_id' => $this->customer->id,
            'court_id' => $this->courts['C']->id,
            'booking_date' => $date->format('Y-m-d'),
            'start_time' => '08:00:00',
            'end_time' => '11:00:00', // 3 hours
            'total_price' => 120000,
            'status' => 'confirmed',
        ]);

        Payment::create([
            'booking_id' => $booking->id,
            'invoice_number' => 'INV-ACC-1',
            'amount' => 120000,
            'payment_method' => 'simulasi_ewallet',
            'status' => 'paid',
            'paid_at' => $date,
        ]);

        $reportHelper = new BookingReportPdf(now()->subDays(7), now());
        $data = $reportHelper->getData();

        $this->assertEquals(120000, $data['totalRevenue']);
        $this->assertEquals(1, $data['totalBookings']);
        $this->assertEquals(1, $data['confirmedBookings']);
        $this->assertEquals(0, $data['cancelledBookings']);
        $this->assertEquals(3.0, $data['totalArenaHoursBooked']);

        $courtCStat = collect($data['courtStats'])->firstWhere('name', 'Lapangan C');
        $this->assertNotNull($courtCStat);
        $this->assertEquals(3.0, $courtCStat['hours_booked']);
        $this->assertEquals(1, $courtCStat['bookings_count']);
        $this->assertEquals(120000, $courtCStat['revenue']);
    }
}

