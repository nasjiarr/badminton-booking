<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Toast from '@/Components/Toast.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, onMounted, onUnmounted, computed } from 'vue';

const props = defineProps({
    payment: Object,
});

const isProcessing = ref(false);

const simulateSuccess = () => {
    isProcessing.value = true;
    router.post(route('payments.simulate-success', props.payment.id), {}, {
        onFinish: () => { isProcessing.value = false; }
    });
};

const simulateFailed = () => {
    isProcessing.value = true;
    router.post(route('payments.simulate-failed', props.payment.id), {}, {
        onFinish: () => { isProcessing.value = false; }
    });
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

const copied = ref(false);
const copyVa = () => {
    navigator.clipboard.writeText(props.payment.simulated_va);
    copied.value = true;
    setTimeout(() => { copied.value = false; }, 2000);
};
</script>

<template>
    <Head :title="`Menunggu Pembayaran — ${payment.invoice_number}`" />

    <AuthenticatedLayout>
        <Toast />

        <template #header>
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h2 class="text-2xl sm:text-3xl font-display font-black tracking-tight text-courtSlate-900 uppercase">
                        Menunggu Pembayaran
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
                        <span class="block text-[10px] font-display font-extrabold uppercase tracking-wider text-courtOrange">Sisa Waktu Bayar</span>
                        <span class="athletic-number text-lg font-black text-courtSlate-900">{{ formattedTimeLeft }}</span>
                    </div>
                </div>
            </div>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8 space-y-8">
                <!-- Payment Instructions Card -->
                <div class="rounded-xl border-2 border-courtSlate-200 bg-white p-6 sm:p-8 shadow-card-elevated">
                    <div class="flex items-center justify-between border-b border-courtSlate-100 pb-4 mb-6">
                        <div>
                            <span class="text-xs font-display font-extrabold uppercase tracking-wider text-courtSlate-500">
                                Metode Pembayaran Dipilih
                            </span>
                            <h3 class="text-xl font-display font-black uppercase tracking-tight text-courtSlate-900 mt-0.5">
                                {{ payment.method === 'simulasi_transfer' ? 'Simulasi Transfer Bank (Virtual Account)' : 'Simulasi E-Wallet & QRIS' }}
                            </h3>
                        </div>
                        <span class="court-badge-orange text-xs"><span>MENUNGGU BAYAR</span></span>
                    </div>

                    <!-- Transfer VA View -->
                    <div v-if="payment.method === 'simulasi_transfer'" class="space-y-6">
                        <div class="rounded-xl bg-courtSlate-50 border-2 border-courtSlate-200 p-6">
                            <span class="block text-xs font-display font-bold uppercase tracking-wider text-courtSlate-500 mb-1">
                                Nomor Virtual Account (Simulasi)
                            </span>
                            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                                <span class="athletic-number text-2xl sm:text-3xl font-black text-courtSlate-900 tracking-wider">
                                    {{ payment.simulated_va }}
                                </span>
                                <button
                                    type="button"
                                    @click="copyVa"
                                    class="inline-flex items-center justify-center gap-1.5 rounded-lg border-2 border-courtSlate-200 bg-white px-3.5 py-1.5 font-display font-bold text-xs uppercase tracking-wider text-courtSlate-700 hover:bg-courtSlate-100 transition shadow-xs"
                                >
                                    <span>{{ copied ? 'Tersalin! ✓' : 'Salin Nomor' }}</span>
                                </button>
                            </div>
                            <div class="mt-4 pt-4 border-t border-courtSlate-200/80 flex flex-col sm:flex-row sm:justify-between text-xs text-courtSlate-600 gap-2">
                                <span>Penerima: <strong class="text-courtSlate-900">SMASH ARENA BADMINTON</strong></span>
                                <span>Bank Mitra: <strong class="text-courtSlate-900">BCA / MANDIRI / BRI / BNI</strong></span>
                            </div>
                        </div>

                        <div class="flex items-baseline justify-between rounded-lg border border-courtSlate-200 p-4">
                            <span class="text-sm font-semibold text-courtSlate-600">Total Nominal Pembayaran</span>
                            <span class="athletic-number text-2xl font-black text-courtSlate-900">{{ formatPrice(payment.amount) }}</span>
                        </div>
                    </div>

                    <!-- E-Wallet / QRIS View -->
                    <div v-else class="space-y-6">
                        <div class="flex flex-col items-center justify-center rounded-xl bg-courtSlate-50 border-2 border-courtSlate-200 p-6 text-center">
                            <!-- Mock QR Code SVG -->
                            <div class="p-4 bg-white rounded-xl border-2 border-courtSlate-200 shadow-sm mb-3">
                                <svg class="h-44 w-44 text-arena-base" viewBox="0 0 100 100" fill="currentColor">
                                    <path d="M0,0 h30 v30 h-30 z M5,5 v20 h20 v-20 z M10,10 h10 v10 h-10 z" />
                                    <path d="M70,0 h30 v30 h-30 z M75,5 v20 h20 v-20 z M80,10 h10 v10 h-10 z" />
                                    <path d="M0,70 h30 v30 h-30 z M5,75 v20 h20 v-20 z M10,80 h10 v10 h-10 z" />
                                    <rect x="40" y="10" width="8" height="8" />
                                    <rect x="52" y="10" width="8" height="8" />
                                    <rect x="40" y="22" width="8" height="8" />
                                    <rect x="40" y="40" width="20" height="20" />
                                    <rect x="10" y="40" width="10" height="10" />
                                    <rect x="70" y="40" width="12" height="8" />
                                    <rect x="85" y="40" width="8" height="8" />
                                    <rect x="70" y="52" width="8" height="12" />
                                    <rect x="82" y="52" width="10" height="10" />
                                    <rect x="40" y="70" width="8" height="8" />
                                    <rect x="52" y="70" width="12" height="8" />
                                    <rect x="40" y="82" width="10" height="10" />
                                    <rect x="70" y="70" width="10" height="10" />
                                    <rect x="85" y="80" width="8" height="12" />
                                </svg>
                            </div>
                            <span class="font-display font-extrabold text-sm text-courtSlate-900 uppercase tracking-wide">
                                QRIS Standar Pembayaran Nasional (Simulasi)
                            </span>
                            <span class="text-xs text-courtSlate-500 mt-0.5">
                                Dukungan: GoPay, OVO, ShopeePay, Dana, LinkAja & BCA Mobile
                            </span>
                        </div>

                        <div class="flex items-baseline justify-between rounded-lg border border-courtSlate-200 p-4">
                            <span class="text-sm font-semibold text-courtSlate-600">Total Nominal Pembayaran</span>
                            <span class="athletic-number text-2xl font-black text-courtSlate-900">{{ formatPrice(payment.amount) }}</span>
                        </div>
                    </div>
                </div>

                <!-- DEVELOPER SIMULATION PANEL -->
                <div class="rounded-xl border-2 border-volt bg-arena-base p-6 sm:p-8 text-white shadow-volt-glow-sm">
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 border-b border-arena-border pb-4 mb-6">
                        <div class="flex items-center gap-3">
                            <span class="flex h-10 w-10 items-center justify-center rounded-lg bg-volt text-volt-contrast font-display font-black text-lg">
                                🛠️
                            </span>
                            <div>
                                <h4 class="font-display font-black text-xl uppercase tracking-tight text-white flex items-center gap-2">
                                    Panel Simulasi Pengembang
                                    <span class="court-badge-volt text-[10px]"><span>DEV DEMO</span></span>
                                </h4>
                                <p class="text-xs text-courtSlate-300 mt-0.5">
                                    Gunakan tombol simulasi di bawah untuk menguji reaksi sistem secara instan.
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <!-- Success Simulation Button -->
                        <div class="rounded-lg border border-arena-border bg-arena-card p-5 flex flex-col justify-between">
                            <div>
                                <span class="font-display font-extrabold text-sm text-volt uppercase tracking-wider block mb-1">
                                    Skenario 1: Bayar Berhasil
                                </span>
                                <p class="text-xs text-courtSlate-300 leading-relaxed mb-4">
                                    Status pembayaran menjadi <strong class="text-white">Lunas (Paid)</strong>, booking menjadi <strong class="text-white">Confirmed</strong>, poin loyalty otomatis ditambahkan ke membership, dan notifikasi email dikirimkan.
                                </p>
                            </div>
                            <button
                                type="button"
                                @click="simulateSuccess"
                                :disabled="isProcessing || remainingSeconds <= 0"
                                class="inline-flex w-full items-center justify-center gap-2 rounded-lg bg-volt px-4 py-3 font-display font-black text-xs uppercase tracking-wider text-volt-contrast shadow-sm hover:bg-volt-hover hover:shadow-volt-glow-sm transition active:scale-[0.98] disabled:opacity-50"
                            >
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                </svg>
                                <span>Simulasikan Pembayaran Berhasil</span>
                            </button>
                        </div>

                        <!-- Failed Simulation Button -->
                        <div class="rounded-lg border border-arena-border bg-arena-card p-5 flex flex-col justify-between">
                            <div>
                                <span class="font-display font-extrabold text-sm text-courtOrange uppercase tracking-wider block mb-1">
                                    Skenario 2: Bayar Gagal
                                </span>
                                <p class="text-xs text-courtSlate-300 leading-relaxed mb-4">
                                    Status pembayaran menjadi <strong class="text-white">Failed</strong>, booking otomatis <strong class="text-white">Cancelled</strong>, slot jadwal langsung terbuka kembali secara real-time via Echo, dan notifikasi pembatalan dikirimkan.
                                </p>
                            </div>
                            <button
                                type="button"
                                @click="simulateFailed"
                                :disabled="isProcessing || remainingSeconds <= 0"
                                class="inline-flex w-full items-center justify-center gap-2 rounded-lg bg-courtOrange px-4 py-3 font-display font-black text-xs uppercase tracking-wider text-white shadow-sm hover:bg-courtOrange-hover hover:shadow-orange-glow transition active:scale-[0.98] disabled:opacity-50"
                            >
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                                <span>Simulasikan Pembayaran Gagal</span>
                            </button>
                        </div>
                    </div>
                </div>

                <div class="text-center">
                    <Link
                        :href="route('my-bookings.index')"
                        class="font-display font-bold text-xs uppercase tracking-wider text-courtSlate-500 hover:text-courtSlate-800 transition"
                    >
                        Kembali ke Booking Saya
                    </Link>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

