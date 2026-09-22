<?php

namespace Tests\Feature;

use App\Events\BookingCancelled;
use App\Events\PaymentCompleted;
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
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class CriticalFlowsIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected User $customer;
    protected User $customerB;
    protected User $admin;
    protected Court $court;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => 'admin']);
        Role::firstOrCreate(['name' => 'user']);

        $this->customer = User::factory()->create(['name' => 'Taufik Hidayat']);
        $this->customer->assignRole('user');

        $this->customerB = User::factory()->create(['name' => 'Lee Chong Wei']);
        $this->customerB->assignRole('user');

        $this->admin = User::factory()->create(['name' => 'Admin Gelanggang']);
        $this->admin->assignRole('admin');

        $this->court = Court::create([
            'name' => 'Lapangan 1 (BWF Standard)',
            'description' => 'Karpet Vinyl 4.5mm PBSI Standard',
            'price_per_hour' => 50000,
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

    /**
     * 1. Critical Flow: Anti-Bentrok Validation
     * Memastikan slot yang sudah dipesan (confirmed atau pending) tidak dapat
     * dipesan kembali oleh pelanggan lain (baik slot identik maupun tumpang tindih).
     */
    public function test_critical_flow_anti_bentrok_prevents_double_booking_and_slot_overlap(): void
    {
        $targetDate = now()->addDays(2)->format('Y-m-d');

        // Customer 1 memesan jam 18:00 - 20:00
        $response1 = $this->actingAs($this->customer)->post(route('bookings.store'), [
            'court_id' => $this->court->id,
            'booking_date' => $targetDate,
            'start_time' => '18:00',
            'end_time' => '20:00',
            'notes' => 'Latihan PB Djarum',
        ]);

        $response1->assertRedirect();
        $this->assertDatabaseHas('bookings', [
            'court_id' => $this->court->id,
            'booking_date' => $targetDate,
            'start_time' => '18:00:00',
            'end_time' => '20:00:00',
            'status' => 'pending',
        ]);

        // Skenario A: Customer 2 mencoba memesan persis jam yang sama (18:00 - 20:00)
        $conflictExact = $this->actingAs($this->customerB)->post(route('bookings.store'), [
            'court_id' => $this->court->id,
            'booking_date' => $targetDate,
            'start_time' => '18:00',
            'end_time' => '20:00',
        ]);
        $conflictExact->assertSessionHasErrors(['slots']);

        // Skenario B: Customer 2 mencoba memesan jam yang tumpang tindih parsial (19:00 - 21:00)
        $conflictOverlap = $this->actingAs($this->customerB)->post(route('bookings.store'), [
            'court_id' => $this->court->id,
            'booking_date' => $targetDate,
            'start_time' => '19:00',
            'end_time' => '21:00',
        ]);
        $conflictOverlap->assertSessionHasErrors(['slots']);

        // Skenario C: Customer 2 memesan slot yang TIDAK bentrok (20:00 - 22:00) -> HARUS BERHASIL
        $nonConflict = $this->actingAs($this->customerB)->post(route('bookings.store'), [
            'court_id' => $this->court->id,
            'booking_date' => $targetDate,
            'start_time' => '20:00',
            'end_time' => '22:00',
        ]);
        $nonConflict->assertRedirect();
        $this->assertDatabaseHas('bookings', [
            'court_id' => $this->court->id,
            'booking_date' => $targetDate,
            'start_time' => '20:00:00',
            'end_time' => '22:00:00',
            'user_id' => $this->customerB->id,
        ]);
    }

    /**
     * 2. Critical Flow: Auto-Expire Pending Booking
     * Memastikan booking yang tidak dibayar dalam batas waktu (15 menit)
     * otomatis dibatalkan via scheduler command dan slotnya terbuka kembali untuk user lain.
     */
    public function test_critical_flow_stale_booking_auto_expires_and_releases_slot_availability(): void
    {
        Event::fake([BookingCancelled::class]);
        Notification::fake();

        $targetDate = now()->addDays(3)->format('Y-m-d');

        // Buat pending booking yang usianya sudah 20 menit yang lalu (melebihi batas 15 menit)
        $staleBooking = Booking::create([
            'user_id' => $this->customer->id,
            'court_id' => $this->court->id,
            'booking_date' => $targetDate,
            'start_time' => '14:00:00',
            'end_time' => '16:00:00',
            'status' => 'pending',
            'total_price' => 100000,
        ]);
        $staleBooking->created_at = now()->subMinutes(20);
        $staleBooking->save();

        $stalePayment = Payment::create([
            'booking_id' => $staleBooking->id,
            'amount' => 100000,
            'method' => 'simulasi_transfer',
            'status' => 'pending',
            'invoice_number' => Payment::generateInvoiceNumber(),
        ]);

        // Jalankan perintah artisan pembersihan booking kadaluarsa
        $this->artisan('bookings:expire-pending')
            ->assertExitCode(0);

        // Verifikasi status booking dan payment berubah
        $this->assertEquals('cancelled', $staleBooking->fresh()->status);
        $this->assertEquals('expired', $stalePayment->fresh()->status);
        Event::assertDispatched(BookingCancelled::class);

        // Sekarang pelanggan lain dapat memesan kembali slot 14:00 - 16:00 tersebut tanpa bentrok
        $rebookResponse = $this->actingAs($this->customerB)->post(route('bookings.store'), [
            'court_id' => $this->court->id,
            'booking_date' => $targetDate,
            'start_time' => '14:00',
            'end_time' => '16:00',
        ]);
        $rebookResponse->assertRedirect();
        $this->assertDatabaseHas('bookings', [
            'court_id' => $this->court->id,
            'booking_date' => $targetDate,
            'start_time' => '14:00:00',
            'end_time' => '16:00:00',
            'status' => 'pending',
            'user_id' => $this->customerB->id,
        ]);
    }

    /**
     * 3. Critical Flow: Points & Tier Loyalty Progression
     * Memastikan setiap pembayaran berhasil mengkreditkan poin dengan benar,
     * otomatis menaikkan tier membership (Bronze -> Silver -> Gold),
     * serta menerapkan benefit diskon ke booking berikutnya.
     */
    public function test_critical_flow_points_award_and_tier_auto_upgrade_upon_payment_success(): void
    {
        // 1. User baru otomatis memiliki membership Bronze (0 poin)
        $membership = Membership::where('user_id', $this->customer->id)->first();
        $this->assertNotNull($membership);
        $this->assertEquals('bronze', $membership->tier);
        $this->assertEquals(0, $membership->points);
        $this->assertEquals(0, $membership->discount_percentage);

        // 2. Simulasi pembayaran senilai Rp 1.200.000 (menghasilkan 120 poin -> naik ke Silver)
        $bookingSilver = Booking::create([
            'user_id' => $this->customer->id,
            'court_id' => $this->court->id,
            'booking_date' => now()->addDays(4)->format('Y-m-d'),
            'start_time' => '10:00:00',
            'end_time' => '12:00:00',
            'status' => 'pending',
            'total_price' => 1200000,
        ]);

        $paymentSilver = Payment::create([
            'booking_id' => $bookingSilver->id,
            'amount' => 1200000,
            'method' => 'simulasi_transfer',
            'status' => 'pending',
            'invoice_number' => Payment::generateInvoiceNumber(),
        ]);

        event(new PaymentCompleted($paymentSilver));

        $membership->refresh();
        $this->assertEquals(120, $membership->points);
        $this->assertEquals('silver', $membership->tier);
        $this->assertEquals(5, $membership->discount_percentage);

        // Verifikasi riwayat poin tercatat
        $this->assertDatabaseHas('point_histories', [
            'user_id' => $this->customer->id,
            'booking_id' => $bookingSilver->id,
            'points_earned' => 120,
        ]);

        // 3. Booking berikutnya mendapatkan diskon Silver 5%
        // Lapangan 50.000/jam x 2 jam = 100.000. Diskon 5% = 5.000 -> Total = 95.000
        $discountBookingResponse = $this->actingAs($this->customer)->post(route('bookings.store'), [
            'court_id' => $this->court->id,
            'booking_date' => now()->addDays(5)->format('Y-m-d'),
            'start_time' => '08:00',
            'end_time' => '10:00',
        ]);
        $discountBookingResponse->assertRedirect();

        $discountBooking = Booking::where('user_id', $this->customer->id)
            ->where('booking_date', now()->addDays(5)->format('Y-m-d'))
            ->first();
        $this->assertEquals(95000, (float) $discountBooking->total_price);

        // 4. Pembayaran tambahan senilai Rp 2.000.000 (menghasilkan 200 poin -> total 320 -> naik ke Gold)
        $paymentGold = Payment::create([
            'booking_id' => $discountBooking->id,
            'amount' => 2000000,
            'method' => 'simulasi_ewallet',
            'status' => 'pending',
            'invoice_number' => Payment::generateInvoiceNumber(),
        ]);

        event(new PaymentCompleted($paymentGold));

        $membership->refresh();
        $this->assertEquals(320, $membership->points);
        $this->assertEquals('gold', $membership->tier);
        $this->assertEquals(10, $membership->discount_percentage);
    }

    /**
     * 4. Critical Flow: Recurring Booking Generation with Conflict Skipping
     * Memastikan recurring booking mingguan meng-generate seluruh minggu yang tersedia
     * dan otomatis men-skip (tidak menggagalkan seluruh rentang) minggu yang bentrok.
     */
    public function test_critical_flow_recurring_booking_generates_correct_sessions_and_skips_conflicted_weeks(): void
    {
        // Tetapkan 4 hari Senin berturut-turut
        $monday1 = now()->next(Carbon::MONDAY);
        $monday2 = $monday1->copy()->addWeek();
        $monday3 = $monday1->copy()->addWeeks(2);
        $monday4 = $monday1->copy()->addWeeks(3);

        // Buat booking bentrok di Monday ke-2 oleh Customer B
        Booking::create([
            'user_id' => $this->customerB->id,
            'court_id' => $this->court->id,
            'booking_date' => $monday2->format('Y-m-d'),
            'start_time' => '19:00:00',
            'end_time' => '20:00:00',
            'status' => 'confirmed',
            'total_price' => 50000,
        ]);

        // Customer A membuat recurring booking untuk 4 minggu (Monday 1 s/d Monday 4)
        $response = $this->actingAs($this->customer)->post(route('bookings.store'), [
            'court_id' => $this->court->id,
            'booking_date' => $monday1->format('Y-m-d'),
            'is_recurring' => true,
            'recurring_end_date' => $monday4->format('Y-m-d'),
            'start_time' => '19:00',
            'end_time' => '20:00',
            'notes' => 'Rutin Senin Malam',
        ]);

        $response->assertRedirect();
        $this->assertTrue(session()->has('success') || session()->has('error') || session()->has('skipped_dates'));

        // Pastikan record induk recurring_bookings terbentuk
        $recurring = RecurringBooking::where('user_id', $this->customer->id)->first();
        $this->assertNotNull($recurring);
        $this->assertCount(3, $recurring->bookings); // 3 sukses dari 4 minggu

        // Pastikan hanya 3 booking sesi anak yang terbentuk (Monday 1, 3, dan 4)
        $createdBookings = Booking::where('recurring_booking_id', $recurring->id)->get();
        $this->assertCount(3, $createdBookings);

        $dates = $createdBookings->pluck('booking_date')->map(fn ($d) => Carbon::parse($d)->format('Y-m-d'))->toArray();
        $this->assertContains($monday1->format('Y-m-d'), $dates);
        $this->assertNotContains($monday2->format('Y-m-d'), $dates); // Harus di-skip!
        $this->assertContains($monday3->format('Y-m-d'), $dates);
        $this->assertContains($monday4->format('Y-m-d'), $dates);
    }

    /**
     * 5. Critical Flow: Role-Based Authorization
     * Memastikan seluruh endpoint admin dilarang (403 Forbidden) untuk role user biasa.
     */
    public function test_critical_flow_admin_endpoints_strictly_forbidden_for_regular_users(): void
    {
        // Regular user mencoba akses dashboard admin -> 403
        $this->actingAs($this->customer)
            ->get(route('admin.dashboard'))
            ->assertForbidden();

        // Regular user mencoba akses manajemen lapangan admin -> 403
        $this->actingAs($this->customer)
            ->get(route('admin.courts.index'))
            ->assertForbidden();

        // Regular user mencoba tambah lapangan baru -> 403
        $this->actingAs($this->customer)
            ->post(route('admin.courts.store'), [
                'name' => 'Lapangan Ilegal',
                'price_per_hour' => 10000,
            ])
            ->assertForbidden();

        // Regular user mencoba akses laporan admin -> 403
        $this->actingAs($this->customer)
            ->get(route('admin.reports.index'))
            ->assertForbidden();

        // Regular user mencoba unduh file export admin -> 403
        $this->actingAs($this->customer)
            ->get(route('admin.reports.export.excel'))
            ->assertForbidden();

        $this->actingAs($this->customer)
            ->get(route('admin.reports.export.pdf'))
            ->assertForbidden();

        // Admin yang sah dapat mengakses seluruh endpoint di atas tanpa hambatan (200 OK)
        $this->actingAs($this->admin)
            ->get(route('admin.dashboard'))
            ->assertOk();

        $this->actingAs($this->admin)
            ->get(route('admin.courts.index'))
            ->assertOk();

        $this->actingAs($this->admin)
            ->get(route('admin.reports.index'))
            ->assertOk();
    }
}
