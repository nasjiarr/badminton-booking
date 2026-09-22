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
    backgroundColor: '#0A0F1D',
    titleColor: '#F8FAFC',
    bodyColor: '#CCFF00',
    borderColor: '#24324F',
    borderWidth: 1.5,
    padding: 12,
    cornerRadius: 10,
    boxPadding: 6,
    titleFont: { family: 'Barlow Condensed', weight: '800', size: 14 },
    bodyFont: { family: 'Plus Jakarta Sans', weight: '600', size: 12 },
};

// 1. Revenue Bar Chart Options (Minimal, clean, rounded bars)
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
                font: { family: 'Plus Jakarta Sans', weight: '600', size: 11 },
                maxRotation: 45,
            },
            border: { display: false },
        },
        y: {
            grid: {
                color: 'rgba(226, 232, 240, 0.7)',
                borderDash: [4, 4],
                drawBorder: false,
            },
            ticks: {
                color: '#64748B',
                font: { family: 'Plus Jakarta Sans', weight: '600', size: 11 },
                callback: (value) => 'Rp ' + (value >= 1000 ? (value / 1000).toLocaleString('id-ID') + 'k' : value),
            },
            border: { display: false },
        },
    },
}));

// 2. Occupancy Trend Line Chart Options (Clean minimalist grid & point styling)
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
                font: { family: 'Plus Jakarta Sans', weight: '600', size: 11 },
                maxRotation: 45,
            },
            border: { display: false },
        },
        y: {
            min: 0,
            max: 100,
            grid: {
                color: 'rgba(226, 232, 240, 0.7)',
                borderDash: [4, 4],
                drawBorder: false,
            },
            ticks: {
                color: '#64748B',
                font: { family: 'Plus Jakarta Sans', weight: '600', size: 11 },
                callback: (value) => value + '%',
            },
            border: { display: false },
        },
    },
}));

// 3. Court Comparison Doughnut Options (Clean 70% cutout & custom legend)
const courtComparisonOptions = computed(() => ({
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: {
            position: 'bottom',
            labels: {
                color: '#334155',
                font: { family: 'Plus Jakarta Sans', weight: '700', size: 12 },
                padding: 16,
                boxWidth: 12,
                boxHeight: 12,
                usePointStyle: true,
                pointStyle: 'circle',
            },
        },
        tooltip: {
            ...commonTooltip,
            callbacks: {
                label: (context) => ' ' + context.label + ': ' + context.parsed + ' Jam Sewa',
            },
        },
    },
    cutout: '70%',
}));
</script>

<template>
    <Head title="Admin Dashboard — Smash Arena" />

    <AdminLayout>
        <div class="space-y-8 p-4 sm:p-6 lg:p-8">
            <!-- Header Section with Quick Actions -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <div class="flex items-center gap-2 mb-1">
                        <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-display font-black tracking-wider uppercase bg-volt/20 text-arena-base border border-volt/40 -skew-x-6">
                            <span class="transform skew-x-6">COMMAND CENTER</span>
                        </span>
                        <span class="text-xs text-courtSlate-500 font-semibold">Real-Time Performance</span>
                    </div>
                    <h1 class="font-display font-black text-2xl sm:text-3xl lg:text-4xl text-courtSlate-900 tracking-tight uppercase">
                        Dashboard Ringkasan Bisnis
                    </h1>
                    <p class="mt-1 text-xs sm:text-sm text-courtSlate-600 font-medium">
                        Pantau metrik finansial, tingkat keterisian lapangan, dan pertumbuhan member arena secara terintegrasi.
                    </p>
                </div>

                <!-- Export Reports Action Group -->
                <div class="flex items-center gap-2.5">
                    <a
                        :href="route('admin.reports.export.excel', { start_date: filterForm.start_date, end_date: filterForm.end_date })"
                        target="_blank"
                        class="inline-flex items-center gap-2 px-4 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-display font-black uppercase tracking-wider rounded-xl shadow-sm transition -skew-x-3 cursor-pointer active:scale-95"
                        title="Unduh laporan Excel sesuai rentang filter saat ini"
                    >
                        <span class="inline-flex items-center gap-1.5 transform skew-x-3">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                            Export Excel
                        </span>
                    </a>
                    <a
                        :href="route('admin.reports.export.pdf', { start_date: filterForm.start_date, end_date: filterForm.end_date })"
                        target="_blank"
                        class="inline-flex items-center gap-2 px-4 py-2.5 bg-courtOrange hover:bg-courtOrange-hover text-white text-xs font-display font-black uppercase tracking-wider rounded-xl shadow-sm transition -skew-x-3 cursor-pointer active:scale-95"
                        title="Unduh ringkasan PDF sesuai rentang filter saat ini"
                    >
                        <span class="inline-flex items-center gap-1.5 transform skew-x-3">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                            </svg>
                            Export PDF
                        </span>
                    </a>
                </div>
            </div>

            <!-- ROW 1: 4 SUMMARY CARDS WITH DISTINCT HIERARCHY -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
                
                <!-- CARD 1: TOTAL PENDAPATAN HARI INI (HERO KPI - DARK ARENA STADIUM TREATMENT) -->
                <div class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-arena-card via-slate-900 to-arena-base border-2 border-volt/80 p-6 text-white shadow-card-active hover:shadow-volt-glow-sm transition-all duration-200">
                    <!-- Top glowing neon Volt accent stripe -->
                    <div class="absolute top-0 left-0 right-0 h-1.5 bg-gradient-to-r from-volt via-lime-300 to-emerald-400"></div>

                    <!-- Watermark shuttlecock background -->
                    <div class="absolute -right-4 -bottom-4 opacity-5 pointer-events-none text-volt">
                        <svg class="w-32 h-32" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M12 2L6 14l2 1 4-3 4 3 2-1L12 2zM12 17a2 2 0 100 4 2 2 0 000-4z" />
                        </svg>
                    </div>

                    <div class="relative z-10 flex items-center justify-between mb-3">
                        <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-display font-black tracking-wider uppercase bg-volt text-arena-base -skew-x-6">
                            <span class="transform skew-x-6">HERO KPI</span>
                        </span>
                        <div class="w-9 h-9 rounded-xl bg-volt/20 text-volt border border-volt/40 flex items-center justify-center font-display font-black text-sm">
                            Rp
                        </div>
                    </div>

                    <div class="relative z-10">
                        <span class="block text-xs font-display font-bold uppercase tracking-wider text-slate-300 mb-1">
                            Pendapatan Hari Ini
                        </span>
                        <div class="font-display font-black text-3xl sm:text-4xl text-volt tracking-tight tabular-nums">
                            {{ formatRupiah(summary?.total_revenue_today ?? 0) }}
                        </div>
                        <p class="mt-3 text-xs text-emerald-400 font-semibold flex items-center gap-1.5">
                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></span>
                            Pembayaran terkonfirmasi (paid)
                        </p>
                    </div>
                </div>

                <!-- CARD 2: TINGKAT OKUPANSI HARI INI (CAPACITY KPI - SPEED ORANGE TREATMENT) -->
                <div class="relative overflow-hidden rounded-2xl bg-white border-2 border-courtOrange/30 p-6 shadow-sm hover:border-courtOrange hover:shadow-card-elevated transition-all duration-200">
                    <!-- Top orange accent stripe -->
                    <div class="absolute top-0 left-0 right-0 h-1.5 bg-gradient-to-r from-courtOrange to-amber-500"></div>

                    <div class="flex items-center justify-between mb-3">
                        <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-display font-black tracking-wider uppercase bg-courtOrange/10 text-courtOrange border border-courtOrange/30 -skew-x-6">
                            <span class="transform skew-x-6">KAPASITAS LAPANGAN</span>
                        </span>
                        <div class="w-9 h-9 rounded-xl bg-orange-50 text-courtOrange border border-orange-200 flex items-center justify-center font-display font-black text-sm">
                            %
                        </div>
                    </div>

                    <div>
                        <span class="block text-xs font-display font-bold uppercase tracking-wider text-courtSlate-500 mb-1">
                            Okupansi Hari Ini
                        </span>
                        <div class="flex items-baseline gap-2">
                            <span class="font-display font-black text-4xl sm:text-5xl text-courtOrange tracking-tight tabular-nums">
                                {{ summary?.occupancy_rate_today ?? 0 }}%
                            </span>
                            <span class="text-xs text-courtSlate-500 font-semibold">
                                ({{ summary?.occupied_hours_today ?? 0 }} / {{ summary?.total_capacity_today ?? 64 }} Jam)
                            </span>
                        </div>

                        <!-- Progress track -->
                        <div class="w-full bg-courtSlate-100 rounded-full h-2 mt-3.5 overflow-hidden p-0.5">
                            <div
                                class="bg-gradient-to-r from-courtOrange to-amber-500 h-full rounded-full transition-all duration-500"
                                :style="{ width: `${Math.min(100, summary?.occupancy_rate_today ?? 0)}%` }"
                            ></div>
                        </div>
                    </div>
                </div>

                <!-- CARD 3: TOTAL BOOKING HARI INI (OPERATIONAL KPI - CLEAN SLATE TREATMENT) -->
                <div class="rounded-2xl bg-white border border-courtSlate-200 p-6 shadow-xs hover:border-courtSlate-400 hover:shadow-card-elevated transition-all duration-200">
                    <div class="flex items-center justify-between mb-3">
                        <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-display font-black tracking-wider uppercase bg-courtSlate-100 text-courtSlate-700 border border-courtSlate-300 -skew-x-6">
                            <span class="transform skew-x-6">OPERASIONAL</span>
                        </span>
                        <div class="w-9 h-9 rounded-xl bg-courtSlate-100 text-courtSlate-700 flex items-center justify-center text-base">
                            🏸
                        </div>
                    </div>

                    <div>
                        <span class="block text-xs font-display font-bold uppercase tracking-wider text-courtSlate-500 mb-1">
                            Booking Hari Ini
                        </span>
                        <div class="flex items-baseline gap-2">
                            <span class="font-display font-black text-4xl sm:text-5xl text-courtSlate-900 tracking-tight tabular-nums">
                                {{ summary?.total_bookings_today ?? 0 }}
                            </span>
                            <span class="text-xs text-courtSlate-500 font-bold uppercase">Sesi</span>
                        </div>
                        <p class="mt-3 text-xs text-courtSlate-500 font-medium">
                            Pesanan terjadwal hari ini (non-batal).
                        </p>
                    </div>
                </div>

                <!-- CARD 4: MEMBER BARU BULAN INI (GROWTH KPI - EMERALD ACCENT TREATMENT) -->
                <div class="rounded-2xl bg-white border border-courtSlate-200 p-6 shadow-xs hover:border-emerald-400 hover:shadow-card-elevated transition-all duration-200">
                    <div class="flex items-center justify-between mb-3">
                        <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-display font-black tracking-wider uppercase bg-emerald-50 text-emerald-700 border border-emerald-200 -skew-x-6">
                            <span class="transform skew-x-6">PERTUMBUHAN</span>
                        </span>
                        <div class="w-9 h-9 rounded-xl bg-emerald-50 text-emerald-600 border border-emerald-200 flex items-center justify-center text-sm">
                            ⭐
                        </div>
                    </div>

                    <div>
                        <span class="block text-xs font-display font-bold uppercase tracking-wider text-courtSlate-500 mb-1">
                            Member Baru Bulan Ini
                        </span>
                        <div class="flex items-baseline gap-2">
                            <span class="font-display font-black text-4xl sm:text-5xl text-courtSlate-900 tracking-tight tabular-nums">
                                {{ summary?.new_members_this_month ?? 0 }}
                            </span>
                            <span class="text-xs text-courtSlate-500 font-bold uppercase">User</span>
                        </div>
                        <p class="mt-3 text-xs text-courtSlate-500 font-medium">
                            Registrasi akun baru pada bulan berjalan.
                        </p>
                    </div>
                </div>

            </div>

            <!-- FILTER BAR: RENTANG TANGGAL CUSTOM (INERTIA PARTIAL RELOAD) -->
            <div class="rounded-2xl bg-white border border-courtSlate-200 p-5 sm:p-6 shadow-xs">
                <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-5">
                    <div>
                        <div class="flex items-center gap-2 mb-1">
                            <h3 class="font-display font-black text-lg text-courtSlate-900 uppercase tracking-tight flex items-center gap-2">
                                <span>📅 Filter Rentang Grafik</span>
                            </h3>
                            <span
                                v-if="filters.mode === 'custom'"
                                class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-display font-black uppercase tracking-wider bg-courtSlate-100 text-courtSlate-800 border border-courtSlate-300 -skew-x-6"
                            >
                                <span class="transform skew-x-6">Custom: {{ formatDateLabel(filters.start_date) }} - {{ formatDateLabel(filters.end_date) }}</span>
                            </span>
                            <span
                                v-else
                                class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-display font-black uppercase tracking-wider bg-volt/30 text-arena-base border border-volt/50 -skew-x-6"
                            >
                                <span class="transform skew-x-6">Rentang Standar</span>
                            </span>
                        </div>
                        <p class="text-xs text-courtSlate-500 font-medium">
                            Pilih rentang tanggal untuk memperbarui visual ketiga grafik secara instan via partial reload.
                        </p>
                    </div>

                    <!-- Presets & Tailored Custom Date Controls -->
                    <div class="flex flex-wrap items-center gap-3">
                        <!-- Preset Quick Buttons Group -->
                        <div class="inline-flex rounded-xl border border-courtSlate-200 bg-courtSlate-100/80 p-1">
                            <button
                                type="button"
                                @click="applyPreset(7)"
                                class="px-3 py-1.5 text-xs font-display font-bold uppercase tracking-wider rounded-lg transition"
                                :class="filters.mode === 'custom' && filterForm.start_date === props.filters.start_date ? 'bg-white text-courtSlate-900 shadow-xs' : 'text-courtSlate-600 hover:text-courtSlate-900'"
                            >
                                7 Hari
                            </button>
                            <button
                                type="button"
                                @click="applyPreset(30)"
                                class="px-3 py-1.5 text-xs font-display font-bold uppercase tracking-wider rounded-lg transition"
                                :class="filters.mode === 'custom' && filterForm.start_date === props.filters.start_date ? 'bg-white text-courtSlate-900 shadow-xs' : 'text-courtSlate-600 hover:text-courtSlate-900'"
                            >
                                30 Hari
                            </button>
                            <button
                                type="button"
                                @click="applyPreset('month')"
                                class="px-3 py-1.5 text-xs font-display font-bold uppercase tracking-wider rounded-lg transition"
                                :class="filters.mode === 'custom' && filterForm.start_date === props.filters.start_date ? 'bg-white text-courtSlate-900 shadow-xs' : 'text-courtSlate-600 hover:text-courtSlate-900'"
                            >
                                Bulan Ini
                            </button>
                        </div>

                        <!-- Tailored Date Inputs Container -->
                        <div class="flex items-center gap-1.5 bg-courtSlate-50 border border-courtSlate-200 rounded-xl p-1">
                            <!-- Start Date -->
                            <div class="relative flex items-center">
                                <svg class="w-3.5 h-3.5 text-courtSlate-400 absolute left-2.5 pointer-events-none" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                </svg>
                                <input
                                    type="date"
                                    v-model="filterForm.start_date"
                                    class="text-xs font-semibold pl-8 pr-2.5 py-1.5 bg-white border border-courtSlate-300 rounded-lg focus:border-volt focus:ring-1 focus:ring-volt text-courtSlate-900"
                                />
                            </div>

                            <span class="text-xs font-bold text-courtSlate-400 px-1">s/d</span>

                            <!-- End Date -->
                            <div class="relative flex items-center">
                                <svg class="w-3.5 h-3.5 text-courtSlate-400 absolute left-2.5 pointer-events-none" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                </svg>
                                <input
                                    type="date"
                                    v-model="filterForm.end_date"
                                    class="text-xs font-semibold pl-8 pr-2.5 py-1.5 bg-white border border-courtSlate-300 rounded-lg focus:border-volt focus:ring-1 focus:ring-volt text-courtSlate-900"
                                />
                            </div>
                        </div>

                        <!-- Submit Filter Button -->
                        <button
                            type="button"
                            @click="submitFilter"
                            :disabled="isFiltering || !filterForm.start_date || !filterForm.end_date"
                            class="inline-flex items-center gap-1.5 px-4 py-2 bg-arena-base hover:bg-arena-card text-volt text-xs font-display font-black uppercase tracking-wider rounded-xl transition disabled:opacity-50 cursor-pointer shadow-xs -skew-x-3 active:scale-95"
                        >
                            <span class="inline-flex items-center gap-1.5 transform skew-x-3">
                                <svg v-if="isFiltering" class="animate-spin -ml-1 mr-1 h-3.5 w-3.5 text-volt" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                <span>Terapkan</span>
                            </span>
                        </button>

                        <!-- Reset Filter -->
                        <button
                            v-if="filters.mode === 'custom'"
                            type="button"
                            @click="resetFilter"
                            :disabled="isFiltering"
                            class="px-2 py-1.5 text-xs text-courtSlate-500 hover:text-courtOrange font-display font-bold uppercase tracking-wider transition"
                        >
                            Reset
                        </button>
                    </div>
                </div>
            </div>

            <!-- ROW 2: 2 MAIN CHARTS (REVENUE BAR & OCCUPANCY TREND LINE) -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                
                <!-- GRAFIK 1: PENDAPATAN (BAR CHART) -->
                <div class="bg-white rounded-2xl border border-courtSlate-200 p-6 shadow-xs flex flex-col justify-between">
                    <div>
                        <div class="flex items-center justify-between mb-1">
                            <h3 class="font-display font-black text-lg text-courtSlate-900 uppercase tracking-tight flex items-center gap-2">
                                <span class="w-3 h-3 rounded-full bg-volt border border-arena-base shadow-xs"></span>
                                <span>Grafik Pendapatan Harian</span>
                            </h3>
                            <span class="text-xs font-display font-black text-courtSlate-900 bg-volt/30 border border-volt/50 px-2.5 py-0.5 rounded -skew-x-6">
                                <span class="inline-block transform skew-x-6">
                                    Total: {{ formatRupiah(charts.revenue?.total_in_period ?? 0) }}
                                </span>
                            </span>
                        </div>
                        <p class="text-xs text-courtSlate-500 font-medium mb-4">
                            Akumulasi pembayaran terkonfirmasi (status paid) per hari.
                        </p>
                    </div>

                    <div class="relative w-full h-64 sm:h-72">
                        <Bar
                            v-if="charts.revenue?.labels?.length"
                            :data="charts.revenue"
                            :options="revenueChartOptions"
                        />
                        <div v-else class="flex items-center justify-center h-full text-xs text-courtSlate-400 font-medium">
                            Tidak ada data pendapatan pada rentang waktu ini.
                        </div>
                    </div>
                </div>

                <!-- GRAFIK 2: TREN OKUPANSI (LINE CHART) -->
                <div class="bg-white rounded-2xl border border-courtSlate-200 p-6 shadow-xs flex flex-col justify-between">
                    <div>
                        <div class="flex items-center justify-between mb-1">
                            <h3 class="font-display font-black text-lg text-courtSlate-900 uppercase tracking-tight flex items-center gap-2">
                                <span class="w-3 h-3 rounded-full bg-courtOrange shadow-xs"></span>
                                <span>Tren Okupansi Lapangan (%)</span>
                            </h3>
                            <span class="text-xs font-display font-black text-courtOrange bg-orange-50 border border-orange-200 px-2.5 py-0.5 rounded -skew-x-6">
                                <span class="inline-block transform skew-x-6">
                                    Rata-rata: {{ charts.occupancy_trend?.average_rate ?? 0 }}%
                                </span>
                            </span>
                        </div>
                        <p class="text-xs text-courtSlate-500 font-medium mb-4">
                            Persentase jam terisi terhadap kapasitas total 4 lapangan per hari.
                        </p>
                    </div>

                    <div class="relative w-full h-64 sm:h-72">
                        <Line
                            v-if="charts.occupancy_trend?.labels?.length"
                            :data="charts.occupancy_trend"
                            :options="occupancyChartOptions"
                        />
                        <div v-else class="flex items-center justify-center h-full text-xs text-courtSlate-400 font-medium">
                            Tidak ada data okupansi pada rentang waktu ini.
                        </div>
                    </div>
                </div>

            </div>

            <!-- ROW 3: GRAFIK 3 (DOUGHNUT PERBANDINGAN 4 LAPANGAN & TABEL DETAIL) -->
            <div class="bg-white rounded-2xl border border-courtSlate-200 p-6 sm:p-8 shadow-xs">
                <div class="mb-6">
                    <h3 class="font-display font-black text-xl text-courtSlate-900 uppercase tracking-tight flex items-center gap-2">
                        <span>🏸 Perbandingan Okupansi Antar 4 Lapangan</span>
                    </h3>
                    <p class="text-xs sm:text-sm text-courtSlate-500 font-medium mt-0.5">
                        Melihat utilisasi jam dan preferensi pemain antar Lapangan A, B, C, dan D pada rentang terpilih.
                    </p>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-center">
                    
                    <!-- Doughnut Chart Container -->
                    <div class="lg:col-span-6 relative w-full h-72 sm:h-80 flex items-center justify-center">
                        <Doughnut
                            v-if="charts.court_comparison?.labels?.length"
                            :data="charts.court_comparison"
                            :options="courtComparisonOptions"
                        />
                        <div v-else class="text-xs text-courtSlate-400 font-medium">
                            Belum ada pemesanan lapangan pada periode ini.
                        </div>
                    </div>

                    <!-- Court Breakdown Table & Stats -->
                    <div class="lg:col-span-6 space-y-4">
                        <div class="border-b border-courtSlate-200 pb-3 flex justify-between items-center text-xs text-courtSlate-500 font-display font-black uppercase tracking-wider">
                            <span>Nama Lapangan</span>
                            <span>Utilisasi Jam / Kontribusi</span>
                        </div>

                        <div class="space-y-3">
                            <div
                                v-for="court in charts.court_comparison?.courts_detail"
                                :key="court.id"
                                class="flex items-center justify-between p-3.5 rounded-xl bg-courtSlate-50/80 border border-courtSlate-200 hover:border-courtSlate-300 transition"
                            >
                                <div class="flex items-center gap-3">
                                    <span
                                        class="w-4 h-4 rounded-full shrink-0 shadow-xs border border-white"
                                        :style="{ backgroundColor: court.color }"
                                    ></span>
                                    <div>
                                        <div class="font-display font-black text-sm text-courtSlate-900 uppercase">
                                            {{ court.name }}
                                        </div>
                                        <div class="text-[11px] text-courtSlate-500 font-medium">
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

                        <div class="pt-3 border-t border-courtSlate-200 flex justify-between items-center text-xs text-courtSlate-600 font-semibold">
                            <span>Total Jam Tersewa Keseluruhan:</span>
                            <span class="font-display font-black text-base text-courtSlate-900">
                                {{ charts.court_comparison?.total_hours ?? 0 }} Jam
                            </span>
                        </div>
                    </div>

                </div>
            </div>

        </div>
    </AdminLayout>
</template>
