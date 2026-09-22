<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { ref, reactive } from 'vue';

const props = defineProps({
    summary: Object,
    bookings: Object,
    filters: Object,
});

const isFiltering = ref(false);

const filterForm = reactive({
    start_date: props.filters.start_date,
    end_date: props.filters.end_date,
});

const formatRupiah = (value) => {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0,
        maximumFractionDigits: 0,
    }).format(value || 0);
};

const formatDate = (dateStr) => {
    if (!dateStr) return '-';
    const d = new Date(dateStr);
    return new Intl.DateTimeFormat('id-ID', {
        day: 'numeric',
        month: 'short',
        year: 'numeric'
    }).format(d);
};

// Preset filters
const applyPreset = (preset) => {
    const today = new Date();
    const end = today.toISOString().split('T')[0];
    let start;

    if (preset === 'week') {
        const d = new Date();
        d.setDate(today.getDate() - 6);
        start = d.toISOString().split('T')[0];
    } else if (preset === 'month') {
        const d = new Date();
        d.setDate(today.getDate() - 29);
        start = d.toISOString().split('T')[0];
    } else if (preset === 'current_month') {
        const firstDay = new Date(today.getFullYear(), today.getMonth(), 1);
        start = firstDay.toISOString().split('T')[0];
    }

    filterForm.start_date = start;
    filterForm.end_date = end;
    submitFilter();
};

const submitFilter = () => {
    if (!filterForm.start_date || !filterForm.end_date) return;

    isFiltering.value = true;
    router.get(
        route('admin.reports.index'),
        {
            start_date: filterForm.start_date,
            end_date: filterForm.end_date,
        },
        {
            preserveState: true,
            preserveScroll: true,
            onFinish: () => {
                isFiltering.value = false;
            }
        }
    );
};

// Export Links
const getExcelExportUrl = () => {
    return route('admin.reports.export.excel', {
        start_date: filterForm.start_date,
        end_date: filterForm.end_date,
    });
};

const getPdfExportUrl = () => {
    return route('admin.reports.export.pdf', {
        start_date: filterForm.start_date,
        end_date: filterForm.end_date,
    });
};
</script>

<template>
    <Head title="Laporan Booking & Pendapatan — Admin Smash Arena" />

    <AdminLayout>
        <div class="space-y-8">
            <!-- Header Section -->
            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                <div>
                    <h1 class="font-display font-black text-2xl sm:text-3xl text-courtSlate-900 tracking-tight uppercase">
                        Laporan Booking & Pendapatan
                    </h1>
                    <p class="mt-1 text-sm text-courtSlate-600">
                        Unduh laporan transaksi detail dalam format Excel atau ringkasan eksekutif siap cetak dalam PDF.
                    </p>
                </div>

                <!-- Export Action Buttons -->
                <div class="flex flex-wrap items-center gap-3">
                    <a
                        :href="getExcelExportUrl()"
                        target="_blank"
                        class="inline-flex items-center gap-2 px-4 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white font-display font-bold text-xs uppercase tracking-wider rounded-lg shadow-sm transition -skew-x-6 cursor-pointer"
                    >
                        <span class="inline-block transform skew-x-6 flex items-center gap-1.5">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                            Export Excel (.xlsx)
                        </span>
                    </a>

                    <a
                        :href="getPdfExportUrl()"
                        target="_blank"
                        class="inline-flex items-center gap-2 px-4 py-2.5 bg-courtOrange hover:bg-courtOrange-hover text-white font-display font-bold text-xs uppercase tracking-wider rounded-lg shadow-sm transition -skew-x-6 cursor-pointer"
                    >
                        <span class="inline-block transform skew-x-6 flex items-center gap-1.5">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                            </svg>
                            Export PDF (.pdf)
                        </span>
                    </a>
                </div>
            </div>

            <!-- Filter Bar Section -->
            <div class="bg-white rounded-xl border border-courtSlate-200 p-5 shadow-xs">
                <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                    <div>
                        <span class="text-xs font-display font-bold uppercase tracking-wider text-courtSlate-500 block mb-1">
                            Rentang Tanggal Laporan
                        </span>
                        <div class="font-display font-black text-base text-courtSlate-900">
                            {{ props.summary.period_label }} ({{ props.summary.days_count }} Hari)
                        </div>
                    </div>

                    <div class="flex flex-wrap items-center gap-2">
                        <!-- Presets -->
                        <div class="inline-flex rounded-lg border border-courtSlate-200 bg-courtSlate-50 p-1">
                            <button
                                type="button"
                                @click="applyPreset('week')"
                                class="px-2.5 py-1 text-xs font-semibold rounded-md text-courtSlate-700 hover:bg-white hover:shadow-xs transition cursor-pointer"
                            >
                                1 Minggu
                            </button>
                            <button
                                type="button"
                                @click="applyPreset('month')"
                                class="px-2.5 py-1 text-xs font-semibold rounded-md text-courtSlate-700 hover:bg-white hover:shadow-xs transition cursor-pointer"
                            >
                                1 Bulan
                            </button>
                            <button
                                type="button"
                                @click="applyPreset('current_month')"
                                class="px-2.5 py-1 text-xs font-semibold rounded-md text-courtSlate-700 hover:bg-white hover:shadow-xs transition cursor-pointer"
                            >
                                Bulan Ini
                            </button>
                        </div>

                        <!-- Date Range Inputs -->
                        <div class="flex items-center gap-2">
                            <input
                                type="date"
                                v-model="filterForm.start_date"
                                class="text-xs rounded-lg border-courtSlate-300 py-1.5 px-2.5 focus:border-volt focus:ring-volt text-courtSlate-800"
                            />
                            <span class="text-xs text-courtSlate-400">s/d</span>
                            <input
                                type="date"
                                v-model="filterForm.end_date"
                                class="text-xs rounded-lg border-courtSlate-300 py-1.5 px-2.5 focus:border-volt focus:ring-volt text-courtSlate-800"
                            />
                        </div>

                        <!-- Submit Button -->
                        <button
                            type="button"
                            @click="submitFilter"
                            :disabled="isFiltering || !filterForm.start_date || !filterForm.end_date"
                            class="inline-flex items-center gap-1.5 px-3.5 py-1.5 bg-arena-base text-white text-xs font-display font-bold uppercase tracking-wider rounded-lg hover:bg-arena-card transition disabled:opacity-50 cursor-pointer shadow-xs"
                        >
                            <svg v-if="isFiltering" class="animate-spin -ml-1 mr-1 h-3.5 w-3.5 text-white" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <span>Filter</span>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Summary KPI Cards -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
                
                <!-- Total Pendapatan -->
                <div class="bg-white rounded-xl border border-courtSlate-200 p-5 shadow-xs">
                    <span class="text-xs font-display font-bold uppercase tracking-wider text-courtSlate-500 block mb-1">
                        Total Pendapatan Terkonfirmasi
                    </span>
                    <div class="font-display font-black text-2xl sm:text-3xl text-courtSlate-900 tabular-nums">
                        {{ formatRupiah(props.summary.total_revenue) }}
                    </div>
                    <p class="mt-2 text-xs text-emerald-600 font-medium">
                        ✓ Akumulasi pembayaran berstatus PAID
                    </p>
                </div>

                <!-- Total Booking -->
                <div class="bg-white rounded-xl border border-courtSlate-200 p-5 shadow-xs">
                    <span class="text-xs font-display font-bold uppercase tracking-wider text-courtSlate-500 block mb-1">
                        Total Pemesanan Lapangan
                    </span>
                    <div class="font-display font-black text-2xl sm:text-3xl text-courtSlate-900 tabular-nums">
                        {{ props.summary.total_bookings }} <span class="text-sm font-normal text-courtSlate-500">Sesi</span>
                    </div>
                    <p class="mt-2 text-xs text-courtSlate-500">
                        {{ props.summary.confirmed_bookings }} Selesai/Konfirm • {{ props.summary.pending_bookings }} Pending • {{ props.summary.cancelled_bookings }} Batal
                    </p>
                </div>

                <!-- Rata-rata Okupansi -->
                <div class="bg-white rounded-xl border border-courtSlate-200 p-5 shadow-xs">
                    <span class="text-xs font-display font-bold uppercase tracking-wider text-courtSlate-500 block mb-1">
                        Rata-rata Okupansi Gelanggang
                    </span>
                    <div class="font-display font-black text-2xl sm:text-3xl text-courtOrange tabular-nums">
                        {{ props.summary.overall_occupancy_rate }}%
                    </div>
                    <p class="mt-2 text-xs text-courtSlate-500">
                        {{ props.summary.total_hours_booked }} jam terpakai dari {{ props.summary.total_capacity }} jam kapasitas
                    </p>
                </div>

            </div>

            <!-- Performance per Court Breakdown -->
            <div class="bg-white rounded-xl border border-courtSlate-200 p-6 shadow-xs">
                <h3 class="font-display font-black text-lg text-courtSlate-900 uppercase tracking-tight mb-4">
                    Rincian Kinerja Antar 4 Lapangan
                </h3>

                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    <div
                        v-for="court in props.summary.court_stats"
                        :key="court.name"
                        class="p-4 rounded-xl border border-courtSlate-200 bg-courtSlate-50/50 hover:bg-white hover:border-courtSlate-300 transition"
                    >
                        <div class="flex justify-between items-start mb-2">
                            <h4 class="font-display font-black text-base text-courtSlate-900 uppercase">
                                {{ court.name }}
                            </h4>
                            <span class="text-[11px] font-display font-black px-1.5 py-0.5 rounded bg-courtSlate-200 text-courtSlate-700">
                                {{ court.occupancy_rate }}%
                            </span>
                        </div>
                        <div class="space-y-1 text-xs text-courtSlate-600">
                            <div class="flex justify-between">
                                <span>Jam Tersewa:</span>
                                <strong class="text-courtSlate-800">{{ court.hours_booked }} Jam</strong>
                            </div>
                            <div class="flex justify-between">
                                <span>Total Pesanan:</span>
                                <strong class="text-courtSlate-800">{{ court.bookings_count }} Sesi</strong>
                            </div>
                            <div class="flex justify-between pt-1 border-t border-courtSlate-200">
                                <span>Pendapatan:</span>
                                <strong class="text-emerald-700">{{ formatRupiah(court.revenue) }}</strong>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Preview Table Section -->
            <div class="bg-white rounded-xl border border-courtSlate-200 shadow-xs overflow-hidden">
                <div class="p-5 border-b border-courtSlate-200 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
                    <div>
                        <h3 class="font-display font-black text-lg text-courtSlate-900 uppercase tracking-tight">
                            Tabel Rincian Pemesanan Lapangan
                        </h3>
                        <p class="text-xs text-courtSlate-500">
                            Menampilkan data pemesanan pada periode yang dipilih.
                        </p>
                    </div>
                    <span class="text-xs text-courtSlate-500 font-medium">
                        Total {{ props.bookings.total }} transaksi
                    </span>
                </div>

                <div v-if="props.bookings.data && props.bookings.data.length > 0" class="overflow-x-auto">
                    <table class="w-full text-left text-xs text-courtSlate-600">
                        <thead class="bg-courtSlate-50 text-[11px] font-display font-extrabold uppercase tracking-wider text-courtSlate-500 border-b border-courtSlate-200">
                            <tr>
                                <th class="px-5 py-3">Invoice</th>
                                <th class="px-5 py-3">Tanggal</th>
                                <th class="px-5 py-3">Jam Main</th>
                                <th class="px-5 py-3">Lapangan</th>
                                <th class="px-5 py-3">Pelanggan</th>
                                <th class="px-5 py-3 text-right">Total Bayar</th>
                                <th class="px-5 py-3 text-center">Status Booking</th>
                                <th class="px-5 py-3 text-center">Pembayaran</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-courtSlate-100 font-medium">
                            <tr
                                v-for="booking in props.bookings.data"
                                :key="booking.id"
                                class="hover:bg-courtSlate-50/60 transition"
                            >
                                <td class="px-5 py-3.5 whitespace-nowrap font-mono text-[11px] text-courtSlate-500">
                                    {{ booking.payment?.invoice_number ?? '-' }}
                                </td>
                                <td class="px-5 py-3.5 whitespace-nowrap text-courtSlate-900 font-semibold">
                                    {{ formatDate(booking.booking_date) }}
                                </td>
                                <td class="px-5 py-3.5 whitespace-nowrap text-courtSlate-700">
                                    {{ booking.start_time.substring(0, 5) }} - {{ booking.end_time.substring(0, 5) }}
                                </td>
                                <td class="px-5 py-3.5 whitespace-nowrap font-bold text-courtSlate-900">
                                    {{ booking.court?.name ?? '-' }}
                                </td>
                                <td class="px-5 py-3.5 whitespace-nowrap">
                                    <div class="text-courtSlate-900 font-semibold">{{ booking.user?.name ?? 'User' }}</div>
                                    <div class="text-[10px] text-courtSlate-400">{{ booking.user?.phone ?? booking.user?.email ?? '-' }}</div>
                                </td>
                                <td class="px-5 py-3.5 whitespace-nowrap text-right font-display font-black text-sm text-courtSlate-900">
                                    {{ formatRupiah(booking.total_price) }}
                                </td>
                                <td class="px-5 py-3.5 whitespace-nowrap text-center">
                                    <span
                                        :class="[
                                            'inline-flex items-center px-2 py-0.5 text-[10px] font-display font-black tracking-wider uppercase rounded',
                                            booking.status === 'confirmed'
                                                ? 'bg-emerald-50 text-emerald-700 border border-emerald-200'
                                                : booking.status === 'pending'
                                                ? 'bg-amber-50 text-amber-700 border border-amber-200'
                                                : 'bg-rose-50 text-rose-700 border border-rose-200'
                                        ]"
                                    >
                                        {{ booking.status }}
                                    </span>
                                </td>
                                <td class="px-5 py-3.5 whitespace-nowrap text-center">
                                    <span
                                        :class="[
                                            'inline-flex items-center px-2 py-0.5 text-[10px] font-bold uppercase rounded',
                                            booking.payment?.status === 'paid'
                                                ? 'bg-emerald-100 text-emerald-800'
                                                : 'bg-courtSlate-100 text-courtSlate-600'
                                        ]"
                                    >
                                        {{ booking.payment?.status ?? 'unpaid' }}
                                    </span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Empty State -->
                <div v-else class="p-10 text-center text-courtSlate-400 text-xs">
                    Tidak ada transaksi booking pada rentang tanggal yang dipilih.
                </div>

                <!-- Pagination Links -->
                <div v-if="props.bookings.links && props.bookings.links.length > 3" class="p-4 border-t border-courtSlate-200 flex justify-center gap-1">
                    <component
                        :is="link.url ? 'Link' : 'span'"
                        v-for="(link, idx) in props.bookings.links"
                        :key="idx"
                        :href="link.url"
                        v-html="link.label"
                        :class="[
                            'px-3 py-1.5 text-xs rounded-md font-medium transition',
                            link.active
                                ? 'bg-arena-base text-white font-bold'
                                : link.url
                                ? 'text-courtSlate-700 hover:bg-courtSlate-100'
                                : 'text-courtSlate-300 cursor-not-allowed'
                        ]"
                    />
                </div>
            </div>
        </div>
    </AdminLayout>
</template>

