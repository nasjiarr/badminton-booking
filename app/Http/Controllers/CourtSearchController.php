<?php

namespace App\Http\Controllers;

use App\Models\Court;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class CourtSearchController extends Controller
{
    /**
     * Search available courts by date, time range, and price filters.
     */
    public function index(Request $request): Response
    {
        // Default date: today
        $defaultDate = now()->format('Y-m-d');
        
        // Default start_time: 08:00
        $defaultStartTime = '08:00';
        $defaultEndTime = '10:00';

        $date = $request->query('date', $defaultDate);
        $startTime = $request->query('start_time', $defaultStartTime);
        $endTime = $request->query('end_time', $defaultEndTime);
        $duration = $request->query('duration');

        // Normalize time formats (HH:mm)
        $startTime = strlen($startTime) >= 5 ? substr($startTime, 0, 5) : $defaultStartTime;
        $endTime = strlen($endTime) >= 5 ? substr($endTime, 0, 5) : $defaultEndTime;

        // If duration is provided or endTime <= startTime, recalculate endTime
        if ($duration && is_numeric($duration) && (int) $duration > 0) {
            $durationHours = min(6, max(1, (int) $duration));
            $startCarbon = Carbon::createFromFormat('H:i', $startTime);
            $endTime = $startCarbon->copy()->addHours($durationHours)->format('H:i');
        } else {
            try {
                $startCarbon = Carbon::createFromFormat('H:i', $startTime);
                $endCarbon = Carbon::createFromFormat('H:i', $endTime);
                if ($endCarbon->lte($startCarbon)) {
                    $endTime = $startCarbon->copy()->addHour()->format('H:i');
                }
                $durationHours = max(1, $startCarbon->diffInHours(Carbon::createFromFormat('H:i', $endTime)));
            } catch (\Exception $e) {
                $startTime = $defaultStartTime;
                $endTime = $defaultEndTime;
                $durationHours = 2;
            }
        }

        $minPrice = $request->query('min_price');
        $maxPrice = $request->query('max_price');
        $sortBy = $request->query('sort_by', 'price_asc');

        // Parse date
        try {
            $carbonDate = Carbon::createFromFormat('Y-m-d', $date);
        } catch (\Exception $e) {
            $carbonDate = now();
            $date = $carbonDate->format('Y-m-d');
        }

        $dayOfWeek = $carbonDate->dayOfWeek;
        $isToday = $carbonDate->isToday();
        $isPast = $isToday && $startTime <= now()->format('H:i');

        $courts = collect([]);
        $suggestions = [];

        if (! $isPast) {
            $courtsQuery = Court::query()
                ->where('is_active', true)
                ->when($minPrice !== null && $minPrice !== '', fn ($q) => $q->where('price_per_hour', '>=', (float) $minPrice))
                ->when($maxPrice !== null && $maxPrice !== '', fn ($q) => $q->where('price_per_hour', '<=', (float) $maxPrice))
                // Schedule operating hours check
                ->whereHas('schedules', function ($q) use ($dayOfWeek, $startTime, $endTime) {
                    $q->where('day_of_week', $dayOfWeek)
                      ->where('open_time', '<=', $startTime)
                      ->where('close_time', '>=', $endTime);
                })
                // Overlap check: must not have confirmed or pending bookings overlapping with requested interval
                ->whereDoesntHave('bookings', function ($q) use ($date, $startTime, $endTime) {
                    $q->where('booking_date', $date)
                      ->whereIn('status', ['confirmed', 'pending'])
                      ->where(function ($sub) use ($startTime, $endTime) {
                          $sub->where('start_time', '<', $endTime)
                              ->where('end_time', '>', $startTime);
                      });
                });

            // Sorting
            if ($sortBy === 'price_desc') {
                $courtsQuery->orderBy('price_per_hour', 'desc');
            } elseif ($sortBy === 'name_asc') {
                $courtsQuery->orderBy('name', 'asc');
            } else {
                $courtsQuery->orderBy('price_per_hour', 'asc');
            }

            $courts = $courtsQuery->get()->map(function (Court $court) use ($durationHours, $date, $startTime, $endTime) {
                $pricePerHour = (float) $court->price_per_hour;
                $totalPrice = $pricePerHour * $durationHours;

                return [
                    'id' => $court->id,
                    'name' => $court->name,
                    'description' => $court->description,
                    'price_per_hour' => $pricePerHour,
                    'duration_hours' => $durationHours,
                    'total_price' => $totalPrice,
                    'image_url' => $court->image_path ? Storage::url($court->image_path) : null,
                    'booking_url' => route('bookings.create', [
                        'court_id' => $court->id,
                        'date' => $date,
                        'start_time' => $startTime,
                        'end_time' => $endTime,
                    ]),
                ];
            });
        }

        // If no courts available, find alternative suggestions
        if ($courts->isEmpty()) {
            $suggestions = $this->findAlternativeSuggestions(
                $carbonDate,
                $startTime,
                $durationHours,
                $minPrice,
                $maxPrice
            );
        }

        return Inertia::render('Courts/Search', [
            'courts' => $courts,
            'filters' => [
                'date' => $date,
                'start_time' => $startTime,
                'end_time' => $endTime,
                'duration' => $durationHours,
                'min_price' => $minPrice,
                'max_price' => $maxPrice,
                'sort_by' => $sortBy,
            ],
            'isPast' => $isPast,
            'suggestions' => $suggestions,
        ]);
    }

    /**
     * Find alternative available time slots nearby on the same day or next day.
     */
    protected function findAlternativeSuggestions(
        Carbon $carbonDate,
        string $requestedStartTime,
        int $durationHours,
        $minPrice,
        $maxPrice
    ): array {
        $suggestions = [];
        $startHour = (int) explode(':', $requestedStartTime)[0];
        $dateString = $carbonDate->format('Y-m-d');
        $dayOfWeek = $carbonDate->dayOfWeek;
        $isToday = $carbonDate->isToday();
        $currentHour = (int) now()->format('H');

        // Check nearby offset hours: +1, +2, -1, -2, +3, -3, +4
        $offsets = [+1, +2, -1, -2, +3, -3, +4];

        foreach ($offsets as $offset) {
            $candStartHour = $startHour + $offset;
            $candEndHour = $candStartHour + $durationHours;

            // Must fall within standard operating bounds (06:00 to 22:00)
            if ($candStartHour < 6 || $candEndHour > 22) {
                continue;
            }

            // If today, cannot suggest slots that are past or currently starting
            if ($isToday && $candStartHour <= $currentHour) {
                continue;
            }

            $candStartTime = sprintf('%02d:00', $candStartHour);
            $candEndTime = sprintf('%02d:00', $candEndHour);

            $availableCourts = Court::where('is_active', true)
                ->when($minPrice !== null && $minPrice !== '', fn ($q) => $q->where('price_per_hour', '>=', (float) $minPrice))
                ->when($maxPrice !== null && $maxPrice !== '', fn ($q) => $q->where('price_per_hour', '<=', (float) $maxPrice))
                ->whereHas('schedules', function ($q) use ($dayOfWeek, $candStartTime, $candEndTime) {
                    $q->where('day_of_week', $dayOfWeek)
                      ->where('open_time', '<=', $candStartTime)
                      ->where('close_time', '>=', $candEndTime);
                })
                ->whereDoesntHave('bookings', function ($q) use ($dateString, $candStartTime, $candEndTime) {
                    $q->where('booking_date', $dateString)
                      ->whereIn('status', ['confirmed', 'pending'])
                      ->where(function ($sub) use ($candStartTime, $candEndTime) {
                          $sub->where('start_time', '<', $candEndTime)
                              ->where('end_time', '>', $candStartTime);
                      });
                })
                ->get(['id', 'name', 'price_per_hour']);

            if ($availableCourts->isNotEmpty()) {
                $suggestions[] = [
                    'date' => $dateString,
                    'date_formatted' => $carbonDate->translatedFormat('l, d F Y'),
                    'start_time' => $candStartTime,
                    'end_time' => $candEndTime,
                    'duration_hours' => $durationHours,
                    'available_count' => $availableCourts->count(),
                    'court_names' => $availableCourts->pluck('name')->toArray(),
                    'min_price' => (float) $availableCourts->min('price_per_hour'),
                ];
            }

            if (count($suggestions) >= 3) {
                break;
            }
        }

        // If still empty and it's late today, suggest slots for tomorrow
        if (empty($suggestions) && $isToday) {
            $tomorrow = $carbonDate->copy()->addDay();
            $tomorrowDayOfWeek = $tomorrow->dayOfWeek;
            $tomorrowDateString = $tomorrow->format('Y-m-d');

            $candidateHours = [8, 14, 19];
            foreach ($candidateHours as $tHour) {
                $tEndHour = $tHour + $durationHours;
                if ($tEndHour > 22) {
                    continue;
                }

                $tStartTime = sprintf('%02d:00', $tHour);
                $tEndTime = sprintf('%02d:00', $tEndHour);

                $availableCourts = Court::where('is_active', true)
                    ->when($minPrice !== null && $minPrice !== '', fn ($q) => $q->where('price_per_hour', '>=', (float) $minPrice))
                    ->when($maxPrice !== null && $maxPrice !== '', fn ($q) => $q->where('price_per_hour', '<=', (float) $maxPrice))
                    ->whereHas('schedules', function ($q) use ($tomorrowDayOfWeek, $tStartTime, $tEndTime) {
                        $q->where('day_of_week', $tomorrowDayOfWeek)
                          ->where('open_time', '<=', $tStartTime)
                          ->where('close_time', '>=', $tEndTime);
                    })
                    ->whereDoesntHave('bookings', function ($q) use ($tomorrowDateString, $tStartTime, $tEndTime) {
                        $q->where('booking_date', $tomorrowDateString)
                          ->whereIn('status', ['confirmed', 'pending'])
                          ->where(function ($sub) use ($tStartTime, $tEndTime) {
                              $sub->where('start_time', '<', $tEndTime)
                                  ->where('end_time', '>', $tStartTime);
                          });
                    })
                    ->get(['id', 'name', 'price_per_hour']);

                if ($availableCourts->isNotEmpty()) {
                    $suggestions[] = [
                        'date' => $tomorrowDateString,
                        'date_formatted' => $tomorrow->translatedFormat('l, d F Y'),
                        'start_time' => $tStartTime,
                        'end_time' => $tEndTime,
                        'duration_hours' => $durationHours,
                        'available_count' => $availableCourts->count(),
                        'court_names' => $availableCourts->pluck('name')->toArray(),
                        'min_price' => (float) $availableCourts->min('price_per_hour'),
                    ];
                }

                if (count($suggestions) >= 3) {
                    break;
                }
            }
        }

        return $suggestions;
    }
}

