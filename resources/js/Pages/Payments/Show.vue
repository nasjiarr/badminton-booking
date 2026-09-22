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
                    <h2 class="text-2xl sm:text-3xl font-display font-black tracking-tight text-courtSlate-900 uppercase">
                        Pembayaran Booking
                    </h2>
                    <p class="text-xs sm:text-sm font-medium text-courtSlate-500">
                        Invoice: <span class="font-display font-extrabold text-courtSlate-900">{{ payment.invoice_number }}</span>
                    </p>
                </div>
                <div class="inline-flex items-center gap-2 rounded-xl border-2 border-courtOrange/30 bg-courtOrange-light px-4 py-2">
                    <svg class="h-5 w-5 text-courtOrange animate-pulse" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <div>
                        <span class="block text-[10px] font-display font-extrabold uppercase tracking-wider text-courtOrange">Batas Waktu Bayar</span>
                        <span class="athletic-number text-lg font-black text-courtSlate-900">{{ formattedTimeLeft }}</span>
                    </div>
                </div>
            </div>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <!-- Info banner: Simulation mode -->
                <div class="mb-6 rounded-xl border-2 border-volt/80 bg-arena-base p-4 text-white shadow-volt-glow-sm flex items-start sm:items-center justify-between gap-4">
                    <div class="flex items-center gap-3">
                        <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-volt text-volt-contrast font-display font-black text-lg">
                            ⚡
                        </span>
                        <div>
                            <span class="court-badge-volt text-xs mb-1"><span>MODE SIMULASI PEMBAYARAN</span></span>
                            <p class="text-xs sm:text-sm text-courtSlate-300">
                                Ini adalah fitur simulasi transaksi untuk portofolio & demonstrasi alur. Tidak melibatkan uang riil.
                            </p>
                        </div>
                    </div>
                    <span class="hidden sm:inline-block text-[11px] font-display font-bold uppercase tracking-wider text-volt">
                        Sandbox Active
                    </span>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
                    <!-- Left Column: Choose Payment Method -->
                    <div class="lg:col-span-7 space-y-6">
                        <div class="rounded-xl border-2 border-courtSlate-200 bg-white p-6 sm:p-8 shadow-card-elevated">
                            <h3 class="text-xl font-display font-black uppercase tracking-tight text-courtSlate-900 mb-2">
                                Pilih Metode Pembayaran
                            </h3>
                            <p class="text-xs sm:text-sm text-courtSlate-500 mb-6">
                                Pilih saluran simulasi yang ingin Anda gunakan untuk menyelesaikan pemesanan ini.
                            </p>

                            <form @submit.prevent="submitPayment" class="space-y-4">
                                <!-- Option 1: Simulasi Transfer Bank -->
                                <label
                                    @click="selectedMethod = 'simulasi_transfer'"
                                    :class="[
                                        'group relative flex cursor-pointer items-start gap-4 rounded-xl border-2 p-5 transition',
                                        selectedMethod === 'simulasi_transfer'
                                            ? 'border-volt bg-courtSlate-50/80 ring-2 ring-volt/40 shadow-volt-glow-sm'
                                            : 'border-courtSlate-200 bg-white hover:border-courtSlate-300 hover:bg-courtSlate-50/50'
                                    ]"
                                >
                                    <input
                                        type="radio"
                                        name="method"
                                        value="simulasi_transfer"
                                        v-model="selectedMethod"
                                        class="mt-1 h-5 w-5 text-arena-base focus:ring-volt border-courtSlate-300"
                                    />
                                    <div class="flex-1">
                                        <div class="flex items-center justify-between">
                                            <span class="font-display font-black text-lg text-courtSlate-900 uppercase">
                                                Simulasi Transfer Bank
                                            </span>
                                            <span class="rounded bg-courtSlate-100 px-2 py-0.5 font-display font-extrabold text-[11px] text-courtSlate-600 uppercase">
                                                Virtual Account
                                            </span>
                                        </div>
                                        <p class="text-xs text-courtSlate-500 mt-1">
                                            Simulasikan pembayaran melalui nomor Virtual Account (BCA, Mandiri, BNI, BRI).
                                        </p>
                                        <div class="mt-3 flex items-center gap-2">
                                            <span class="rounded border border-courtSlate-200 bg-white px-2 py-0.5 text-[10px] font-bold text-courtSlate-700">BCA</span>
                                            <span class="rounded border border-courtSlate-200 bg-white px-2 py-0.5 text-[10px] font-bold text-courtSlate-700">MANDIRI</span>
                                            <span class="rounded border border-courtSlate-200 bg-white px-2 py-0.5 text-[10px] font-bold text-courtSlate-700">BNI</span>
                                            <span class="rounded border border-courtSlate-200 bg-white px-2 py-0.5 text-[10px] font-bold text-courtSlate-700">BRI</span>
                                        </div>
                                    </div>
                                </label>

                                <!-- Option 2: Simulasi E-Wallet -->
                                <label
                                    @click="selectedMethod = 'simulasi_ewallet'"
                                    :class="[
                                        'group relative flex cursor-pointer items-start gap-4 rounded-xl border-2 p-5 transition',
                                        selectedMethod === 'simulasi_ewallet'
                                            ? 'border-volt bg-courtSlate-50/80 ring-2 ring-volt/40 shadow-volt-glow-sm'
                                            : 'border-courtSlate-200 bg-white hover:border-courtSlate-300 hover:bg-courtSlate-50/50'
                                    ]"
                                >
                                    <input
                                        type="radio"
                                        name="method"
                                        value="simulasi_ewallet"
                                        v-model="selectedMethod"
                                        class="mt-1 h-5 w-5 text-arena-base focus:ring-volt border-courtSlate-300"
                                    />
                                    <div class="flex-1">
                                        <div class="flex items-center justify-between">
                                            <span class="font-display font-black text-lg text-courtSlate-900 uppercase">
                                                Simulasi E-Wallet & QRIS
                                            </span>
                                            <span class="rounded bg-courtSlate-100 px-2 py-0.5 font-display font-extrabold text-[11px] text-courtSlate-600 uppercase">
                                                Instan
                                            </span>
                                        </div>
                                        <p class="text-xs text-courtSlate-500 mt-1">
                                            Simulasikan scan kode QRIS atau konfirmasi e-wallet (GoPay, OVO, ShopeePay, Dana).
                                        </p>
                                        <div class="mt-3 flex items-center gap-2">
                                            <span class="rounded border border-courtSlate-200 bg-white px-2 py-0.5 text-[10px] font-bold text-courtSlate-700">QRIS</span>
                                            <span class="rounded border border-courtSlate-200 bg-white px-2 py-0.5 text-[10px] font-bold text-courtSlate-700">GOPAY</span>
                                            <span class="rounded border border-courtSlate-200 bg-white px-2 py-0.5 text-[10px] font-bold text-courtSlate-700">OVO</span>
                                            <span class="rounded border border-courtSlate-200 bg-white px-2 py-0.5 text-[10px] font-bold text-courtSlate-700">DANA</span>
                                        </div>
                                    </div>
                                </label>

                                <div class="pt-6 border-t border-courtSlate-200 flex items-center justify-between">
                                    <Link
                                        :href="route('my-bookings.index')"
                                        class="font-display font-bold text-xs uppercase tracking-wider text-courtSlate-500 hover:text-courtSlate-800 transition"
                                    >
                                        Bayar Nanti (Booking Saya)
                                    </Link>
                                    <button
                                        type="submit"
                                        :disabled="form.processing || remainingSeconds <= 0"
                                        class="inline-flex items-center justify-center gap-2 rounded-lg bg-volt px-8 py-3 font-display font-black text-sm uppercase tracking-wider text-volt-contrast shadow-sm hover:bg-volt-hover hover:shadow-volt-glow-sm transition active:scale-[0.98] disabled:opacity-50 disabled:cursor-not-allowed"
                                    >
                                        <span v-if="form.processing">Memproses...</span>
                                        <span v-else>Lanjutkan ke Pembayaran</span>
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Right Column: Booking Summary Card -->
                    <div class="lg:col-span-5">
                        <div class="rounded-xl border-2 border-courtSlate-200 bg-white p-6 shadow-card-elevated sticky top-24">
                            <h3 class="text-lg font-display font-black uppercase tracking-tight text-courtSlate-900 border-b border-courtSlate-100 pb-3 mb-4">
                                Rincian Pemesanan
                            </h3>

                            <!-- Court Photo & Name -->
                            <div class="flex items-center gap-4 mb-5">
                                <img
                                    v-if="payment.court.image_url"
                                    :src="payment.court.image_url"
                                    :alt="payment.court.name"
                                    class="h-16 w-24 rounded-lg object-cover border border-courtSlate-200"
                                />
                                <div
                                    v-else
                                    class="flex h-16 w-24 items-center justify-center rounded-lg bg-courtSlate-100 border border-courtSlate-200 text-courtSlate-400 font-display font-black text-sm"
                                >
                                    ARENA
                                </div>
                                <div>
                                    <h4 class="font-display font-black text-lg text-courtSlate-900 uppercase">
                                        {{ payment.court.name }}
                                    </h4>
                                    <span class="text-xs text-courtSlate-500">Standar Karpet Vinyl PBSI</span>
                                </div>
                            </div>

                            <!-- Details list -->
                            <dl class="space-y-3 text-sm border-y border-courtSlate-100 py-4 mb-4">
                                <div class="flex justify-between">
                                    <dt class="text-courtSlate-500">Tanggal Booking</dt>
                                    <dd class="font-semibold text-courtSlate-900">{{ payment.booking.booking_date_formatted }}</dd>
                                </div>
                                <div class="flex justify-between">
                                    <dt class="text-courtSlate-500">Jadwal Jam</dt>
                                    <dd class="font-semibold text-courtSlate-900">{{ payment.booking.start_time }} - {{ payment.booking.end_time }} WIB</dd>
                                </div>
                                <div class="flex justify-between">
                                    <dt class="text-courtSlate-500">Durasi</dt>
                                    <dd class="font-semibold text-courtSlate-900">{{ payment.booking.duration_hours }} Jam</dd>
                                </div>
                                <div class="flex justify-between">
                                    <dt class="text-courtSlate-500">Tarif / Jam</dt>
                                    <dd class="font-semibold text-courtSlate-900">{{ formatPrice(payment.court.price_per_hour) }}</dd>
                                </div>
                            </dl>

                            <!-- Total Price -->
                            <div class="flex items-baseline justify-between pt-1">
                                <div>
                                    <span class="block text-xs font-display font-extrabold uppercase tracking-wider text-courtSlate-500">Total Pembayaran</span>
                                    <span class="text-[11px] text-emerald-600 font-semibold">+{{ Math.floor(payment.amount / 10000) }} Poin Loyalty</span>
                                </div>
                                <span class="athletic-number text-2xl sm:text-3xl font-black text-courtSlate-900">
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

