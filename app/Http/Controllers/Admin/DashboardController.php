<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\DashboardStatsService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __construct(
        protected DashboardStatsService $statsService
    ) {}

    /**
     * Display the admin business dashboard.
     */
    public function index(Request $request): Response
    {
        $hasCustomRange = $request->filled('start_date') && $request->filled('end_date');

        if ($hasCustomRange) {
            $startDate = Carbon::parse($request->input('start_date'))->startOfDay();
            $endDate = Carbon::parse($request->input('end_date'))->endOfDay();

            // When custom range is active, all charts follow this range
            $revenueChart = $this->statsService->getRevenueChart($startDate, $endDate);
            $occupancyTrendChart = $this->statsService->getOccupancyTrendChart($startDate, $endDate);
            $courtComparisonChart = $this->statsService->getCourtOccupancyComparison($startDate, $endDate);
            $filterMode = 'custom';
        } else {
            // Default ranges per requirements:
            // 1. Revenue: 7 hari terakhir
            $revStart = now()->subDays(6)->startOfDay();
            $revEnd = now()->endOfDay();
            $revenueChart = $this->statsService->getRevenueChart($revStart, $revEnd);

            // 2. Okupansi: 30 hari terakhir
            $occStart = now()->subDays(29)->startOfDay();
            $occEnd = now()->endOfDay();
            $occupancyTrendChart = $this->statsService->getOccupancyTrendChart($occStart, $occEnd);

            // 3. Perbandingan lapangan: Bulan berjalan
            $courtStart = now()->startOfMonth()->startOfDay();
            $courtEnd = now()->endOfMonth()->endOfDay();
            $courtComparisonChart = $this->statsService->getCourtOccupancyComparison($courtStart, $courtEnd);

            $startDate = $occStart;
            $endDate = now();
            $filterMode = 'default';
        }

        return Inertia::render('Admin/Dashboard', [
            'summary' => fn () => $this->statsService->getSummaryCards(),
            'charts' => [
                'revenue' => $revenueChart,
                'occupancy_trend' => $occupancyTrendChart,
                'court_comparison' => $courtComparisonChart,
            ],
            'filters' => [
                'start_date' => $startDate->format('Y-m-d'),
                'end_date' => $endDate->format('Y-m-d'),
                'mode' => $filterMode,
            ],
        ]);
    }
}

