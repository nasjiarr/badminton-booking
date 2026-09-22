<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import DangerButton from '@/Components/DangerButton.vue';
import Modal from '@/Components/Modal.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    recurringBookings: Array,
    filters: Object,
});

const confirmingCancellation = ref(false);
const itemToCancel = ref(null);
const cancelling = ref(false);
const expandedItems = ref([]);

const filterTabs = [
    { label: 'Semua', value: 'all' },
    { label: 'Aktif', value: 'active' },
    { label: 'Dibatalkan', value: 'cancelled' },
];

const applyFilter = (filterValue) => {
    router.get(
        route('recurring-bookings.index'),
        { status: filterValue },
        { preserveState: true, preserveScroll: true }
    );
};

const toggleExpand = (id) => {
    if (expandedItems.value.includes(id)) {
        expandedItems.value = expandedItems.value.filter(i => i !== id);
    } else {
        expandedItems.value.push(id);
    }
};

const openCancelModal = (item) => {
    itemToCancel.value = item;
    confirmingCancellation.value = true;
};

const closeCancelModal = () => {
    confirmingCancellation.value = false;
    itemToCancel.value = null;
};

const submitCancel = () => {
    if (!itemToCancel.value) return;

    cancelling.value = true;
    router.patch(
        route('recurring-bookings.cancel', itemToCancel.value.id),
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
</script>

<template>
    <Head title="Booking Rutin Saya" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <div class="flex items-center gap-2 mb-1">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-black tracking-wider uppercase bg-indigo-500/20 text-indigo-400 border border-indigo-500/30">
                            Jadwal Rutin Mingguan
                        </span>
                    </div>
                    <h1 class="text-2xl sm:text-3xl font-black tracking-tight text-white uppercase italic">
                        Booking Rutin Saya
                    </h1>
                </div>

                <!-- Navigation Switcher -->
                <div class="flex items-center gap-2 bg-slate-900 p-1.5 rounded-2xl border border-slate-800">
                    <Link
                        :href="route('my-bookings.index')"
                        class="px-4 py-2 rounded-xl text-xs font-bold text-slate-400 hover:text-white transition"
                    >
                        Booking Satuan
                    </Link>
                    <Link
                        :href="route('recurring-bookings.index')"
                        class="px-4 py-2 rounded-xl text-xs font-black bg-indigo-600 text-white shadow-md shadow-indigo-600/30"
                    >
                        🔁 Booking Rutin
                    </Link>
                </div>
            </div>
        </template>

        <div class="py-8 bg-slate-950 min-h-screen">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

                <!-- Filter Tabs -->
                <div class="flex items-center gap-2 border-b border-slate-800 pb-4 overflow-x-auto">
                    <button
                        v-for="tab in filterTabs"
                        :key="tab.value"
                        @click="applyFilter(tab.value)"
                        class="px-4 py-2 rounded-xl text-xs font-bold transition whitespace-nowrap"
                        :class="filters.status === tab.value ? 'bg-indigo-600 text-white shadow-md shadow-indigo-600/20' : 'bg-slate-900 text-slate-400 hover:text-white border border-slate-800'"
                    >
                        {{ tab.label }}
                    </button>
                </div>

                <!-- Recurring Bookings List -->
                <div v-if="recurringBookings.length > 0" class="space-y-6">
                    <div
                        v-for="item in recurringBookings"
                        :key="item.id"
                        class="bg-slate-900/90 border border-slate-800 rounded-3xl overflow-hidden shadow-xl backdrop-blur-xl transition hover:border-slate-700"
                    >
                        <!-- Card Header -->
                        <div class="p-6 sm:p-8 flex flex-col lg:flex-row lg:items-center justify-between gap-6 border-b border-slate-800/80">
                            <div class="flex items-start gap-4">
                                <div class="w-16 h-16 rounded-2xl bg-slate-950 border border-slate-800 flex items-center justify-center shrink-0 text-indigo-400 text-2xl font-black">
                                    🔁
                                </div>
                                <div>
                                    <div class="flex flex-wrap items-center gap-2.5 mb-1.5">
                                        <span class="text-xs font-black uppercase tracking-wider px-3 py-1 rounded-full bg-indigo-500/20 text-indigo-400 border border-indigo-500/30">
                                            SETIAP {{ item.day_name.toUpperCase() }}
                                        </span>
                                        <span
                                            v-if="item.status === 'active'"
                                            class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-black uppercase tracking-wider bg-emerald-500/20 text-emerald-400 border border-emerald-500/30"
                                        >
                                            <span class="w-2 h-2 rounded-full bg-emerald-400"></span>
                                            AKTIF
                                        </span>
                                        <span
                                            v-else
                                            class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-black uppercase tracking-wider bg-red-500/20 text-red-400 border border-red-500/30"
                                        >
                                            DIBATALKAN
                                        </span>

                                        <span
                                            v-if="item.is_all_paid"
                                            class="text-xs font-bold text-emerald-400 bg-emerald-950/60 px-2.5 py-1 rounded-lg border border-emerald-800/50"
                                        >
                                            ✓ LUNAS
                                        </span>
                                        <span
                                            v-else-if="item.pending_payment_id"
                                            class="text-xs font-bold text-amber-400 bg-amber-950/60 px-2.5 py-1 rounded-lg border border-amber-800/50 animate-pulse"
                                        >
                                            MENUNGGU PEMBAYARAN
                                        </span>
                                    </div>

                                    <h3 class="text-xl sm:text-2xl font-black text-white uppercase italic tracking-tight">
                                        {{ item.court.name }}
                                    </h3>
                                    <p class="text-xs sm:text-sm text-slate-400 mt-1">
                                        Pukul <span class="text-white font-bold">{{ item.start_time }} - {{ item.end_time }} WIB</span> &bull; 
                                        Periode: <span class="text-slate-300">{{ item.start_date_formatted }} s/d {{ item.end_date_formatted }}</span>
                                    </p>
                                </div>
                            </div>

                            <!-- Right Stats & Action Buttons -->
                            <div class="flex flex-col sm:flex-row sm:items-center gap-4 lg:self-center">
                                <div class="text-left sm:text-right">
                                    <div class="text-xs font-bold uppercase tracking-wider text-slate-400">
                                        Total Paket ({{ item.total_sessions_count }} Sesi)
                                    </div>
                                    <div class="text-2xl font-black text-emerald-400 tracking-tight">
                                        {{ formatPrice(item.total_price) }}
                                    </div>
                                    <div class="text-[11px] text-slate-400">
                                        {{ item.upcoming_sessions_count }} sesi mendatang &bull; {{ item.completed_sessions_count }} selesai
                                    </div>
                                </div>

                                <div class="flex items-center gap-2">
                                    <!-- Pending Payment Button -->
                                    <Link
                                        v-if="item.pending_payment_id && item.status === 'active'"
                                        :href="route('payments.show', item.pending_payment_id)"
                                        class="px-5 py-2.5 rounded-xl text-xs font-black uppercase tracking-wider bg-emerald-400 hover:bg-emerald-300 text-slate-950 shadow-md shadow-emerald-500/20 transition"
                                    >
                                        Bayar Paket
                                    </Link>

                                    <!-- Cancel Series Button -->
                                    <button
                                        v-if="item.status === 'active' && item.upcoming_sessions_count > 0"
                                        type="button"
                                        @click="openCancelModal(item)"
                                        class="px-4 py-2.5 rounded-xl text-xs font-bold uppercase tracking-wider bg-red-500/10 hover:bg-red-500/20 text-red-400 border border-red-500/30 transition"
                                    >
                                        Batalkan Rangkaian
                                    </button>

                                    <!-- Accordion Toggle Button -->
                                    <button
                                        type="button"
                                        @click="toggleExpand(item.id)"
                                        class="p-2.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 border border-slate-700 transition"
                                        :title="expandedItems.includes(item.id) ? 'Sembunyikan Sesi' : 'Lihat Rincian Sesi'"
                                    >
                                        <svg
                                            class="w-4 h-4 transition-transform duration-200"
                                            :class="{ 'rotate-180': expandedItems.includes(item.id) }"
                                            fill="none"
                                            stroke="currentColor"
                                            viewBox="0 0 24 24"
                                        >
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                        </svg>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Accordion Sesi Detail -->
                        <div v-if="expandedItems.includes(item.id)" class="p-6 sm:p-8 bg-slate-950/60 border-t border-slate-800">
                            <h4 class="text-xs font-black uppercase tracking-wider text-slate-300 mb-4 flex items-center gap-2">
                                <span>📅 Rincian Sesi Mingguan ({{ item.sessions.length }} Sesi Terjadwal)</span>
                            </h4>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                <div
                                    v-for="(session, sIdx) in item.sessions"
                                    :key="session.id"
                                    class="p-4 rounded-2xl bg-slate-900 border border-slate-800/80 flex items-center justify-between gap-3"
                                >
                                    <div>
                                        <div class="flex items-center gap-2">
                                            <span class="text-xs font-extrabold text-indigo-400">Minggu {{ sIdx + 1 }}:</span>
                                            <span class="text-sm font-black text-white">{{ session.booking_date_formatted }}</span>
                                        </div>
                                        <div class="text-xs text-slate-400 mt-0.5">
                                            {{ session.start_time }} - {{ session.end_time }} WIB &bull; {{ formatPrice(session.total_price) }}
                                        </div>
                                    </div>

                                    <div class="text-right">
                                        <span
                                            v-if="session.status === 'confirmed'"
                                            class="inline-block px-2.5 py-0.5 rounded-full text-[10px] font-black uppercase bg-emerald-500/20 text-emerald-400 border border-emerald-500/30"
                                        >
                                            Terkonfirmasi
                                        </span>
                                        <span
                                            v-else-if="session.status === 'pending'"
                                            class="inline-block px-2.5 py-0.5 rounded-full text-[10px] font-black uppercase bg-amber-500/20 text-amber-400 border border-amber-500/30"
                                        >
                                            Pending
                                        </span>
                                        <span
                                            v-else
                                            class="inline-block px-2.5 py-0.5 rounded-full text-[10px] font-black uppercase bg-red-500/20 text-red-400 border border-red-500/30"
                                        >
                                            Dibatalkan
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Empty State -->
                <div v-else class="text-center py-16 bg-slate-900/60 border border-slate-800 rounded-3xl p-8">
                    <div class="w-16 h-16 mx-auto mb-4 rounded-2xl bg-slate-800/60 flex items-center justify-center text-slate-500 text-3xl">
                        🔁
                    </div>
                    <h3 class="text-lg font-black uppercase tracking-tight text-white">
                        Belum Ada Booking Rutin
                    </h3>
                    <p class="text-xs sm:text-sm text-slate-400 max-w-md mx-auto mt-1 mb-6">
                        Anda belum memiliki rangkaian jadwal booking rutin mingguan. Pilih slot lapangan dan aktifkan opsi "Jadikan Booking Rutin" saat memesan.
                    </p>
                    <Link
                        :href="route('bookings.create')"
                        class="inline-flex items-center gap-2 px-6 py-3 rounded-2xl text-xs font-black uppercase tracking-wider text-slate-950 bg-emerald-400 hover:bg-emerald-300 transition shadow-lg shadow-emerald-500/20"
                    >
                        Buat Booking Sekarang
                    </Link>
                </div>

            </div>
        </div>

        <!-- Modal Konfirmasi Pembatalan Rangkaian Rutin -->
        <Modal :show="confirmingCancellation" @close="closeCancelModal">
            <div class="p-6 bg-slate-900 text-white rounded-2xl border border-slate-800">
                <div class="flex items-center gap-3 text-red-400 mb-3">
                    <div class="w-10 h-10 rounded-xl bg-red-500/10 border border-red-500/20 flex items-center justify-center">
                        <svg class="w-5 h-5 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                    </div>
                    <h3 class="text-lg font-black uppercase italic tracking-tight">
                        Batalkan Rangkaian Booking Rutin?
                    </h3>
                </div>

                <p class="text-sm text-slate-300 leading-relaxed">
                    Anda akan membatalkan rangkaian booking rutin untuk
                    <strong class="text-white">{{ itemToCancel?.court.name }}</strong> (Setiap {{ itemToCancel?.day_name }}).
                </p>

                <div class="mt-4 p-3 rounded-xl bg-red-950/40 border border-red-800/40 text-xs text-red-300">
                    ⚠️ <strong>Perhatian:</strong> Tindakan ini akan membatalkan seluruh <strong>{{ itemToCancel?.upcoming_sessions_count }} sesi mendatang</strong> yang belum lewat, dan slot lapangan tersebut akan langsung dibebaskan untuk dipesan pengguna lain.
                </div>

                <div class="mt-6 flex justify-end gap-3">
                    <SecondaryButton @click="closeCancelModal" :disabled="cancelling" class="bg-slate-800 text-slate-300 hover:bg-slate-700">
                        Kembali
                    </SecondaryButton>

                    <DangerButton @click="submitCancel" :disabled="cancelling" class="bg-red-600 hover:bg-red-500">
                        <span v-if="cancelling">Membatalkan...</span>
                        <span v-else>Ya, Batalkan Rangkaian</span>
                    </DangerButton>
                </div>
            </div>
        </Modal>
    </AuthenticatedLayout>
</template>

