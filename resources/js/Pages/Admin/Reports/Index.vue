<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import EmptyState from '@/Components/EmptyState.vue';
import LoadingSkeleton from '@/Components/LoadingSkeleton.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, reactive, computed } from 'vue';

const props = defineProps({
    summary: {
        type: Object,
        default: () => ({}),
    },
    bookings: {
        type: Object,
        default: () => ({ data: [], links: [] }),
    },
    filters: {
        type: Object,
        default: () => ({}),
    },
});

const isFiltering = ref(false);

const filterForm = reactive({
    start_date: props.filters?.start_date || '',
    end_date: props.filters?.end_date || '',
});

const formatRupiah = (value) => {
    const num = Number(value) || 0;
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0,
        maximumFractionDigits: 0,
    }).format(num);
};

const formatDate = (dateStr) => {
    if (!dateStr) return '-';
    try {
        const cleanStr = String(dateStr).split('T')[0];
        const parts = cleanStr.split('-');
        if (parts.length === 3) {
            const year = parseInt(parts[0], 10);
            const month = parseInt(parts[1], 10) - 1;
            const day = parseInt(parts[2], 10);
            const d = new Date(year, month, day);
            if (!isNaN(d.getTime())) {
                return new Intl.DateTimeFormat('id-ID', {
                    day: 'numeric',
                    month: 'short',
                    year: 'numeric'
                }).format(d);
            }
        }
        const d = new Date(dateStr);
        if (!isNaN(d.getTime())) {
            return new Intl.DateTimeFormat('id-ID', {
                day: 'numeric',
                month: 'short',
                year: 'numeric'
            }).format(d);
        }
    } catch {
        // fallback
    }
    return String(dateStr).split('T')[0];
};

const formatTimeSlot = (start, end) => {
    if (!start || !end) return '-';
    const s = String(start).substring(0, 5);
    const e = String(end).substring(0, 5);
    return `${s} - ${e}`;
};

// Check which preset is currently selected
const currentPreset = computed(() => {
    if (!filterForm.start_date || !filterForm.end_date) return null;
    const today = new Date();
    const end = today.toISOString().split('T')[0];
    if (filterForm.end_date !== end) return null;

    const dWeek = new Date();
    dWeek.setDate(today.getDate() - 6);
    if (filterForm.start_date === dWeek.toISOString().split('T')[0]) return 'week';

    const dMonth = new Date();
    dMonth.setDate(today.getDate() - 29);
    if (filterForm.start_date === dMonth.toISOString().split('T')[0]) return 'month';

    const firstDay = new Date(today.getFullYear(), today.getMonth(), 1).toISOString().split('T')[0];
    if (filterForm.start_date === firstDay) return 'current_month';

    return null;
});

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

// Status badge styling helpers
const getBookingStatusBadge = (status) => {
    const s = String(status || '').toLowerCase();
    switch (s) {
        case 'confirmed':
        case 'completed':
            return {
                label: s === 'completed' ? 'Selesai' : 'Terkonfirmasi',
                classes: 'bg-emerald-50 text-emerald-700 border-emerald-300',
                dotClass: 'bg-emerald-500',
            };
        case 'pending':
            return {
                label: 'Menunggu',
                classes: 'bg-amber-50 text-amber-700 border-amber-300',
                dotClass: 'bg-amber-500',
            };
        case 'cancelled':
            return {
                label: 'Dibatalkan',
                classes: 'bg-rose-50 text-rose-700 border-rose-300',
                dotClass: 'bg-rose-500',
            };
        default:
            return {
                label: status || 'Pending',
                classes: 'bg-courtSlate-100 text-courtSlate-600 border-courtSlate-300',
                dotClass: 'bg-courtSlate-400',
            };
    }
};

const getPaymentStatusBadge = (status) => {
    const s = String(status || '').toLowerCase();
    switch (s) {
        case 'paid':
            return {
                label: 'LUNAS',
                classes: 'bg-volt/20 text-volt-deep border-volt/40 font-black',
                dotClass: 'bg-emerald-600',
            };
        case 'pending':
        case 'unpaid':
            return {
                label: 'MENUNGGU',
                classes: 'bg-amber-50 text-amber-700 border-amber-300',
                dotClass: 'bg-amber-500',
            };
        case 'failed':
        case 'expired':
            return {
                label: s === 'expired' ? 'KEDALUWARSA' : 'GAGAL',
                classes: 'bg-rose-50 text-rose-700 border-rose-300',
                dotClass: 'bg-rose-500',
            };
        default:
            return {
                label: status ? String(status).toUpperCase() : 'BELUM BAYAR',
                classes: 'bg-courtSlate-100 text-courtSlate-600 border-courtSlate-300',
                dotClass: 'bg-courtSlate-400',
            };
    }
};

const getInitials = (name) => {
    if (!name || typeof name !== 'string') return 'U';
    const words = name.trim().split(/\s+/);
    if (words.length >= 2) {
        return (words[0][0] + words[1][0]).toUpperCase();
    }
    return name.slice(0, 2).toUpperCase();
};
</script>

<template>
    <Head title="Laporan Booking & Pendapatan — Admin Smash Arena" />

    <AdminLayout>
        <div class="space-y-8 p-4 sm:p-6 lg:p-8 max-w-7xl mx-auto">
            
            <!-- Header Section with Action Cards -->
            <div class="flex flex-col lg:flex-row lg:items-start lg:justify-between gap-6">
                <div>
                    <div class="flex items-center gap-2 mb-2">
                        <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-display font-black tracking-wider uppercase bg-volt/20 text-arena-base border border-volt/40 -skew-x-6">
                            <span class="transform skew-x-6">PUSAT LAPORAN BISNIS</span>
                        </span>
                        <span class="text-xs text-courtSlate-500 font-semibold">Financial & Operational Analytics</span>
                    </div>
                    <h1 class="font-display font-black text-2xl sm:text-3xl lg:text-4xl text-courtSlate-900 tracking-tight uppercase">
                        Laporan Booking & Pendapatan
                    </h1>
                    <p class="mt-1.5 text-xs sm:text-sm text-courtSlate-600 max-w-2xl">
                        Pantau performa pendapatan gelanggang, tingkat okupansi lapangan, dan unduh rekapan resmi dalam format spreadsheet Excel (.xlsx) atau dokumen cetak PDF (.pdf).
                    </p>
                </div>

                <!-- Export Action Buttons — Prominent Cards -->
                <div class="flex flex-col sm:flex-row items-stretch gap-3 shrink-0">
                    <!-- Export Excel -->
                    <a
                        :href="getExcelExportUrl()"
                        target="_blank"
                        class="group relative flex items-center gap-3.5 px-5 py-3.5 bg-white rounded-2xl border-2 border-courtSlate-200 hover:border-emerald-500 shadow-xs hover:shadow-card-elevated hover:-translate-y-0.5 transition-all duration-200 cursor-pointer"
                        title="Unduh seluruh baris transaksi dalam format Excel"
                    >
                        <!-- Excel Icon -->
                        <div class="w-11 h-11 rounded-xl bg-emerald-50 border border-emerald-200 flex items-center justify-center shrink-0 group-hover:scale-105 group-hover:bg-emerald-100 transition-all text-emerald-600">
                            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                        </div>
                        <div>
                            <div class="flex items-center gap-1.5 font-display font-black text-sm text-courtSlate-900 uppercase tracking-wider">
                                <span>Export Excel</span>
                                <span class="text-[10px] font-bold px-1.5 py-0.5 rounded bg-emerald-100 text-emerald-800 border border-emerald-200">.XLSX</span>
                            </div>
                            <div class="text-[11px] text-courtSlate-500 font-medium mt-0.5">
                                Detail semua baris transaksi
                            </div>
                        </div>
                        <svg class="w-4 h-4 text-courtSlate-400 group-hover:text-emerald-600 group-hover:translate-x-0.5 transition-all ml-1 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                        </svg>
                    </a>

                    <!-- Export PDF -->
                    <a
                        :href="getPdfExportUrl()"
                        target="_blank"
                        class="group relative flex items-center gap-3.5 px-5 py-3.5 bg-white rounded-2xl border-2 border-courtSlate-200 hover:border-courtOrange shadow-xs hover:shadow-card-elevated hover:-translate-y-0.5 transition-all duration-200 cursor-pointer"
                        title="Unduh ringkasan eksekutif siap cetak dalam format PDF"
                    >
                        <!-- PDF Icon -->
                        <div class="w-11 h-11 rounded-xl bg-courtOrange-light border border-courtOrange/30 flex items-center justify-center shrink-0 group-hover:scale-105 group-hover:bg-courtOrange-light/80 transition-all text-courtOrange">
                            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                            </svg>
                        </div>
                        <div>
                            <div class="flex items-center gap-1.5 font-display font-black text-sm text-courtSlate-900 uppercase tracking-wider">
                                <span>Export PDF</span>
                                <span class="text-[10px] font-bold px-1.5 py-0.5 rounded bg-courtOrange/15 text-courtOrange border border-courtOrange/30">.PDF</span>
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

            <!-- Filter Bar Section (Unified, Responsive, Athletic) -->
            <div class="bg-white rounded-2xl border-2 border-courtSlate-200 p-5 sm:p-6 shadow-xs">
                <div class="flex flex-col xl:flex-row xl:items-center xl:justify-between gap-5">
                    
                    <!-- Left: Current Period Info -->
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-courtSlate-100 border border-courtSlate-200 flex items-center justify-center text-courtSlate-600 shrink-0">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                        </div>
                        <div>
                            <span class="text-[10px] font-display font-black uppercase tracking-widest text-courtSlate-400 block leading-tight">
                                Periode Laporan Aktif
                            </span>
                            <div class="flex items-center gap-2 mt-0.5">
                                <span class="font-display font-black text-lg text-courtSlate-900 leading-tight">
                                    {{ props.summary?.period_label || 'Bulan Ini' }}
                                </span>
                                <span class="text-[11px] font-display font-bold px-2 py-0.5 rounded-full bg-courtSlate-100 text-courtSlate-600 border border-courtSlate-200">
                                    {{ props.summary?.days_count || 0 }} Hari
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Right: Preset Buttons + Date Range Form -->
                    <div class="flex flex-col sm:flex-row flex-wrap items-stretch sm:items-center gap-3">
                        
                        <!-- Quick Presets -->
                        <div class="inline-flex rounded-xl border-2 border-courtSlate-200 bg-courtSlate-100/70 p-1 gap-1">
                            <button
                                type="button"
                                @click="applyPreset('week')"
                                :class="[
                                    'px-3 py-1.5 text-xs font-display uppercase tracking-wider rounded-lg transition-all cursor-pointer',
                                    currentPreset === 'week'
                                        ? 'bg-arena-base text-volt font-black shadow-xs'
                                        : 'text-courtSlate-600 hover:text-courtSlate-900 hover:bg-white font-bold'
                                ]"
                            >
                                7 Hari
                            </button>
                            <button
                                type="button"
                                @click="applyPreset('month')"
                                :class="[
                                    'px-3 py-1.5 text-xs font-display uppercase tracking-wider rounded-lg transition-all cursor-pointer',
                                    currentPreset === 'month'
                                        ? 'bg-arena-base text-volt font-black shadow-xs'
                                        : 'text-courtSlate-600 hover:text-courtSlate-900 hover:bg-white font-bold'
                                ]"
                            >
                                30 Hari
                            </button>
                            <button
                                type="button"
                                @click="applyPreset('current_month')"
                                :class="[
                                    'px-3 py-1.5 text-xs font-display uppercase tracking-wider rounded-lg transition-all cursor-pointer',
                                    currentPreset === 'current_month'
                                        ? 'bg-arena-base text-volt font-black shadow-xs'
                                        : 'text-courtSlate-600 hover:text-courtSlate-900 hover:bg-white font-bold'
                                ]"
                            >
                                Bulan Ini
                            </button>
                        </div>

                        <!-- Date Range Inputs Container -->
                        <div class="flex items-center gap-2 bg-courtSlate-50 border-2 border-courtSlate-200 rounded-xl px-2.5 py-1">
                            <input
                                type="date"
                                v-model="filterForm.start_date"
                                aria-label="Tanggal Mulai"
                                class="border-0 bg-transparent text-xs text-courtSlate-800 font-semibold p-1 focus:ring-0 cursor-pointer"
                            />
                            <span class="text-xs font-display font-bold text-courtSlate-400 uppercase">s/d</span>
                            <input
                                type="date"
                                v-model="filterForm.end_date"
                                aria-label="Tanggal Akhir"
                                class="border-0 bg-transparent text-xs text-courtSlate-800 font-semibold p-1 focus:ring-0 cursor-pointer"
                            />
                        </div>

                        <!-- Submit Filter Button -->
                        <button
                            type="button"
                            @click="submitFilter"
                            :disabled="isFiltering || !filterForm.start_date || !filterForm.end_date"
                            class="inline-flex items-center justify-center gap-2 px-5 py-2.5 bg-arena-base text-volt text-xs font-display font-black uppercase tracking-wider rounded-xl hover:bg-arena-surface hover:shadow-volt-glow-sm transition disabled:opacity-50 cursor-pointer active:scale-95 -skew-x-3"
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

            <!-- Summary KPI Cards (Stadium Polish) -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
                
                <!-- CARD 1: Total Pendapatan — Primary Hero Card -->
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
                            Total Pendapatan Terkonfirmasi
                        </span>
                        <div class="font-display font-black text-3xl sm:text-4xl text-volt tracking-tight tabular-nums">
                            {{ formatRupiah(props.summary?.total_revenue) }}
                        </div>
                        <p class="mt-3 text-xs text-emerald-400 font-semibold flex items-center gap-1.5">
                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></span>
                            Akumulasi pembayaran berstatus PAID
                        </p>
                    </div>
                </div>

                <!-- CARD 2: Total Pemesanan Sesi -->
                <div class="bg-white rounded-2xl border-2 border-courtSlate-200 p-6 shadow-xs hover:border-courtSlate-300 transition-all flex flex-col justify-between">
                    <div>
                        <div class="flex items-center justify-between mb-3">
                            <span class="text-[10px] font-display font-black uppercase tracking-widest text-courtSlate-500">
                                Total Pemesanan Lapangan
                            </span>
                            <div class="w-9 h-9 rounded-xl bg-courtSlate-100 border border-courtSlate-200 flex items-center justify-center text-courtSlate-700 font-display font-black text-sm">
                                🏸
                            </div>
                        </div>
                        
                        <div class="flex items-baseline gap-2">
                            <span class="font-display font-black text-3xl sm:text-4xl text-courtSlate-900 tracking-tight tabular-nums">
                                {{ props.summary?.total_bookings ?? 0 }}
                            </span>
                            <span class="text-sm font-display font-extrabold text-courtSlate-400 uppercase">Sesi</span>
                        </div>
                    </div>

                    <div class="mt-4 pt-4 border-t border-courtSlate-100 flex flex-wrap items-center gap-2 text-[10px] font-display font-black uppercase tracking-wider">
                        <span class="px-2.5 py-1 rounded-lg bg-emerald-50 text-emerald-700 border border-emerald-200">
                            {{ props.summary?.confirmed_bookings ?? 0 }} Konfirmasi
                        </span>
                        <span class="px-2.5 py-1 rounded-lg bg-amber-50 text-amber-700 border border-amber-200">
                            {{ props.summary?.pending_bookings ?? 0 }} Menunggu
                        </span>
                        <span class="px-2.5 py-1 rounded-lg bg-courtSlate-100 text-courtSlate-600 border border-courtSlate-200">
                            {{ props.summary?.cancelled_bookings ?? 0 }} Batal
                        </span>
                    </div>
                </div>

                <!-- CARD 3: Rata-rata Okupansi Gelanggang -->
                <div class="bg-white rounded-2xl border-2 border-courtSlate-200 p-6 shadow-xs hover:border-courtSlate-300 transition-all flex flex-col justify-between">
                    <div>
                        <div class="flex items-center justify-between mb-3">
                            <span class="text-[10px] font-display font-black uppercase tracking-widest text-courtSlate-500">
                                Rata-rata Okupansi Gelanggang
                            </span>
                            <div class="w-9 h-9 rounded-xl bg-courtOrange-light border border-courtOrange/30 flex items-center justify-center text-courtOrange font-display font-black text-sm">
                                %
                            </div>
                        </div>
                        
                        <div class="flex items-baseline gap-2">
                            <span class="font-display font-black text-3xl sm:text-4xl text-courtOrange tracking-tight tabular-nums">
                                {{ props.summary?.overall_occupancy_rate ?? 0 }}%
                            </span>
                        </div>

                        <!-- Progress Bar Okupansi -->
                        <div class="w-full bg-courtSlate-100 rounded-full h-2 mt-3 overflow-hidden border border-courtSlate-200">
                            <div
                                class="h-full rounded-full transition-all duration-500"
                                :class="(props.summary?.overall_occupancy_rate ?? 0) >= 70 ? 'bg-emerald-500' : (props.summary?.overall_occupancy_rate ?? 0) >= 40 ? 'bg-courtOrange' : 'bg-courtSlate-400'"
                                :style="{ width: `${Math.min(100, props.summary?.overall_occupancy_rate ?? 0)}%` }"
                            ></div>
                        </div>
                    </div>

                    <div class="mt-4 pt-4 border-t border-courtSlate-100 text-xs text-courtSlate-500">
                        <span class="font-display font-black text-courtSlate-900">{{ props.summary?.total_hours_booked ?? 0 }}</span> jam terpakai dari
                        <span class="font-display font-black text-courtSlate-900">{{ props.summary?.total_capacity ?? 0 }}</span> jam kapasitas total
                    </div>
                </div>

            </div>

            <!-- Court Breakdown Cards ("Rincian Performa Antar Lapangan") -->
            <div class="bg-white rounded-2xl border-2 border-courtSlate-200 p-5 sm:p-6 shadow-xs">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 mb-5">
                    <div>
                        <h2 class="font-display font-black text-lg text-courtSlate-900 uppercase tracking-tight">
                            Rincian Performa Antar 4 Lapangan
                        </h2>
                        <p class="text-xs text-courtSlate-500 mt-0.5">
                            Perbandingan utilisasi waktu sewa, jumlah sesi, dan perolehan omzet setiap lapangan.
                        </p>
                    </div>
                    <span class="text-xs font-display font-bold uppercase tracking-wider text-courtSlate-400">
                        4 Lapangan Aktif
                    </span>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    <div
                        v-for="court in (props.summary?.court_stats || [])"
                        :key="court.name"
                        class="p-4 rounded-xl border-2 border-courtSlate-200 bg-courtSlate-50/60 hover:bg-white hover:border-courtSlate-300 hover:shadow-xs transition-all flex flex-col justify-between"
                    >
                        <div>
                            <div class="flex justify-between items-start mb-2.5">
                                <h3 class="font-display font-black text-sm text-courtSlate-900 uppercase flex items-center gap-1.5">
                                    <span>🏸</span>
                                    <span>{{ court.name }}</span>
                                </h3>
                                <span
                                    class="text-[10px] font-display font-black px-2 py-0.5 rounded border"
                                    :class="court.occupancy_rate >= 70 ? 'bg-emerald-50 text-emerald-700 border-emerald-300' : court.occupancy_rate >= 40 ? 'bg-amber-50 text-amber-700 border-amber-300' : 'bg-courtSlate-100 text-courtSlate-600 border-courtSlate-300'"
                                >
                                    {{ court.occupancy_rate }}%
                                </span>
                            </div>

                            <!-- Mini Occupancy Bar -->
                            <div class="w-full bg-courtSlate-200 rounded-full h-1.5 mb-3.5 overflow-hidden">
                                <div
                                    class="h-full rounded-full transition-all duration-500"
                                    :class="court.occupancy_rate >= 70 ? 'bg-emerald-500' : court.occupancy_rate >= 40 ? 'bg-amber-500' : 'bg-courtSlate-400'"
                                    :style="{ width: `${Math.min(100, court.occupancy_rate)}%` }"
                                ></div>
                            </div>
                        </div>

                        <div class="space-y-2 text-xs text-courtSlate-600">
                            <div class="flex justify-between items-center">
                                <span>Jam Tersewa:</span>
                                <span class="text-courtSlate-900 font-display font-extrabold">{{ court.hours_booked }} Jam</span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span>Total Pesanan:</span>
                                <span class="text-courtSlate-900 font-display font-extrabold">{{ court.bookings_count }} Sesi</span>
                            </div>
                            <div class="flex justify-between items-center pt-2 border-t border-courtSlate-200">
                                <span class="font-medium text-courtSlate-500">Omzet:</span>
                                <span class="text-emerald-700 font-display font-black text-sm tabular-nums">
                                    {{ formatRupiah(court.revenue) }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Transactions Table Section -->
            <div class="bg-white rounded-2xl border-2 border-courtSlate-200 shadow-card-elevated overflow-hidden">
                
                <!-- Table Header Bar -->
                <div class="p-5 sm:p-6 border-b-2 border-courtSlate-200 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                    <div>
                        <h2 class="font-display font-black text-lg text-courtSlate-900 uppercase tracking-tight flex items-center gap-2">
                            <span>📋</span>
                            <span>Tabel Rincian Pemesanan Lapangan</span>
                        </h2>
                        <p class="text-xs text-courtSlate-500 mt-0.5">
                            Menampilkan seluruh riwayat transaksi booking gelanggang pada rentang tanggal yang dipilih.
                        </p>
                    </div>
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-display font-black uppercase tracking-wider bg-courtSlate-100 text-courtSlate-700 border border-courtSlate-200 self-start sm:self-auto">
                        {{ props.bookings?.total ?? 0 }} Transaksi
                    </span>
                </div>

                <!-- Loading Skeleton during Inertia partial reload -->
                <div v-if="isFiltering" class="p-6">
                    <LoadingSkeleton variant="table-row" :count="5" />
                </div>

                <!-- Table Content -->
                <div v-else-if="props.bookings?.data && props.bookings.data.length > 0" class="overflow-x-auto">
                    <table class="w-full text-left text-xs text-courtSlate-600 min-w-[760px]">
                        <thead class="bg-arena-base text-[11px] font-display font-black uppercase tracking-wider text-volt/90 border-b border-arena-border">
                            <tr>
                                <th scope="col" class="px-5 py-3.5">Invoice</th>
                                <th scope="col" class="px-5 py-3.5">Tanggal & Jam</th>
                                <th scope="col" class="px-5 py-3.5">Lapangan</th>
                                <th scope="col" class="px-5 py-3.5">Pelanggan</th>
                                <th scope="col" class="px-5 py-3.5 text-right">Total Bayar</th>
                                <th scope="col" class="px-5 py-3.5 text-center">Status Booking</th>
                                <th scope="col" class="px-5 py-3.5 text-center">Pembayaran</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-courtSlate-100 font-medium">
                            <tr
                                v-for="booking in props.bookings.data"
                                :key="booking.id"
                                class="hover:bg-courtSlate-50/80 transition-colors"
                            >
                                <!-- Invoice Code -->
                                <td class="px-5 py-3.5 whitespace-nowrap">
                                    <span class="inline-block font-mono text-[11px] font-bold text-courtSlate-700 bg-courtSlate-100 px-2 py-0.5 rounded border border-courtSlate-200">
                                        #{{ booking.payment?.invoice_number || ('BK-' + booking.id) }}
                                    </span>
                                </td>

                                <!-- Tanggal & Jam Main -->
                                <td class="px-5 py-3.5 whitespace-nowrap">
                                    <div class="text-courtSlate-900 font-bold">
                                        {{ formatDate(booking.booking_date) }}
                                    </div>
                                    <div class="text-[11px] text-courtSlate-500 font-medium flex items-center gap-1 mt-0.5">
                                        <svg class="w-3 h-3 text-courtSlate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                        <span>{{ formatTimeSlot(booking.start_time, booking.end_time) }}</span>
                                    </div>
                                </td>

                                <!-- Lapangan -->
                                <td class="px-5 py-3.5 whitespace-nowrap">
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-courtSlate-100 text-courtSlate-800 font-display font-extrabold border border-courtSlate-200 text-xs">
                                        <span>🏸</span>
                                        <span>{{ booking.court?.name ?? '-' }}</span>
                                    </span>
                                </td>

                                <!-- Pelanggan -->
                                <td class="px-5 py-3.5 whitespace-nowrap">
                                    <div class="flex items-center gap-2.5">
                                        <div class="w-7 h-7 rounded-full bg-arena-base text-volt font-display font-black text-[11px] flex items-center justify-center shrink-0 border border-arena-border">
                                            {{ getInitials(booking.user?.name) }}
                                        </div>
                                        <div>
                                            <div class="text-courtSlate-900 font-bold leading-tight">
                                                {{ booking.user?.name ?? 'User' }}
                                            </div>
                                            <div class="text-[11px] text-courtSlate-400 leading-tight mt-0.5">
                                                {{ booking.user?.phone || booking.user?.email || '-' }}
                                            </div>
                                        </div>
                                    </div>
                                </td>

                                <!-- Total Biaya -->
                                <td class="px-5 py-3.5 whitespace-nowrap text-right">
                                    <span class="font-display font-black text-sm text-courtSlate-900 tabular-nums">
                                        {{ formatRupiah(booking.total_price) }}
                                    </span>
                                </td>

                                <!-- Status Booking -->
                                <td class="px-5 py-3.5 whitespace-nowrap text-center">
                                    <span
                                        :class="[
                                            'inline-flex items-center gap-1.5 px-2.5 py-0.5 text-[10px] font-display font-black tracking-wider uppercase rounded-md border',
                                            getBookingStatusBadge(booking.status).classes
                                        ]"
                                    >
                                        <span :class="['w-1.5 h-1.5 rounded-full', getBookingStatusBadge(booking.status).dotClass]"></span>
                                        <span>{{ getBookingStatusBadge(booking.status).label }}</span>
                                    </span>
                                </td>

                                <!-- Status Pembayaran -->
                                <td class="px-5 py-3.5 whitespace-nowrap text-center">
                                    <div class="inline-flex flex-col items-center">
                                        <span
                                            :class="[
                                                'inline-flex items-center gap-1.5 px-2.5 py-0.5 text-[10px] font-display tracking-wider uppercase rounded-md border',
                                                getPaymentStatusBadge(booking.payment?.status).classes
                                            ]"
                                        >
                                            <span :class="['w-1.5 h-1.5 rounded-full', getPaymentStatusBadge(booking.payment?.status).dotClass]"></span>
                                            <span>{{ getPaymentStatusBadge(booking.payment?.status).label }}</span>
                                        </span>
                                        <span v-if="booking.payment?.method || booking.payment?.payment_method" class="text-[9px] font-semibold text-courtSlate-400 uppercase tracking-tight mt-0.5">
                                            {{ booking.payment?.method || booking.payment?.payment_method }}
                                        </span>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Empty State Reusable Component -->
                <div v-else class="p-8">
                    <EmptyState
                        icon="📋"
                        title="Tidak Ada Transaksi"
                        description="Tidak ada transaksi pemesanan lapangan pada rentang tanggal yang dipilih. Silakan sesuaikan filter tanggal atau klik tombol di bawah untuk melihat bulan ini."
                    >
                        <button
                            type="button"
                            @click="applyPreset('current_month')"
                            class="mt-4 px-6 py-2.5 bg-arena-base text-volt font-display font-black text-xs uppercase tracking-wider rounded-xl shadow-sm hover:bg-arena-surface hover:shadow-volt-glow-sm -skew-x-3 transition-all duration-200 inline-flex items-center justify-center cursor-pointer"
                        >
                            <span class="skew-x-3">Kembali ke Bulan Ini</span>
                        </button>
                    </EmptyState>
                </div>

                <!-- Pagination Section -->
                <div
                    v-if="props.bookings?.links && props.bookings.links.length > 3"
                    class="p-4 sm:p-5 border-t-2 border-courtSlate-200 flex flex-col sm:flex-row items-center justify-between gap-3 bg-courtSlate-50/50"
                >
                    <div class="text-xs text-courtSlate-500 font-medium">
                        Menampilkan <span class="font-bold text-courtSlate-800">{{ props.bookings.from ?? 0 }}</span> sampai
                        <span class="font-bold text-courtSlate-800">{{ props.bookings.to ?? 0 }}</span> dari
                        <span class="font-bold text-courtSlate-800">{{ props.bookings.total }}</span> transaksi
                    </div>

                    <div class="flex items-center gap-1">
                        <component
                            :is="link.url ? Link : 'span'"
                            v-for="(link, idx) in props.bookings.links"
                            :key="idx"
                            :href="link.url"
                            v-html="link.label"
                            :class="[
                                'px-3 py-1.5 text-xs rounded-lg font-display font-bold transition-all',
                                link.active
                                    ? 'bg-arena-base text-volt font-black shadow-xs'
                                    : link.url
                                    ? 'text-courtSlate-700 hover:bg-white hover:border-courtSlate-300 border border-courtSlate-200 bg-courtSlate-50'
                                    : 'text-courtSlate-300 border border-transparent cursor-not-allowed'
                            ]"
                        />
                    </div>
                </div>

            </div>

        </div>
    </AdminLayout>
</template>
