<?php

namespace App\Exports;

use App\Models\Booking;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Class BookingsExport
 *
 * Reusable export class for booking and revenue data in Excel format.
 *
 * ARCHITECTURAL TRADE-OFF NOTE (Requirement 5):
 * -------------------------------------------------------------
 * For small to medium scale venues (hundreds to thousands of rows),
 * direct synchronous generation (via FromQuery stream) is optimal,
 * offering instant feedback and direct download without queue delay.
 *
 * For enterprise/large scale datasets (tens of thousands or millions of records),
 * this class can implement `Maatwebsite\Excel\Concerns\ShouldQueue` to delegate
 * file creation to a background Queue Worker, saving the file to cloud storage
 * (e.g., S3) and dispatching a completion notification with a temporary signed
 * download URL to avoid web server timeouts and high memory consumption.
 */
class BookingsExport implements FromQuery, WithHeadings, WithMapping, ShouldAutoSize, WithStyles, WithColumnFormatting
{
    use Exportable;

    protected int $rowNumber = 0;

    public function __construct(
        protected Carbon $startDate,
        protected Carbon $endDate
    ) {}

    /**
     * Query bookings within date range with eager-loaded relations.
     */
    public function query(): Builder
    {
        return Booking::query()
            ->with(['user', 'court', 'payment'])
            ->whereBetween('booking_date', [
                $this->startDate->format('Y-m-d'),
                $this->endDate->format('Y-m-d'),
            ])
            ->orderBy('booking_date')
            ->orderBy('start_time');
    }

    /**
     * Define header row.
     */
    public function headings(): array
    {
        return [
            'No',
            'No Invoice',
            'Tanggal Booking',
            'Jam Main',
            'Lapangan',
            'Nama Pelanggan',
            'Kontak (Telp / Email)',
            'Status Booking',
            'Total Harga (Rp)',
            'Status Pembayaran',
            'Metode Pembayaran',
        ];
    }

    /**
     * Map each booking model to a spreadsheet row.
     *
     * @param Booking $booking
     */
    public function map($booking): array
    {
        $this->rowNumber++;

        $payment = $booking->payment;
        $customer = $booking->user;

        return [
            $this->rowNumber,
            $payment?->invoice_number ?? '-',
            Carbon::parse($booking->booking_date)->translatedFormat('d M Y'),
            substr($booking->start_time, 0, 5) . ' - ' . substr($booking->end_time, 0, 5),
            $booking->court?->name ?? 'Lapangan',
            $customer?->name ?? 'Pelanggan',
            $customer?->phone ?? $customer?->email ?? '-',
            strtoupper($booking->status),
            (float) $booking->total_price,
            strtoupper($payment?->status ?? 'UNPAID'),
            $payment?->payment_method ? strtoupper(str_replace('_', ' ', $payment->payment_method)) : '-',
        ];
    }

    /**
     * Set column data formats.
     */
    public function columnFormats(): array
    {
        return [
            'A' => NumberFormat::FORMAT_NUMBER,
            'I' => '#,##0', // IDR Currency
        ];
    }

    /**
     * Style the worksheet header and rows.
     */
    public function styles(Worksheet $sheet): array
    {
        return [
            1 => [
                'font' => [
                    'bold' => true,
                    'color' => ['argb' => 'FFFFFFFF'],
                    'size' => 11,
                ],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['argb' => 'FF111A2E'], // Arena Card Dark
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical' => Alignment::VERTICAL_CENTER,
                ],
            ],
        ];
    }
}

