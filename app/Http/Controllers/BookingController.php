<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreBookingRequest;
use App\Models\Booking;
use App\Models\Court;
use App\Models\CourtSchedule;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class BookingController extends Controller
{
    /**
     * Show the booking page with courts list.
     */
    public function create(Request $request): Response
    {
        $courts = Court::where('is_active', true)
            ->orderBy('name')
            ->get()
            ->map(function (Court $court) {
                return [
                    'id' => $court->id,
                    'name' => $court->name,
                    'description' => $court->description,
                    'price_per_hour' => (float) $court->price_per_hour,
                    'image_url' => $court->image_path ? Storage::url($court->image_path) : null,
                ];
            });

        return Inertia::render('Bookings/Create', [
            'courts' => $courts,
            'initialCourtId' => $request->query('court_id') ? (int) $request->query('court_id') : ($courts->first()['id'] ?? null),
            'initialDate' => $request->query('date', now()->format('Y-m-d')),
        ]);
    }

    /**
     * Get 1-hour slot availability for a specific court on a given date.
     */
    public function availability(Request $request, Court $court): JsonResponse
    {
        $dateString = $request->query('date', now()->format('Y-m-d'));
        
        try {
            $date = Carbon::createFromFormat('Y-m-d', $dateString);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Format tanggal tidak valid.'], 422);
        }

        // Get court operating hours for this day of week (0=Sunday .. 6=Saturday)
        $dayOfWeek = $date->dayOfWeek;
        $schedule = CourtSchedule::where('court_id', $court->id)
            ->where('day_of_week', $dayOfWeek)
            ->first();

        // Default open 06:00 to 22:00 if no specific schedule found
        $openTime = $schedule ? substr($schedule->open_time, 0, 5) : '06:00';
        $closeTime = $schedule ? substr($schedule->close_time, 0, 5) : '22:00';

        $openHour = (int) explode(':', $openTime)[0];
        $closeHour = (int) explode(':', $closeTime)[0];

        // Fetch active bookings for this court and date (excluding cancelled)
        $bookings = Booking::where('court_id', $court->id)
            ->where('booking_date', $date->format('Y-m-d'))
            ->where('status', '!=', 'cancelled')
            ->get(['id', 'user_id', 'start_time', 'end_time', 'status']);

        $currentUserId = auth()->id();
        $isToday = $date->isToday();
        $currentTime = now()->format('H:i');

        $slots = [];
        for ($hour = $openHour; $hour < $closeHour; $hour++) {
            $slotStart = sprintf('%02d:00', $hour);
            $slotEnd = sprintf('%02d:00', $hour + 1);

            // Check if slot falls in any existing booking
            $overlappingBooking = $bookings->first(function ($booking) use ($slotStart, $slotEnd) {
                $bStart = substr($booking->start_time, 0, 5);
                $bEnd = substr($booking->end_time, 0, 5);
                return $bStart < $slotEnd && $bEnd > $slotStart;
            });

            if ($overlappingBooking) {
                $status = ($currentUserId && $overlappingBooking->user_id === $currentUserId) ? 'mine' : 'booked';
                $bookingId = $overlappingBooking->id;
            } else {
                // If the slot is in the past for today, mark as unavailable
                if ($isToday && $slotStart <= $currentTime) {
                    $status = 'past';
                } else {
                    $status = 'available';
                }
                $bookingId = null;
            }

            $slots[] = [
                'start_time' => $slotStart,
                'end_time' => $slotEnd,
                'status' => $status,
                'booking_id' => $bookingId,
            ];
        }

        return response()->json([
            'court_id' => $court->id,
            'court_name' => $court->name,
            'price_per_hour' => (float) $court->price_per_hour,
            'date' => $date->format('Y-m-d'),
            'slots' => $slots,
        ]);
    }

    /**
     * Store a new booking with anti-bentrok pessimistic locking.
     */
    public function store(StoreBookingRequest $request)
    {
        $user = $request->user();
        $courtId = (int) $request->validated('court_id');
        $bookingDate = $request->validated('booking_date');
        $startTime = $request->validated('start_time');
        $endTime = $request->validated('end_time');
        $notes = $request->validated('notes');

        $booking = DB::transaction(function () use ($user, $courtId, $bookingDate, $startTime, $endTime, $notes) {
            /**
             * =========================================================================
             * STRATEGI VALIDASI ANTI-BENTROK / RACE CONDITION (PESSIMISTIC LOCKING)
             * =========================================================================
             * Masalah:
             * Jika 2 pengguna menekan tombol "Booking Sekarang" secara bersamaan untuk
             * lapangan dan jam yang sama, kedua request dapat mengecek ketersediaan pada
             * milidetik yang sama sebelum salah satu data tersimpan (phantom read).
             * Akibatnya, kedua request menganggap slot tersebut kosong dan terjadi double-booking.
             *
             * Solusi:
             * 1. DB::transaction(): Menjamin seluruh proses (lock, validasi overlap, insert)
             *    bersifat atomik (ACID).
             * 2. Court::lockForUpdate(): Melakukan exclusive lock pada baris Court yang
             *    bersangkutan di MySQL (SELECT ... FOR UPDATE).
             *    Artinya, request kedua yang mencoba memesan lapangan yang sama HARUS MENUNGGU
             *    (antre) sampai transaksi pertama selesai (commit / rollback).
             * 3. Booking overlap check with lockForUpdate(): Memastikan tidak ada booking aktif
             *    (status != 'cancelled') yang bertabrakan waktu.
             * 4. Jika terjadi bentrok, transaksi langsung memicu ValidationException dan rollback.
             * 5. Keuntungan: Sangat handal mencegah bentrok pada level database tanpa mengunci
             *    lapangan lain (pemesanan lapangan A dan B tetap bisa berjalan paralel).
             * =========================================================================
             */
            $court = Court::where('id', $courtId)
                ->where('is_active', true)
                ->lockForUpdate()
                ->firstOrFail();

            // Cek jadwal operasional lapangan
            $dayOfWeek = Carbon::createFromFormat('Y-m-d', $bookingDate)->dayOfWeek;
            $schedule = CourtSchedule::where('court_id', $court->id)
                ->where('day_of_week', $dayOfWeek)
                ->first();

            $openTime = $schedule ? substr($schedule->open_time, 0, 5) : '06:00';
            $closeTime = $schedule ? substr($schedule->close_time, 0, 5) : '22:00';

            if ($startTime < $openTime || $endTime > $closeTime) {
                throw ValidationException::withMessages([
                    'slots' => "Jam booking harus berada di dalam jam operasional ({$openTime} - {$closeTime}).",
                ]);
            }

            // Cek apakah slot sudah dibooking (overlapping non-cancelled)
            $conflict = Booking::where('court_id', $court->id)
                ->where('booking_date', $bookingDate)
                ->where('status', '!=', 'cancelled')
                ->where(function ($query) use ($startTime, $endTime) {
                    $query->where('start_time', '<', $endTime)
                          ->where('end_time', '>', $startTime);
                })
                ->lockForUpdate()
                ->exists();

            if ($conflict) {
                throw ValidationException::withMessages([
                    'slots' => "Slot waktu yang dipilih ({$startTime} - {$endTime}) sudah dipesan oleh pengguna lain. Silakan pilih slot lain.",
                ]);
            }

            // Hitung durasi dan total harga
            $startCarbon = Carbon::createFromFormat('H:i', $startTime);
            $endCarbon = Carbon::createFromFormat('H:i', $endTime);
            $durationMinutes = $startCarbon->diffInMinutes($endCarbon);
            $hours = $durationMinutes / 60;

            if ($hours <= 0) {
                throw ValidationException::withMessages([
                    'end_time' => 'Durasi booking minimal 1 jam.',
                ]);
            }

            $totalPrice = $hours * (float) $court->price_per_hour;

            return Booking::create([
                'user_id' => $user->id,
                'court_id' => $court->id,
                'booking_date' => $bookingDate,
                'start_time' => $startTime . ':00',
                'end_time' => $endTime . ':00',
                'status' => 'pending',
                'total_price' => $totalPrice,
                'notes' => $notes,
                'is_recurring' => false,
                'recurring_booking_id' => null,
            ]);
        });

        return redirect()->route('my-bookings.index')
            ->with('success', 'Booking berhasil dibuat! Status saat ini menunggu pembayaran (pending).');
    }

    /**
     * Display logged-in user's bookings history.
     */
    public function myBookings(Request $request): Response
    {
        $statusFilter = $request->query('status', 'all');

        $query = $request->user()->bookings()
            ->with('court')
            ->latest('booking_date')
            ->latest('start_time');

        if ($statusFilter && $statusFilter !== 'all') {
            $query->where('status', $statusFilter);
        }

        $bookings = $query->paginate(10)->withQueryString()->through(function (Booking $booking) use ($request) {
            $date = $booking->booking_date instanceof Carbon
                ? $booking->booking_date
                : Carbon::parse($booking->booking_date);

            return [
                'id' => $booking->id,
                'court_name' => $booking->court->name,
                'court_image' => $booking->court->image_path ? Storage::url($booking->court->image_path) : null,
                'booking_date' => $date->format('Y-m-d'),
                'booking_date_formatted' => $date->translatedFormat('d M Y'),
                'start_time' => substr($booking->start_time, 0, 5),
                'end_time' => substr($booking->end_time, 0, 5),
                'status' => $booking->status,
                'total_price' => (float) $booking->total_price,
                'notes' => $booking->notes,
                'can_cancel' => $request->user()->can('cancel', $booking),
                'created_at' => $booking->created_at->format('d M Y H:i'),
            ];
        });

        return Inertia::render('Bookings/MyBookings', [
            'bookings' => $bookings,
            'currentFilter' => $statusFilter,
        ]);
    }

    /**
     * Cancel a booking by the user.
     */
    public function cancel(Booking $booking)
    {
        Gate::authorize('cancel', $booking);

        $booking->update([
            'status' => 'cancelled',
        ]);

        return redirect()->back()
            ->with('success', "Booking #{$booking->id} berhasil dibatalkan.");
    }
}

