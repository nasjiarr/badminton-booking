<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import EmptyState from '@/Components/EmptyState.vue';
import LoadingSkeleton from '@/Components/LoadingSkeleton.vue';
import { Head, Link, router } from '@inertiajs/vue3';
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
            <div class="flex flex-col lg:flex-row lg:items-start lg:justify-between gap-5">
                <div>
                    <div class="flex items-center gap-2 mb-1">
                        <span class="court-badge-volt text-[10px] py-0.5"><span>Pusat Laporan</span></span>
                    </div>
                    <h1 class="font-display font-black text-2xl sm:text-3xl text-courtSlate-900 tracking-tight uppercase">
                        Laporan Booking & Pendapatan
                    </h1>
                    <p class="mt-1 text-sm text-courtSlate-600">
                        Unduh laporan transaksi detail dalam format Excel atau ringkasan eksekutif siap cetak dalam PDF.
                    </p>
                </div>

                <!-- Export Action Buttons — Prominent Cards -->
                <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3">
                    <a
                        :href="getExcelExportUrl()"
                        target="_blank"
                        class="group relative inline-flex items-center gap-3.5 px-5 py-3.5 bg-white rounded-2xl border-2 border-courtSlate-200 hover:border-emerald-500 shadow-xs hover:shadow-card-elevated transition-all duration-200 cursor-pointer"
                    >
                        <!-- Excel Icon -->
                        <div class="w-11 h-11 rounded-xl bg-emerald-50 border border-emerald-200 flex items-center justify-center shrink-0 group-hover:scale-105 transition-transform text-emerald-600">
                            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                        </div>
                        <div>
                            <div class="flex items-center gap-1.5 font-display font-black text-sm text-courtSlate-900 uppercase tracking-wider">
                                <span>Export Excel</span>
                                <span class="text-[10px] font-bold px-1.5 py-0.2 rounded bg-emerald-100 text-emerald-800">.XLSX</span>
                            </div>
                            <div class="text-[11px] text-courtSlate-500 font-medium mt-0.5">
                                Detail semua baris transaksi
                            </div>
                        </div>
                        <svg class="w-4 h-4 text-courtSlate-400 group-hover:text-emerald-600 group-hover:translate-x-0.5 transition-all ml-1 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                        </svg>
                    </a>

                    <a
                        :href="getPdfExportUrl()"
                        target="_blank"
                        class="group relative inline-flex items-center gap-3.5 px-5 py-3.5 bg-white rounded-2xl border-2 border-courtSlate-200 hover:border-courtOrange shadow-xs hover:shadow-card-elevated transition-all duration-200 cursor-pointer"
                    >
                        <!-- PDF Icon -->
                        <div class="w-11 h-11 rounded-xl bg-courtOrange-light border border-courtOrange/30 flex items-center justify-center shrink-0 group-hover:scale-105 transition-transform text-courtOrange">
                            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                            </svg>
                        </div>
                        <div>
                            <div class="flex items-center gap-1.5 font-display font-black text-sm text-courtSlate-900 uppercase tracking-wider">
                                <span>Export PDF</span>
                                <span class="text-[10px] font-bold px-1.5 py-0.2 rounded bg-courtOrange/20 text-courtOrange">.PDF</span>
                            </div>
                            <div class="text-[11px] text-courtSlate-500 font-medium mt-0.5">
                                Ringkasan resmi siap cetak
                            </div>
                        </div>
                        <svg class="w-4 h-4 text-courtSlate-400 group-hover:text-courtOrange group-hover:translate-x-0.5 transition-all ml-1 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                        </svg>
                    </a>
                </div>
            </div>

            <!-- Filter Bar Section -->
            <div class="bg-white rounded-2xl border-2 border-courtSlate-200 p-5 sm:p-6 shadow-card-elevated">
                <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                    <div>
                        <span class="text-[10px] font-display font-black uppercase tracking-widest text-courtSlate-500 block mb-1">
                            Rentang Tanggal Laporan
                        </span>
                        <div class="font-display font-black text-lg text-courtSlate-900">
                            {{ props.summary.period_label }}
                            <span class="text-sm font-bold text-courtSlate-500">({{ props.summary.days_count }} Hari)</span>
                        </div>
                    </div>

                    <div class="flex flex-wrap items-center gap-2">
                        <!-- Presets -->
                        <div class="inline-flex rounded-xl border-2 border-courtSlate-200 bg-courtSlate-50 p-1 gap-0.5">
                            <button
                                type="button"
                                @click="applyPreset('week')"
                                class="px-3 py-1.5 text-[10px] font-display font-black uppercase tracking-wider rounded-lg text-courtSlate-700 hover:bg-white hover:shadow-xs transition cursor-pointer"
                            >
                                7 Hari
                            </button>
                            <button
                                type="button"
                                @click="applyPreset('month')"
                                class="px-3 py-1.5 text-[10px] font-display font-black uppercase tracking-wider rounded-lg text-courtSlate-700 hover:bg-white hover:shadow-xs transition cursor-pointer"
                            >
                                30 Hari
                            </button>
                            <button
                                type="button"
                                @click="applyPreset('current_month')"
                                class="px-3 py-1.5 text-[10px] font-display font-black uppercase tracking-wider rounded-lg text-courtSlate-700 hover:bg-white hover:shadow-xs transition cursor-pointer"
                            >
                                Bulan Ini
                            </button>
                        </div>

                        <!-- Date Range Inputs -->
                        <div class="flex items-center gap-2">
                            <input
                                type="date"
                                v-model="filterForm.start_date"
                                class="text-xs rounded-xl border-2 border-courtSlate-200 bg-courtSlate-50 py-2 px-3 focus:border-volt focus:ring-2 focus:ring-volt/30 text-courtSlate-800 font-semibold"
                            />
                            <span class="text-xs font-display font-bold text-courtSlate-400">s/d</span>
                            <input
                                type="date"
                                v-model="filterForm.end_date"
                                class="text-xs rounded-xl border-2 border-courtSlate-200 bg-courtSlate-50 py-2 px-3 focus:border-volt focus:ring-2 focus:ring-volt/30 text-courtSlate-800 font-semibold"
                            />
                        </div>

                        <!-- Submit Button -->
                        <button
                            type="button"
                            @click="submitFilter"
                            :disabled="isFiltering || !filterForm.start_date || !filterForm.end_date"
                            class="inline-flex items-center gap-2 px-4 py-2 bg-arena-base text-volt text-xs font-display font-black uppercase tracking-wider rounded-xl hover:bg-arena-surface hover:shadow-volt-glow-sm transition disabled:opacity-50 cursor-pointer -skew-x-3"
                        >
                            <span class="inline-flex items-center gap-1.5 skew-x-3">
                                <svg v-if="isFiltering" class="animate-spin h-3.5 w-3.5" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                <svg v-else class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" />
                                </svg>
                                <span>Filter</span>
                            </span>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Summary KPI Cards -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
                
                <!-- Total Pendapatan — Primary emphasis -->
                <div class="relative overflow-hidden bg-arena-card rounded-2xl border-2 border-arena-border p-6 text-white shadow-card-elevated">
                    <div class="absolute inset-0 bg-gradient-to-br from-volt/10 via-transparent to-transparent pointer-events-none"></div>
                    <div class="relative">
                        <span class="text-[10px] font-display font-black uppercase tracking-widest text-courtSlate-400 block mb-2">
                            Total Pendapatan Terkonfirmasi
                        </span>
                        <div class="athletic-number font-black text-3xl sm:text-4xl text-volt tracking-tight leading-none">
                            {{ formatRupiah(props.summary.total_revenue) }}
                        </div>
                        <p class="mt-3 text-xs text-courtSlate-400 flex items-center gap-1.5">
                            <span class="w-1.5 h-1.5 rounded-full bg-volt"></span>
                            Akumulasi pembayaran berstatus PAID
                        </p>
                    </div>
                </div>

                <!-- Total Booking -->
                <div class="bg-white rounded-2xl border-2 border-courtSlate-200 p-6 shadow-xs">
                    <span class="text-[10px] font-display font-black uppercase tracking-widest text-courtSlate-500 block mb-2">
                        Total Pemesanan Lapangan
                    </span>
                    <div class="flex items-baseline gap-2">
                        <span class="athletic-number font-black text-3xl sm:text-4xl text-courtSlate-900 tracking-tight">
                            {{ props.summary.total_bookings }}
                        </span>
                        <span class="text-sm font-display font-bold text-courtSlate-400 uppercase">Sesi</span>
                    </div>
                    <div class="mt-3 flex flex-wrap items-center gap-2 text-[10px] font-display font-bold uppercase tracking-wider">
                        <span class="px-2 py-0.5 rounded bg-emerald-100 text-emerald-800 border border-emerald-200">
                            {{ props.summary.confirmed_bookings }} Konfirm
                        </span>
                        <span class="px-2 py-0.5 rounded bg-amber-100 text-amber-800 border border-amber-200">
                            {{ props.summary.pending_bookings }} Pending
                        </span>
                        <span class="px-2 py-0.5 rounded bg-courtSlate-100 text-courtSlate-600 border border-courtSlate-200">
                            {{ props.summary.cancelled_bookings }} Batal
                        </span>
                    </div>
                </div>

                <!-- Rata-rata Okupansi -->
                <div class="bg-white rounded-2xl border-2 border-courtSlate-200 p-6 shadow-xs">
                    <span class="text-[10px] font-display font-black uppercase tracking-widest text-courtSlate-500 block mb-2">
                        Rata-rata Okupansi Gelanggang
                    </span>
                    <div class="flex items-baseline gap-2">
                        <span class="athletic-number font-black text-3xl sm:text-4xl text-courtOrange tracking-tight">
                            {{ props.summary.overall_occupancy_rate }}%
                        </span>
                    </div>
                    <p class="mt-3 text-xs text-courtSlate-500">
                        <span class="font-display font-extrabold text-courtSlate-900">{{ props.summary.total_hours_booked }}</span> jam terpakai dari
                        <span class="font-display font-extrabold text-courtSlate-900">{{ props.summary.total_capacity }}</span> jam kapasitas
                    </p>
                </div>

            </div>

            <!-- Performance per Court Breakdown -->
            <div class="bg-white rounded-2xl border-2 border-courtSlate-200 p-6 shadow-xs">
                <h3 class="font-display font-black text-lg text-courtSlate-900 uppercase tracking-tight mb-5">
                    Rincian Kinerja Antar 4 Lapangan
                </h3>

                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    <div
                        v-for="court in props.summary.court_stats"
                        :key="court.name"
                        class="p-4 rounded-xl border-2 border-courtSlate-200 bg-courtSlate-50/50 hover:bg-white hover:border-courtSlate-300 hover:shadow-xs transition-all"
                    >
                        <div class="flex justify-between items-start mb-3">
                            <h4 class="font-display font-black text-sm text-courtSlate-900 uppercase">
                                {{ court.name }}
                            </h4>
                            <span
                                class="text-[10px] font-display font-black px-2 py-0.5 rounded border"
                                :class="court.occupancy_rate >= 70 ? 'bg-volt/20 text-volt-deep border-volt/30' : court.occupancy_rate >= 40 ? 'bg-amber-100 text-amber-800 border-amber-200' : 'bg-courtSlate-100 text-courtSlate-600 border-courtSlate-200'"
                            >
                                {{ court.occupancy_rate }}%
                            </span>
                        </div>
                        <!-- Mini occupancy bar -->
                        <div class="w-full bg-courtSlate-200 rounded-full h-1.5 mb-3 overflow-hidden">
                            <div
                                class="h-full rounded-full transition-all duration-500"
                                :class="court.occupancy_rate >= 70 ? 'bg-volt' : court.occupancy_rate >= 40 ? 'bg-amber-400' : 'bg-courtSlate-400'"
                                :style="{ width: `${court.occupancy_rate}%` }"
                            ></div>
                        </div>
                        <div class="space-y-1.5 text-xs text-courtSlate-600">
                            <div class="flex justify-between">
                                <span>Jam Tersewa:</span>
                                <strong class="text-courtSlate-800 font-display font-bold">{{ court.hours_booked }} Jam</strong>
                            </div>
                            <div class="flex justify-between">
                                <span>Total Pesanan:</span>
                                <strong class="text-courtSlate-800 font-display font-bold">{{ court.bookings_count }} Sesi</strong>
                            </div>
                            <div class="flex justify-between pt-2 border-t border-courtSlate-200">
                                <span>Pendapatan:</span>
                                <strong class="text-volt-deep font-display font-bold">{{ formatRupiah(court.revenue) }}</strong>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Preview Table Section with Loading Skeleton & Empty State -->
            <div class="bg-white rounded-2xl border-2 border-courtSlate-200 shadow-card-elevated overflow-hidden">
                <div class="p-5 sm:p-6 border-b-2 border-courtSlate-200 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
                    <div>
                        <h3 class="font-display font-black text-lg text-courtSlate-900 uppercase tracking-tight">
                            Tabel Rincian Pemesanan Lapangan
                        </h3>
                        <p class="text-xs text-courtSlate-500 mt-0.5">
                            Menampilkan data pemesanan pada periode yang dipilih.
                        </p>
                    </div>
                    <span class="text-xs text-courtSlate-400 font-display font-bold uppercase tracking-wider">
                        {{ props.bookings.total }} Transaksi
                    </span>
                </div>

                <!-- Loading Skeleton during filter request -->
                <div v-if="isFiltering" class="p-6">
                    <LoadingSkeleton variant="table-row" :count="5" />
                </div>

                <!-- Table Content -->
                <div v-else-if="props.bookings.data && props.bookings.data.length > 0" class="overflow-x-auto">
                    <table class="w-full text-left text-xs text-courtSlate-600">
                        <thead class="bg-arena-base text-[10px] font-display font-black uppercase tracking-widest text-volt/80 border-b border-arena-border">
                            <tr>
                                <th class="px-5 py-3.5">Invoice</th>
                                <th class="px-5 py-3.5">Tanggal</th>
                                <th class="px-5 py-3.5">Jam Main</th>
                                <th class="px-5 py-3.5">Lapangan</th>
                                <th class="px-5 py-3.5">Pelanggan</th>
                                <th class="px-5 py-3.5 text-right">Total Bayar</th>
                                <th class="px-5 py-3.5 text-center">Status Booking</th>
                                <th class="px-5 py-3.5 text-center">Pembayaran</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-courtSlate-100 font-medium">
                            <tr
                                v-for="booking in props.bookings.data"
                                :key="booking.id"
                                class="hover:bg-courtSlate-50/60 transition"
                            >
                                <td class="px-5 py-3.5 whitespace-nowrap font-mono text-[10px] text-courtSlate-500">
                                    {{ booking.payment?.invoice_number ?? '-' }}
                                </td>
                                <td class="px-5 py-3.5 whitespace-nowrap text-courtSlate-900 font-semibold">
                                    {{ formatDate(booking.booking_date) }}
                                </td>
                                <td class="px-5 py-3.5 whitespace-nowrap font-display font-bold text-courtSlate-700">
                                    {{ booking.start_time.substring(0, 5) }} - {{ booking.end_time.substring(0, 5) }}
                                </td>
                                <td class="px-5 py-3.5 whitespace-nowrap font-display font-bold text-courtSlate-900">
                                    {{ booking.court?.name ?? '-' }}
                                </td>
                                <td class="px-5 py-3.5 whitespace-nowrap">
                                    <div class="text-courtSlate-900 font-semibold">{{ booking.user?.name ?? 'User' }}</div>
                                    <div class="text-[10px] text-courtSlate-400">{{ booking.user?.phone ?? booking.user?.email ?? '-' }}</div>
                                </td>
                                <td class="px-5 py-3.5 whitespace-nowrap text-right athletic-number font-black text-sm text-courtSlate-900">
                                    {{ formatRupiah(booking.total_price) }}
                                </td>
                                <td class="px-5 py-3.5 whitespace-nowrap text-center">
                                    <span
                                        :class="[
                                            'inline-flex items-center px-2 py-0.5 text-[10px] font-display font-black tracking-wider uppercase rounded border',
                                            booking.status === 'confirmed'
                                                ? 'bg-emerald-100 text-emerald-800 border-emerald-300'
                                                : booking.status === 'pending'
                                                ? 'bg-amber-100 text-amber-800 border-amber-300'
                                                : 'bg-courtOrange/10 text-courtOrange border-courtOrange/30'
                                        ]"
                                    >
                                        {{ booking.status }}
                                    </span>
                                </td>
                                <td class="px-5 py-3.5 whitespace-nowrap text-center">
                                    <span
                                        :class="[
                                            'inline-flex items-center px-2 py-0.5 text-[10px] font-display font-bold uppercase rounded border',
                                            booking.payment?.status === 'paid'
                                                ? 'bg-volt/20 text-volt-deep border-volt/30'
                                                : 'bg-courtSlate-100 text-courtSlate-600 border-courtSlate-200'
                                        ]"
                                    >
                                        {{ booking.payment?.status ?? 'unpaid' }}
                                    </span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Empty State Component Reusable -->
                <div v-else class="p-8">
                    <EmptyState
                        icon="📋"
                        title="Tidak Ada Transaksi"
                        description="Tidak ada transaksi pemesanan lapangan pada rentang tanggal yang dipilih. Silakan ubah filter tanggal atau pilih preset lain."
                    />
                </div>

                <!-- Pagination Links -->
                <div v-if="props.bookings.links && props.bookings.links.length > 3" class="p-4 border-t-2 border-courtSlate-200 flex justify-center gap-1">
                    <component
                        :is="link.url ? Link : 'span'"
                        v-for="(link, idx) in props.bookings.links"
                        :key="idx"
                        :href="link.url"
                        v-html="link.label"
                        :class="[
                            'px-3 py-1.5 text-xs rounded-lg font-display font-bold transition',
                            link.active
                                ? 'bg-arena-base text-volt'
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
