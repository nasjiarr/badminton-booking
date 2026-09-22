<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan Kinerja Bisnis - Smash Arena</title>
    <style>
        @page {
            margin: 28px 32px 35px 32px;
        }
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            color: #1e293b;
            font-size: 11px;
            line-height: 1.4;
            margin: 0;
            padding: 0;
        }

        /* Header / Kop */
        .header-table {
            width: 100%;
            border-bottom: 2px solid #0f172a;
            padding-bottom: 12px;
            margin-bottom: 18px;
        }
        .brand-title {
            font-size: 20px;
            font-weight: 800;
            color: #0f172a;
            letter-spacing: -0.5px;
            text-transform: uppercase;
        }
        .brand-subtitle {
            font-size: 11px;
            color: #64748b;
            margin-top: 2px;
        }
        .report-meta {
            text-align: right;
            font-size: 10px;
            color: #475569;
        }
        .report-badge {
            background-color: #0f172a;
            color: #ccff00;
            font-weight: bold;
            font-size: 10px;
            padding: 3px 8px;
            border-radius: 3px;
            display: inline-block;
            margin-bottom: 4px;
        }

        /* KPI Cards Table */
        .kpi-table {
            width: 100%;
            margin-bottom: 20px;
            border-spacing: 6px;
            border-collapse: separate;
        }
        .kpi-card {
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            padding: 10px 14px;
            text-align: left;
        }
        .kpi-title {
            font-size: 9px;
            font-weight: bold;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 4px;
        }
        .kpi-value {
            font-size: 16px;
            font-weight: 800;
            color: #0f172a;
        }
        .kpi-subtitle {
            font-size: 9px;
            color: #94a3b8;
            margin-top: 3px;
        }

        /* Section Headings */
        .section-title {
            font-size: 13px;
            font-weight: 800;
            color: #0f172a;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            margin-top: 14px;
            margin-bottom: 8px;
            padding-bottom: 4px;
            border-bottom: 1px solid #cbd5e1;
        }

        /* Data Tables */
        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 18px;
            font-size: 10px;
        }
        .data-table th {
            background-color: #111a2e;
            color: #ffffff;
            font-weight: bold;
            text-align: left;
            padding: 6px 8px;
            border: 1px solid #111a2e;
            text-transform: uppercase;
            font-size: 9px;
            letter-spacing: 0.3px;
        }
        .data-table td {
            padding: 6px 8px;
            border: 1px solid #e2e8f0;
            color: #334155;
        }
        .data-table tr:nth-child(even) td {
            background-color: #f8fafc;
        }
        .text-right {
            text-align: right;
        }
        .text-center {
            text-align: center;
        }
        .font-bold {
            font-weight: bold;
        }
        .total-row td {
            background-color: #f1f5f9 !important;
            font-weight: bold;
            border-top: 2px solid #0f172a;
            color: #0f172a;
        }

        /* Status Badges */
        .badge {
            display: inline-block;
            padding: 2px 6px;
            font-size: 8px;
            font-weight: bold;
            border-radius: 3px;
            text-transform: uppercase;
        }
        .badge-success { background-color: #dcfce7; color: #15803d; }
        .badge-warning { background-color: #fef9c3; color: #a16207; }
        .badge-danger { background-color: #fee2e2; color: #b91c1c; }
        .badge-slate { background-color: #f1f5f9; color: #475569; }

        /* Sign-off */
        .footer-table {
            width: 100%;
            margin-top: 30px;
        }
        .sign-box {
            width: 200px;
            text-align: center;
            float: right;
        }
        .sign-line {
            margin-top: 50px;
            border-bottom: 1px solid #0f172a;
            font-weight: bold;
            padding-bottom: 4px;
        }
    </style>
</head>
<body>

    <!-- Header / Kop Dokumen -->
    <table class="header-table">
        <tr>
            <td style="width: 60%; vertical-align: middle;">
                <div class="brand-title">🏸 SMASH ARENA</div>
                <div class="brand-subtitle">
                    Laporan Resmi Kinerja Bisnis, Okupansi & Pendapatan Gelanggang Badminton
                </div>
                <div style="font-size: 9px; color: #64748b; margin-top: 3px;">
                    Jl. Arena Badminton No. 128 • Telp: (021) 8899-7766 • Email: admin@smasharena.com
                </div>
            </td>
            <td style="width: 40%; vertical-align: middle;" class="report-meta">
                <div class="report-badge">LAPORAN EKSEKUTIF OWNER</div>
                <div><strong>Periode:</strong> {{ $periodLabel }}</div>
                <div><strong>Dicetak Pada:</strong> {{ $generatedAt }}</div>
                <div><strong>Total Durasi:</strong> {{ $daysCount }} Hari Kalender</div>
            </td>
        </tr>
    </table>

    <!-- Ringkasan Eksekutif (KPI Cards) -->
    <table class="kpi-table">
        <tr>
            <td class="kpi-card" style="width: 33%;">
                <div class="kpi-title">Total Pendapatan Terkonfirmasi</div>
                <div class="kpi-value" style="color: #0f172a;">
                    Rp {{ number_format($totalRevenue, 0, ',', '.') }}
                </div>
                <div class="kpi-subtitle">
                    Dari pembayaran berstatus <strong>PAID</strong>
                </div>
            </td>
            <td class="kpi-card" style="width: 33%;">
                <div class="kpi-title">Total Pemesanan Lapangan</div>
                <div class="kpi-value" style="color: #0f172a;">
                    {{ $totalBookings }} Sesi
                </div>
                <div class="kpi-subtitle">
                    {{ $confirmedBookings }} Konfirmasi • {{ $pendingBookings }} Pending • {{ $cancelledBookings }} Batal
                </div>
            </td>
            <td class="kpi-card" style="width: 34%;">
                <div class="kpi-title">Rata-rata Okupansi Gelanggang</div>
                <div class="kpi-value" style="color: #ea580c;">
                    {{ $overallOccupancyRate }}%
                </div>
                <div class="kpi-subtitle">
                    {{ $totalArenaHoursBooked }} jam terpakai dari {{ $totalArenaCapacity }} jam kapasitas
                </div>
            </td>
        </tr>
    </table>

    <!-- Tabel Analisis Performa 4 Lapangan -->
    <div class="section-title">1. Perbandingan Okupansi & Pendapatan Antar Lapangan</div>
    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 5%;">No</th>
                <th style="width: 25%;">Nama Lapangan</th>
                <th style="width: 15%;" class="text-right">Tarif / Jam</th>
                <th style="width: 12%;" class="text-center">Total Pesanan</th>
                <th style="width: 13%;" class="text-center">Jam Disewa</th>
                <th style="width: 15%;" class="text-center">Tingkat Okupansi</th>
                <th style="width: 15%;" class="text-right">Pendapatan</th>
            </tr>
        </thead>
        <tbody>
            @foreach($courtStats as $index => $stat)
            <tr>
                <td class="text-center">{{ $index + 1 }}</td>
                <td class="font-bold">{{ $stat['name'] }}</td>
                <td class="text-right">Rp {{ number_format($stat['price_per_hour'], 0, ',', '.') }}</td>
                <td class="text-center">{{ $stat['bookings_count'] }} sesi</td>
                <td class="text-center">{{ $stat['hours_booked'] }} Jam</td>
                <td class="text-center font-bold" style="color: {{ $stat['occupancy_rate'] >= 50 ? '#15803d' : '#ea580c' }};">
                    {{ $stat['occupancy_rate'] }}%
                </td>
                <td class="text-right font-bold">
                    Rp {{ number_format($stat['revenue'], 0, ',', '.') }}
                </td>
            </tr>
            @endforeach
            <tr class="total-row">
                <td colspan="3" class="text-center">TOTAL KESELURUHAN</td>
                <td class="text-center">{{ array_sum(array_column($courtStats, 'bookings_count')) }} sesi</td>
                <td class="text-center">{{ $totalArenaHoursBooked }} Jam</td>
                <td class="text-center">{{ $overallOccupancyRate }}% (Rata-rata)</td>
                <td class="text-right">Rp {{ number_format($totalRevenue, 0, ',', '.') }}</td>
            </tr>
        </tbody>
    </table>

    <!-- Tabel Rincian Transaksi Booking -->
    <div class="section-title">2. Rincian Pemesanan Lapangan ({{ count($bookingsList) }} Transaksi)</div>
    @if(count($bookingsList) > 0)
    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 4%;">No</th>
                <th style="width: 15%;">No Invoice</th>
                <th style="width: 12%;">Tanggal</th>
                <th style="width: 11%;" class="text-center">Jam</th>
                <th style="width: 14%;">Lapangan</th>
                <th style="width: 18%;">Pelanggan</th>
                <th style="width: 14%;" class="text-right">Total Bayar</th>
                <th style="width: 12%;" class="text-center">Status</th>
            </tr>
        </thead>
        <tbody>
            @foreach($bookingsList->take(60) as $idx => $booking)
            <tr>
                <td class="text-center">{{ $idx + 1 }}</td>
                <td style="font-family: monospace; font-size: 8.5px;">
                    {{ $booking->payment?->invoice_number ?? '-' }}
                </td>
                <td>{{ \Carbon\Carbon::parse($booking->booking_date)->format('d/m/Y') }}</td>
                <td class="text-center">
                    {{ substr($booking->start_time, 0, 5) }} - {{ substr($booking->end_time, 0, 5) }}
                </td>
                <td>{{ $booking->court?->name ?? 'Lapangan' }}</td>
                <td>{{ $booking->user?->name ?? 'User' }}</td>
                <td class="text-right font-bold">
                    Rp {{ number_format($booking->total_price, 0, ',', '.') }}
                </td>
                <td class="text-center">
                    @if($booking->status === 'confirmed')
                        <span class="badge badge-success">CONFIRMED</span>
                    @elseif($booking->status === 'pending')
                        <span class="badge badge-warning">PENDING</span>
                    @elseif($booking->status === 'cancelled')
                        <span class="badge badge-danger">BATAL</span>
                    @else
                        <span class="badge badge-slate">{{ strtoupper($booking->status) }}</span>
                    @endif
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @if(count($bookingsList) > 60)
    <div style="font-size: 9px; color: #64748b; font-style: italic; margin-top: -12px; margin-bottom: 12px;">
        * Menampilkan 60 transaksi pertama dari total {{ count($bookingsList) }} transaksi. Untuk rincian lengkap seluruh transaksi, gunakan ekspor file Excel (.xlsx).
    </div>
    @endif
    @else
    <div style="padding: 15px; background: #f8fafc; border: 1px solid #e2e8f0; text-align: center; color: #64748b;">
        Tidak ada data pemesanan pada rentang tanggal yang dipilih.
    </div>
    @endif

    <!-- Tanda Tangan / Otorisasi -->
    <table class="footer-table">
        <tr>
            <td style="width: 60%; vertical-align: top;">
                <div style="font-size: 9px; color: #64748b;">
                    Dokumen ini digenerate secara otomatis oleh Sistem Reservasi Smash Arena.<br>
                    Informasi finansial dan okupansi bersifat rahasia dan ditujukan untuk manajemen.
                </div>
            </td>
            <td style="width: 40%; vertical-align: top;">
                <div class="sign-box">
                    <div style="font-size: 10px; color: #475569;">Disetujui Oleh:</div>
                    <div class="sign-line">Manajemen Gelanggang</div>
                    <div style="font-size: 9px; color: #64748b; margin-top: 2px;">Smash Arena Indonesia</div>
                </div>
            </td>
        </tr>
    </table>

</body>
</html>

