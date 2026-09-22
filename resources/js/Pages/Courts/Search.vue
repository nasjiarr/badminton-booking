<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
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
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-black tracking-wider uppercase bg-emerald-500/20 text-emerald-400 border border-emerald-500/30">
                            Pencarian Cepat
                        </span>
                        <span class="text-xs text-slate-400">Ketersediaan Real-Time</span>
                    </div>
                    <h1 class="text-2xl sm:text-3xl font-black tracking-tight text-white uppercase italic">
                        Cari Lapangan Kosong
                    </h1>
                </div>

                <div class="flex items-center gap-2">
                    <button
                        type="button"
                        @click="router.visit(route('bookings.create'))"
                        class="inline-flex items-center gap-2 px-4 py-2 rounded-xl text-sm font-bold bg-slate-800 hover:bg-slate-700 text-slate-200 border border-slate-700 transition"
                    >
                        <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                        Lihat Jadwal Per Lapangan
                    </button>
                </div>
            </div>
        </template>

        <div class="py-8 bg-slate-950 min-h-screen">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-8">

                <!-- Filter Card -->
                <div class="bg-slate-900/90 border border-slate-800 rounded-3xl p-6 sm:p-8 shadow-2xl backdrop-blur-xl">
                    <form @submit.prevent="submitSearch" class="space-y-6">
                        
                        <!-- Top Row: Date & Quick Buttons -->
                        <div>
                            <div class="flex flex-wrap items-center justify-between gap-3 mb-2">
                                <label class="text-xs font-black tracking-wider text-slate-300 uppercase">
                                    1. Pilih Tanggal Main
                                </label>
                                <div class="flex items-center gap-1.5">
                                    <button
                                        type="button"
                                        @click="setDateOffset(0)"
                                        class="px-2.5 py-1 text-xs font-bold rounded-lg border transition"
                                        :class="form.date === todayString ? 'bg-emerald-500 text-slate-950 border-emerald-400 shadow-md shadow-emerald-500/20' : 'bg-slate-800 text-slate-400 border-slate-700 hover:text-white'"
                                    >
                                        Hari Ini
                                    </button>
                                    <button
                                        type="button"
                                        @click="setDateOffset(1)"
                                        class="px-2.5 py-1 text-xs font-bold rounded-lg border transition bg-slate-800 text-slate-400 border-slate-700 hover:text-white"
                                    >
                                        Besok
                                    </button>
                                    <button
                                        type="button"
                                        @click="setDateOffset(2)"
                                        class="px-2.5 py-1 text-xs font-bold rounded-lg border transition bg-slate-800 text-slate-400 border-slate-700 hover:text-white"
                                    >
                                        Lusa
                                    </button>
                                </div>
                            </div>

                            <input
                                type="date"
                                v-model="form.date"
                                :min="todayString"
                                class="w-full bg-slate-950 border border-slate-800 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 text-white rounded-2xl px-4 py-3 text-base font-semibold"
                            />
                            <p class="mt-1.5 text-xs text-slate-400 font-medium">
                                {{ formatDateIndonesian(form.date) }}
                            </p>
                        </div>

                        <!-- Middle Row: Time & Duration Grid -->
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 pt-2 border-t border-slate-800/80">
                            <!-- Jam Mulai -->
                            <div>
                                <label class="block text-xs font-black tracking-wider text-slate-300 uppercase mb-2">
                                    2. Jam Mulai
                                </label>
                                <select
                                    v-model="form.start_time"
                                    @change="onStartTimeChange"
                                    class="w-full bg-slate-950 border border-slate-800 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 text-white rounded-2xl px-4 py-3 text-sm font-semibold"
                                >
                                    <option v-for="t in startTimeOptions" :key="t" :value="t">
                                        {{ t }} WIB
                                    </option>
                                </select>
                            </div>

                            <!-- Durasi -->
                            <div>
                                <label class="block text-xs font-black tracking-wider text-slate-300 uppercase mb-2">
                                    3. Durasi Main
                                </label>
                                <div class="grid grid-cols-4 gap-1.5">
                                    <button
                                        v-for="dur in [1, 2, 3, 4]"
                                        :key="dur"
                                        type="button"
                                        @click="onDurationChange(dur)"
                                        class="py-3 text-xs font-black rounded-xl border transition text-center"
                                        :class="form.duration === dur ? 'bg-amber-400 text-slate-950 border-amber-300 shadow-md shadow-amber-400/20' : 'bg-slate-950 text-slate-300 border-slate-800 hover:border-slate-700'"
                                    >
                                        {{ dur }} Jam
                                    </button>
                                </div>
                            </div>

                            <!-- Jam Selesai -->
                            <div>
                                <label class="block text-xs font-black tracking-wider text-slate-300 uppercase mb-2">
                                    Jam Selesai (Otomatis)
                                </label>
                                <select
                                    v-model="form.end_time"
                                    @change="onEndTimeChange"
                                    class="w-full bg-slate-950 border border-slate-800 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 text-white rounded-2xl px-4 py-3 text-sm font-semibold"
                                >
                                    <option v-for="t in timeOptions" :key="t" :value="t">
                                        {{ t }} WIB
                                    </option>
                                </select>
                            </div>
                        </div>

                        <!-- Bottom Row: Price Filter & Sorting -->
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 pt-2 border-t border-slate-800/80">
                            <div>
                                <label class="block text-xs font-black tracking-wider text-slate-300 uppercase mb-2">
                                    Min Tarif / Jam (Opsional)
                                </label>
                                <div class="relative">
                                    <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center text-xs font-bold text-slate-500">Rp</span>
                                    <input
                                        type="number"
                                        v-model="form.min_price"
                                        placeholder="0"
                                        step="5000"
                                        min="0"
                                        class="w-full bg-slate-950 border border-slate-800 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 text-white rounded-2xl pl-10 pr-4 py-2.5 text-sm font-semibold"
                                    />
                                </div>
                            </div>

                            <div>
                                <label class="block text-xs font-black tracking-wider text-slate-300 uppercase mb-2">
                                    Max Tarif / Jam (Opsional)
                                </label>
                                <div class="relative">
                                    <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center text-xs font-bold text-slate-500">Rp</span>
                                    <input
                                        type="number"
                                        v-model="form.max_price"
                                        placeholder="100000"
                                        step="5000"
                                        min="0"
                                        class="w-full bg-slate-950 border border-slate-800 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 text-white rounded-2xl pl-10 pr-4 py-2.5 text-sm font-semibold"
                                    />
                                </div>
                            </div>

                            <div>
                                <label class="block text-xs font-black tracking-wider text-slate-300 uppercase mb-2">
                                    Urutkan Hasil
                                </label>
                                <select
                                    v-model="form.sort_by"
                                    class="w-full bg-slate-950 border border-slate-800 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 text-white rounded-2xl px-4 py-2.5 text-sm font-semibold"
                                >
                                    <option value="price_asc">Harga Termurah</option>
                                    <option value="price_desc">Harga Termahal</option>
                                    <option value="name_asc">Nama Lapangan (A - Z)</option>
                                </select>
                            </div>
                        </div>

                        <!-- Action Submit Button -->
                        <div class="flex items-center justify-end pt-4 border-t border-slate-800">
                            <button
                                type="submit"
                                :disabled="isSearching"
                                class="w-full sm:w-auto inline-flex items-center justify-center gap-3 px-8 py-3.5 rounded-2xl text-sm font-black tracking-wider uppercase text-slate-950 bg-emerald-400 hover:bg-emerald-300 active:scale-[0.98] transition shadow-lg shadow-emerald-500/20 disabled:opacity-50"
                            >
                                <svg v-if="!isSearching" class="w-5 h-5 text-slate-950" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                </svg>
                                <svg v-else class="animate-spin w-5 h-5 text-slate-950" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                Cari Lapangan Tersedia
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Results Header Bar -->
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 px-2">
                    <div class="flex items-center gap-3">
                        <h2 class="text-xl font-black uppercase tracking-tight text-white">
                            Hasil Pencarian
                        </h2>
                        <span
                            v-if="courts.length > 0"
                            class="inline-flex items-center px-3 py-1 rounded-full text-xs font-black bg-emerald-500/20 text-emerald-400 border border-emerald-500/30 shadow-sm"
                        >
                            ⚡ {{ courts.length }} Lapangan Tersedia
                        </span>
                        <span
                            v-else
                            class="inline-flex items-center px-3 py-1 rounded-full text-xs font-black bg-red-500/20 text-red-400 border border-red-500/30"
                        >
                            0 Lapangan
                        </span>
                    </div>

                    <div class="text-xs text-slate-400 font-medium">
                        Rentang: <span class="text-emerald-400 font-bold">{{ form.start_time }} - {{ form.end_time }} WIB</span> ({{ form.duration }} Jam) &bull; 
                        <span class="text-slate-300 font-semibold">{{ formatDateIndonesian(form.date) }}</span>
                    </div>
                </div>

                <!-- Available Courts Grid (If Any) -->
                <div v-if="courts.length > 0" class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div
                        v-for="court in courts"
                        :key="court.id"
                        class="group relative bg-slate-900/90 border border-slate-800 hover:border-emerald-500/50 rounded-3xl overflow-hidden p-6 transition-all duration-300 hover:shadow-2xl hover:shadow-emerald-500/10 flex flex-col justify-between"
                    >
                        <div>
                            <!-- Badge Status & Header -->
                            <div class="flex items-center justify-between gap-2 mb-4">
                                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-black uppercase tracking-wider bg-emerald-500/20 text-emerald-400 border border-emerald-500/30">
                                    <span class="w-2 h-2 rounded-full bg-emerald-400 animate-ping"></span>
                                    KOSONG & TERSEDIA
                                </span>
                                <span class="text-xs font-bold text-slate-400 bg-slate-950 px-2.5 py-1 rounded-lg border border-slate-800">
                                    {{ form.start_time }} - {{ form.end_time }}
                                </span>
                            </div>

                            <div class="flex items-start gap-4">
                                <!-- Image Thumbnail / Sport Icon -->
                                <div class="w-24 h-24 rounded-2xl overflow-hidden bg-slate-950 border border-slate-800 shrink-0 relative flex items-center justify-center">
                                    <img
                                        v-if="court.image_url"
                                        :src="court.image_url"
                                        :alt="court.name"
                                        class="w-full h-full object-cover group-hover:scale-105 transition duration-300"
                                    />
                                    <div v-else class="text-emerald-500 flex flex-col items-center">
                                        <svg class="w-10 h-10 opacity-70" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M13 10V3L4 14h7v7l9-11h-7z" />
                                        </svg>
                                    </div>
                                </div>

                                <!-- Court Info -->
                                <div class="flex-1 min-w-0">
                                    <h3 class="text-xl font-black text-white uppercase italic tracking-tight group-hover:text-emerald-400 transition">
                                        {{ court.name }}
                                    </h3>
                                    <p class="text-xs text-slate-400 line-clamp-2 mt-1 leading-relaxed">
                                        {{ court.description || 'Lapangan standar kompetisi dengan karpet vinil dan pencahayaan LED profesional.' }}
                                    </p>
                                    
                                    <div class="flex flex-wrap items-center gap-2 mt-3">
                                        <span class="px-2 py-0.5 text-[10px] font-bold uppercase rounded bg-slate-800 text-slate-300 border border-slate-700">
                                            Karpet Vinil
                                        </span>
                                        <span class="px-2 py-0.5 text-[10px] font-bold uppercase rounded bg-slate-800 text-slate-300 border border-slate-700">
                                            LED Lighting
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Price and Booking Action -->
                        <div class="mt-6 pt-5 border-t border-slate-800/80 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                            <div>
                                <div class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">
                                    Estimasi Total ({{ court.duration_hours }} Jam)
                                </div>
                                <div class="flex items-baseline gap-2">
                                    <span class="text-2xl font-black text-emerald-400 tracking-tight">
                                        {{ formatPrice(court.total_price) }}
                                    </span>
                                    <span class="text-xs text-slate-500">
                                        ({{ formatPrice(court.price_per_hour) }} / jam)
                                    </span>
                                </div>
                            </div>

                            <a
                                :href="court.booking_url"
                                class="inline-flex items-center justify-center gap-2 px-6 py-3 rounded-2xl text-xs font-black uppercase tracking-wider text-slate-950 bg-emerald-400 hover:bg-emerald-300 active:scale-[0.98] transition shadow-lg shadow-emerald-500/20"
                            >
                                <span>Booking Slot Ini</span>
                                <svg class="w-4 h-4 text-slate-950" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                                </svg>
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Empty State & Alternative Suggestions (When No Courts Available) -->
                <div v-else class="space-y-6">
                    
                    <!-- Alert Banner -->
                    <div class="bg-slate-900/90 border border-amber-500/30 rounded-3xl p-8 text-center space-y-4 shadow-xl">
                        <div class="w-16 h-16 mx-auto rounded-2xl bg-amber-500/10 border border-amber-500/20 flex items-center justify-center text-amber-400">
                            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                            </svg>
                        </div>

                        <div>
                            <h3 class="text-xl font-black text-white uppercase italic tracking-tight">
                                {{ isPast ? 'Jam yang Dicari Sudah Lewat' : 'Tidak Ada Lapangan Kosong di Rentang Waktu Ini' }}
                            </h3>
                            <p class="text-sm text-slate-400 max-w-lg mx-auto mt-1">
                                <span v-if="isPast">
                                    Slot jam <span class="text-amber-400 font-bold">{{ form.start_time }} WIB</span> pada hari ini sudah lewat. Silakan pilih jam mendatang atau tanggal lain.
                                </span>
                                <span v-else>
                                    Semua lapangan badminton telah terisi untuk jam <span class="text-amber-400 font-bold">{{ form.start_time }} - {{ form.end_time }} WIB</span> pada <span class="text-white font-semibold">{{ formatDateIndonesian(form.date) }}</span>, atau berada di luar jam operasional.
                                </span>
                            </p>
                        </div>
                    </div>

                    <!-- Smart Alternative Suggestions Section -->
                    <div v-if="suggestions.length > 0" class="space-y-4">
                        <div class="flex items-center gap-2">
                            <span class="text-amber-400 text-lg">💡</span>
                            <h4 class="text-sm font-black uppercase tracking-wider text-slate-200">
                                Saran Waktu Alternatif Terdekat Yang Masih Kosong
                            </h4>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div
                                v-for="(sug, idx) in suggestions"
                                :key="idx"
                                @click="applySuggestion(sug)"
                                class="cursor-pointer group bg-slate-900/80 hover:bg-slate-900 border border-slate-800 hover:border-emerald-500/50 rounded-2xl p-5 transition-all duration-200 hover:shadow-lg hover:shadow-emerald-500/10"
                            >
                                <div class="flex items-center justify-between mb-3">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-black uppercase bg-emerald-500/20 text-emerald-400 border border-emerald-500/30">
                                        {{ sug.available_count }} Lapangan Kosong
                                    </span>
                                    <span class="text-[11px] font-bold text-slate-400">
                                        {{ sug.duration_hours }} Jam
                                    </span>
                                </div>

                                <div class="text-lg font-black text-white group-hover:text-emerald-400 transition flex items-center gap-2">
                                    <span>{{ sug.start_time }} - {{ sug.end_time }} WIB</span>
                                </div>
                                <div class="text-xs text-slate-400 mt-1">
                                    {{ sug.date_formatted }}
                                </div>

                                <div class="mt-3 text-xs text-slate-400 font-medium">
                                    Lapangan: <span class="text-slate-200 font-semibold">{{ sug.court_names.join(', ') }}</span>
                                </div>

                                <div class="mt-4 pt-3 border-t border-slate-800/80 flex items-center justify-between">
                                    <span class="text-xs text-slate-400 font-semibold">
                                        Mulai {{ formatPrice(sug.min_price) }}
                                    </span>
                                    <span class="text-xs font-bold text-emerald-400 group-hover:translate-x-1 transition flex items-center gap-1">
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

