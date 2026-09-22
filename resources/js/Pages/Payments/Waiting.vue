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
                    <div class="flex items-center gap-2 mb-1">
                        <span class="inline-flex items-center gap-1.5 -skew-x-6 bg-courtOrange/15 text-courtOrange border border-courtOrange/30 px-2.5 py-0.5 rounded text-[10px] font-display font-black tracking-widest uppercase">
                            <span class="h-1.5 w-1.5 rounded-full bg-courtOrange animate-ping"></span>
                            <span>Menunggu Transfer</span>
                        </span>
                        <span class="inline-flex items-center -skew-x-6 bg-volt/20 text-volt-deep border border-volt/50 px-2 py-0.5 rounded text-[10px] font-display font-extrabold tracking-wider uppercase">
                            Mode Simulasi Sandbox
                        </span>
                    </div>
                    <h2 class="text-2xl sm:text-3xl font-display font-black tracking-tight text-courtSlate-900 uppercase">
                        Menunggu Pembayaran
                    </h2>
                    <p class="text-xs sm:text-sm font-medium text-courtSlate-500">
                        Invoice: <span class="font-display font-extrabold text-courtSlate-900">{{ payment.invoice_number }}</span>
                    </p>
                </div>
                
                <!-- Athletic Countdown Timer -->
                <div class="inline-flex items-center gap-3 rounded-2xl border-2 border-courtOrange/40 bg-courtOrange-light p-3 sm:px-5 sm:py-2.5 shadow-sm">
                    <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-courtOrange text-white shrink-0 shadow-sm">
                        <svg class="h-5 w-5 animate-pulse" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div>
                        <span class="block text-[10px] font-display font-extrabold uppercase tracking-wider text-courtOrange">Batas Waktu Bayar</span>
                        <span class="athletic-number text-xl sm:text-2xl font-black text-courtSlate-900 tracking-tight">{{ formattedTimeLeft }}</span>
                    </div>
                </div>
            </div>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8 space-y-8">
                
                <!-- Aesthetic Simulation Notice Banner -->
                <div class="rounded-2xl border-2 border-volt/60 bg-gradient-to-r from-arena-card via-slate-900 to-arena-base p-4 sm:p-5 text-white shadow-volt-glow-sm flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <div class="flex items-center gap-3.5">
                        <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-volt/20 text-volt border border-volt/40 font-display font-black text-base shrink-0">
                            ⚡
                        </div>
                        <div>
                            <div class="flex items-center gap-2">
                                <span class="text-xs sm:text-sm font-display font-black uppercase tracking-wider text-volt">
                                    Simulasi Sandbox Aktif
                                </span>
                                <span class="court-badge-volt text-[9px] py-0.5"><span>PORTFOLIO DEMO</span></span>
                            </div>
                            <p class="text-xs text-courtSlate-300 mt-0.5">
                                Tidak ada penarikan dana nyata. Selesaikan pembayaran dengan klik tombol tes di panel dev bawah.
                            </p>
                        </div>
                    </div>
                    <div class="shrink-0">
                        <span class="text-xs font-bold text-volt-light tracking-wide bg-white/10 px-3 py-1.5 rounded-lg border border-white/10">
                            ID: #{{ payment.invoice_number }}
                        </span>
                    </div>
                </div>

                <!-- Payment Instructions Card -->
                <div class="rounded-2xl border-2 border-courtSlate-200 bg-white p-6 sm:p-8 shadow-card-elevated">
                    <div class="flex items-center justify-between border-b border-courtSlate-100 pb-4 mb-6">
                        <div>
                            <span class="text-xs font-display font-extrabold uppercase tracking-wider text-courtSlate-500">
                                Metode Pembayaran Dipilih
                            </span>
                            <h3 class="text-xl font-display font-black uppercase tracking-tight text-courtSlate-900 mt-0.5 flex items-center gap-2">
                                <span>{{ payment.method === 'simulasi_transfer' ? 'Simulasi Transfer Bank (Virtual Account)' : 'Simulasi E-Wallet & QRIS' }}</span>
                            </h3>
                        </div>
                        <span class="court-badge-orange text-xs"><span>MENUNGGU BAYAR</span></span>
                    </div>

                    <!-- Recurring Booking Bundle Summary if Applicable -->
                    <div v-if="payment.is_recurring && payment.recurring" class="mb-6 p-5 rounded-2xl bg-indigo-50 border-2 border-indigo-200">
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-xs font-display font-black text-indigo-900 uppercase tracking-wider flex items-center gap-1.5">
                                <span>🔁 Paket Booking Rutin ({{ payment.recurring.total_sessions }} Sesi)</span>
                            </span>
                            <span class="text-xs font-bold text-indigo-700 bg-white px-3 py-1 rounded-full border border-indigo-200">
                                Setiap {{ payment.recurring.day_name }}
                            </span>
                        </div>
                        <p class="text-xs text-indigo-700 leading-relaxed mb-3">
                            Pembayaran ini mencakup seluruh {{ payment.recurring.total_sessions }} sesi mingguan periode <strong>{{ payment.recurring.start_date }} s/d {{ payment.recurring.end_date }}</strong>.
                        </p>
                        <div class="space-y-2 max-h-40 overflow-y-auto pr-1">
                            <div v-for="(sess, idx) in payment.recurring.sessions" :key="sess.id" class="flex justify-between items-center text-xs bg-white px-3.5 py-2 rounded-xl border border-indigo-100 shadow-2xs">
                                <span class="font-medium text-courtSlate-700">Minggu {{ idx + 1 }}: {{ sess.booking_date_formatted }}</span>
                                <span class="athletic-number font-black text-courtSlate-900">{{ formatPrice(sess.total_price) }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Transfer VA View -->
                    <div v-if="payment.method === 'simulasi_transfer'" class="space-y-6">
                        <div class="rounded-2xl bg-courtSlate-50 border-2 border-courtSlate-200 p-6 sm:p-7">
                            <div class="flex items-center justify-between mb-2">
                                <span class="text-xs font-display font-bold uppercase tracking-wider text-courtSlate-500">
                                    Nomor Virtual Account (Simulasi)
                                </span>
                                <span class="text-[11px] font-bold text-courtSlate-500 bg-white border border-courtSlate-200 px-2 py-0.5 rounded">
                                    Otomatis Diverifikasi
                                </span>
                            </div>
                            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mt-2">
                                <span class="athletic-number text-2xl sm:text-3xl font-black text-courtSlate-900 tracking-widest bg-white border border-courtSlate-200 px-4 py-2.5 rounded-xl">
                                    {{ payment.simulated_va }}
                                </span>
                                <button
                                    type="button"
                                    @click="copyVa"
                                    class="inline-flex items-center justify-center gap-2 rounded-xl border-2 border-courtSlate-300 bg-white px-4 py-2.5 font-display font-bold text-xs uppercase tracking-wider text-courtSlate-800 hover:bg-courtSlate-100 hover:border-courtSlate-400 transition shadow-2xs"
                                >
                                    <svg v-if="!copied" class="h-4 w-4 text-courtSlate-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m0 0h2a2 2 0 012 2v3m2 4H10a2 2 0 00-2 2v6a2 2 0 002 2h10a2 2 0 002-2v-6a2 2 0 00-2-2z" />
                                    </svg>
                                    <svg v-else class="h-4 w-4 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                    </svg>
                                    <span>{{ copied ? 'Tersalin! ✓' : 'Salin Nomor VA' }}</span>
                                </button>
                            </div>
                            <div class="mt-5 pt-4 border-t border-courtSlate-200/80 flex flex-col sm:flex-row sm:justify-between text-xs text-courtSlate-600 gap-2">
                                <span>Penerima: <strong class="text-courtSlate-900">SMASH ARENA BADMINTON</strong></span>
                                <div class="flex items-center gap-1.5 flex-wrap">
                                    <span class="text-courtSlate-500">Bank Mitra:</span>
                                    <span class="px-1.5 py-0.5 rounded bg-white border border-courtSlate-200 font-bold text-[10px] text-courtSlate-700">BCA</span>
                                    <span class="px-1.5 py-0.5 rounded bg-white border border-courtSlate-200 font-bold text-[10px] text-courtSlate-700">MANDIRI</span>
                                    <span class="px-1.5 py-0.5 rounded bg-white border border-courtSlate-200 font-bold text-[10px] text-courtSlate-700">BNI</span>
                                    <span class="px-1.5 py-0.5 rounded bg-white border border-courtSlate-200 font-bold text-[10px] text-courtSlate-700">BRI</span>
                                </div>
                            </div>
                        </div>

                        <!-- Amount row with athletic numbers -->
                        <div class="flex items-baseline justify-between rounded-xl bg-courtSlate-50 border-2 border-courtSlate-200 p-5">
                            <div>
                                <span class="block text-xs font-display font-extrabold uppercase tracking-wider text-courtSlate-500">
                                    Total Nominal Pembayaran
                                </span>
                                <span class="text-xs text-courtSlate-500 mt-0.5">Transfer persis sesuai nominal tertera</span>
                            </div>
                            <span class="athletic-number text-2xl sm:text-3xl font-black text-courtSlate-900 tracking-tight">
                                {{ formatPrice(payment.amount) }}
                            </span>
                        </div>
                    </div>

                    <!-- E-Wallet / QRIS View -->
                    <div v-else class="space-y-6">
                        <div class="flex flex-col items-center justify-center rounded-2xl bg-courtSlate-50 border-2 border-courtSlate-200 p-6 sm:p-8 text-center">
                            <!-- Mock QR Code SVG with athletic frame -->
                            <div class="relative p-5 bg-white rounded-2xl border-2 border-courtSlate-300 shadow-md mb-4">
                                <!-- Corner indicators in Volt deep -->
                                <div class="absolute top-2 left-2 w-3 h-3 border-t-2 border-l-2 border-volt-deep"></div>
                                <div class="absolute top-2 right-2 w-3 h-3 border-t-2 border-r-2 border-volt-deep"></div>
                                <div class="absolute bottom-2 left-2 w-3 h-3 border-b-2 border-l-2 border-volt-deep"></div>
                                <div class="absolute bottom-2 right-2 w-3 h-3 border-b-2 border-r-2 border-volt-deep"></div>

                                <svg class="h-44 w-44 text-arena-base mx-auto" viewBox="0 0 100 100" fill="currentColor">
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
                            <span class="font-display font-extrabold text-sm sm:text-base text-courtSlate-900 uppercase tracking-wide">
                                QRIS Standar Pembayaran Nasional (Simulasi)
                            </span>
                            <div class="flex flex-wrap items-center justify-center gap-1.5 mt-2">
                                <span class="px-2 py-0.5 rounded bg-white border border-courtSlate-200 text-[10px] font-bold text-courtSlate-600">GoPay</span>
                                <span class="px-2 py-0.5 rounded bg-white border border-courtSlate-200 text-[10px] font-bold text-courtSlate-600">OVO</span>
                                <span class="px-2 py-0.5 rounded bg-white border border-courtSlate-200 text-[10px] font-bold text-courtSlate-600">ShopeePay</span>
                                <span class="px-2 py-0.5 rounded bg-white border border-courtSlate-200 text-[10px] font-bold text-courtSlate-600">DANA</span>
                                <span class="px-2 py-0.5 rounded bg-white border border-courtSlate-200 text-[10px] font-bold text-courtSlate-600">BCA Mobile</span>
                            </div>
                        </div>

                        <div class="flex items-baseline justify-between rounded-xl bg-courtSlate-50 border-2 border-courtSlate-200 p-5">
                            <div>
                                <span class="block text-xs font-display font-extrabold uppercase tracking-wider text-courtSlate-500">
                                    Total Tagihan QRIS
                                </span>
                                <span class="text-xs text-courtSlate-500 mt-0.5">Scan kode di atas menggunakan aplikasi e-wallet Anda</span>
                            </div>
                            <span class="athletic-number text-2xl sm:text-3xl font-black text-courtSlate-900 tracking-tight">
                                {{ formatPrice(payment.amount) }}
                            </span>
                        </div>
                    </div>
                </div>

                <!-- DEVELOPER SIMULATION PANEL (Aesthetic Dark Arena Sandbox) -->
                <div class="relative overflow-hidden rounded-2xl border-2 border-volt/60 bg-gradient-to-br from-arena-card via-slate-900 to-arena-base p-6 sm:p-8 text-white shadow-2xl">
                    <!-- Subtle Court Line Accent Background -->
                    <div class="absolute inset-0 opacity-5 pointer-events-none">
                        <div class="absolute top-0 right-0 w-80 h-80 border-4 border-volt rounded-full -mr-20 -mt-20"></div>
                        <div class="absolute bottom-0 left-0 w-60 h-60 border-2 border-volt rounded-full -ml-20 -mb-20"></div>
                    </div>

                    <div class="relative z-10">
                        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 border-b border-arena-border pb-5 mb-6">
                            <div class="flex items-center gap-3">
                                <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-volt text-arena-base font-display font-black text-xl shadow-volt-glow-sm shrink-0">
                                    ⚡
                                </span>
                                <div>
                                    <div class="flex items-center gap-2">
                                        <h4 class="font-display font-black text-xl uppercase tracking-tight text-white">
                                            Panel Simulasi Pengembang
                                        </h4>
                                        <span class="court-badge-volt text-[10px] py-0.5"><span>SANDBOX</span></span>
                                    </div>
                                    <p class="text-xs text-courtSlate-300 mt-0.5">
                                        Uji alur transaksi dan event listener secara instan tanpa gateway pembayaran nyata.
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                            <!-- Success Simulation Button (Volt Token) -->
                            <div class="rounded-xl border border-arena-border bg-arena-surface/80 p-5 sm:p-6 flex flex-col justify-between hover:border-volt/40 transition">
                                <div>
                                    <div class="flex items-center justify-between mb-2">
                                        <span class="font-display font-black text-sm text-volt uppercase tracking-wider flex items-center gap-1.5">
                                            <span>✓ Skenario: Bayar Sukses</span>
                                        </span>
                                        <span class="text-[10px] font-bold text-volt bg-volt/10 border border-volt/30 px-2 py-0.5 rounded">
                                            Status: Paid
                                        </span>
                                    </div>
                                    <p class="text-xs text-courtSlate-300 leading-relaxed mb-5">
                                        Men-simulasikan dana terverifikasi lunas. Booking menjadi <strong class="text-white">Confirmed</strong>, paid_at tercatat, poin membership otomatis dikreditkan, dan notifikasi email dikirimkan.
                                    </p>
                                </div>
                                <button
                                    type="button"
                                    @click="simulateSuccess"
                                    :disabled="isProcessing || remainingSeconds <= 0"
                                    class="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-volt px-5 py-3.5 font-display font-black text-xs sm:text-sm uppercase tracking-wider text-arena-base shadow-volt-glow-sm hover:bg-volt-hover hover:shadow-volt-glow transition-all duration-150 transform hover:-translate-y-0.5 active:translate-y-0 active:scale-[0.98] disabled:opacity-50 -skew-x-3 cursor-pointer"
                                >
                                    <span class="inline-flex items-center gap-2 skew-x-3">
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                        </svg>
                                        <span>Simulasikan Pembayaran Berhasil</span>
                                    </span>
                                </button>
                            </div>

                            <!-- Failed Simulation Button (Speed Orange Token) -->
                            <div class="rounded-xl border border-arena-border bg-arena-surface/80 p-5 sm:p-6 flex flex-col justify-between hover:border-courtOrange/40 transition">
                                <div>
                                    <div class="flex items-center justify-between mb-2">
                                        <span class="font-display font-black text-sm text-courtOrange uppercase tracking-wider flex items-center gap-1.5">
                                            <span>✕ Skenario: Bayar Gagal</span>
                                        </span>
                                        <span class="text-[10px] font-bold text-courtOrange bg-courtOrange/10 border border-courtOrange/30 px-2 py-0.5 rounded">
                                            Status: Failed
                                        </span>
                                    </div>
                                    <p class="text-xs text-courtSlate-300 leading-relaxed mb-5">
                                        Men-simulasikan pembayaran ditolak atau kedaluwarsa. Status payment menjadi <strong class="text-white">Failed</strong>, booking menjadi <strong class="text-white">Cancelled</strong>, dan slot jadwal langsung dibuka kembali secara real-time.
                                    </p>
                                </div>
                                <button
                                    type="button"
                                    @click="simulateFailed"
                                    :disabled="isProcessing || remainingSeconds <= 0"
                                    class="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-courtOrange px-5 py-3.5 font-display font-black text-xs sm:text-sm uppercase tracking-wider text-white shadow-orange-glow hover:bg-courtOrange-hover hover:shadow-lg transition-all duration-150 transform hover:-translate-y-0.5 active:translate-y-0 active:scale-[0.98] disabled:opacity-50 -skew-x-3 cursor-pointer"
                                >
                                    <span class="inline-flex items-center gap-2 skew-x-3">
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                        </svg>
                                        <span>Simulasikan Pembayaran Gagal</span>
                                    </span>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="text-center pt-2">
                    <Link
                        :href="route('my-bookings.index')"
                        class="inline-flex items-center gap-1.5 font-display font-bold text-xs uppercase tracking-wider text-courtSlate-500 hover:text-courtSlate-900 transition"
                    >
                        <span>&larr; Kembali ke Riwayat Booking Saya</span>
                    </Link>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
