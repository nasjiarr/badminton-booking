<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import DangerButton from '@/Components/DangerButton.vue';
import Modal from '@/Components/Modal.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import EmptyState from '@/Components/EmptyState.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    bookings: Object,
    currentFilter: String,
});

const confirmingCancellation = ref(false);
const bookingToCancel = ref(null);
const cancelling = ref(false);

const filterTabs = [
    { label: 'Semua', value: 'all' },
    { label: 'Menunggu Bayar', value: 'pending' },
    { label: 'Dikonfirmasi', value: 'confirmed' },
    { label: 'Selesai', value: 'completed' },
    { label: 'Dibatalkan', value: 'cancelled' },
];

const applyFilter = (filterValue) => {
    router.get(
        route('my-bookings.index'),
        { status: filterValue },
        { preserveState: true, preserveScroll: true }
    );
};

const openCancelModal = (booking) => {
    bookingToCancel.value = booking;
    confirmingCancellation.value = true;
};

const closeCancelModal = () => {
    confirmingCancellation.value = false;
    bookingToCancel.value = null;
};

const submitCancel = () => {
    if (!bookingToCancel.value) return;

    cancelling.value = true;
    router.patch(
        route('bookings.cancel', bookingToCancel.value.id),
        {},
        {
            preserveScroll: true,
            onFinish: () => {
                cancelling.value = false;
                closeCancelModal();
            },
        }
    );
};

const formatPrice = (price) => {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0,
    }).format(price);
};

const getStatusBadge = (status) => {
    switch (status) {
        case 'pending':
            return {
                label: 'Menunggu Pembayaran',
                class: 'bg-amber-100 text-amber-800 border-amber-300',
            };
        case 'confirmed':
            return {
                label: 'Dikonfirmasi',
                class: 'bg-volt/20 text-volt-deep border-volt/40',
            };
        case 'completed':
            return {
                label: 'Selesai',
                class: 'bg-emerald-100 text-emerald-800 border-emerald-300',
            };
        case 'cancelled':
            return {
                label: 'Dibatalkan',
                class: 'bg-courtOrange/10 text-courtOrange border-courtOrange/30',
            };
        default:
            return {
                label: status,
                class: 'bg-courtSlate-100 text-courtSlate-700 border-courtSlate-200',
            };
    }
};
</script>

<template>
    <Head title="Booking Saya" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <div class="flex items-center gap-2 mb-1">
                        <span class="court-badge-volt text-[10px] py-0.5"><span>Pemesanan Saya</span></span>
                    </div>
                    <h2 class="text-2xl sm:text-3xl font-display font-black tracking-tight text-courtSlate-900 uppercase">
                        Riwayat Booking Lapangan
                    </h2>
                    <p class="text-xs sm:text-sm text-courtSlate-600 mt-0.5">
                        Kelola dan pantau seluruh jadwal pemesanan lapangan Anda.
                    </p>
                </div>

                <div class="flex items-center gap-3">
                    <!-- Navigation switcher -->
                    <div class="flex items-center gap-1 bg-courtSlate-100 p-1 rounded-xl border border-courtSlate-200">
                        <span class="px-3 py-1.5 rounded-lg text-xs font-display font-black uppercase tracking-wider bg-arena-base text-volt shadow-xs">
                            Booking Satuan
                        </span>
                        <Link
                            :href="route('recurring-bookings.index')"
                            class="px-3 py-1.5 rounded-lg text-xs font-display font-bold uppercase tracking-wider text-courtSlate-600 hover:text-courtSlate-900 hover:bg-white/50 transition"
                        >
                            🔁 Booking Rutin
                        </Link>
                    </div>

                    <Link
                        :href="route('bookings.create')"
                        class="inline-flex items-center gap-1.5 px-4 py-2.5 rounded-xl bg-volt text-volt-contrast font-display font-black text-xs uppercase tracking-wider shadow-volt-glow-sm hover:bg-volt-hover transition active:scale-[0.98] -skew-x-3 cursor-pointer"
                    >
                        <span class="inline-flex items-center gap-1 skew-x-3">
                            <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                            </svg>
                            <span>Booking Baru</span>
                        </span>
                    </Link>
                </div>
            </div>
        </template>

        <div class="py-8">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
                <!-- Filter Tabs -->
                <div class="flex items-center gap-2 border-b-2 border-courtSlate-200 pb-3 overflow-x-auto">
                    <button
                        v-for="tab in filterTabs"
                        :key="tab.value"
                        type="button"
                        @click="applyFilter(tab.value)"
                        class="px-4 py-2 rounded-xl text-xs font-display font-black uppercase tracking-wider transition whitespace-nowrap"
                        :class="[
                            currentFilter === tab.value
                                ? 'bg-arena-base text-volt shadow-xs'
                                : 'bg-white text-courtSlate-600 hover:text-courtSlate-900 border border-courtSlate-200',
                        ]"
                    >
                        {{ tab.label }}
                    </button>
                </div>

                <!-- Bookings List -->
                <div v-if="bookings.data.length > 0" class="space-y-4">
                    <div
                        v-for="booking in bookings.data"
                        :key="booking.id"
                        class="bg-white rounded-2xl border-2 border-courtSlate-200 p-5 sm:p-6 shadow-xs hover:shadow-card-elevated hover:border-courtSlate-300 transition-all flex flex-col md:flex-row md:items-center justify-between gap-5"
                    >
                        <!-- Left info -->
                        <div class="flex items-start gap-4">
                            <!-- Court image/thumb -->
                            <div class="w-16 h-16 sm:w-20 sm:h-20 rounded-xl overflow-hidden bg-arena-card border border-arena-border flex-shrink-0 flex items-center justify-center">
                                <img
                                    v-if="booking.court_image"
                                    :src="booking.court_image"
                                    :alt="booking.court_name"
                                    class="w-full h-full object-cover"
                                />
                                <div v-else class="text-courtSlate-400 text-2xl">
                                    🏸
                                </div>
                            </div>

                            <!-- Details -->
                            <div>
                                <div class="flex items-center gap-2.5 flex-wrap">
                                    <h3 class="font-display font-black text-lg sm:text-xl text-courtSlate-900 uppercase tracking-tight">
                                        {{ booking.court_name }}
                                    </h3>
                                    <span
                                        :class="getStatusBadge(booking.status).class"
                                        class="inline-flex items-center px-2.5 py-0.5 rounded text-[10px] font-display font-black uppercase tracking-wider border -skew-x-6"
                                    >
                                        <span class="skew-x-6">{{ getStatusBadge(booking.status).label }}</span>
                                    </span>
                                </div>

                                <div class="mt-2 flex flex-wrap items-center gap-x-4 gap-y-1 text-xs text-courtSlate-600 font-medium">
                                    <div class="flex items-center gap-1.5">
                                        <span class="text-courtSlate-400">📅</span>
                                        <span>{{ booking.booking_date_formatted }}</span>
                                    </div>
                                    <div class="flex items-center gap-1.5 font-display font-extrabold text-courtSlate-900 text-sm">
                                        <span class="text-courtSlate-400 font-normal">⏰</span>
                                        <span>{{ booking.start_time }} - {{ booking.end_time }} WIB</span>
                                    </div>
                                    <div class="text-[11px] text-courtSlate-400 font-mono">
                                        #{{ booking.id }}
                                    </div>
                                </div>

                                <p v-if="booking.notes" class="text-xs text-courtSlate-600 mt-2 bg-courtSlate-50 px-3 py-1.5 rounded-lg border border-courtSlate-100 max-w-lg">
                                    <span class="font-bold text-courtSlate-700">Catatan:</span> {{ booking.notes }}
                                </p>
                            </div>
                        </div>

                        <!-- Right pricing & actions -->
                        <div class="flex sm:flex-col items-center sm:items-end justify-between sm:justify-center gap-3 pt-4 sm:pt-0 border-t sm:border-t-0 border-courtSlate-100">
                            <div class="sm:text-right">
                                <span class="text-[10px] font-display font-black uppercase tracking-widest text-courtSlate-400 block">Total Tagihan:</span>
                                <span class="athletic-number text-xl sm:text-2xl font-black text-courtSlate-900 tracking-tight">
                                    {{ formatPrice(booking.total_price) }}
                                </span>
                            </div>

                            <div class="flex flex-wrap items-center gap-2">
                                <!-- Pay now button for pending bookings -->
                                <Link
                                    v-if="booking.status === 'pending' && booking.payment"
                                    :href="route('payments.show', booking.payment.id)"
                                    class="inline-flex items-center gap-1.5 rounded-xl bg-volt px-3.5 py-2 font-display font-black text-xs uppercase tracking-wider text-volt-contrast shadow-volt-glow-sm hover:bg-volt-hover transition active:scale-[0.98] -skew-x-3 cursor-pointer"
                                >
                                    <span class="inline-flex items-center gap-1.5 skew-x-3">
                                        <span>Bayar Sekarang</span>
                                        <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                                        </svg>
                                    </span>
                                </Link>

                                <!-- View invoice for paid bookings -->
                                <Link
                                    v-if="booking.payment && booking.payment.status === 'paid'"
                                    :href="route('payments.invoice', booking.payment.id)"
                                    class="inline-flex items-center gap-1.5 rounded-xl border-2 border-courtSlate-200 bg-white px-3.5 py-2 font-display font-extrabold text-xs uppercase tracking-wider text-courtSlate-800 hover:bg-courtSlate-100 transition shadow-xs"
                                >
                                    <svg class="h-3.5 w-3.5 text-courtSlate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                    </svg>
                                    <span>Invoice</span>
                                </Link>

                                <!-- Cancel button if eligible under policy -->
                                <button
                                    v-if="booking.can_cancel"
                                    type="button"
                                    @click="openCancelModal(booking)"
                                    class="inline-flex items-center px-3 py-2 text-xs font-display font-bold uppercase tracking-wider text-courtOrange bg-courtOrange-light hover:bg-courtOrange/20 border border-courtOrange/30 rounded-xl transition"
                                >
                                    Batalkan
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Pagination -->
                    <div v-if="bookings.links && bookings.links.length > 3" class="flex justify-center mt-6">
                        <div class="flex gap-1">
                            <template v-for="(link, i) in bookings.links" :key="i">
                                <Link
                                    v-if="link.url"
                                    :href="link.url"
                                    v-html="link.label"
                                    :class="[
                                        link.active
                                            ? 'bg-arena-base text-volt font-bold'
                                            : 'bg-white text-courtSlate-700 hover:bg-courtSlate-100 border border-courtSlate-200',
                                        'px-3.5 py-1.5 text-xs rounded-lg font-display font-bold transition',
                                    ]"
                                />
                                <span
                                    v-else
                                    v-html="link.label"
                                    class="px-3.5 py-1.5 text-xs text-courtSlate-400 bg-courtSlate-50 border border-courtSlate-200 rounded-lg cursor-not-allowed font-display font-bold"
                                />
                            </template>
                        </div>
                    </div>
                </div>

                <!-- Reusable Empty State -->
                <EmptyState
                    v-else
                    icon="🏸"
                    title="Tidak Ada Riwayat Booking"
                    description="Anda belum memiliki jadwal pemesanan lapangan pada filter status ini. Silakan buat booking baru untuk memesan lapangan badminton."
                    actionLabel="🏸 Booking Lapangan Sekarang"
                    :actionUrl="route('bookings.create')"
                />
            </div>
        </div>

        <!-- Cancellation Confirmation Modal -->
        <Modal :show="confirmingCancellation" @close="closeCancelModal">
            <div class="p-6 bg-white rounded-2xl">
                <div class="flex items-center gap-3 text-courtOrange mb-3">
                    <div class="w-10 h-10 rounded-xl bg-courtOrange/10 border-2 border-courtOrange/20 flex items-center justify-center shrink-0">
                        <svg class="h-5 w-5 text-courtOrange" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                    </div>
                    <h2 class="font-display font-black text-xl uppercase tracking-tight text-courtSlate-900">
                        Konfirmasi Pembatalan Booking
                    </h2>
                </div>

                <p class="text-sm text-courtSlate-600 mt-2 leading-relaxed">
                    Apakah Anda yakin ingin membatalkan booking <strong class="text-courtSlate-900">#{{ bookingToCancel?.id }}</strong> di <strong class="text-courtSlate-900">{{ bookingToCancel?.court_name }}</strong> pada tanggal <strong class="text-courtSlate-900">{{ bookingToCancel?.booking_date_formatted }} ({{ bookingToCancel?.start_time }} - {{ bookingToCancel?.end_time }})</strong>?
                </p>

                <div class="mt-4 p-3.5 rounded-xl bg-courtOrange-light border border-courtOrange/20 text-xs text-courtOrange leading-relaxed">
                    ⚠️ <strong>Perhatian:</strong> Slot jam ini akan segera dibuka kembali untuk dapat dipesan oleh pelanggan lain.
                </div>

                <div class="mt-6 flex justify-end gap-3">
                    <SecondaryButton @click="closeCancelModal" class="rounded-xl border-2 border-courtSlate-200 text-courtSlate-700 hover:bg-courtSlate-100">
                        Kembali
                    </SecondaryButton>
                    <DangerButton :disabled="cancelling" @click="submitCancel" class="rounded-xl bg-courtOrange hover:bg-courtOrange-hover text-white">
                        <span v-if="cancelling">Membatalkan...</span>
                        <span v-else>Ya, Batalkan Booking</span>
                    </DangerButton>
                </div>
            </div>
        </Modal>
    </AuthenticatedLayout>
</template>
