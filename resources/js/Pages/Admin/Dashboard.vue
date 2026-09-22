<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { ref, reactive, computed } from 'vue';
import {
    Chart as ChartJS,
    Title,
    Tooltip,
    Legend,
    BarElement,
    CategoryScale,
    LinearScale,
    PointElement,
    LineElement,
    ArcElement,
    Filler
} from 'chart.js';
import { Bar, Line, Doughnut } from 'vue-chartjs';

ChartJS.register(
    Title,
    Tooltip,
    Legend,
    BarElement,
    CategoryScale,
    LinearScale,
    PointElement,
    LineElement,
    ArcElement,
    Filler
);

const props = defineProps({
    summary: Object,
    charts: Object,
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

const formatDateLabel = (dateStr) => {
    if (!dateStr) return '';
    const d = new Date(dateStr);
    return new Intl.DateTimeFormat('id-ID', {
        day: 'numeric',
        month: 'short',
        year: 'numeric'
    }).format(d);
};

// Date Presets
const applyPreset = (days) => {
    const today = new Date();
    const end = today.toISOString().split('T')[0];
    let start;

    if (days === 'month') {
        const firstDay = new Date(today.getFullYear(), today.getMonth(), 1);
        start = firstDay.toISOString().split('T')[0];
    } else {
        const startDate = new Date();
        startDate.setDate(today.getDate() - (days - 1));
        start = startDate.toISOString().split('T')[0];
    }

    filterForm.start_date = start;
    filterForm.end_date = end;
    submitFilter();
};

const resetFilter = () => {
    filterForm.start_date = '';
    filterForm.end_date = '';
    isFiltering.value = true;
    router.get(
        route('admin.dashboard'),
        {},
        {
            preserveState: true,
            preserveScroll: true,
            only: ['charts', 'filters'],
            onFinish: () => {
                isFiltering.value = false;
                filterForm.start_date = props.filters.start_date;
                filterForm.end_date = props.filters.end_date;
            }
        }
    );
};

const submitFilter = () => {
    if (!filterForm.start_date || !filterForm.end_date) return;

    isFiltering.value = true;
    router.get(
        route('admin.dashboard'),
        {
            start_date: filterForm.start_date,
            end_date: filterForm.end_date,
        },
        {
            preserveState: true,
            preserveScroll: true,
            only: ['charts', 'filters'],
            onFinish: () => {
                isFiltering.value = false;
            }
        }
    );
};

// Chart.js Shared Config & Themes (DESIGN.md Tokens)
const commonTooltip = {
    backgroundColor: '#111A2E',
    titleColor: '#F8FAFC',
    bodyColor: '#CCFF00',
    borderColor: '#24324F',
    borderWidth: 1,
    padding: 10,
    cornerRadius: 8,
    titleFont: { family: 'Barlow Condensed', weight: '700', size: 14 },
    bodyFont: { family: 'Plus Jakarta Sans', weight: '600', size: 12 },
};

// 1. Revenue Bar Chart Options
const revenueChartOptions = computed(() => ({
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: { display: false },
        tooltip: {
            ...commonTooltip,
            callbacks: {
                label: (context) => ' Pendapatan: ' + formatRupiah(context.parsed.y),
            },
        },
    },
    scales: {
        x: {
            grid: { display: false },
            ticks: {
                color: '#64748B',
                font: { family: 'Plus Jakarta Sans', size: 11 },
                maxRotation: 45,
            },
        },
        y: {
            grid: { color: '#F1F5F9' },
            ticks: {
                color: '#64748B',
                font: { family: 'Plus Jakarta Sans', size: 11 },
                callback: (value) => 'Rp ' + (value >= 1000 ? (value / 1000).toLocaleString('id-ID') + 'k' : value),
            },
        },
    },
}));

// 2. Occupancy Trend Line Chart Options
const occupancyChartOptions = computed(() => ({
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: { display: false },
        tooltip: {
            ...commonTooltip,
            bodyColor: '#FF5500',
            callbacks: {
                label: (context) => ' Okupansi: ' + context.parsed.y + '%',
            },
        },
    },
    scales: {
        x: {
            grid: { display: false },
            ticks: {
                color: '#64748B',
                font: { family: 'Plus Jakarta Sans', size: 11 },
                maxRotation: 45,
            },
        },
        y: {
            min: 0,
            max: 100,
            grid: { color: '#F1F5F9' },
            ticks: {
                color: '#64748B',
                font: { family: 'Plus Jakarta Sans', size: 11 },
                callback: (value) => value + '%',
            },
        },
    },
}));

// 3. Court Comparison Doughnut Options
const courtComparisonOptions = computed(() => ({
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: {
            position: 'bottom',
            labels: {
                color: '#334155',
                font: { family: 'Plus Jakarta Sans', weight: '600', size: 12 },
                padding: 14,
                boxWidth: 12,
                boxHeight: 12,
            },
        },
        tooltip: {
            ...commonTooltip,
            callbacks: {
                label: (context) => ' ' + context.label + ': ' + context.parsed + ' Jam Sewa',
            },
        },
    },
    cutout: '65%',
}));
</script>

<template>
    <Head title="Admin Dashboard — Smash Arena" />

    <AdminLayout>
        <div class="space-y-8">
            <!-- Header Section -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h1 class="font-display font-black text-2xl sm:text-3xl text-courtSlate-900 tracking-tight uppercase">
                        Dashboard Ringkasan Bisnis
                    </h1>
                    <p class="mt-1 text-sm text-courtSlate-600">
                        Pantau metrik finansial, tingkat keterisian lapangan, dan pertumbuhan member arena.
                    </p>
                </div>

                <div class="flex items-center gap-2">
                    <span class="inline-flex items-center gap-1.5 px-3 py-1 bg-volt/10 text-arena-base text-xs font-display font-black uppercase tracking-wider rounded border border-volt/30">
                        <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                        Sistem Aktif
                    </span>
                </div>
            </div>

            <!-- ROW 1: 4 SUMMARY CARDS -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
                
                <!-- Card 1: Total Booking Hari Ini -->
                <div class="bg-white rounded-xl border border-courtSlate-200 p-5 shadow-xs hover:border-courtSlate-300 transition">
                    <div class="flex items-center justify-between text-xs text-courtSlate-500 font-display font-bold uppercase tracking-wider mb-2">
                        <span>Booking Hari Ini</span>
                        <div class="w-8 h-8 rounded-lg bg-courtSlate-100 flex items-center justify-center text-courtSlate-700">
                            🏸
                        </div>
                    </div>
                    <div class="flex items-baseline gap-2">
                        <span class="font-display font-black text-3xl sm:text-4xl text-courtSlate-900 tabular-nums">
                            {{ summary?.total_bookings_today ?? 0 }}
                        </span>
                        <span class="text-xs text-courtSlate-500 font-medium">Sesi</span>
                    </div>
                    <p class="mt-2 text-xs text-courtSlate-500">
                        Total pesanan terjadwal hari ini (non-batal).
                    </p>
                </div>

                <!-- Card 2: Total Pendapatan Hari Ini -->
                <div class="bg-white rounded-xl border border-courtSlate-200 p-5 shadow-xs hover:border-volt/60 transition">
                    <div class="flex items-center justify-between text-xs text-courtSlate-500 font-display font-bold uppercase tracking-wider mb-2">
                        <span>Pendapatan Hari Ini</span>
                        <div class="w-8 h-8 rounded-lg bg-volt/20 flex items-center justify-center text-volt-contrast font-bold text-sm">
                            Rp
                        </div>
                    </div>
                    <div class="flex items-baseline gap-1">
                        <span class="font-display font-black text-2xl sm:text-3xl text-courtSlate-900 tabular-nums">
                            {{ formatRupiah(summary?.total_revenue_today ?? 0) }}
                        </span>
                    </div>
                    <p class="mt-2 text-xs text-emerald-600 font-medium flex items-center gap-1">
                        <span>✓</span> Pembayaran terkonfirmasi hari ini
                    </p>
                </div>

                <!-- Card 3: Tingkat Okupansi Hari Ini -->
                <div class="bg-white rounded-xl border border-courtSlate-200 p-5 shadow-xs hover:border-courtOrange/50 transition">
                    <div class="flex items-center justify-between text-xs text-courtSlate-500 font-display font-bold uppercase tracking-wider mb-2">
                        <span>Okupansi Hari Ini</span>
                        <div class="w-8 h-8 rounded-lg bg-orange-50 text-courtOrange flex items-center justify-center font-bold text-xs">
                            %
                        </div>
                    </div>
                    <div class="flex items-baseline gap-2">
                        <span class="font-display font-black text-3xl sm:text-4xl text-courtOrange tabular-nums">
                            {{ summary?.occupancy_rate_today ?? 0 }}%
                        </span>
                        <span class="text-xs text-courtSlate-500 font-medium">
                            ({{ summary?.occupied_hours_today ?? 0 }} / {{ summary?.total_capacity_today ?? 64 }} Jam)
                        </span>
                    </div>
                    <!-- Mini progress track -->
                    <div class="w-full bg-courtSlate-100 rounded-full h-1.5 mt-3 overflow-hidden">
                        <div
                            class="bg-courtOrange h-full rounded-full transition-all duration-500"
                            :style="{ width: `${Math.min(100, summary?.occupancy_rate_today ?? 0)}%` }"
                        ></div>
                    </div>
                </div>

                <!-- Card 4: Member Baru Bulan Ini -->
                <div class="bg-white rounded-xl border border-courtSlate-200 p-5 shadow-xs hover:border-courtSlate-300 transition">
                    <div class="flex items-center justify-between text-xs text-courtSlate-500 font-display font-bold uppercase tracking-wider mb-2">
                        <span>Member Baru Bulan Ini</span>
                        <div class="w-8 h-8 rounded-lg bg-sky-50 text-sky-600 flex items-center justify-center text-sm">
                            ⭐
                        </div>
                    </div>
                    <div class="flex items-baseline gap-2">
                        <span class="font-display font-black text-3xl sm:text-4xl text-courtSlate-900 tabular-nums">
                            {{ summary?.new_members_this_month ?? 0 }}
                        </span>
                        <span class="text-xs text-courtSlate-500 font-medium">User</span>
                    </div>
                    <p class="mt-2 text-xs text-courtSlate-500">
                        Registrasi user baru pada bulan berjalan.
                    </p>
                </div>

            </div>

            <!-- FILTER BAR: RENTANG TANGGAL CUSTOM (INERTIA PARTIAL RELOAD) -->
            <div class="bg-white rounded-xl border border-courtSlate-200 p-5 shadow-xs">
                <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                    <div>
                        <h3 class="font-display font-bold text-base text-courtSlate-900 uppercase tracking-tight flex items-center gap-2">
                            <span>📅 Filter Analitik Grafik</span>
                            <span v-if="filters.mode === 'custom'" class="text-[10px] font-sans font-semibold bg-courtSlate-100 text-courtSlate-700 px-2 py-0.5 rounded">
                                Custom Range: {{ formatDateLabel(filters.start_date) }} - {{ formatDateLabel(filters.end_date) }}
                            </span>
                            <span v-else class="text-[10px] font-sans font-semibold bg-volt/20 text-arena-base px-2 py-0.5 rounded">
                                Rentang Standar
                            </span>
                        </h3>
                        <p class="text-xs text-courtSlate-500 mt-0.5">
                            Pilih rentang tanggal untuk memperbarui data ketiga grafik secara serentak.
                        </p>
                    </div>

                    <!-- Preset Buttons & Custom Form -->
                    <div class="flex flex-wrap items-center gap-2">
                        <!-- Presets -->
                        <div class="inline-flex rounded-lg border border-courtSlate-200 bg-courtSlate-50 p-1">
                            <button
                                type="button"
                                @click="applyPreset(7)"
                                class="px-2.5 py-1 text-xs font-semibold rounded-md text-courtSlate-700 hover:bg-white hover:shadow-xs transition"
                            >
                                7 Hari
                            </button>
                            <button
                                type="button"
                                @click="applyPreset(30)"
                                class="px-2.5 py-1 text-xs font-semibold rounded-md text-courtSlate-700 hover:bg-white hover:shadow-xs transition"
                            >
                                30 Hari
                            </button>
                            <button
                                type="button"
                                @click="applyPreset('month')"
                                class="px-2.5 py-1 text-xs font-semibold rounded-md text-courtSlate-700 hover:bg-white hover:shadow-xs transition"
                            >
                                Bulan Ini
                            </button>
                        </div>

                        <!-- Date inputs -->
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
                            <span>Terapkan</span>
                        </button>

                        <!-- Reset -->
                        <button
                            v-if="filters.mode === 'custom'"
                            type="button"
                            @click="resetFilter"
                            :disabled="isFiltering"
                            class="px-2.5 py-1.5 text-xs text-courtSlate-500 hover:text-courtOrange transition font-medium"
                        >
                            Reset
                        </button>
                    </div>
                </div>
            </div>

            <!-- ROW 2: 2 MAIN CHARTS (REVENUE & OCCUPANCY TREND) -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                
                <!-- GRAFIK 1: PENDAPATAN (BAR CHART) -->
                <div class="bg-white rounded-xl border border-courtSlate-200 p-6 shadow-xs flex flex-col justify-between">
                    <div>
                        <div class="flex items-center justify-between mb-1">
                            <h3 class="font-display font-black text-lg text-courtSlate-900 uppercase tracking-tight flex items-center gap-2">
                                <span class="w-3 h-3 rounded-full bg-volt border border-arena-base"></span>
                                Grafik Pendapatan
                            </h3>
                            <span class="text-xs font-display font-black text-courtSlate-900 bg-volt/20 px-2 py-0.5 rounded -skew-x-6">
                                <span class="inline-block transform skew-x-6">
                                    Total: {{ formatRupiah(charts.revenue?.total_in_period ?? 0) }}
                                </span>
                            </span>
                        </div>
                        <p class="text-xs text-courtSlate-500 mb-4">
                            Akumulasi pembayaran terkonfirmasi (status paid) per hari.
                        </p>
                    </div>

                    <div class="relative w-full h-64 sm:h-72">
                        <Bar
                            v-if="charts.revenue?.labels?.length"
                            :data="charts.revenue"
                            :options="revenueChartOptions"
                        />
                        <div v-else class="flex items-center justify-center h-full text-xs text-courtSlate-400">
                            Tidak ada data pendapatan pada rentang waktu ini.
                        </div>
                    </div>
                </div>

                <!-- GRAFIK 2: TREN OKUPANSI (LINE CHART) -->
                <div class="bg-white rounded-xl border border-courtSlate-200 p-6 shadow-xs flex flex-col justify-between">
                    <div>
                        <div class="flex items-center justify-between mb-1">
                            <h3 class="font-display font-black text-lg text-courtSlate-900 uppercase tracking-tight flex items-center gap-2">
                                <span class="w-3 h-3 rounded-full bg-courtOrange"></span>
                                Tren Okupansi Lapangan (%)
                            </h3>
                            <span class="text-xs font-display font-black text-courtOrange bg-orange-50 px-2 py-0.5 rounded -skew-x-6">
                                <span class="inline-block transform skew-x-6">
                                    Rata-rata: {{ charts.occupancy_trend?.average_rate ?? 0 }}%
                                </span>
                            </span>
                        </div>
                        <p class="text-xs text-courtSlate-500 mb-4">
                            Persentase jam terisi terhadap kapasitas total 4 lapangan per hari.
                        </p>
                    </div>

                    <div class="relative w-full h-64 sm:h-72">
                        <Line
                            v-if="charts.occupancy_trend?.labels?.length"
                            :data="charts.occupancy_trend"
                            :options="occupancyChartOptions"
                        />
                        <div v-else class="flex items-center justify-center h-full text-xs text-courtSlate-400">
                            Tidak ada data okupansi pada rentang waktu ini.
                        </div>
                    </div>
                </div>

            </div>

            <!-- ROW 3: GRAFIK 3 (DOUGHNUT PERBANDINGAN 4 LAPANGAN & TABEL DETAIL) -->
            <div class="bg-white rounded-xl border border-courtSlate-200 p-6 shadow-xs">
                <div class="mb-4">
                    <h3 class="font-display font-black text-lg text-courtSlate-900 uppercase tracking-tight flex items-center gap-2">
                        <span>🏸 Perbandingan Okupansi Antar 4 Lapangan</span>
                    </h3>
                    <p class="text-xs text-courtSlate-500">
                        Melihat lapangan mana yang paling laris dan menghasilkan utilisasi jam tertinggi.
                    </p>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-center">
                    
                    <!-- Doughnut Chart Container -->
                    <div class="lg:col-span-6 relative w-full h-72 flex items-center justify-center">
                        <Doughnut
                            v-if="charts.court_comparison?.labels?.length"
                            :data="charts.court_comparison"
                            :options="courtComparisonOptions"
                        />
                        <div v-else class="text-xs text-courtSlate-400">
                            Belum ada pemesanan lapangan pada periode ini.
                        </div>
                    </div>

                    <!-- Court Breakdown Table & Stats -->
                    <div class="lg:col-span-6 space-y-4">
                        <div class="border-b border-courtSlate-100 pb-3 flex justify-between items-center text-xs text-courtSlate-500 font-display font-bold uppercase tracking-wider">
                            <span>Nama Lapangan</span>
                            <span>Utilisasi Jam / Pangsa</span>
                        </div>

                        <div class="space-y-3">
                            <div
                                v-for="court in charts.court_comparison?.courts_detail"
                                :key="court.id"
                                class="flex items-center justify-between p-3 rounded-lg bg-courtSlate-50/70 border border-courtSlate-100 hover:bg-courtSlate-100/60 transition"
                            >
                                <div class="flex items-center gap-3">
                                    <span
                                        class="w-3.5 h-3.5 rounded-full shrink-0 shadow-xs"
                                        :style="{ backgroundColor: court.color }"
                                    ></span>
                                    <div>
                                        <div class="font-display font-black text-sm text-courtSlate-900 uppercase">
                                            {{ court.name }}
                                        </div>
                                        <div class="text-[11px] text-courtSlate-500">
                                            Karpet Vinyl PBSI
                                        </div>
                                    </div>
                                </div>

                                <div class="text-right">
                                    <div class="font-display font-black text-base text-courtSlate-900 tabular-nums">
                                        {{ court.hours }} <span class="text-xs font-normal text-courtSlate-500">Jam</span>
                                    </div>
                                    <div class="text-xs font-bold text-courtSlate-600">
                                        {{ court.percentage }}%
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="pt-2 flex justify-between items-center text-xs text-courtSlate-500 font-medium">
                            <span>Total Jam Tersewa Keseluruhan:</span>
                            <span class="font-display font-black text-sm text-courtSlate-900">
                                {{ charts.court_comparison?.total_hours ?? 0 }} Jam
                            </span>
                        </div>
                    </div>

                </div>
            </div>

        </div>
    </AdminLayout>
</template>

