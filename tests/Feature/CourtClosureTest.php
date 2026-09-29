<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Court;
use App\Models\CourtClosure;
use App\Models\CourtSchedule;
use App\Models\RecurringBooking;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class CourtClosureTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $customer;
    protected Court $courtA;
    protected Court $courtB;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => 'admin']);
        Role::firstOrCreate(['name' => 'user']);

        $this->admin = User::factory()->create();
        $this->admin->assignRole('admin');

        $this->customer = User::factory()->create();
        $this->customer->assignRole('user');

        $this->courtA = Court::create([
            'name' => 'Lapangan A',
            'description' => 'Karpet Vinyl BWF Standard A',
            'price_per_hour' => 45000,
            'is_active' => true,
        ]);

        $this->courtB = Court::create([
            'name' => 'Lapangan B',
            'description' => 'Karpet Vinyl BWF Standard B',
            'price_per_hour' => 45000,
            'is_active' => true,
        ]);

        // Schedules for all 7 days for both courts
        foreach ([$this->courtA, $this->courtB] as $court) {
            for ($day = 0; $day <= 6; $day++) {
                CourtSchedule::create([
                    'court_id' => $court->id,
                    'day_of_week' => $day,
                    'open_time' => '06:00:00',
                    'close_time' => '22:00:00',
                ]);
            }
        }
    }

    public function test_admin_can_view_closures_index_page(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.closures.index'));

        $response->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Closures/Index')
                ->has('closures')
                ->has('courts')
                ->has('summary')
            );
    }

    public function test_customer_cannot_access_closures_admin_page(): void
    {
        $response = $this->actingAs($this->customer)->get(route('admin.closures.index'));

        $response->assertForbidden();
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $response = $this->get(route('admin.closures.index'));

        $response->assertRedirect(route('login'));
    }

    public function test_admin_can_create_multi_day_tournament_closure_for_all_courts(): void
    {
        $startDate = now()->addDays(3)->format('Y-m-d');
        $endDate = now()->addDays(4)->format('Y-m-d'); // 2 full days

        $payload = [
            'name' => 'Turnamen Antar Klub Regional 2026',
            'type' => 'tournament',
            'court_id' => null, // applies to all courts
            'start_date' => $startDate,
            'end_date' => $endDate,
            'start_time' => '06:00',
            'end_time' => '22:00',
            'notes' => 'Kejuaraan resmi PBSI daerah, semua lapangan dibooking panitia.',
        ];

        $response = $this->actingAs($this->admin)->post(route('admin.closures.store'), $payload);

        $response->assertRedirect(route('admin.closures.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('court_closures', [
            'name' => 'Turnamen Antar Klub Regional 2026',
            'type' => 'tournament',
            'court_id' => null,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'created_by' => $this->admin->id,
        ]);
    }

    public function test_admin_can_create_single_court_maintenance_closure(): void
    {
        $targetDate = now()->addDays(2)->format('Y-m-d');

        $payload = [
            'name' => 'Perbaikan Karpet Lapangan A',
            'type' => 'maintenance',
            'court_id' => $this->courtA->id,
            'start_date' => $targetDate,
            'end_date' => $targetDate,
            'start_time' => '08:00',
            'end_time' => '12:00',
            'notes' => 'Pengeleman ulang garis karpet vinyl',
        ];

        $response = $this->actingAs($this->admin)->post(route('admin.closures.store'), $payload);

        $response->assertRedirect(route('admin.closures.index'));
        $this->assertDatabaseHas('court_closures', [
            'name' => 'Perbaikan Karpet Lapangan A',
            'court_id' => $this->courtA->id,
            'type' => 'maintenance',
            'start_date' => $targetDate,
            'end_date' => $targetDate,
        ]);
    }

    public function test_closure_validation_rejects_invalid_date_range(): void
    {
        $payload = [
            'name' => 'Jadwal Salah',
            'type' => 'tournament',
            'court_id' => null,
            'start_date' => now()->addDays(5)->format('Y-m-d'),
            'end_date' => now()->addDays(3)->format('Y-m-d'), // end_date before start_date
            'start_time' => '08:00',
            'end_time' => '17:00',
        ];

        $response = $this->actingAs($this->admin)->post(route('admin.closures.store'), $payload);

        $response->assertSessionHasErrors(['end_date']);
    }

    public function test_admin_can_delete_court_closure(): void
    {
        $closure = CourtClosure::create([
            'name' => 'Turnamen Mini Batal',
            'type' => 'tournament',
            'court_id' => $this->courtA->id,
            'start_date' => now()->addDays(3)->format('Y-m-d'),
            'end_date' => now()->addDays(3)->format('Y-m-d'),
            'start_time' => '08:00:00',
            'end_time' => '16:00:00',
            'created_by' => $this->admin->id,
        ]);

        $response = $this->actingAs($this->admin)->delete(route('admin.closures.destroy', $closure));

        $response->assertRedirect(route('admin.closures.index'));
        $this->assertDatabaseMissing('court_closures', ['id' => $closure->id]);
    }

    public function test_availability_marks_slots_as_closed_with_closure_details(): void
    {
        $targetDate = now()->addDays(2)->format('Y-m-d');

        CourtClosure::create([
            'name' => 'Piala Walikota Cup 2026',
            'type' => 'tournament',
            'court_id' => $this->courtA->id,
            'start_date' => $targetDate,
            'end_date' => $targetDate,
            'start_time' => '08:00:00',
            'end_time' => '14:00:00',
            'created_by' => $this->admin->id,
        ]);

        $response = $this->actingAs($this->customer)->getJson(
            route('courts.availability', $this->courtA->id) . "?date={$targetDate}"
        );

        $response->assertOk();
        $data = $response->json();

        $this->assertNotNull($data['active_closure']);
        $this->assertEquals('Piala Walikota Cup 2026', $data['active_closure']['name']);
        $this->assertEquals('tournament', $data['active_closure']['type']);

        $slots = collect($data['slots']);

        // 08:00 - 09:00 should be closed
        $closedSlot = $slots->firstWhere('start_time', '08:00');
        $this->assertNotNull($closedSlot);
        $this->assertEquals('closed', $closedSlot['status']);
        $this->assertEquals('Piala Walikota Cup 2026', $closedSlot['closure_name']);
        $this->assertEquals('tournament', $closedSlot['closure_type']);

        // 13:00 - 14:00 should also be closed
        $closedSlot2 = $slots->firstWhere('start_time', '13:00');
        $this->assertEquals('closed', $closedSlot2['status']);

        // 14:00 - 15:00 should be available (closure ended at 14:00)
        $availSlot = $slots->firstWhere('start_time', '14:00');
        $this->assertEquals('available', $availSlot['status']);
    }

    public function test_global_closure_applies_to_all_courts(): void
    {
        $targetDate = now()->addDays(2)->format('Y-m-d');

        // Global closure (court_id = null)
        CourtClosure::create([
            'name' => 'Kejurda Badminton Se-Jawa',
            'type' => 'tournament',
            'court_id' => null,
            'start_date' => $targetDate,
            'end_date' => $targetDate,
            'start_time' => '06:00:00',
            'end_time' => '22:00:00',
            'created_by' => $this->admin->id,
        ]);

        // Check Court A
        $resA = $this->actingAs($this->customer)->getJson(
            route('courts.availability', $this->courtA->id) . "?date={$targetDate}"
        );
        $resA->assertOk();
        $this->assertEquals('Kejurda Badminton Se-Jawa', $resA->json('active_closure.name'));
        $this->assertEquals('closed', collect($resA->json('slots'))->first()['status']);

        // Check Court B
        $resB = $this->actingAs($this->customer)->getJson(
            route('courts.availability', $this->courtB->id) . "?date={$targetDate}"
        );
        $resB->assertOk();
        $this->assertEquals('Kejurda Badminton Se-Jawa', $resB->json('active_closure.name'));
        $this->assertEquals('closed', collect($resB->json('slots'))->first()['status']);
    }

    public function test_user_cannot_book_during_tournament_closure(): void
    {
        $targetDate = now()->addDays(2)->format('Y-m-d');

        CourtClosure::create([
            'name' => 'Turnamen Smash Arena Championship',
            'type' => 'tournament',
            'court_id' => $this->courtA->id,
            'start_date' => $targetDate,
            'end_date' => $targetDate,
            'start_time' => '08:00:00',
            'end_time' => '17:00:00',
            'created_by' => $this->admin->id,
        ]);

        $payload = [
            'court_id' => $this->courtA->id,
            'booking_date' => $targetDate,
            'start_time' => '10:00',
            'end_time' => '12:00',
            'notes' => 'Mencoba booking saat turnamen',
        ];

        $response = $this->actingAs($this->customer)->post(route('bookings.store'), $payload);

        $response->assertSessionHasErrors(['slots']);
        $this->assertDatabaseMissing('bookings', [
            'court_id' => $this->courtA->id,
            'booking_date' => $targetDate,
            'start_time' => '10:00:00',
        ]);
    }

    public function test_user_can_book_outside_closure_hours(): void
    {
        $targetDate = now()->addDays(2)->format('Y-m-d');

        CourtClosure::create([
            'name' => 'Turnamen Siang',
            'type' => 'tournament',
            'court_id' => $this->courtA->id,
            'start_date' => $targetDate,
            'end_date' => $targetDate,
            'start_time' => '08:00:00',
            'end_time' => '16:00:00',
            'created_by' => $this->admin->id,
        ]);

        $payload = [
            'court_id' => $this->courtA->id,
            'booking_date' => $targetDate,
            'start_time' => '17:00',
            'end_time' => '19:00',
            'notes' => 'Booking malam setelah turnamen',
        ];

        $response = $this->actingAs($this->customer)->post(route('bookings.store'), $payload);

        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('bookings', [
            'court_id' => $this->courtA->id,
            'booking_date' => $targetDate,
            'start_time' => '17:00:00',
            'end_time' => '19:00:00',
            'status' => 'pending',
        ]);
    }

    public function test_recurring_booking_skips_dates_with_tournament_closures(): void
    {
        $startDate = now()->addDays(2)->format('Y-m-d');
        $endDate = Carbon::parse($startDate)->addWeeks(3)->format('Y-m-d'); // 4 weeks total

        // Set closure on Week 2
        $week2Date = Carbon::parse($startDate)->addWeek()->format('Y-m-d');

        CourtClosure::create([
            'name' => 'Kejurcab PBSI Pekan 2',
            'type' => 'tournament',
            'court_id' => $this->courtA->id,
            'start_date' => $week2Date,
            'end_date' => $week2Date,
            'start_time' => '06:00:00',
            'end_time' => '22:00:00',
            'created_by' => $this->admin->id,
        ]);

        $payload = [
            'court_id' => $this->courtA->id,
            'booking_date' => $startDate,
            'start_time' => '08:00',
            'end_time' => '10:00',
            'is_recurring' => true,
            'recurring_end_date' => $endDate,
        ];

        $response = $this->actingAs($this->customer)->post(route('bookings.store'), $payload);

        $response->assertSessionHasNoErrors();

        // Total 4 candidate weeks, 1 was closed => 3 bookings created
        $recurring = RecurringBooking::where('court_id', $this->courtA->id)->first();
        $this->assertNotNull($recurring);

        $bookings = Booking::where('recurring_booking_id', $recurring->id)->get();
        $this->assertCount(3, $bookings);

        $datesBooked = $bookings->pluck('booking_date')->map(fn ($d) => $d->format('Y-m-d'))->toArray();

        // Week 2 must NOT have a booking
        $this->assertNotContains($week2Date, $datesBooked);

        // Week 1, 3, 4 must have bookings
        $this->assertContains($startDate, $datesBooked);
        $this->assertContains(Carbon::parse($startDate)->addWeeks(2)->format('Y-m-d'), $datesBooked);
        $this->assertContains(Carbon::parse($startDate)->addWeeks(3)->format('Y-m-d'), $datesBooked);
    }

    public function test_court_search_excludes_closed_courts(): void
    {
        $targetDate = now()->addDays(3)->format('Y-m-d');

        // Close court A for tournament
        CourtClosure::create([
            'name' => 'Turnamen Lapangan A',
            'type' => 'tournament',
            'court_id' => $this->courtA->id,
            'start_date' => $targetDate,
            'end_date' => $targetDate,
            'start_time' => '08:00:00',
            'end_time' => '18:00:00',
            'created_by' => $this->admin->id,
        ]);

        $response = $this->actingAs($this->customer)->get(route('courts.search', [
            'date' => $targetDate,
            'start_time' => '09:00',
            'end_time' => '11:00',
        ]));

        $response->assertOk();

        // In Inertia props, courts should contain Court B, but not Court A
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Courts/Search')
            ->has('courts', 1)
            ->where('courts.0.id', $this->courtB->id)
        );
    }
}
