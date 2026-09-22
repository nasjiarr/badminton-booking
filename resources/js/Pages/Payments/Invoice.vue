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
    <Head :title="`Invoice Resmi — ${payment.invoice_number}`" />

    <AuthenticatedLayout>
        <Toast />

        <template #header>
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 print:hidden">
                <div class="flex items-center gap-3">
                    <Link
                        :href="route('my-bookings.index')"
                        class="inline-flex h-10 w-10 items-center justify-center rounded-xl border-2 border-courtSlate-200 bg-white text-courtSlate-700 hover:bg-arena-base hover:text-volt hover:border-arena-base transition shadow-2xs"
                        title="Kembali ke Riwayat Booking"
                    >
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                        </svg>
                    </Link>
                    <div>
                        <div class="flex items-center gap-2">
                            <span class="inline-flex items-center -skew-x-6 bg-volt/20 text-volt-deep border border-volt/50 px-2 py-0.5 rounded text-[10px] font-display font-black tracking-wider uppercase">
                                Bukti Transaksi Sah
                            </span>
                            <span class="text-xs text-courtSlate-400 font-mono">
                                #{{ payment.invoice_number }}
                            </span>
                        </div>
                        <h2 class="text-2xl sm:text-3xl font-display font-black tracking-tight text-courtSlate-900 uppercase mt-0.5">
                            Invoice Pembayaran
                        </h2>
                    </div>
                </div>

                <div class="flex items-center gap-2.5">
                    <Link
                        :href="route('my-bookings.index')"
                        class="rounded-xl border-2 border-courtSlate-200 bg-white px-4 py-2.5 font-display font-bold text-xs uppercase tracking-wider text-courtSlate-700 hover:bg-courtSlate-100 transition shadow-2xs"
                    >
                        Riwayat Booking
                    </Link>
                    <button
                        type="button"
                        @click="printInvoice"
                        class="inline-flex items-center gap-2 rounded-xl bg-arena-base px-5 py-2.5 font-display font-black text-xs uppercase tracking-wider text-volt shadow-sm hover:bg-arena-surface hover:shadow-volt-glow-sm transition-all active:scale-[0.98] -skew-x-3 cursor-pointer"
                    >
                        <span class="inline-flex items-center gap-2 skew-x-3">
                            <svg class="h-4 w-4 text-volt" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                            </svg>
                            <span>Cetak / Download PDF</span>
                        </span>
                    </button>
                </div>
            </div>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
                
                <!-- Printable Official Sports Arena Invoice Sheet -->
                <div class="relative overflow-hidden rounded-3xl border-2 border-courtSlate-300 bg-white p-6 sm:p-12 shadow-2xl print:border-none print:shadow-none print:p-0 print:m-0">
                    
                    <!-- Decorative Top Court Accent Line -->
                    <div class="absolute top-0 left-0 right-0 h-3 bg-gradient-to-r from-arena-base via-volt to-courtOrange print:hidden"></div>

                    <!-- Header: Brand & Official Seal -->
                    <div class="flex flex-col md:flex-row md:items-start md:justify-between gap-6 border-b-2 border-courtSlate-200 pb-8 pt-2">
                        <div>
                            <div class="flex items-center gap-2.5">
                                <span class="flex h-11 w-11 items-center justify-center rounded-2xl bg-arena-base text-volt font-display font-black text-xl shadow-xs">
                                    🏸
                                </span>
                                <div>
                                    <span class="text-2xl sm:text-3xl font-display font-black tracking-wider text-arena-base uppercase">
                                        SMASH <span class="text-volt-deep">ARENA</span>
                                    </span>
                                    <span class="block text-[10px] font-display font-extrabold tracking-widest text-courtSlate-400 uppercase">
                                        Gelanggang Badminton Indoor Standar PBSI
                                    </span>
                                </div>
                            </div>
                            <p class="text-xs text-courtSlate-500 mt-3 leading-relaxed">
                                Kawasan Gelanggang Olahraga Modern No. 01, Jakarta Selatan<br />
                                Call Center: (021) 555-7627 • WhatsApp: 0812-8899-SMASH<br />
                                Website: arena.badminton-booking.test • NPWPD: 92.482.110.4-012.000
                            </p>
                        </div>

                        <!-- Right: Invoice Code & Official Stamp -->
                        <div class="flex flex-col sm:items-end justify-between">
                            <div class="sm:text-right">
                                <span class="text-[10px] font-display font-black uppercase tracking-widest text-courtSlate-400 block">
                                    NOMOR INVOICE RESMI
                                </span>
                                <h3 class="athletic-number text-2xl sm:text-3xl font-black text-courtSlate-900 tracking-wider">
                                    {{ payment.invoice_number }}
                                </h3>
                                <div class="text-xs text-courtSlate-500 mt-0.5">
                                    Diterbitkan: <span class="font-medium text-courtSlate-700">{{ payment.booking.created_at }} WIB</span>
                                </div>
                            </div>

                            <!-- Official PAID / LUNAS Stamp Seal -->
                            <div class="mt-4">
                                <div
                                    v-if="payment.status === 'paid'"
                                    class="inline-block transform -rotate-6 rounded-2xl border-4 border-emerald-600 bg-emerald-50/90 px-4 py-2 text-center shadow-md print:border-emerald-700"
                                >
                                    <div class="font-display font-black text-lg sm:text-xl tracking-widest text-emerald-700 uppercase flex items-center gap-1.5">
                                        <span>★ LUNAS / PAID ★</span>
                                    </div>
                                    <div class="text-[9px] font-extrabold tracking-wider text-emerald-800 uppercase mt-0.5">
                                        Terverifikasi: {{ payment.paid_at || payment.booking.created_at }} WIB
                                    </div>
                                </div>
                                <div
                                    v-else
                                    class="inline-block transform -rotate-3 rounded-2xl border-4 border-courtOrange bg-courtOrange-light px-4 py-2 text-center shadow-md"
                                >
                                    <div class="font-display font-black text-lg tracking-widest text-courtOrange uppercase">
                                        {{ payment.status.toUpperCase() }}
                                    </div>
                                    <div class="text-[9px] font-bold text-courtOrange uppercase mt-0.5">
                                        Menunggu Pelunasan
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Client & Transaction Info Grid -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 py-6 border-b-2 border-courtSlate-100 text-sm">
                        <!-- Left: Customer Information -->
                        <div class="rounded-2xl bg-courtSlate-50/80 border border-courtSlate-200 p-5">
                            <span class="block text-[10px] font-display font-black uppercase tracking-widest text-courtSlate-400 mb-2">
                                DITAGIHKAN KEPADA:
                            </span>
                            <div class="font-display font-black text-xl text-courtSlate-900 uppercase tracking-tight">
                                {{ payment.customer.name }}
                            </div>
                            <div class="text-xs text-courtSlate-600 mt-1 flex items-center gap-2">
                                <span>📧 {{ payment.customer.email }}</span>
                            </div>
                            <div class="text-xs text-courtSlate-500 mt-1">
                                Kode Reservasi: <strong class="text-courtSlate-800 font-mono">#BKG-{{ payment.booking.id }}</strong>
                            </div>
                        </div>

                        <!-- Right: Transaction Information -->
                        <div class="rounded-2xl bg-courtSlate-50/80 border border-courtSlate-200 p-5">
                            <span class="block text-[10px] font-display font-black uppercase tracking-widest text-courtSlate-400 mb-2">
                                RINCIAN PEMBAYARAN:
                            </span>
                            <div class="flex justify-between items-center text-xs text-courtSlate-700 py-0.5">
                                <span class="text-courtSlate-500">Metode Bayar:</span>
                                <span class="font-bold text-courtSlate-900">
                                    {{ payment.method === 'simulasi_transfer' ? 'Virtual Account Bank' : 'QRIS Standar Nasional' }}
                                </span>
                            </div>
                            <div class="flex justify-between items-center text-xs text-courtSlate-700 py-0.5">
                                <span class="text-courtSlate-500">Kanal Transaksi:</span>
                                <span class="font-semibold text-courtSlate-800">Simulasi Settlement Otomatis</span>
                            </div>
                            <div class="flex justify-between items-center text-xs text-courtSlate-700 py-0.5">
                                <span class="text-courtSlate-500">Waktu Pelunasan:</span>
                                <span class="font-semibold text-courtSlate-900">{{ payment.paid_at || '-' }} WIB</span>
                            </div>
                            <div v-if="pointsEarned > 0" class="mt-2 pt-2 border-t border-courtSlate-200 flex justify-between items-center">
                                <span class="text-[11px] font-bold text-emerald-800">Reward Loyalty Member:</span>
                                <span class="inline-flex items-center gap-1 text-xs font-display font-black text-emerald-700 bg-emerald-100 border border-emerald-300 px-2 py-0.5 rounded-md">
                                    +{{ pointsEarned }} Poin Ditambahkan
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Itemized Services Table -->
                    <div class="py-6">
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y-2 divide-courtSlate-200 text-left">
                                <thead>
                                    <tr class="bg-arena-base text-white">
                                        <th class="py-3.5 px-4 font-display font-black text-[11px] uppercase tracking-wider text-volt">
                                            Rincian Sesi & Fasilitas
                                        </th>
                                        <th class="py-3.5 px-4 font-display font-black text-[11px] uppercase tracking-wider text-white text-center">
                                            Durasi
                                        </th>
                                        <th class="py-3.5 px-4 font-display font-black text-[11px] uppercase tracking-wider text-white text-right">
                                            Tarif / Jam
                                        </th>
                                        <th class="py-3.5 px-4 font-display font-black text-[11px] uppercase tracking-wider text-volt text-right">
                                            Total Nominal
                                        </th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-courtSlate-200 text-sm">
                                    <!-- Recurring Sessions Rows -->
                                    <template v-if="payment.is_recurring && payment.recurring">
                                        <tr
                                            v-for="(sess, sIdx) in payment.recurring.sessions"
                                            :key="sess.id"
                                            class="hover:bg-courtSlate-50 transition"
                                        >
                                            <td class="py-4 px-4">
                                                <div class="font-display font-black text-sm text-courtSlate-900 uppercase">
                                                    Sewa {{ payment.court.name }} &bull; Sesi Minggu Ke-{{ sIdx + 1 }}
                                                </div>
                                                <div class="text-xs text-courtSlate-500 mt-0.5 flex items-center gap-2">
                                                    <span>📅 {{ sess.booking_date_formatted }}</span>
                                                    <span>⏰ {{ sess.start_time }} - {{ sess.end_time }} WIB</span>
                                                </div>
                                            </td>
                                            <td class="py-4 px-4 text-center font-bold text-courtSlate-700">
                                                {{ payment.booking.duration_hours }} Jam
                                            </td>
                                            <td class="py-4 px-4 text-right font-medium text-courtSlate-600">
                                                {{ formatPrice(payment.court.price_per_hour) }}
                                            </td>
                                            <td class="py-4 px-4 text-right athletic-number font-black text-sm text-courtSlate-900">
                                                {{ formatPrice(sess.total_price) }}
                                            </td>
                                        </tr>
                                    </template>

                                    <!-- Single Booking Row -->
                                    <tr v-else class="hover:bg-courtSlate-50 transition">
                                        <td class="py-5 px-4">
                                            <div class="flex items-center gap-2">
                                                <span class="font-display font-black text-base text-courtSlate-900 uppercase">
                                                    Sewa {{ payment.court.name }}
                                                </span>
                                                <span class="court-badge-base text-[9px]"><span>STANDAR PBSI</span></span>
                                            </div>
                                            <div class="text-xs text-courtSlate-500 mt-1 flex flex-wrap items-center gap-3">
                                                <span>📅 Tanggal: <strong class="text-courtSlate-800">{{ payment.booking.booking_date_formatted }}</strong></span>
                                                <span>⏰ Waktu: <strong class="text-courtSlate-800">{{ payment.booking.start_time }} - {{ payment.booking.end_time }} WIB</strong></span>
                                            </div>
                                            <div v-if="payment.booking.notes" class="text-xs text-courtSlate-400 italic mt-1.5 bg-courtSlate-100/60 p-2 rounded-lg">
                                                Catatan Pemesan: "{{ payment.booking.notes }}"
                                            </div>
                                        </td>
                                        <td class="py-5 px-4 text-center font-extrabold text-courtSlate-800 text-sm">
                                            {{ payment.booking.duration_hours }} Jam
                                        </td>
                                        <td class="py-5 px-4 text-right font-medium text-courtSlate-600 text-sm">
                                            {{ formatPrice(payment.court.price_per_hour) }}
                                        </td>
                                        <td class="py-5 px-4 text-right athletic-number font-black text-base text-courtSlate-900">
                                            {{ formatPrice(payment.amount) }}
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Total Calculations Section -->
                    <div class="border-t-2 border-courtSlate-200 pt-6">
                        <div class="flex flex-col sm:flex-row sm:justify-between items-start sm:items-end gap-6">
                            
                            <!-- Left: Payment Notice & Venue Rules -->
                            <div class="max-w-md text-xs text-courtSlate-500 space-y-1.5">
                                <div class="font-display font-black uppercase tracking-wider text-courtSlate-700 text-xs flex items-center gap-1.5">
                                    <span>⚠️ Ketentuan Gelanggang:</span>
                                </div>
                                <ul class="list-disc pl-4 space-y-0.5 text-[11px] text-courtSlate-500 leading-relaxed">
                                    <li>Wajib memakai sepatu badminton khusus dengan sol karet non-marking.</li>
                                    <li>Hadir setidaknya 10-15 menit sebelum sesi bermain Anda dimulai.</li>
                                    <li>Tunjukkan barcode atau invoice ini kepada staf resepsionis gelanggang.</li>
                                </ul>
                            </div>

                            <!-- Right: Price Breakdown -->
                            <div class="w-full sm:w-72 space-y-2 text-xs">
                                <div class="flex justify-between text-courtSlate-600">
                                    <span>Subtotal Reservasi</span>
                                    <span class="font-semibold text-courtSlate-900">{{ formatPrice(payment.amount) }}</span>
                                </div>
                                <div class="flex justify-between text-courtSlate-600">
                                    <span>Pajak Gelanggang (0% Bebas PPN)</span>
                                    <span class="font-semibold text-courtSlate-900">Rp 0</span>
                                </div>
                                <div class="flex justify-between items-baseline border-t-2 border-courtSlate-900 pt-3 text-courtSlate-900">
                                    <div>
                                        <span class="font-display font-black text-sm uppercase tracking-wider block">
                                            Total Lunas
                                        </span>
                                        <span class="text-[10px] text-emerald-700 font-bold uppercase">PAID IN FULL</span>
                                    </div>
                                    <span class="athletic-number text-2xl sm:text-3xl font-black text-courtSlate-900 tracking-tight">
                                        {{ formatPrice(payment.amount) }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Bottom Official Seal & Verification Footer -->
                    <div class="mt-10 pt-6 border-t border-courtSlate-200 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 text-xs text-courtSlate-400">
                        <div class="flex items-center gap-3">
                            <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-courtSlate-100 border border-courtSlate-200 text-courtSlate-600 font-mono text-xs shrink-0">
                                QR
                            </div>
                            <div>
                                <span class="font-display font-bold uppercase tracking-wider text-courtSlate-700 block text-[11px]">
                                    SMASH ARENA VERIFIED RECEIPT
                                </span>
                                <span class="text-[10px] text-courtSlate-500">
                                    Validasi digital: SHA-256 / {{ payment.invoice_number }}
                                </span>
                            </div>
                        </div>

                        <div class="text-right">
                            <span class="text-[10px] text-courtSlate-500 italic block">
                                Dokumen ini diterbitkan secara otomatis dan sah tanpa tanda tangan basah.
                            </span>
                            <span class="text-[10px] text-courtSlate-400 font-mono">
                                Smash Arena System &bull; Mode Portofolio Simulasi
                            </span>
                        </div>
                    </div>

                </div>

            </div>
        </div>
    </AuthenticatedLayout>
</template>
