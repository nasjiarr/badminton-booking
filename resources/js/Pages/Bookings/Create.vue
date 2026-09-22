<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { computed, onMounted, ref, watch } from 'vue';

const props = defineProps({
    courts: Array,
    initialCourtId: Number,
    initialDate: String,
});

const selectedCourtId = ref(props.initialCourtId || props.courts[0]?.id || null);
const selectedDate = ref(props.initialDate || new Date().toISOString().split('T')[0]);
const slots = ref([]);
const loadingSlots = ref(false);
const selectedSlotTimes = ref([]); // array of 'HH:mm' start_times
const notes = ref('');

const selectedCourt = computed(() => {
    return props.courts.find((c) => c.id === selectedCourtId.value);
});

// Min date: today
const todayString = new Date().toISOString().split('T')[0];
// Max date: 30 days from today
const maxDate = new Date();
maxDate.setDate(maxDate.getDate() + 30);
const maxDateString = maxDate.toISOString().split('T')[0];

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

// Quick date helpers
const setDateOffset = (offsetDays) => {
    const d = new Date();
    d.setDate(d.getDate() + offsetDays);
    selectedDate.value = d.toISOString().split('T')[0];
};

// Fetch availability slots
const fetchAvailability = async () => {
    if (!selectedCourtId.value || !selectedDate.value) return;

    loadingSlots.value = true;
    selectedSlotTimes.value = [];

    try {
        const response = await fetch(
            route('courts.availability', selectedCourtId.value) + `?date=${selectedDate.value}`,
            {
                headers: {
                    Accept: 'application/json',
                },
            }
        );

        if (response.ok) {
            const data = await response.json();
            slots.value = data.slots || [];
        } else {
            slots.value = [];
        }
    } catch (e) {
        slots.value = [];
    } finally {
        loadingSlots.value = false;
    }
};

watch([selectedCourtId, selectedDate], () => {
    fetchAvailability();
});

onMounted(() => {
    fetchAvailability();
});

// Slot selection handling ensuring consecutive slots
const toggleSlot = (slot) => {
    if (slot.status !== 'available') return;

    const time = slot.start_time;

    if (selectedSlotTimes.value.length === 0) {
        selectedSlotTimes.value = [time];
        return;
    }

    // If clicking already selected slot
    if (selectedSlotTimes.value.includes(time)) {
        // If clicking the only selected slot, deselect it
        if (selectedSlotTimes.value.length === 1) {
            selectedSlotTimes.value = [];
            return;
        }

        // If clicking start or end of the current range, remove it
        const sorted = [...selectedSlotTimes.value].sort();
        if (time === sorted[0]) {
            selectedSlotTimes.value = sorted.slice(1);
            return;
        }
        if (time === sorted[sorted.length - 1]) {
            selectedSlotTimes.value = sorted.slice(0, -1);
            return;
        }

        // Clicking in the middle resets to just that clicked slot
        selectedSlotTimes.value = [time];
        return;
    }

    // Clicking a new slot: attempt to select range from current selection to clicked slot
    const allAvailableTimes = slots.value
        .filter((s) => s.status === 'available')
        .map((s) => s.start_time);

    const sortedCurrent = [...selectedSlotTimes.value].sort();
    const minCurrent = sortedCurrent[0];
    const maxCurrent = sortedCurrent[sortedCurrent.length - 1];

    let rangeStart = minCurrent;
    let rangeEnd = time;

    if (time < minCurrent) {
        rangeStart = time;
        rangeEnd = maxCurrent;
    } else if (time > maxCurrent) {
        rangeStart = minCurrent;
        rangeEnd = time;
    }

    // Find all slots between rangeStart and rangeEnd
    const allSlotTimes = slots.value.map((s) => s.start_time);
    const startIndex = allSlotTimes.indexOf(rangeStart);
    const endIndex = allSlotTimes.indexOf(rangeEnd);

    if (startIndex !== -1 && endIndex !== -1) {
        const potentialSlots = slots.value.slice(
            Math.min(startIndex, endIndex),
            Math.max(startIndex, endIndex) + 1
        );

        // Check if all in between are available
        const hasUnavailable = potentialSlots.some((s) => s.status !== 'available');
        if (!hasUnavailable) {
            selectedSlotTimes.value = potentialSlots.map((s) => s.start_time);
            return;
        }
    }

    // If cannot create a continuous available range, reset to just the clicked slot
    selectedSlotTimes.value = [time];
};

// Computed summary details
const sortedSelectedSlots = computed(() => {
    return [...selectedSlotTimes.value].sort().map((time) => {
        return slots.value.find((s) => s.start_time === time);
    }).filter(Boolean);
});

const bookingStartTime = computed(() => {
    if (sortedSelectedSlots.value.length === 0) return null;
    return sortedSelectedSlots.value[0].start_time;
});

const bookingEndTime = computed(() => {
    if (sortedSelectedSlots.value.length === 0) return null;
    return sortedSelectedSlots.value[sortedSelectedSlots.value.length - 1].end_time;
});

const totalHours = computed(() => {
    return sortedSelectedSlots.value.length;
});

const totalPrice = computed(() => {
    if (!selectedCourt.value || totalHours.value === 0) return 0;
    return totalHours.value * selectedCourt.value.price_per_hour;
});

// Booking form submission
const form = useForm({
    court_id: null,
    booking_date: '',
    start_time: '',
    end_time: '',
    notes: '',
});

const submitBooking = () => {
    if (totalHours.value === 0 || !selectedCourt.value) return;

    form.court_id = selectedCourtId.value;
    form.booking_date = selectedDate.value;
    form.start_time = bookingStartTime.value;
    form.end_time = bookingEndTime.value;
    form.notes = notes.value;

    form.post(route('bookings.store'), {
        preserveScroll: true,
    });
};
</script>

<template>
    <Head title="Booking Lapangan" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-xl font-semibold leading-tight text-gray-800">
                        Booking Lapangan Badminton
                    </h2>
                    <p class="text-sm text-gray-500 mt-1">
                        Pilih lapangan, tanggal, dan slot jam yang Anda inginkan.
                    </p>
                </div>
            </div>
        </template>

        <div class="py-8">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-8">
                <!-- STEP 1: PILIH LAPANGAN -->
                <div>
                    <div class="flex items-center gap-2 mb-4">
                        <span class="flex items-center justify-center w-7 h-7 rounded-full bg-indigo-600 text-white font-bold text-sm">
                            1
                        </span>
                        <h3 class="text-lg font-bold text-gray-900">
                            Pilih Lapangan
                        </h3>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
                        <div
                            v-for="court in courts"
                            :key="court.id"
                            @click="selectedCourtId = court.id"
                            :class="[
                                selectedCourtId === court.id
                                    ? 'border-indigo-600 ring-2 ring-indigo-600 shadow-md bg-indigo-50/20'
                                    : 'border-gray-200 hover:border-gray-300 hover:shadow-sm bg-white',
                                'relative flex flex-col rounded-xl border-2 p-4 cursor-pointer transition-all duration-150',
                            ]"
                        >
                            <!-- Image or placeholder -->
                            <div class="h-36 w-full rounded-lg overflow-hidden bg-gray-100 mb-3 flex items-center justify-center">
                                <img
                                    v-if="court.image_url"
                                    :src="court.image_url"
                                    :alt="court.name"
                                    class="w-full h-full object-cover"
                                />
                                <div v-else class="flex flex-col items-center justify-center text-gray-400">
                                    <svg class="h-10 w-10 mb-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                    </svg>
                                    <span class="text-xs">Foto Lapangan</span>
                                </div>
                            </div>

                            <!-- Name & Price -->
                            <div class="flex items-start justify-between gap-2">
                                <h4 class="font-bold text-gray-900 text-base">
                                    {{ court.name }}
                                </h4>
                                <span
                                    v-if="selectedCourtId === court.id"
                                    class="inline-flex items-center rounded-full bg-indigo-100 px-2 py-0.5 text-xs font-semibold text-indigo-700"
                                >
                                    Dipilih
                                </span>
                            </div>

                            <p class="text-xs text-gray-500 mt-1 line-clamp-2">
                                {{ court.description }}
                            </p>

                            <div class="mt-4 pt-3 border-t border-gray-100 flex items-center justify-between">
                                <span class="text-xs text-gray-500">Tarif Sewa:</span>
                                <span class="text-sm font-bold text-indigo-600">
                                    {{ formatPrice(court.price_per_hour) }}<span class="text-xs font-normal text-gray-500">/jam</span>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- STEP 2: PILIH TANGGAL -->
                <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
                    <div class="flex items-center gap-2 mb-4">
                        <span class="flex items-center justify-center w-7 h-7 rounded-full bg-indigo-600 text-white font-bold text-sm">
                            2
                        </span>
                        <h3 class="text-lg font-bold text-gray-900">
                            Pilih Tanggal
                        </h3>
                    </div>

                    <div class="flex flex-wrap items-center gap-3">
                        <!-- Quick buttons -->
                        <button
                            type="button"
                            @click="setDateOffset(0)"
                            :class="[
                                selectedDate === todayString
                                    ? 'bg-indigo-600 text-white shadow-sm'
                                    : 'bg-gray-100 text-gray-700 hover:bg-gray-200',
                                'px-4 py-2 rounded-lg text-sm font-medium transition',
                            ]"
                        >
                            Hari Ini
                        </button>
                        <button
                            type="button"
                            @click="setDateOffset(1)"
                            :class="[
                                selectedDate === new Date(Date.now() + 86400000).toISOString().split('T')[0]
                                    ? 'bg-indigo-600 text-white shadow-sm'
                                    : 'bg-gray-100 text-gray-700 hover:bg-gray-200',
                                'px-4 py-2 rounded-lg text-sm font-medium transition',
                            ]"
                        >
                            Besok
                        </button>
                        <button
                            type="button"
                            @click="setDateOffset(2)"
                            :class="[
                                selectedDate === new Date(Date.now() + 172800000).toISOString().split('T')[0]
                                    ? 'bg-indigo-600 text-white shadow-sm'
                                    : 'bg-gray-100 text-gray-700 hover:bg-gray-200',
                                'px-4 py-2 rounded-lg text-sm font-medium transition',
                            ]"
                        >
                            Lusa
                        </button>

                        <!-- Date input picker -->
                        <div class="flex items-center gap-2 ml-auto">
                            <label for="date-picker" class="text-sm font-medium text-gray-700 hidden sm:inline">
                                Tanggal Spesifik:
                            </label>
                            <input
                                id="date-picker"
                                type="date"
                                v-model="selectedDate"
                                :min="todayString"
                                :max="maxDateString"
                                class="rounded-lg border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            />
                        </div>
                    </div>

                    <div class="mt-3 text-sm text-indigo-700 font-medium bg-indigo-50/60 px-3 py-1.5 rounded-lg inline-block">
                        📅 Tanggal Terpilih: {{ formatDateIndonesian(selectedDate) }}
                    </div>
                </div>

                <!-- STEP 3: GRID SLOT JAM -->
                <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
                        <div class="flex items-center gap-2">
                            <span class="flex items-center justify-center w-7 h-7 rounded-full bg-indigo-600 text-white font-bold text-sm">
                                3
                            </span>
                            <div>
                                <h3 class="text-lg font-bold text-gray-900">
                                    Pilih Slot Jam
                                </h3>
                                <p class="text-xs text-gray-500">
                                    Klik slot untuk memilih. Anda bisa memilih beberapa slot berurutan.
                                </p>
                            </div>
                        </div>

                        <!-- Legend Status -->
                        <div class="flex flex-wrap items-center gap-3 text-xs">
                            <div class="flex items-center gap-1.5">
                                <span class="w-3.5 h-3.5 rounded-md bg-emerald-500"></span>
                                <span class="text-gray-700">Tersedia</span>
                            </div>
                            <div class="flex items-center gap-1.5">
                                <span class="w-3.5 h-3.5 rounded-md bg-indigo-600"></span>
                                <span class="text-gray-700">Dipilih</span>
                            </div>
                            <div class="flex items-center gap-1.5">
                                <span class="w-3.5 h-3.5 rounded-md bg-blue-500"></span>
                                <span class="text-gray-700">Milik Saya</span>
                            </div>
                            <div class="flex items-center gap-1.5">
                                <span class="w-3.5 h-3.5 rounded-md bg-gray-300"></span>
                                <span class="text-gray-500">Sudah Dibooking</span>
                            </div>
                        </div>
                    </div>

                    <!-- Loading State -->
                    <div v-if="loadingSlots" class="py-12 flex flex-col items-center justify-center text-gray-400">
                        <svg class="animate-spin h-8 w-8 text-indigo-600 mb-3" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <p class="text-sm">Memuat ketersediaan slot jam...</p>
                    </div>

                    <!-- Slots Grid -->
                    <div v-else-if="slots.length > 0" class="grid grid-cols-2 sm:grid-cols-4 md:grid-cols-6 lg:grid-cols-8 gap-3">
                        <button
                            v-for="slot in slots"
                            :key="slot.start_time"
                            type="button"
                            @click="toggleSlot(slot)"
                            :disabled="slot.status !== 'available'"
                            :class="[
                                selectedSlotTimes.includes(slot.start_time)
                                    ? 'bg-indigo-600 text-white ring-2 ring-indigo-600 ring-offset-2 shadow-md'
                                    : slot.status === 'available'
                                    ? 'bg-emerald-50 text-emerald-800 border border-emerald-200 hover:bg-emerald-100 hover:border-emerald-300'
                                    : slot.status === 'mine'
                                    ? 'bg-blue-100 text-blue-800 border border-blue-200 cursor-not-allowed'
                                    : slot.status === 'past'
                                    ? 'bg-gray-100 text-gray-400 border border-gray-200 cursor-not-allowed opacity-60'
                                    : 'bg-gray-100 text-gray-400 border border-gray-200 cursor-not-allowed',
                                'relative flex flex-col items-center justify-center py-3 px-2 rounded-xl text-xs font-semibold transition-all duration-150',
                            ]"
                        >
                            <span class="text-sm font-bold tracking-tight">
                                {{ slot.start_time }}
                            </span>
                            <span class="text-[10px] mt-0.5 opacity-80">
                                {{ slot.end_time }}
                            </span>

                            <!-- Badge on slot -->
                            <span
                                v-if="selectedSlotTimes.includes(slot.start_time)"
                                class="mt-1 text-[9px] bg-white text-indigo-700 px-1.5 py-0.2 rounded font-bold uppercase"
                            >
                                Dipilih
                            </span>
                            <span
                                v-else-if="slot.status === 'mine'"
                                class="mt-1 text-[9px] bg-blue-600 text-white px-1.5 py-0.2 rounded font-bold"
                            >
                                Milik Anda
                            </span>
                            <span
                                v-else-if="slot.status === 'booked'"
                                class="mt-1 text-[9px] text-gray-500 font-medium"
                            >
                                Terisi
                            </span>
                            <span
                                v-else-if="slot.status === 'past'"
                                class="mt-1 text-[9px] text-gray-400"
                            >
                                Lewat
                            </span>
                            <span
                                v-else
                                class="mt-1 text-[9px] text-emerald-700"
                            >
                                Tersedia
                            </span>
                        </button>
                    </div>

                    <div v-else class="py-12 text-center text-gray-500 text-sm">
                        Tidak ada slot operasional untuk hari ini.
                    </div>

                    <InputError class="mt-4" :message="form.errors.slots" />
                    <InputError class="mt-1" :message="form.errors.start_time" />
                    <InputError class="mt-1" :message="form.errors.end_time" />
                </div>

                <!-- STEP 4: RINGKASAN BOOKING & KONFIRMASI -->
                <div class="bg-white rounded-xl shadow-md border border-indigo-100 overflow-hidden">
                    <div class="bg-indigo-600 px-6 py-4 text-white flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <span class="flex items-center justify-center w-7 h-7 rounded-full bg-white text-indigo-600 font-bold text-sm">
                                4
                            </span>
                            <h3 class="text-base font-bold">
                                Ringkasan Pemesanan
                            </h3>
                        </div>
                        <span v-if="totalHours > 0" class="text-xs bg-indigo-700 px-2.5 py-1 rounded-full font-medium">
                            {{ totalHours }} Jam Terpilih
                        </span>
                    </div>

                    <div class="p-6">
                        <div v-if="totalHours > 0" class="space-y-6">
                            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-4 pb-6 border-b border-gray-100">
                                <div>
                                    <span class="text-xs text-gray-500 font-medium">Lapangan:</span>
                                    <p class="text-base font-bold text-gray-900 mt-0.5">
                                        {{ selectedCourt?.name }}
                                    </p>
                                </div>
                                <div>
                                    <span class="text-xs text-gray-500 font-medium">Tanggal:</span>
                                    <p class="text-base font-bold text-gray-900 mt-0.5">
                                        {{ formatDateIndonesian(selectedDate) }}
                                    </p>
                                </div>
                                <div>
                                    <span class="text-xs text-gray-500 font-medium">Waktu Sewa:</span>
                                    <p class="text-base font-bold text-indigo-600 mt-0.5">
                                        {{ bookingStartTime }} - {{ bookingEndTime }}
                                        <span class="text-xs font-normal text-gray-500">({{ totalHours }} jam)</span>
                                    </p>
                                </div>
                                <div>
                                    <span class="text-xs text-gray-500 font-medium">Total Tarif:</span>
                                    <p class="text-xl font-extrabold text-emerald-600 mt-0.5">
                                        {{ formatPrice(totalPrice) }}
                                    </p>
                                </div>
                            </div>

                            <!-- Catatan Opsional -->
                            <div>
                                <InputLabel for="notes" value="Catatan Tambahan (Opsional)" />
                                <textarea
                                    id="notes"
                                    v-model="notes"
                                    rows="2"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm"
                                    placeholder="Contoh: Tolong siapkan shuttlecock, atau request lainnya..."
                                />
                                <InputError class="mt-1" :message="form.errors.notes" />
                            </div>

                            <!-- Warning / Info -->
                            <div class="rounded-lg bg-amber-50 p-4 border border-amber-200 text-xs text-amber-800 flex items-start gap-2">
                                <svg class="h-4 w-4 text-amber-600 flex-shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <p>
                                    Setelah menekan tombol <strong>Booking Sekarang</strong>, status booking akan berstatus <strong>Pending</strong> (menunggu pembayaran). Slot Anda aman dan terlindungi dari bentrok pesanan pengguna lain.
                                </p>
                            </div>

                            <!-- Action button -->
                            <div class="flex items-center justify-end gap-4 pt-2">
                                <PrimaryButton
                                    @click="submitBooking"
                                    :disabled="form.processing"
                                    class="px-6 py-3 text-sm font-bold bg-indigo-600 hover:bg-indigo-700"
                                >
                                    <span v-if="form.processing">Memproses Booking...</span>
                                    <span v-else>🏸 Booking Sekarang ({{ formatPrice(totalPrice) }})</span>
                                </PrimaryButton>
                            </div>
                        </div>

                        <!-- Empty selection state -->
                        <div v-else class="py-6 text-center text-gray-500">
                            <svg class="mx-auto h-12 w-12 text-gray-300 mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                            <p class="font-medium text-gray-700">Belum ada slot jam yang dipilih.</p>
                            <p class="text-xs text-gray-500 mt-1">
                                Silakan pilih satu atau beberapa slot jam hijau pada grid di atas untuk melanjutkan pemesanan.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

