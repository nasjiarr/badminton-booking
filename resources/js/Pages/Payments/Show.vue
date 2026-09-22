<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Toast from '@/Components/Toast.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref, onMounted, onUnmounted, computed } from 'vue';

const props = defineProps({
    payment: Object,
});

const selectedMethod = ref(props.payment.method || 'simulasi_transfer');

const form = useForm({
    method: selectedMethod.value,
});

const submitPayment = () => {
    form.method = selectedMethod.value;
    form.post(route('payments.pay', props.payment.id));
};

// Countdown timer (15 minutes from created_at)
const remainingSeconds = ref(props.payment.remaining_seconds || 900);
let timerInterval = null;

onMounted(() => {
    timerInterval = setInterval(() => {
        if (remainingSeconds.value > 0) {
            remainingSeconds.value--;
        } else {
            clearInterval(timerInterval);
        }
    }, 1000);
});

onUnmounted(() => {
    if (timerInterval) clearInterval(timerInterval);
});

const formattedTimeLeft = computed(() => {
    const mins = Math.floor(remainingSeconds.value / 60);
    const secs = remainingSeconds.value % 60;
    return `${String(mins).padStart(2, '0')}:${String(secs).padStart(2, '0')}`;
});

const formatPrice = (price) => {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0,
    }).format(price);
};
</script>

<template>
    <Head :title="`Pembayaran — ${payment.invoice_number}`" />

    <AuthenticatedLayout>
        <Toast />

        <template #header>
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <div class="flex items-center gap-2 mb-1">
                        <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-display font-black tracking-wider uppercase bg-volt/20 text-arena-base border border-volt/40 -skew-x-6">
                            <span class="transform skew-x-6">CHECKOUT TRANSAKSI</span>
                        </span>
                        <span class="text-xs text-courtSlate-400 font-semibold">• Tagihan Resmi</span>
                    </div>
                    <h1 class="text-2xl sm:text-3xl font-display font-black tracking-tight text-courtSlate-900 uppercase">
                        Pembayaran Booking Lapangan
                    </h1>
                    <p class="text-xs sm:text-sm font-medium text-courtSlate-500">
                        Nomor Invoice: <span class="font-display font-black text-courtSlate-900">{{ payment.invoice_number }}</span>
                    </p>
                </div>

                <!-- Timer Bar -->
                <div class="inline-flex items-center gap-3 rounded-2xl border-2 border-courtOrange/40 bg-orange-50/80 px-4 py-2.5 shadow-xs">
                    <div class="w-8 h-8 rounded-xl bg-courtOrange/10 flex items-center justify-center text-courtOrange">
                        <svg class="h-5 w-5 animate-pulse" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div>
                        <span class="block text-[10px] font-display font-extrabold uppercase tracking-wider text-courtOrange">Sisa Batas Waktu Bayar</span>
                        <span class="athletic-number text-xl font-black text-courtSlate-900 tabular-nums">{{ formattedTimeLeft }}</span>
                    </div>
                </div>
            </div>
        </template>

        <div class="py-8 bg-courtSlate-50/50 min-h-screen">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 space-y-8">
                
                <!-- AESTHETIC SANDBOX SIMULATION BANNER -->
                <div class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-arena-card via-slate-900 to-arena-base border-2 border-volt/60 p-5 sm:p-6 text-white shadow-card-active">
                    <div class="absolute top-0 left-0 right-0 h-1 bg-gradient-to-r from-volt via-lime-300 to-emerald-400"></div>
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                        <div class="flex items-start sm:items-center gap-3.5">
                            <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-volt text-arena-base font-display font-black text-xl shadow-xs -skew-x-3">
                                <span class="transform skew-x-3">⚡</span>
                            </div>
                            <div>
                                <div class="flex items-center gap-2 mb-1">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-display font-black uppercase tracking-wider bg-volt text-arena-base -skew-x-6">
                                        <span class="transform skew-x-6">MODE SIMULASI SANDBOX</span>
                                    </span>
                                    <span class="text-xs text-volt font-mono font-bold">PORTFOLIO DEMO</span>
                                </div>
                                <p class="text-xs sm:text-sm text-courtSlate-300 font-medium leading-relaxed">
                                    Alur transaksi di bawah mereplikasi proses pembayaran online secara nyata (Virtual Account & QRIS) tanpa melibatkan uang riil.
                                </p>
                            </div>
                        </div>
                        <span class="hidden lg:inline-flex items-center px-3 py-1 rounded-lg bg-slate-800 text-xs font-display font-bold uppercase text-slate-300 border border-slate-700">
                            🛡️ 100% Bebas Risiko Finansial
                        </span>
                    </div>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
                    
                    <!-- Left Column: Choose Payment Method -->
                    <div class="lg:col-span-7 space-y-6">
                        <div class="rounded-2xl border-2 border-courtSlate-200 bg-white p-6 sm:p-8 shadow-card-elevated">
                            <div class="border-b border-courtSlate-100 pb-4 mb-6">
                                <h3 class="text-xl font-display font-black uppercase tracking-tight text-courtSlate-900">
                                    Pilih Saluran Pembayaran
                                </h3>
                                <p class="text-xs sm:text-sm text-courtSlate-500 font-medium mt-0.5">
                                    Pilih metode simulasi yang ingin Anda gunakan untuk menyelesaikan invoice ini.
                                </p>
                            </div>

                            <form @submit.prevent="submitPayment" class="space-y-4">
                                <!-- Option 1: Simulasi Transfer Bank -->
                                <label
                                    @click="selectedMethod = 'simulasi_transfer'"
                                    :class="[
                                        'group relative flex cursor-pointer items-start gap-4 rounded-2xl border-2 p-5 transition-all duration-200',
                                        selectedMethod === 'simulasi_transfer'
                                            ? 'border-volt bg-volt/5 ring-2 ring-volt/50 shadow-volt-glow-sm'
                                            : 'border-courtSlate-200 bg-white hover:border-courtSlate-300 hover:bg-courtSlate-50/50'
                                    ]"
                                >
                                    <input
                                        type="radio"
                                        name="method"
                                        value="simulasi_transfer"
                                        v-model="selectedMethod"
                                        class="mt-1 h-5 w-5 text-volt focus:ring-volt border-courtSlate-300"
                                    />
                                    <div class="flex-1">
                                        <div class="flex items-center justify-between">
                                            <span class="font-display font-black text-lg text-courtSlate-900 uppercase">
                                                Simulasi Transfer Virtual Account
                                            </span>
                                            <span class="rounded-lg bg-courtSlate-100 px-2 py-0.5 font-display font-black text-[11px] text-courtSlate-700 uppercase -skew-x-6">
                                                <span class="transform skew-x-6">VA Otomatis</span>
                                            </span>
                                        </div>
                                        <p class="text-xs text-courtSlate-500 font-medium mt-1">
                                            Simulasikan pembayaran melalui nomor Virtual Account mitra perbankan nasional.
                                        </p>
                                        <div class="mt-3 flex flex-wrap items-center gap-1.5">
                                            <span class="rounded-md border border-courtSlate-200 bg-white px-2.5 py-1 text-[10px] font-display font-black text-courtSlate-700">BCA</span>
                                            <span class="rounded-md border border-courtSlate-200 bg-white px-2.5 py-1 text-[10px] font-display font-black text-courtSlate-700">MANDIRI</span>
                                            <span class="rounded-md border border-courtSlate-200 bg-white px-2.5 py-1 text-[10px] font-display font-black text-courtSlate-700">BNI</span>
                                            <span class="rounded-md border border-courtSlate-200 bg-white px-2.5 py-1 text-[10px] font-display font-black text-courtSlate-700">BRI</span>
                                        </div>
                                    </div>
                                </label>

                                <!-- Option 2: Simulasi E-Wallet -->
                                <label
                                    @click="selectedMethod = 'simulasi_ewallet'"
                                    :class="[
                                        'group relative flex cursor-pointer items-start gap-4 rounded-2xl border-2 p-5 transition-all duration-200',
                                        selectedMethod === 'simulasi_ewallet'
                                            ? 'border-volt bg-volt/5 ring-2 ring-volt/50 shadow-volt-glow-sm'
                                            : 'border-courtSlate-200 bg-white hover:border-courtSlate-300 hover:bg-courtSlate-50/50'
                                    ]"
                                >
                                    <input
                                        type="radio"
                                        name="method"
                                        value="simulasi_ewallet"
                                        v-model="selectedMethod"
                                        class="mt-1 h-5 w-5 text-volt focus:ring-volt border-courtSlate-300"
                                    />
                                    <div class="flex-1">
                                        <div class="flex items-center justify-between">
                                            <span class="font-display font-black text-lg text-courtSlate-900 uppercase">
                                                Simulasi E-Wallet & QRIS Standar
                                            </span>
                                            <span class="rounded-lg bg-courtSlate-100 px-2 py-0.5 font-display font-black text-[11px] text-courtSlate-700 uppercase -skew-x-6">
                                                <span class="transform skew-x-6">Instan QR</span>
                                            </span>
                                        </div>
                                        <p class="text-xs text-courtSlate-500 font-medium mt-1">
                                            Simulasikan pemindaian kode QRIS nasional atau konfirmasi dompet digital.
                                        </p>
                                        <div class="mt-3 flex flex-wrap items-center gap-1.5">
                                            <span class="rounded-md border border-courtSlate-200 bg-white px-2.5 py-1 text-[10px] font-display font-black text-courtSlate-700">QRIS</span>
                                            <span class="rounded-md border border-courtSlate-200 bg-white px-2.5 py-1 text-[10px] font-display font-black text-courtSlate-700">GOPAY</span>
                                            <span class="rounded-md border border-courtSlate-200 bg-white px-2.5 py-1 text-[10px] font-display font-black text-courtSlate-700">OVO</span>
                                            <span class="rounded-md border border-courtSlate-200 bg-white px-2.5 py-1 text-[10px] font-display font-black text-courtSlate-700">SHOPEEPAY</span>
                                            <span class="rounded-md border border-courtSlate-200 bg-white px-2.5 py-1 text-[10px] font-display font-black text-courtSlate-700">DANA</span>
                                        </div>
                                    </div>
                                </label>

                                <div class="pt-6 border-t border-courtSlate-200 flex flex-col sm:flex-row items-center justify-between gap-4">
                                    <Link
                                        :href="route('my-bookings.index')"
                                        class="font-display font-bold text-xs uppercase tracking-wider text-courtSlate-500 hover:text-courtSlate-900 transition"
                                    >
                                        ← Bayar Nanti (Daftar Booking Saya)
                                    </Link>
                                    <button
                                        type="submit"
                                        :disabled="form.processing || remainingSeconds <= 0"
                                        class="inline-flex w-full sm:w-auto items-center justify-center gap-2 rounded-xl bg-volt px-8 py-3.5 font-display font-black text-sm uppercase tracking-wider text-arena-base shadow-sm hover:bg-volt-hover hover:shadow-volt-glow-sm transition active:scale-[0.98] disabled:opacity-50 disabled:cursor-not-allowed -skew-x-3 cursor-pointer"
                                    >
                                        <span class="inline-flex items-center gap-1.5 transform skew-x-3">
                                            <span v-if="form.processing">Memproses Saluran...</span>
                                            <span v-else>💳 Lanjutkan ke Pembayaran →</span>
                                        </span>
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Right Column: Booking Summary Card -->
                    <div class="lg:col-span-5">
                        <div class="rounded-2xl border-2 border-courtSlate-200 bg-white p-6 sm:p-7 shadow-card-elevated sticky top-24">
                            <h3 class="text-lg font-display font-black uppercase tracking-tight text-courtSlate-900 border-b border-courtSlate-100 pb-3 mb-4">
                                Rincian Pesanan Lapangan
                            </h3>

                            <!-- Court Photo & Name -->
                            <div class="flex items-center gap-4 mb-5">
                                <div class="relative h-20 w-28 shrink-0 rounded-xl overflow-hidden bg-arena-card border border-courtSlate-200">
                                    <img
                                        v-if="payment.court.image_url"
                                        :src="payment.court.image_url"
                                        :alt="payment.court.name"
                                        class="h-full w-full object-cover"
                                    />
                                    <div
                                        v-else
                                        class="flex h-full w-full items-center justify-center text-courtSlate-400 font-display font-black text-xs"
                                    >
                                        ARENA PBSI
                                    </div>
                                </div>
                                <div>
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-display font-black uppercase tracking-wider bg-courtSlate-100 text-courtSlate-700">
                                        STANDAR PBSI
                                    </span>
                                    <h4 class="font-display font-black text-xl text-courtSlate-900 uppercase mt-0.5">
                                        {{ payment.court.name }}
                                    </h4>
                                    <span class="text-xs text-courtSlate-500 font-medium">Karpet Vinyl Kompetisi</span>
                                </div>
                            </div>

                            <!-- Recurring Booking Badge if applicable -->
                            <div v-if="payment.is_recurring && payment.recurring" class="mb-4 p-3.5 rounded-xl bg-indigo-50 border border-indigo-200">
                                <div class="flex items-center gap-1.5 text-xs font-black text-indigo-700 uppercase">
                                    <span>🔁 Paket Booking Rutin ({{ payment.recurring.total_sessions }} Sesi)</span>
                                </div>
                                <p class="text-[11px] text-indigo-600 mt-1 leading-relaxed">
                                    Setiap hari {{ payment.recurring.day_name }}, periode {{ payment.recurring.start_date }} s/d {{ payment.recurring.end_date }}. Pembayaran di muka untuk seluruh sesi.
                                </p>
                            </div>

                            <!-- Details list -->
                            <dl class="space-y-3 text-sm border-y border-courtSlate-100 py-4 mb-4">
                                <div class="flex justify-between items-center">
                                    <dt class="text-courtSlate-500 font-medium">Tanggal Booking</dt>
                                    <dd class="font-bold text-courtSlate-900">{{ payment.booking.booking_date_formatted }}</dd>
                                </div>
                                <div class="flex justify-between items-center">
                                    <dt class="text-courtSlate-500 font-medium">Jadwal Jam</dt>
                                    <dd class="font-bold text-courtSlate-900">{{ payment.booking.start_time }} - {{ payment.booking.end_time }} WIB</dd>
                                </div>
                                <div class="flex justify-between items-center">
                                    <dt class="text-courtSlate-500 font-medium">Durasi Sewa</dt>
                                    <dd class="font-bold text-courtSlate-900">{{ payment.booking.duration_hours }} Jam</dd>
                                </div>
                                <div class="flex justify-between items-center">
                                    <dt class="text-courtSlate-500 font-medium">Tarif Dasar / Jam</dt>
                                    <dd class="font-semibold text-courtSlate-800">{{ formatPrice(payment.court.price_per_hour) }}</dd>
                                </div>
                            </dl>

                            <!-- Total Price -->
                            <div class="flex items-baseline justify-between pt-1">
                                <div>
                                    <span class="block text-xs font-display font-extrabold uppercase tracking-wider text-courtSlate-500">Total Tagihan</span>
                                    <span class="inline-flex items-center gap-1 text-[11px] text-emerald-600 font-bold mt-0.5">
                                        ✓ +{{ Math.floor(payment.amount / 10000) }} Poin Loyalty
                                    </span>
                                </div>
                                <span class="athletic-number text-3xl sm:text-4xl font-black text-courtSlate-900 tracking-tight">
                                    {{ formatPrice(payment.amount) }}
                                </span>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
