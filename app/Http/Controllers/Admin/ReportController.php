<?php

namespace App\Http\Controllers\Admin;

use App\Exports\BookingsExport;
use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Reports\BookingReportPdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ReportController extends Controller
{
    /**
     * Display the Admin Reports page with date filter and live summary preview.
     */
    public function index(Request $request): Response
    {
        $startDate = $request->filled('start_date')
            ? Carbon::parse($request->input('start_date'))->startOfDay()
            : now()->startOfMonth()->startOfDay();

        $endDate = $request->filled('end_date')
            ? Carbon::parse($request->input('end_date'))->endOfDay()
            : now()->endOfDay();

        // Gather metrics using reusable BookingReportPdf data aggregator
        $reportHelper = new BookingReportPdf($startDate, $endDate);
        $reportData = $reportHelper->getData();

        // Paginated bookings preview for UI
        $bookingsPreview = Booking::with(['user', 'court', 'payment'])
            ->whereBetween('booking_date', [
                $startDate->format('Y-m-d'),
                $endDate->format('Y-m-d'),
            ])
            ->orderBy('booking_date', 'desc')
            ->orderBy('start_time', 'desc')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Admin/Reports/Index', [
            'summary' => [
                'total_revenue' => $reportData['totalRevenue'],
                'total_bookings' => $reportData['totalBookings'],
                'confirmed_bookings' => $reportData['confirmedBookings'],
                'pending_bookings' => $reportData['pendingBookings'],
                'cancelled_bookings' => $reportData['cancelledBookings'],
                'overall_occupancy_rate' => $reportData['overallOccupancyRate'],
                'total_hours_booked' => $reportData['totalArenaHoursBooked'],
                'total_capacity' => $reportData['totalArenaCapacity'],
                'court_stats' => $reportData['courtStats'],
                'period_label' => $reportData['periodLabel'],
                'days_count' => $reportData['daysCount'],
            ],
            'bookings' => $bookingsPreview,
            'filters' => [
                'start_date' => $startDate->format('Y-m-d'),
                'end_date' => $endDate->format('Y-m-d'),
            ],
        ]);
    }

    /**
     * Export detailed booking data to Excel (.xlsx).
     */
    public function exportExcel(Request $request): BinaryFileResponse
    {
        $startDate = $request->filled('start_date')
            ? Carbon::parse($request->input('start_date'))->startOfDay()
            : now()->startOfMonth()->startOfDay();

        $endDate = $request->filled('end_date')
            ? Carbon::parse($request->input('end_date'))->endOfDay()
            : now()->endOfDay();

        $filename = 'Laporan-Booking-SmashArena-' . $startDate->format('Ymd') . '-sd-' . $endDate->format('Ymd') . '.xlsx';

        return Excel::download(new BookingsExport($startDate, $endDate), $filename);
    }

    /**
     * Export executive summary report to PDF (.pdf).
     */
    public function exportPdf(Request $request)
    {
        $startDate = $request->filled('start_date')
            ? Carbon::parse($request->input('start_date'))->startOfDay()
            : now()->startOfMonth()->startOfDay();

        $endDate = $request->filled('end_date')
            ? Carbon::parse($request->input('end_date'))->endOfDay()
            : now()->endOfDay();

        $filename = 'Laporan-Ringkasan-SmashArena-' . $startDate->format('Ymd') . '-sd-' . $endDate->format('Ymd') . '.pdf';

        $reportPdf = new BookingReportPdf($startDate, $endDate);

        return $reportPdf->download($filename);
    }
}

