<?php

namespace App\Http\Controllers;

use App\Events\BookingCancelled;
use App\Events\BookingCreated;
use App\Http\Requests\StoreBookingRequest;
use App\Models\Booking;
use App\Models\Court;
use App\Models\CourtSchedule;
use App\Models\Payment;
use App\Models\RecurringBooking;
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

        $user = $request->user();
        $membership = $user?->membership()->firstOrCreate(
            ['user_id' => $user->id],
            ['points' => 0, 'tier' => 'bronze']
        );

        return Inertia::render('Bookings/Create', [
            'courts' => $courts,
            'initialCourtId' => $request->query('court_id') ? (int) $request->query('court_id') : ($courts->first()['id'] ?? null),
            'initialDate' => $request->query('date', now()->format('Y-m-d')),
            'initialStartTime' => $request->query('start_time'),
            'initialEndTime' => $request->query('end_time'),
            'userTier' => $membership ? $membership->tier : 'bronze',
            'discountPercentage' => $membership ? $membership->discount_percentage : 0,
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
    /**
     * Store a new booking (single or recurring) with anti-bentrok pessimistic locking.
     */
     public function store(StoreBookingRequest $request)
     {
         $user = $request->user();
         $courtId = (int) $request->validated('court_id');
         $bookingDate = $request->validated('booking_date');
         $startTime = $request->validated('start_time');
         $endTime = $request->validated('end_time');
         $notes = $request->validated('notes');
         $isRecurring = $request->boolean('is_recurring');
         $recurringEndDate = $request->validated('recurring_end_date');

         // Hitung durasi jam
         $startCarbon = Carbon::createFromFormat('H:i', $startTime);
         $endCarbon = Carbon::createFromFormat('H:i', $endTime);
         $durationMinutes = $startCarbon->diffInMinutes($endCarbon);
         $hours = $durationMinutes / 60;

         if ($hours <= 0) {
             throw ValidationException::withMessages([
                 'end_time' => 'Durasi booking minimal 1 jam.',
             ]);
         }

         if ($isRecurring) {
             // Handle Recurring Booking Flow
             $startIter = Carbon::createFromFormat('Y-m-d', $bookingDate);
             $endIter = Carbon::createFromFormat('Y-m-d', $recurringEndDate);
             $dayOfWeek = $startIter->dayOfWeek;

             // Generate daftar tanggal per minggu
             $candidateDates = [];
             $curr = $startIter->copy();
             while ($curr->lte($endIter)) {
                 $candidateDates[] = $curr->copy();
                 $curr->addWeek();
             }

             if (empty($candidateDates)) {
                 throw ValidationException::withMessages([
                     'recurring_end_date' => 'Rentang tanggal booking rutin tidak valid.',
                 ]);
             }

             $result = DB::transaction(function () use ($user, $courtId, $candidateDates, $startIter, $endIter, $dayOfWeek, $startTime, $endTime, $hours, $notes) {
                 $court = Court::where('id', $courtId)
                     ->where('is_active', true)
                     ->lockForUpdate()
                     ->firstOrFail();

                 // Cek jam operasional lapangan untuk hari tersebut
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

                 // Calculate tier discount
                 $membership = $user->membership()->firstOrCreate(
                     ['user_id' => $user->id],
                     ['points' => 0, 'tier' => 'bronze']
                 );
                 $discountPercent = $membership->discount_percentage;
                 $rawSessionPrice = $hours * (float) $court->price_per_hour;
                 $sessionDiscount = $rawSessionPrice * ($discountPercent / 100);
                 $sessionPrice = round($rawSessionPrice - $sessionDiscount, 2);

                 // Evaluasi ketersediaan per minggu (anti-bentrok per sesi)
                 $successfulDates = [];
                 $skippedDates = [];

                 foreach ($candidateDates as $targetDate) {
                     $targetDateStr = $targetDate->format('Y-m-d');

                     $conflict = Booking::where('court_id', $court->id)
                         ->where('booking_date', $targetDateStr)
                         ->where('status', '!=', 'cancelled')
                         ->where(function ($query) use ($startTime, $endTime) {
                             $query->where('start_time', '<', $endTime)
                                   ->where('end_time', '>', $startTime);
                         })
                         ->lockForUpdate()
                         ->exists();

                     if ($conflict) {
                         $skippedDates[] = $targetDateStr;
                     } else {
                         $successfulDates[] = $targetDate;
                     }
                 }

                 if (empty($successfulDates)) {
                     throw ValidationException::withMessages([
                         'slots' => 'Seluruh sesi mingguan pada rentang waktu yang dipilih bentrok dengan pemesanan lain. Tidak ada sesi yang dapat dibuat.',
                     ]);
                 }

                 // Buat parent record RecurringBooking
                 $recurringBooking = RecurringBooking::create([
                     'user_id' => $user->id,
                     'court_id' => $court->id,
                     'day_of_week' => $dayOfWeek,
                     'start_time' => $startTime . ':00',
                     'end_time' => $endTime . ':00',
                     'start_date' => $startIter->format('Y-m-d'),
                     'end_date' => $endIter->format('Y-m-d'),
                     'status' => 'active',
                 ]);

                 // Generate booking individual untuk setiap minggu yang berhasil
                 $createdBookings = [];
                 foreach ($successfulDates as $succDate) {
                     $booking = Booking::create([
                         'user_id' => $user->id,
                         'court_id' => $court->id,
                         'booking_date' => $succDate->format('Y-m-d'),
                         'start_time' => $startTime . ':00',
                         'end_time' => $endTime . ':00',
                         'status' => 'pending',
                         'total_price' => $sessionPrice,
                         'notes' => $notes,
                         'is_recurring' => true,
                         'recurring_booking_id' => $recurringBooking->id,
                     ]);

                     Payment::create([
                         'booking_id' => $booking->id,
                         'amount' => $sessionPrice,
                         'method' => 'simulasi_transfer',
                         'status' => 'pending',
                         'invoice_number' => Payment::generateInvoiceNumber(),
                     ]);

                     $createdBookings[] = $booking;
                 }

                 return [
                     'recurring' => $recurringBooking,
                     'bookings' => $createdBookings,
                     'skipped_dates' => $skippedDates,
                 ];
             });

             // Broadcast event real-time untuk setiap booking yang berhasil dibuat
             foreach ($result['bookings'] as $b) {
                 event(new BookingCreated($b));
             }

             // Susun notifikasi informasi skip jika ada
             $succCount = count($result['bookings']);
             $skipCount = count($result['skipped_dates']);

             if ($skipCount > 0) {
                 $skippedList = implode(', ', array_map(fn ($d) => Carbon::parse($d)->translatedFormat('d M Y'), $result['skipped_dates']));
                 $flashMsg = "Booking rutin berhasil dibuat untuk {$succCount} sesi. {$skipCount} sesi dilewati karena slot sudah terisi ({$skippedList}). Silakan lakukan pembayaran paket di muka.";
             } else {
                 $flashMsg = "Booking rutin berhasil dibuat untuk seluruh {$succCount} sesi! Silakan lakukan pembayaran paket di muka.";
             }

             $firstPayment = $result['bookings'][0]->payment;

             return redirect()->route('payments.show', $firstPayment->id)
                 ->with('success', $flashMsg);
         }

         // Single Booking Flow (Standard)
         $booking = DB::transaction(function () use ($user, $courtId, $bookingDate, $startTime, $endTime, $hours, $notes) {
             $court = Court::where('id', $courtId)
                 ->where('is_active', true)
                 ->lockForUpdate()
                 ->firstOrFail();

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

              // Calculate tier discount
              $membership = $user->membership()->firstOrCreate(
                  ['user_id' => $user->id],
                  ['points' => 0, 'tier' => 'bronze']
              );
              $discountPercent = $membership->discount_percentage;
              $rawTotal = $hours * (float) $court->price_per_hour;
              $discountAmount = $rawTotal * ($discountPercent / 100);
              $totalPrice = round($rawTotal - $discountAmount, 2);

              $booking = Booking::create([
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

             Payment::create([
                 'booking_id' => $booking->id,
                 'amount' => $totalPrice,
                 'method' => 'simulasi_transfer',
                 'status' => 'pending',
                 'invoice_number' => Payment::generateInvoiceNumber(),
             ]);

             return $booking;
         });

         event(new BookingCreated($booking));

         return redirect()->route('payments.show', $booking->payment->id)
             ->with('success', 'Booking berhasil dibuat! Silakan pilih metode pembayaran.');
     }

    /**
     * Display logged-in user's bookings history.
     */
    public function myBookings(Request $request): Response
    {
        $statusFilter = $request->query('status', 'all');

        $query = $request->user()->bookings()
            ->with(['court', 'payment'])
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
                'payment' => $booking->payment ? [
                    'id' => $booking->payment->id,
                    'invoice_number' => $booking->payment->invoice_number,
                    'method' => $booking->payment->method,
                    'status' => $booking->payment->status,
                    'amount' => (float) $booking->payment->amount,
                ] : null,
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

        event(new BookingCancelled($booking));

        return redirect()->back()
            ->with('success', "Booking #{$booking->id} berhasil dibatalkan.");
    }
}

