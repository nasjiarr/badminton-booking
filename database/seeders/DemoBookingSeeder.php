<?php

namespace Database\Seeders;

use App\Models\Booking;
use App\Models\Court;
use App\Models\Membership;
use App\Models\Payment;
use App\Models\PointHistory;
use App\Models\RecurringBooking;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DemoBookingSeeder extends Seeder
{
    /**
     * Run the demo booking database seeder.
     * Generates 100+ realistic bookings with payments, recurring series, and points
     * across 35 days (past 30 days, today, next 4 days) to power portfolio charts & dashboard.
     */
    public function run(): void
    {
        $courts = Court::where('is_active', true)->get();
        if ($courts->isEmpty()) {
            $this->call(CourtSeeder::class);
            $courts = Court::where('is_active', true)->get();
        }

        // 1. Seed additional customer players with realistic Indonesian badminton profiles
        $playerData = [
            ['name' => 'Fajar Alfian', 'email' => 'fajar@badminton.test', 'points' => 380, 'tier' => 'gold'],
            ['name' => 'Anthony Ginting', 'email' => 'ginting@badminton.test', 'points' => 420, 'tier' => 'gold'],
            ['name' => 'Kevin Sanjaya', 'email' => 'kevin@badminton.test', 'points' => 290, 'tier' => 'silver'],
            ['name' => 'Hendra Setiawan', 'email' => 'hendra@badminton.test', 'points' => 190, 'tier' => 'silver'],
            ['name' => 'Greysia Polii', 'email' => 'greysia@badminton.test', 'points' => 140, 'tier' => 'silver'],
            ['name' => 'Dewi Lestari', 'email' => 'dewi@badminton.test', 'points' => 60, 'tier' => 'bronze'],
            ['name' => 'Rizky Pratama', 'email' => 'rizky@badminton.test', 'points' => 40, 'tier' => 'bronze'],
            ['name' => 'Ahmad Fauzi', 'email' => 'ahmad@badminton.test', 'points' => 10, 'tier' => 'bronze'],
        ];

        $users = collect();
        // Also fetch existing users (Budi Santoso & Siti Rahma)
        $existingUsers = User::whereHas('roles', fn ($q) => $q->where('name', 'user'))->get();
        foreach ($existingUsers as $eu) {
            $users->push($eu);
        }

        foreach ($playerData as $p) {
            $user = User::firstOrCreate(
                ['email' => $p['email']],
                [
                    'name' => $p['name'],
                    'password' => Hash::make('password'),
                    'email_verified_at' => now(),
                ]
            );
            if (!$user->hasRole('user')) {
                $user->assignRole('user');
            }
            Membership::updateOrCreate(
                ['user_id' => $user->id],
                ['points' => $p['points'], 'tier' => $p['tier']]
            );
            $users->push($user);
        }

        // Slot schedule options: 1-hour slots from 07:00 to 21:00
        $standardSlots = [
            ['07:00:00', '08:00:00'],
            ['08:00:00', '09:00:00'],
            ['09:00:00', '10:00:00'],
            ['10:00:00', '11:00:00'],
            ['14:00:00', '15:00:00'],
            ['15:00:00', '16:00:00'],
            ['16:00:00', '17:00:00'],
            ['17:00:00', '18:00:00'],
            ['18:00:00', '19:00:00'],
            ['19:00:00', '20:00:00'],
            ['20:00:00', '21:00:00'],
        ];

        // Track occupied slots to prevent duplicate booking in seeder: court_id => date => [start_time => true]
        $occupiedSlots = [];

        // Helper to check and reserve slot
        $tryReserve = function ($courtId, $dateStr, $startTime) use (&$occupiedSlots) {
            if (isset($occupiedSlots[$courtId][$dateStr][$startTime])) {
                return false;
            }
            $occupiedSlots[$courtId][$dateStr][$startTime] = true;
            return true;
        };

        $startDate = Carbon::today()->subDays(29);
        $endDate = Carbon::today()->addDays(4);
        $invoiceSeq = 1000;

        // 2. Generate 2 recurring booking series first
        $recurringCustomer1 = $users->firstWhere('email', 'kevin@badminton.test') ?? $users->first();
        $recurringCustomer2 = $users->firstWhere('email', 'fajar@badminton.test') ?? $users->last();

        // Series 1: 4 weeks recurring on Lapangan A
        $courtA = $courts->first();
        $rec1Start = Carbon::today()->subWeeks(3)->startOfWeek()->addDays(1); // Tuesday 3 weeks ago
        $rec1End = $rec1Start->copy()->addWeeks(3);

        $recRecord1 = RecurringBooking::create([
            'user_id' => $recurringCustomer1->id,
            'court_id' => $courtA->id,
            'day_of_week' => 2, // Tuesday
            'start_time' => '19:00:00',
            'end_time' => '21:00:00',
            'start_date' => $rec1Start->format('Y-m-d'),
            'end_date' => $rec1End->format('Y-m-d'),
            'status' => 'active',
        ]);

        for ($w = 0; $w < 4; $w++) {
            $sessDate = $rec1Start->copy()->addWeeks($w);
            $dateStr = $sessDate->format('Y-m-d');
            $tryReserve($courtA->id, $dateStr, '19:00:00');
            $tryReserve($courtA->id, $dateStr, '20:00:00');

            $sessPrice = $courtA->price_per_hour * 2;
            $isPast = $sessDate->isPast();

            $booking = Booking::create([
                'user_id' => $recurringCustomer1->id,
                'court_id' => $courtA->id,
                'booking_date' => $dateStr,
                'start_time' => '19:00:00',
                'end_time' => '21:00:00',
                'status' => 'confirmed',
                'total_price' => $sessPrice,
                'notes' => 'Sesi rutin mingguan #'.($w + 1),
                'is_recurring' => true,
                'recurring_booking_id' => $recRecord1->id,
                'created_at' => $rec1Start->copy()->subDays(2),
            ]);

            $invoiceSeq++;
            Payment::create([
                'booking_id' => $booking->id,
                'amount' => $sessPrice,
                'method' => 'simulasi_transfer',
                'status' => 'paid',
                'paid_at' => $rec1Start->copy()->subDays(1),
                'invoice_number' => 'INV-REC-' . sprintf('%05d', $invoiceSeq),
                'created_at' => $rec1Start->copy()->subDays(2),
            ]);
        }

        // Series 2: 4 weeks recurring on Lapangan B
        $courtB = $courts->get(1) ?? $courtA;
        $rec2Start = Carbon::today()->subWeeks(2)->startOfWeek()->addDays(3); // Thursday 2 weeks ago
        $rec2End = $rec2Start->copy()->addWeeks(3);

        $recRecord2 = RecurringBooking::create([
            'user_id' => $recurringCustomer2->id,
            'court_id' => $courtB->id,
            'day_of_week' => 4, // Thursday
            'start_time' => '20:00:00',
            'end_time' => '21:00:00',
            'start_date' => $rec2Start->format('Y-m-d'),
            'end_date' => $rec2End->format('Y-m-d'),
            'status' => 'active',
        ]);

        for ($w = 0; $w < 4; $w++) {
            $sessDate = $rec2Start->copy()->addWeeks($w);
            $dateStr = $sessDate->format('Y-m-d');
            $tryReserve($courtB->id, $dateStr, '20:00:00');

            $sessPrice = $courtB->price_per_hour;
            $booking = Booking::create([
                'user_id' => $recurringCustomer2->id,
                'court_id' => $courtB->id,
                'booking_date' => $dateStr,
                'start_time' => '20:00:00',
                'end_time' => '21:00:00',
                'status' => 'confirmed',
                'total_price' => $sessPrice,
                'notes' => 'Rutin Sparring Kamis Malam #'.($w + 1),
                'is_recurring' => true,
                'recurring_booking_id' => $recRecord2->id,
                'created_at' => $rec2Start->copy()->subDays(2),
            ]);

            $invoiceSeq++;
            Payment::create([
                'booking_id' => $booking->id,
                'amount' => $sessPrice,
                'method' => 'simulasi_ewallet',
                'status' => 'paid',
                'paid_at' => $rec2Start->copy()->subDays(1),
                'invoice_number' => 'INV-REC-' . sprintf('%05d', $invoiceSeq),
                'created_at' => $rec2Start->copy()->subDays(2),
            ]);
        }

        // 3. Generate distributed individual bookings across all days
        $current = $startDate->copy();
        $notesPool = [
            'Latihan persiapan turnamen lokal',
            'Main santai bersama teman kantor',
            'Latihan fisik & kelincahan',
            'Sparring ganda putra',
            'Drill netting & smash',
            'Main keluarga akhir pekan',
            'Ganda campuran latihan rutin',
            null,
            null,
        ];

        while ($current->lte($endDate)) {
            $dateStr = $current->format('Y-m-d');
            $isToday = $current->isToday();
            $isPast = $current->isPast() && !$isToday;
            $isWeekend = $current->isWeekend();

            // Target bookings for this date (more bookings on weekends and today for lively dashboard)
            $bookingsCount = $isToday ? 8 : ($isWeekend ? rand(4, 7) : rand(3, 5));

            for ($i = 0; $i < $bookingsCount; $i++) {
                $court = $courts->random();
                $slot = $standardSlots[array_rand($standardSlots)];
                $startTime = $slot[0];
                $endTime = $slot[1];

                if (!$tryReserve($court->id, $dateStr, $startTime)) {
                    continue; // Skip if this slot was already booked
                }

                $user = $users->random();
                $discount = $user->membership ? $user->membership->discount_percentage : 0;
                $rawPrice = $court->price_per_hour;
                $finalPrice = max(0, $rawPrice - ($rawPrice * $discount / 100));

                // Determine realistic status based on time
                if ($isPast) {
                    // Past bookings: 88% confirmed & paid, 8% cancelled, 4% expired
                    $rand = rand(1, 100);
                    if ($rand <= 88) {
                        $status = 'confirmed';
                        $paymentStatus = 'paid';
                    } elseif ($rand <= 96) {
                        $status = 'cancelled';
                        $paymentStatus = 'failed';
                    } else {
                        $status = 'cancelled';
                        $paymentStatus = 'expired';
                    }
                } elseif ($isToday) {
                    // Today bookings: 75% confirmed & paid, 15% pending, 10% cancelled
                    $rand = rand(1, 100);
                    if ($rand <= 75) {
                        $status = 'confirmed';
                        $paymentStatus = 'paid';
                    } elseif ($rand <= 90) {
                        $status = 'pending';
                        $paymentStatus = 'pending';
                    } else {
                        $status = 'cancelled';
                        $paymentStatus = 'failed';
                    }
                } else {
                    // Future bookings: 60% confirmed, 40% pending
                    if (rand(1, 10) <= 6) {
                        $status = 'confirmed';
                        $paymentStatus = 'paid';
                    } else {
                        $status = 'pending';
                        $paymentStatus = 'pending';
                    }
                }

                $createdAt = $current->copy()->subDays(rand(1, 5))->setTime(rand(8, 20), rand(0, 59));
                $booking = Booking::create([
                    'user_id' => $user->id,
                    'court_id' => $court->id,
                    'booking_date' => $dateStr,
                    'start_time' => $startTime,
                    'end_time' => $endTime,
                    'status' => $status,
                    'total_price' => $finalPrice,
                    'notes' => $notesPool[array_rand($notesPool)],
                    'is_recurring' => false,
                    'created_at' => $createdAt,
                    'updated_at' => $createdAt,
                ]);

                $invoiceSeq++;
                $method = (rand(1, 10) <= 5) ? 'simulasi_transfer' : 'simulasi_ewallet';
                $paidAt = ($paymentStatus === 'paid')
                    ? ($isToday ? now()->subMinutes(rand(10, 300)) : $createdAt->copy()->addMinutes(rand(5, 45)))
                    : null;

                $payment = Payment::create([
                    'booking_id' => $booking->id,
                    'amount' => $finalPrice,
                    'method' => $method,
                    'status' => $paymentStatus,
                    'paid_at' => $paidAt,
                    'invoice_number' => 'INV-' . $current->format('Ymd') . '-' . sprintf('%04d', $invoiceSeq % 10000),
                    'created_at' => $createdAt,
                    'updated_at' => $paidAt ?? $createdAt,
                ]);

                // Create point history for paid confirmed bookings
                if ($paymentStatus === 'paid' && $status === 'confirmed') {
                    $earned = (int) floor($finalPrice / 10000);
                    if ($earned > 0) {
                        PointHistory::create([
                            'user_id' => $user->id,
                            'booking_id' => $booking->id,
                            'points_earned' => $earned,
                            'points_used' => 0,
                            'description' => 'Poin booking ' . $court->name . ' (' . $dateStr . ')',
                            'created_at' => $paidAt,
                            'updated_at' => $paidAt,
                        ]);
                    }
                }
            }

            $current->addDay();
        }
    }
}

