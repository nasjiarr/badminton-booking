<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\Court;
use App\Models\Payment;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class DashboardStatsService
{
    /**
     * Operational hours per court per day (06:00 - 22:00 = 16 hours).
     */
    protected const DAILY_HOURS_PER_COURT = 16;

    /**
     * Get business summary cards for today.
     *
     * @return array<string, mixed>
     */
    public function getSummaryCards(): array
    {
        $today = now()->format('Y-m-d');

        // 1. Total booking hari ini (non-cancelled)
        $totalBookingsToday = Booking::whereDate('booking_date', $today)
            ->where('status', '!=', 'cancelled')
            ->count();

        // 2. Total pendapatan hari ini (status paid)
        $totalRevenueToday = (float) Payment::where('status', 'paid')
            ->whereDate('paid_at', $today)
            ->sum('amount');

        // 3. Tingkat okupansi hari ini
        $activeCourtsCount = Court::where('is_active', true)->count();
        $totalSlotCapacityToday = max(1, $activeCourtsCount * self::DAILY_HOURS_PER_COURT);

        $occupiedHoursToday = (float) Booking::whereDate('booking_date', $today)
            ->where('status', '!=', 'cancelled')
            ->selectRaw('COALESCE(SUM(TIME_TO_SEC(TIMEDIFF(end_time, start_time)) / 3600), 0) as total_hours')
            ->value('total_hours');

        $occupancyRateToday = round(($occupiedHoursToday / $totalSlotCapacityToday) * 100, 1);

        // 4. Jumlah member baru bulan ini
        $newMembersThisMonth = User::where('created_at', '>=', now()->startOfMonth())->count();

        return [
            'total_bookings_today' => $totalBookingsToday,
            'total_revenue_today' => $totalRevenueToday,
            'occupancy_rate_today' => $occupancyRateToday,
            'occupied_hours_today' => $occupiedHoursToday,
            'total_capacity_today' => $totalSlotCapacityToday,
            'new_members_this_month' => $newMembersThisMonth,
        ];
    }

    /**
     * Get revenue bar chart data for given date range.
     *
     * @param Carbon $startDate
     * @param Carbon $endDate
     * @return array<string, mixed>
     */
    public function getRevenueChart(Carbon $startDate, Carbon $endDate): array
    {
        // Query daily revenue aggregated by date
        $dailyRevenue = Payment::where('status', 'paid')
            ->whereBetween('paid_at', [$startDate->copy()->startOfDay(), $endDate->copy()->endOfDay()])
            ->selectRaw('DATE(paid_at) as date_key, SUM(amount) as total')
            ->groupBy('date_key')
            ->pluck('total', 'date_key')
            ->toArray();

        $labels = [];
        $data = [];
        $current = $startDate->copy();

        while ($current->lte($endDate)) {
            $key = $current->format('Y-m-d');
            $labels[] = $current->translatedFormat('d M');
            $data[] = (float) ($dailyRevenue[$key] ?? 0);
            $current->addDay();
        }

        return [
            'labels' => $labels,
            'datasets' => [
                [
                    'label' => 'Pendapatan (Rp)',
                    'data' => $data,
                    'backgroundColor' => '#CCFF00', // Smash Volt
                    'hoverBackgroundColor' => '#B5E600',
                    'borderRadius' => 6,
                    'borderSkipped' => false,
                ],
            ],
            'total_in_period' => array_sum($data),
        ];
    }

    /**
     * Get daily occupancy trend line chart for given date range.
     *
     * @param Carbon $startDate
     * @param Carbon $endDate
     * @return array<string, mixed>
     */
    public function getOccupancyTrendChart(Carbon $startDate, Carbon $endDate): array
    {
        $activeCourtsCount = Court::where('is_active', true)->count();
        $dailySlotCapacity = max(1, $activeCourtsCount * self::DAILY_HOURS_PER_COURT);

        $dailyOccupiedHours = Booking::where('status', '!=', 'cancelled')
            ->whereBetween('booking_date', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')])
            ->selectRaw('booking_date, COALESCE(SUM(TIME_TO_SEC(TIMEDIFF(end_time, start_time)) / 3600), 0) as occupied_hours')
            ->groupBy('booking_date')
            ->pluck('occupied_hours', 'booking_date')
            ->toArray();

        $labels = [];
        $data = [];
        $current = $startDate->copy();

        while ($current->lte($endDate)) {
            $key = $current->format('Y-m-d');
            $hours = (float) ($dailyOccupiedHours[$key] ?? 0);
            $rate = round(($hours / $dailySlotCapacity) * 100, 1);
            $labels[] = $current->translatedFormat('d M');
            $data[] = $rate;
            $current->addDay();
        }

        $avgRate = count($data) > 0 ? round(array_sum($data) / count($data), 1) : 0;

        return [
            'labels' => $labels,
            'datasets' => [
                [
                    'label' => 'Tingkat Okupansi (%)',
                    'data' => $data,
                    'borderColor' => '#FF5500', // Speed Orange
                    'backgroundColor' => 'rgba(255, 85, 0, 0.12)',
                    'pointBackgroundColor' => '#FF5500',
                    'pointBorderColor' => '#FFFFFF',
                    'pointHoverBackgroundColor' => '#FFFFFF',
                    'pointHoverBorderColor' => '#FF5500',
                    'pointRadius' => 3,
                    'pointHoverRadius' => 5,
                    'borderWidth' => 2.5,
                    'fill' => true,
                    'tension' => 0.35,
                ],
            ],
            'average_rate' => $avgRate,
        ];
    }

    /**
     * Get court occupancy comparison pie/donut chart data.
     *
     * @param Carbon $startDate
     * @param Carbon $endDate
     * @return array<string, mixed>
     */
    public function getCourtOccupancyComparison(Carbon $startDate, Carbon $endDate): array
    {
        $courtStats = DB::table('courts')
            ->leftJoin('bookings', function ($join) use ($startDate, $endDate) {
                $join->on('courts.id', '=', 'bookings.court_id')
                    ->where('bookings.status', '!=', 'cancelled')
                    ->whereBetween('bookings.booking_date', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')]);
            })
            ->where('courts.is_active', true)
            ->selectRaw('courts.id, courts.name, COALESCE(SUM(TIME_TO_SEC(TIMEDIFF(bookings.end_time, bookings.start_time)) / 3600), 0) as total_hours')
            ->groupBy('courts.id', 'courts.name')
            ->orderBy('courts.name')
            ->get();

        $colors = [
            '#CCFF00', // Smash Volt
            '#FF5500', // Speed Orange
            '#38BDF8', // Electric Blue
            '#10B981', // Emerald Arena
            '#A855F7', // Violet
            '#F59E0B', // Amber
        ];

        $labels = [];
        $data = [];
        $backgroundColors = [];
        $borderColors = [];
        $courtsDetail = [];

        $totalAllHours = (float) $courtStats->sum('total_hours');

        foreach ($courtStats as $index => $stat) {
            $hours = (float) $stat->total_hours;
            $color = $colors[$index % count($colors)];
            $percentage = $totalAllHours > 0 ? round(($hours / $totalAllHours) * 100, 1) : 0;

            $labels[] = $stat->name;
            $data[] = $hours;
            $backgroundColors[] = $color;
            $borderColors[] = '#111A2E';

            $courtsDetail[] = [
                'id' => $stat->id,
                'name' => $stat->name,
                'hours' => $hours,
                'percentage' => $percentage,
                'color' => $color,
            ];
        }

        return [
            'labels' => $labels,
            'datasets' => [
                [
                    'label' => 'Total Jam Terpakai',
                    'data' => $data,
                    'backgroundColor' => $backgroundColors,
                    'borderColor' => $borderColors,
                    'borderWidth' => 2,
                    'hoverOffset' => 6,
                ],
            ],
            'courts_detail' => $courtsDetail,
            'total_hours' => $totalAllHours,
        ];
    }
}

