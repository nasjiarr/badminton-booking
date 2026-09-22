<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import DangerButton from '@/Components/DangerButton.vue';
import Modal from '@/Components/Modal.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
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
    { label: 'Menunggu Pembayaran', value: 'pending' },
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

const getStatusBadgeClass = (status) => {
    switch (status) {
        case 'pending':
            return 'bg-amber-100 text-amber-800 border border-amber-200';
        case 'confirmed':
            return 'bg-emerald-100 text-emerald-800 border border-emerald-200';
        case 'completed':
            return 'bg-blue-100 text-blue-800 border border-blue-200';
        case 'cancelled':
            return 'bg-rose-100 text-rose-800 border border-rose-200';
        default:
            return 'bg-gray-100 text-gray-800 border border-gray-200';
    }
};

const getStatusLabel = (status) => {
    switch (status) {
        case 'pending':
            return 'Menunggu Pembayaran';
        case 'confirmed':
            return 'Dikonfirmasi';
        case 'completed':
            return 'Selesai';
        case 'cancelled':
            return 'Dibatalkan';
        default:
            return status;
    }
};
</script>

<template>
    <Head title="Booking Saya" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h2 class="text-xl font-semibold leading-tight text-gray-800">
                        Riwayat Booking Saya
                    </h2>
                    <p class="text-sm text-gray-500 mt-0.5">
                        Kelola dan pantau seluruh jadwal pemesanan lapangan Anda.
                    </p>
                </div>
                <Link
                    :href="route('bookings.create')"
                    class="inline-flex items-center justify-center rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 transition"
                >
                    <svg class="-ml-0.5 mr-1.5 h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    Booking Lapangan Baru
                </Link>
            </div>
        </template>

        <div class="py-8">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
                <!-- Filter Tabs -->
                <div class="flex flex-wrap gap-2 pb-2 border-b border-gray-200">
                    <button
                        v-for="tab in filterTabs"
                        :key="tab.value"
                        type="button"
                        @click="applyFilter(tab.value)"
                        :class="[
                            currentFilter === tab.value
                                ? 'bg-indigo-600 text-white shadow-sm font-semibold'
                                : 'bg-white text-gray-600 hover:bg-gray-100 hover:text-gray-900 border border-gray-200',
                            'px-4 py-2 rounded-lg text-xs sm:text-sm font-medium transition',
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
                        class="bg-white rounded-xl shadow-sm border border-gray-200 p-5 sm:p-6 transition hover:shadow-md flex flex-col md:flex-row md:items-center justify-between gap-5"
                    >
                        <!-- Left info -->
                        <div class="flex items-start gap-4">
                            <!-- Court image/thumb -->
                            <div class="w-16 h-16 sm:w-20 sm:h-20 rounded-lg overflow-hidden bg-gray-100 flex-shrink-0 flex items-center justify-center">
                                <img
                                    v-if="booking.court_image"
                                    :src="booking.court_image"
                                    :alt="booking.court_name"
                                    class="w-full h-full object-cover"
                                />
                                <div v-else class="text-gray-400">
                                    <svg class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                    </svg>
                                </div>
                            </div>

                            <!-- Details -->
                            <div>
                                <div class="flex items-center gap-2 flex-wrap">
                                    <h3 class="text-base sm:text-lg font-bold text-gray-900">
                                        {{ booking.court_name }}
                                    </h3>
                                    <span
                                        :class="getStatusBadgeClass(booking.status)"
                                        class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold"
                                    >
                                        {{ getStatusLabel(booking.status) }}
                                    </span>
                                </div>

                                <div class="mt-1.5 flex flex-wrap items-center gap-x-4 gap-y-1 text-xs sm:text-sm text-gray-600">
                                    <div class="flex items-center gap-1">
                                        <svg class="h-4 w-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                        </svg>
                                        <span>{{ booking.booking_date_formatted }}</span>
                                    </div>
                                    <div class="flex items-center gap-1 font-semibold text-indigo-700">
                                        <svg class="h-4 w-4 text-indigo-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                        <span>{{ booking.start_time }} - {{ booking.end_time }}</span>
                                    </div>
                                    <div class="text-gray-400 text-xs">
                                        Kode Booking: #{{ booking.id }}
                                    </div>
                                </div>

                                <p v-if="booking.notes" class="text-xs text-gray-500 mt-2 bg-gray-50 px-2.5 py-1.5 rounded-md border border-gray-100">
                                    Catatan: {{ booking.notes }}
                                </p>
                            </div>
                        </div>

                        <!-- Right pricing & actions -->
                        <div class="flex sm:flex-col items-center sm:items-end justify-between sm:justify-center gap-3 pt-4 sm:pt-0 border-t sm:border-t-0 border-gray-100">
                            <div>
                                <span class="text-xs text-gray-500 sm:text-right block">Total Tagihan:</span>
                                <span class="text-lg font-extrabold text-emerald-600">
                                    {{ formatPrice(booking.total_price) }}
                                </span>
                            </div>

                            <!-- Cancel button if eligible under policy -->
                            <div v-if="booking.can_cancel">
                                <button
                                    type="button"
                                    @click="openCancelModal(booking)"
                                    class="inline-flex items-center px-3 py-1.5 text-xs font-semibold text-rose-600 bg-rose-50 hover:bg-rose-100 border border-rose-200 rounded-lg transition"
                                >
                                    Batalkan Booking
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
                                            ? 'bg-indigo-600 text-white font-bold'
                                            : 'bg-white text-gray-700 hover:bg-gray-100 border border-gray-300',
                                        'px-3.5 py-1.5 text-xs rounded-md transition',
                                    ]"
                                />
                                <span
                                    v-else
                                    v-html="link.label"
                                    class="px-3.5 py-1.5 text-xs text-gray-400 bg-gray-50 border border-gray-200 rounded-md cursor-not-allowed"
                                />
                            </template>
                        </div>
                    </div>
                </div>

                <!-- Empty State -->
                <div v-else class="bg-white rounded-xl border border-gray-200 p-12 text-center">
                    <svg class="mx-auto h-16 w-16 text-gray-300 mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                    <h3 class="text-base font-bold text-gray-800">
                        Tidak ada riwayat booking
                    </h3>
                    <p class="text-xs text-gray-500 mt-1 max-w-sm mx-auto">
                        Anda belum memiliki jadwal booking dengan status ini. Silakan buat booking baru untuk memesan lapangan badminton.
                    </p>
                    <div class="mt-6">
                        <Link
                            :href="route('bookings.create')"
                            class="inline-flex items-center px-4 py-2 rounded-lg bg-indigo-600 text-white text-sm font-semibold hover:bg-indigo-500 shadow-sm transition"
                        >
                            Booking Lapangan Sekarang
                        </Link>
                    </div>
                </div>
            </div>
        </div>

        <!-- Cancellation Confirmation Modal -->
        <Modal :show="confirmingCancellation" @close="closeCancelModal">
            <div class="p-6">
                <div class="flex items-center gap-3 text-rose-600 mb-2">
                    <div class="p-2 rounded-full bg-rose-100">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                    </div>
                    <h2 class="text-lg font-bold text-gray-900">
                        Konfirmasi Pembatalan
                    </h2>
                </div>

                <p class="text-sm text-gray-600 mt-2">
                    Apakah Anda yakin ingin membatalkan booking <strong>#{{ bookingToCancel?.id }}</strong> di <strong>{{ bookingToCancel?.court_name }}</strong> pada tanggal <strong>{{ bookingToCancel?.booking_date_formatted }} ({{ bookingToCancel?.start_time }} - {{ bookingToCancel?.end_time }})</strong>?
                </p>

                <p class="text-xs text-gray-500 mt-2 bg-gray-50 p-2.5 rounded border border-gray-200">
                    Slot jam ini akan segera dibuka kembali untuk pelanggan lain.
                </p>

                <div class="mt-6 flex justify-end gap-3">
                    <SecondaryButton @click="closeCancelModal">
                        Batal
                    </SecondaryButton>
                    <DangerButton :disabled="cancelling" @click="submitCancel">
                        <span v-if="cancelling">Membatalkan...</span>
                        <span v-else>Ya, Batalkan Booking</span>
                    </DangerButton>
                </div>
            </div>
        </Modal>
    </AuthenticatedLayout>
</template>

