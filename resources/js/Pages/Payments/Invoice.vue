<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Toast from '@/Components/Toast.vue';
import { Head, Link } from '@inertiajs/vue3';

const props = defineProps({
    payment: Object,
    pointsEarned: Number,
});

const printInvoice = () => {
    window.print();
};

const formatPrice = (price) => {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0,
    }).format(price);
};
</script>

<template>
    <Head :title="`Invoice — ${payment.invoice_number}`" />

    <AuthenticatedLayout>
        <Toast />

        <template #header>
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 print:hidden">
                <div class="flex items-center gap-3">
                    <Link
                        :href="route('my-bookings.index')"
                        class="inline-flex h-9 w-9 items-center justify-center rounded-lg border-2 border-courtSlate-200 bg-white text-courtSlate-700 hover:bg-arena-base hover:text-volt hover:border-arena-base transition shadow-xs"
                        title="Kembali ke Booking Saya"
                    >
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                        </svg>
                    </Link>
                    <div>
                        <h2 class="text-2xl sm:text-3xl font-display font-black tracking-tight text-courtSlate-900 uppercase">
                            Invoice Pembayaran
                        </h2>
                        <p class="text-xs sm:text-sm font-medium text-courtSlate-500">
                            {{ payment.invoice_number }}
                        </p>
                    </div>
                </div>

                <div class="flex items-center gap-3">
                    <Link
                        :href="route('my-bookings.index')"
                        class="rounded-lg border-2 border-courtSlate-200 bg-white px-4 py-2 font-display font-bold text-xs uppercase tracking-wider text-courtSlate-700 hover:bg-courtSlate-100 transition"
                    >
                        Riwayat Booking
                    </Link>
                    <button
                        type="button"
                        @click="printInvoice"
                        class="inline-flex items-center gap-2 rounded-lg bg-arena-base px-4 py-2 font-display font-black text-xs uppercase tracking-wider text-volt shadow-sm hover:bg-arena-surface transition"
                    >
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                        </svg>
                        <span>Cetak Invoice</span>
                    </button>
                </div>
            </div>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
                <!-- Printable Invoice Sheet -->
                <div class="overflow-hidden rounded-2xl border-2 border-courtSlate-200 bg-white p-8 sm:p-12 shadow-card-elevated print:border-none print:shadow-none print:p-0">
                    <!-- Top Brand Bar -->
                    <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-6 border-b-2 border-courtSlate-200 pb-8">
                        <div>
                            <span class="text-2xl sm:text-3xl font-display font-black tracking-wider text-arena-base uppercase flex items-center gap-2">
                                🏸 SMASH <span class="text-volt-deep">ARENA</span>
                            </span>
                            <p class="text-xs text-courtSlate-500 mt-1 leading-relaxed">
                                Gelanggang Badminton Indoor Standar PBSI<br />
                                Jl. Arena Olahraga No. 1, Jakarta • Telp: (021) 555-7627
                            </p>
                        </div>
                        <div class="sm:text-right">
                            <span class="court-badge-volt text-sm">
                                <span>{{ payment.status === 'paid' ? 'LUNAS / PAID' : payment.status.toUpperCase() }}</span>
                            </span>
                            <h3 class="athletic-number text-xl sm:text-2xl font-black text-courtSlate-900 mt-2">
                                {{ payment.invoice_number }}
                            </h3>
                            <span class="text-xs text-courtSlate-500">
                                Diterbitkan: {{ payment.booking.created_at }} WIB
                            </span>
                        </div>
                    </div>

                    <!-- Client & Payment Meta Grid -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-6 py-6 border-b border-courtSlate-100 text-sm">
                        <div>
                            <span class="block text-xs font-display font-extrabold uppercase tracking-wider text-courtSlate-500 mb-1">
                                Ditagihkan Kepada:
                            </span>
                            <span class="font-display font-black text-lg text-courtSlate-900 uppercase block">
                                {{ payment.customer.name }}
                            </span>
                            <span class="text-courtSlate-600 block">{{ payment.customer.email }}</span>
                            <span class="text-courtSlate-500 text-xs mt-1 block">ID Pengguna: #{{ payment.customer.name ? 'USR-' + payment.booking.id : '-' }}</span>
                        </div>

                        <div class="sm:text-right">
                            <span class="block text-xs font-display font-extrabold uppercase tracking-wider text-courtSlate-500 mb-1">
                                Rincian Transaksi:
                            </span>
                            <span class="font-semibold text-courtSlate-900 block">
                                Metode: {{ payment.method === 'simulasi_transfer' ? 'Simulasi Transfer Virtual Account' : 'Simulasi E-Wallet & QRIS' }}
                            </span>
                            <span class="text-courtSlate-600 block text-xs mt-0.5">
                                Tanggal Lunas: {{ payment.paid_at || '-' }} WIB
                            </span>
                            <span v-if="pointsEarned > 0" class="inline-flex items-center gap-1 mt-1 text-xs font-bold text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded">
                                <span>Reward: +{{ pointsEarned }} Poin Loyalty</span>
                            </span>
                        </div>
                    </div>

                    <!-- Itemized Table -->
                    <div class="py-6">
                        <table class="min-w-full divide-y-2 divide-courtSlate-200">
                            <thead>
                                <tr class="font-display font-extrabold text-xs uppercase tracking-wider text-courtSlate-500">
                                    <th class="py-3 text-left">Deskripsi Layanan</th>
                                    <th class="py-3 text-center">Durasi</th>
                                    <th class="py-3 text-right">Tarif / Jam</th>
                                    <th class="py-3 text-right">Subtotal</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-courtSlate-100 text-sm">
                                <tr>
                                    <td class="py-4">
                                        <div class="font-display font-black text-base text-courtSlate-900 uppercase">
                                            Sewa {{ payment.court.name }}
                                        </div>
                                        <div class="text-xs text-courtSlate-500 mt-0.5">
                                            Tanggal: {{ payment.booking.booking_date_formatted }} • Jam: {{ payment.booking.start_time }} - {{ payment.booking.end_time }} WIB
                                        </div>
                                        <div v-if="payment.booking.notes" class="text-xs text-courtSlate-400 italic mt-0.5">
                                            Catatan: "{{ payment.booking.notes }}"
                                        </div>
                                    </td>
                                    <td class="py-4 text-center font-semibold text-courtSlate-800">
                                        {{ payment.booking.duration_hours }} Jam
                                    </td>
                                    <td class="py-4 text-right font-medium text-courtSlate-600">
                                        {{ formatPrice(payment.court.price_per_hour) }}
                                    </td>
                                    <td class="py-4 text-right athletic-number font-black text-base text-courtSlate-900">
                                        {{ formatPrice(payment.amount) }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Total Calculation Section -->
                    <div class="border-t-2 border-courtSlate-200 pt-6">
                        <div class="flex flex-col items-end space-y-2 text-sm">
                            <div class="flex justify-between w-64 text-courtSlate-600">
                                <span>Subtotal</span>
                                <span class="font-semibold text-courtSlate-900">{{ formatPrice(payment.amount) }}</span>
                            </div>
                            <div class="flex justify-between w-64 text-courtSlate-600">
                                <span>Pajak (0% Bebas PPN)</span>
                                <span class="font-semibold text-courtSlate-900">Rp 0</span>
                            </div>
                            <div class="flex justify-between w-64 border-t border-courtSlate-200 pt-3">
                                <span class="font-display font-black text-base uppercase text-courtSlate-900">Total Tagihan</span>
                                <span class="athletic-number text-2xl font-black text-courtSlate-900">{{ formatPrice(payment.amount) }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Bottom Notice / Watermark -->
                    <div class="mt-12 rounded-xl bg-courtSlate-50 border border-courtSlate-200 p-5 text-center text-xs text-courtSlate-500">
                        <p class="font-semibold text-courtSlate-700">
                            Terima kasih atas reservasi Anda di Smash Arena Badminton!
                        </p>
                        <p class="mt-1">
                            Harap tunjukkan lembar invoice ini (atau versi digital di ponsel) kepada petugas resepsionis gelanggang saat kedatangan.
                        </p>
                        <span class="block text-[10px] text-courtSlate-400 mt-2">
                            *Dokumen ini merupakan bukti transaksi yang sah secara elektronik. Pembayaran dilakukan dalam mode simulasi portofolio.
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

