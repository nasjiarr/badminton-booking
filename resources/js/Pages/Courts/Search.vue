<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import LoadingSkeleton from '@/Components/LoadingSkeleton.vue';
import { Head, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';

const props = defineProps({
    courts: {
        type: Array,
        default: () => [],
    },
    filters: {
        type: Object,
        default: () => ({}),
    },
    isPast: {
        type: Boolean,
        default: false,
    },
    suggestions: {
        type: Array,
        default: () => [],
    },
});

// Form state initialized with props.filters
const form = ref({
    date: props.filters.date || new Date().toISOString().split('T')[0],
    start_time: props.filters.start_time || '08:00',
    end_time: props.filters.end_time || '10:00',
    duration: props.filters.duration || 2,
    min_price: props.filters.min_price || '',
    max_price: props.filters.max_price || '',
    sort_by: props.filters.sort_by || 'price_asc',
});

const isSearching = ref(false);
const showPriceFilter = ref(false);

// Generate time options: 06:00 to 22:00
const timeOptions = [];
for (let h = 6; h <= 22; h++) {
    timeOptions.push(`${String(h).padStart(2, '0')}:00`);
}

// Start time options up to 21:00
const startTimeOptions = timeOptions.slice(0, -1);

// When start_time or duration changes, update end_time automatically
const onStartTimeChange = () => {
    const startHour = parseInt(form.value.start_time.split(':')[0], 10);
    const endHour = Math.min(22, startHour + parseInt(form.value.duration, 10));
    form.value.end_time = `${String(endHour).padStart(2, '0')}:00`;
};

const onDurationChange = (dur) => {
    form.value.duration = dur;
    const startHour = parseInt(form.value.start_time.split(':')[0], 10);
    const endHour = Math.min(22, startHour + dur);
    form.value.end_time = `${String(endHour).padStart(2, '0')}:00`;
};

const onEndTimeChange = () => {
    const startHour = parseInt(form.value.start_time.split(':')[0], 10);
    const endHour = parseInt(form.value.end_time.split(':')[0], 10);
    if (endHour > startHour) {
        form.value.duration = endHour - startHour;
    } else {
        // adjust start_time if end_time <= start_time
        const newStart = Math.max(6, endHour - 1);
        form.value.start_time = `${String(newStart).padStart(2, '0')}:00`;
        form.value.duration = endHour - newStart;
    }
};

// Quick date helper
const setDateOffset = (offsetDays) => {
    const d = new Date();
    d.setDate(d.getDate() + offsetDays);
    form.value.date = d.toISOString().split('T')[0];
    submitSearch();
};

const todayString = new Date().toISOString().split('T')[0];

const submitSearch = () => {
    isSearching.value = true;
    router.get(
        route('courts.search'),
        {
            date: form.value.date,
            start_time: form.value.start_time,
            end_time: form.value.end_time,
            duration: form.value.duration,
            min_price: form.value.min_price || undefined,
            max_price: form.value.max_price || undefined,
            sort_by: form.value.sort_by,
        },
        {
            preserveState: true,
            preserveScroll: true,
            onFinish: () => {
                isSearching.value = false;
            },
        }
    );
};

const applySuggestion = (sug) => {
    form.value.date = sug.date;
    form.value.start_time = sug.start_time;
    form.value.end_time = sug.end_time;
    form.value.duration = sug.duration_hours;
    submitSearch();
};

const formatPrice = (price) => {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0,
    }).format(price);
};

const formatDateIndonesian = (dateStr) => {
    if (!dateStr) return '';
    const date = new Date(dateStr + 'T00:00:00');
    return new Intl.DateTimeFormat('id-ID', {
        weekday: 'long',
        day: 'numeric',
        month: 'long',
        year: 'numeric',
    }).format(date);
};
</script>

<template>
    <Head title="Cari Lapangan Kosong" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <div class="flex items-center gap-2 mb-1">
                        <span class="court-badge-volt text-[10px] py-0.5"><span>Pencarian Cepat</span></span>
                        <span class="text-xs text-courtSlate-500">Ketersediaan Real-Time</span>
                    </div>
                    <h2 class="text-2xl sm:text-3xl font-display font-black tracking-tight text-courtSlate-900 uppercase">
                        Cari Lapangan Kosong
                    </h2>
                </div>

                <div class="flex items-center gap-2">
                    <button
                        type="button"
                        @click="router.visit(route('bookings.create'))"
                        class="inline-flex items-center gap-2 px-4 py-2 rounded-xl border-2 border-courtSlate-200 bg-white text-courtSlate-700 font-display font-bold text-xs uppercase tracking-wider hover:bg-courtSlate-100 transition shadow-2xs"
                    >
                        <svg class="w-4 h-4 text-courtSlate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                        Lihat Jadwal Per Lapangan
                    </button>
                </div>
            </div>
        </template>

        <div class="py-8">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-8">

                <!-- Compact Filter Bar -->
                <div class="rounded-2xl border-2 border-courtSlate-200 bg-white p-5 sm:p-6 shadow-card-elevated">
                    <form @submit.prevent="submitSearch">
                        <!-- Row 1: Date + Quick Buttons + Time + Duration — all in one bar -->
                        <div class="flex flex-col lg:flex-row lg:items-end gap-4">
                            <!-- Date -->
                            <div class="flex-1 min-w-0">
                                <label class="block text-[10px] font-display font-black tracking-widest text-courtSlate-500 uppercase mb-1.5">
                                    Tanggal Main
                                </label>
                                <div class="flex items-center gap-2">
                                    <input
                                        type="date"
                                        v-model="form.date"
                                        :min="todayString"
                                        class="flex-1 bg-courtSlate-50 border-2 border-courtSlate-200 focus:border-volt focus:ring-2 focus:ring-volt/30 text-courtSlate-900 rounded-xl px-3.5 py-2.5 text-sm font-semibold"
                                    />
                                    <div class="hidden sm:flex items-center gap-1">
                                        <button
                                            type="button"
                                            @click="setDateOffset(0)"
                                            class="px-2.5 py-2 text-[10px] font-display font-black uppercase tracking-wider rounded-lg border transition"
                                            :class="form.date === todayString ? 'bg-volt text-arena-base border-volt shadow-sm' : 'bg-courtSlate-50 text-courtSlate-500 border-courtSlate-200 hover:border-courtSlate-400'"
                                        >
                                            Hari Ini
                                        </button>
                                        <button
                                            type="button"
                                            @click="setDateOffset(1)"
                                            class="px-2.5 py-2 text-[10px] font-display font-black uppercase tracking-wider rounded-lg border transition bg-courtSlate-50 text-courtSlate-500 border-courtSlate-200 hover:border-courtSlate-400"
                                        >
                                            Besok
                                        </button>
                                        <button
                                            type="button"
                                            @click="setDateOffset(2)"
                                            class="px-2.5 py-2 text-[10px] font-display font-black uppercase tracking-wider rounded-lg border transition bg-courtSlate-50 text-courtSlate-500 border-courtSlate-200 hover:border-courtSlate-400"
                                        >
                                            Lusa
                                        </button>
                                    </div>
                                </div>
                                <p class="mt-1 text-[11px] text-courtSlate-500 font-medium">
                                    {{ formatDateIndonesian(form.date) }}
                                </p>
                            </div>

                            <!-- Start Time -->
                            <div class="w-full lg:w-32">
                                <label class="block text-[10px] font-display font-black tracking-widest text-courtSlate-500 uppercase mb-1.5">
                                    Jam Mulai
                                </label>
                                <select
                                    v-model="form.start_time"
                                    @change="onStartTimeChange"
                                    class="w-full bg-courtSlate-50 border-2 border-courtSlate-200 focus:border-volt focus:ring-2 focus:ring-volt/30 text-courtSlate-900 rounded-xl px-3 py-2.5 text-sm font-semibold"
                                >
                                    <option v-for="t in startTimeOptions" :key="t" :value="t">
                                        {{ t }} WIB
                                    </option>
                                </select>
                            </div>

                            <!-- Duration Quick Pills -->
                            <div class="w-full lg:w-auto">
                                <label class="block text-[10px] font-display font-black tracking-widest text-courtSlate-500 uppercase mb-1.5">
                                    Durasi
                                </label>
                                <div class="flex items-center gap-1.5">
                                    <button
                                        v-for="dur in [1, 2, 3, 4]"
                                        :key="dur"
                                        type="button"
                                        @click="onDurationChange(dur)"
                                        class="px-3 py-2.5 text-xs font-display font-black rounded-xl border-2 transition text-center min-w-[48px]"
                                        :class="form.duration === dur ? 'bg-volt text-arena-base border-volt shadow-volt-glow-sm' : 'bg-courtSlate-50 text-courtSlate-600 border-courtSlate-200 hover:border-courtSlate-400'"
                                    >
                                        {{ dur }}h
                                    </button>
                                </div>
                            </div>

                            <!-- End Time (auto) -->
                            <div class="w-full lg:w-32">
                                <label class="block text-[10px] font-display font-black tracking-widest text-courtSlate-500 uppercase mb-1.5">
                                    Selesai
                                </label>
                                <select
                                    v-model="form.end_time"
                                    @change="onEndTimeChange"
                                    class="w-full bg-courtSlate-50 border-2 border-courtSlate-200 focus:border-volt focus:ring-2 focus:ring-volt/30 text-courtSlate-900 rounded-xl px-3 py-2.5 text-sm font-semibold"
                                >
                                    <option v-for="t in timeOptions" :key="t" :value="t">
                                        {{ t }} WIB
                                    </option>
                                </select>
                            </div>

                            <!-- Spacer & Actions -->
                            <div class="flex items-center gap-2 lg:ml-auto">
                                <!-- Price Filter Toggle -->
                                <button
                                    type="button"
                                    @click="showPriceFilter = !showPriceFilter"
                                    class="inline-flex items-center gap-1.5 px-3 py-2.5 rounded-xl border-2 text-xs font-display font-bold uppercase tracking-wider transition"
                                    :class="showPriceFilter ? 'bg-courtSlate-900 text-white border-courtSlate-900' : 'bg-courtSlate-50 text-courtSlate-600 border-courtSlate-200 hover:border-courtSlate-400'"
                                >
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" />
                                    </svg>
                                    Filter Harga
                                </button>

                                <!-- Search Button -->
                                <button
                                    type="submit"
                                    :disabled="isSearching"
                                    class="inline-flex items-center justify-center gap-2 px-5 py-2.5 rounded-xl bg-arena-base text-volt font-display font-black text-xs uppercase tracking-wider shadow-sm hover:bg-arena-surface hover:shadow-volt-glow-sm transition-all active:scale-[0.98] disabled:opacity-50 -skew-x-3 cursor-pointer"
                                >
                                    <span class="inline-flex items-center gap-2 skew-x-3">
                                        <svg v-if="!isSearching" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                        </svg>
                                        <svg v-else class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                        </svg>
                                        <span>Cari</span>
                                    </span>
                                </button>
                            </div>
                        </div>

                        <!-- Row 2: Price + Sort (collapsible) -->
                        <div v-if="showPriceFilter" class="mt-4 pt-4 border-t border-courtSlate-100 grid grid-cols-1 sm:grid-cols-3 gap-4">
                            <div>
                                <label class="block text-[10px] font-display font-black tracking-widest text-courtSlate-500 uppercase mb-1.5">
                                    Min Tarif / Jam
                                </label>
                                <div class="relative">
                                    <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-xs font-bold text-courtSlate-400">Rp</span>
                                    <input
                                        type="number"
                                        v-model="form.min_price"
                                        placeholder="0"
                                        step="5000"
                                        min="0"
                                        class="w-full bg-courtSlate-50 border-2 border-courtSlate-200 focus:border-volt focus:ring-2 focus:ring-volt/30 text-courtSlate-900 rounded-xl pl-10 pr-3 py-2.5 text-sm font-semibold"
                                    />
                                </div>
                            </div>

                            <div>
                                <label class="block text-[10px] font-display font-black tracking-widest text-courtSlate-500 uppercase mb-1.5">
                                    Max Tarif / Jam
                                </label>
                                <div class="relative">
                                    <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-xs font-bold text-courtSlate-400">Rp</span>
                                    <input
                                        type="number"
                                        v-model="form.max_price"
                                        placeholder="100000"
                                        step="5000"
                                        min="0"
                                        class="w-full bg-courtSlate-50 border-2 border-courtSlate-200 focus:border-volt focus:ring-2 focus:ring-volt/30 text-courtSlate-900 rounded-xl pl-10 pr-3 py-2.5 text-sm font-semibold"
                                    />
                                </div>
                            </div>

                            <div>
                                <label class="block text-[10px] font-display font-black tracking-widest text-courtSlate-500 uppercase mb-1.5">
                                    Urutkan Hasil
                                </label>
                                <select
                                    v-model="form.sort_by"
                                    class="w-full bg-courtSlate-50 border-2 border-courtSlate-200 focus:border-volt focus:ring-2 focus:ring-volt/30 text-courtSlate-900 rounded-xl px-3 py-2.5 text-sm font-semibold"
                                >
                                    <option value="price_asc">Harga Termurah</option>
                                    <option value="price_desc">Harga Termahal</option>
                                    <option value="name_asc">Nama Lapangan (A - Z)</option>
                                </select>
                            </div>
                        </div>
                    </form>
                </div>

                <!-- Results Header Bar -->
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 px-1">
                    <div class="flex items-center gap-3">
                        <h3 class="text-lg font-display font-black uppercase tracking-tight text-courtSlate-900">
                            Hasil Pencarian
                        </h3>
                        <span
                            v-if="courts.length > 0"
                            class="court-badge-volt text-[10px] py-0.5"
                        >
                            <span>⚡ {{ courts.length }} Lapangan Tersedia</span>
                        </span>
                        <span
                            v-else
                            class="court-badge-orange text-[10px] py-0.5"
                        >
                            <span>0 Lapangan</span>
                        </span>
                    </div>

                    <div class="text-xs text-courtSlate-500 font-medium">
                        Rentang: <span class="font-display font-extrabold text-courtSlate-900">{{ form.start_time }} - {{ form.end_time }} WIB</span> ({{ form.duration }} Jam) &bull; 
                        <span class="text-courtSlate-700 font-semibold">{{ formatDateIndonesian(form.date) }}</span>
                    </div>
                </div>

                <!-- Loading Skeleton during search -->
                <div v-if="isSearching" class="py-4">
                    <LoadingSkeleton variant="card" :count="4" />
                </div>

                <!-- Available Courts Grid — consistent with booking page court card style -->
                <div v-else-if="courts.length > 0" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
                    <div
                        v-for="court in courts"
                        :key="court.id"
                        class="relative flex flex-col rounded-xl border-2 border-courtSlate-200 bg-white p-4 transition-all duration-200 hover:border-volt hover:shadow-card-active hover:-translate-y-1 group court-stripe-accent"
                    >
                        <!-- Image / Placeholder -->
                        <div class="relative h-36 w-full rounded-lg overflow-hidden bg-arena-card mb-3.5 flex items-center justify-center">
                            <img
                                v-if="court.image_url"
                                :src="court.image_url"
                                :alt="court.name"
                                class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
                            />
                            <div v-else class="flex flex-col items-center justify-center text-courtSlate-400">
                                <svg class="h-10 w-10 mb-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                </svg>
                                <span class="text-xs font-display font-bold uppercase tracking-wider">Foto Lapangan</span>
                            </div>

                            <!-- Availability Badge -->
                            <div class="absolute top-2.5 left-2.5">
                                <span class="court-badge-volt text-[9px] py-0.5 shadow-sm"><span>TERSEDIA</span></span>
                            </div>

                            <!-- Time Badge -->
                            <div class="absolute bottom-2 right-2">
                                <span class="inline-flex items-center px-2 py-0.5 rounded bg-arena-base/80 text-white text-[10px] font-display font-bold backdrop-blur-sm">
                                    {{ form.start_time }} - {{ form.end_time }}
                                </span>
                            </div>
                        </div>

                        <!-- Court Info -->
                        <h4 class="font-display font-black text-base text-courtSlate-900 uppercase tracking-tight group-hover:text-volt-deep transition">
                            {{ court.name }}
                        </h4>
                        <p class="text-xs text-courtSlate-500 line-clamp-2 mt-0.5 leading-relaxed">
                            {{ court.description || 'Lapangan standar kompetisi dengan karpet vinil dan pencahayaan LED profesional.' }}
                        </p>

                        <!-- Facility Badges -->
                        <div class="flex flex-wrap gap-1.5 mt-2.5">
                            <span class="px-1.5 py-0.5 text-[9px] font-display font-bold uppercase rounded bg-courtSlate-100 text-courtSlate-600 border border-courtSlate-200">
                                Karpet Vinil
                            </span>
                            <span class="px-1.5 py-0.5 text-[9px] font-display font-bold uppercase rounded bg-courtSlate-100 text-courtSlate-600 border border-courtSlate-200">
                                LED Lighting
                            </span>
                        </div>

                        <!-- Price + Action Footer -->
                        <div class="mt-auto pt-4 border-t border-courtSlate-100 mt-3.5 flex items-end justify-between gap-2">
                            <div>
                                <div class="text-[10px] font-display font-bold text-courtSlate-400 uppercase tracking-wider">
                                    Est. Total ({{ court.duration_hours }}h)
                                </div>
                                <div class="athletic-number text-xl font-black text-courtSlate-900 tracking-tight">
                                    {{ formatPrice(court.total_price) }}
                                </div>
                                <div class="text-[10px] text-courtSlate-400">
                                    {{ formatPrice(court.price_per_hour) }}/jam
                                </div>
                            </div>

                            <a
                                :href="court.booking_url"
                                class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl bg-arena-base text-volt font-display font-black text-[10px] uppercase tracking-wider shadow-sm hover:bg-arena-surface hover:shadow-volt-glow-sm transition-all active:scale-[0.98] -skew-x-3 cursor-pointer"
                            >
                                <span class="inline-flex items-center gap-1 skew-x-3">
                                    <span>Booking</span>
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                                    </svg>
                                </span>
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Empty State & Alternative Suggestions -->
                <div v-else class="space-y-6">
                    
                    <!-- Alert Banner -->
                    <div class="rounded-2xl border-2 border-courtOrange/30 bg-courtOrange-light p-8 text-center space-y-4 shadow-sm">
                        <div class="w-14 h-14 mx-auto rounded-2xl bg-courtOrange/10 border-2 border-courtOrange/20 flex items-center justify-center text-courtOrange">
                            <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                            </svg>
                        </div>

                        <div>
                            <h3 class="text-xl font-display font-black text-courtSlate-900 uppercase tracking-tight">
                                {{ isPast ? 'Jam yang Dicari Sudah Lewat' : 'Tidak Ada Lapangan Kosong di Rentang Waktu Ini' }}
                            </h3>
                            <p class="text-sm text-courtSlate-600 max-w-lg mx-auto mt-1">
                                <span v-if="isPast">
                                    Slot jam <span class="font-display font-black text-courtOrange">{{ form.start_time }} WIB</span> pada hari ini sudah lewat. Silakan pilih jam mendatang atau tanggal lain.
                                </span>
                                <span v-else>
                                    Semua lapangan badminton telah terisi untuk jam <span class="font-display font-black text-courtOrange">{{ form.start_time }} - {{ form.end_time }} WIB</span> pada <span class="font-semibold text-courtSlate-900">{{ formatDateIndonesian(form.date) }}</span>, atau berada di luar jam operasional.
                                </span>
                            </p>
                        </div>
                    </div>

                    <!-- Smart Alternative Suggestions Section -->
                    <div v-if="suggestions.length > 0" class="space-y-4">
                        <div class="flex items-center gap-2">
                            <span class="text-courtOrange text-lg">💡</span>
                            <h4 class="text-sm font-display font-black uppercase tracking-wider text-courtSlate-900">
                                Saran Waktu Alternatif Terdekat Yang Masih Kosong
                            </h4>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div
                                v-for="(sug, idx) in suggestions"
                                :key="idx"
                                @click="applySuggestion(sug)"
                                class="cursor-pointer group relative rounded-xl border-2 border-courtSlate-200 bg-white p-5 transition-all duration-200 hover:border-volt hover:shadow-card-active hover:-translate-y-1"
                            >
                                <div class="flex items-center justify-between mb-3">
                                    <span class="court-badge-volt text-[9px] py-0.5">
                                        <span>{{ sug.available_count }} Lapangan Kosong</span>
                                    </span>
                                    <span class="text-[11px] font-display font-bold text-courtSlate-500">
                                        {{ sug.duration_hours }} Jam
                                    </span>
                                </div>

                                <div class="athletic-number text-lg font-black text-courtSlate-900 group-hover:text-volt-deep transition flex items-center gap-2">
                                    <span>{{ sug.start_time }} - {{ sug.end_time }} WIB</span>
                                </div>
                                <div class="text-xs text-courtSlate-500 mt-1">
                                    {{ sug.date_formatted }}
                                </div>

                                <div class="mt-3 text-xs text-courtSlate-500 font-medium">
                                    Lapangan: <span class="text-courtSlate-800 font-semibold">{{ sug.court_names.join(', ') }}</span>
                                </div>

                                <div class="mt-3 pt-3 border-t border-courtSlate-100 flex items-center justify-between">
                                    <span class="text-xs text-courtSlate-500 font-semibold">
                                        Mulai {{ formatPrice(sug.min_price) }}
                                    </span>
                                    <span class="text-xs font-display font-bold text-volt-deep group-hover:translate-x-1 transition-transform flex items-center gap-1">
                                        Pilih Waktu Ini &rarr;
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>

            </div>
        </div>
    </AuthenticatedLayout>
</template>
