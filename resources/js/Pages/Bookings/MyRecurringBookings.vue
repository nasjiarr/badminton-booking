<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import DangerButton from '@/Components/DangerButton.vue';
import Modal from '@/Components/Modal.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';

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

const today = new Date().toISOString().split('T')[0];

const isSessionPast = (session) => {
    return session.booking_date < today;
};

const isSessionUpcoming = (session) => {
    return session.booking_date >= today && session.status !== 'cancelled';
};

const getSessionBadge = (session) => {
    if (session.status === 'cancelled') {
        return { label: 'Dibatalkan', color: 'bg-courtOrange/10 text-courtOrange border-courtOrange/30' };
    }
    if (session.status === 'confirmed' && isSessionPast(session)) {
        return { label: 'Selesai', color: 'bg-emerald-100 text-emerald-800 border-emerald-300' };
    }
    if (session.status === 'confirmed') {
        return { label: 'Terkonfirmasi', color: 'bg-volt/20 text-volt-deep border-volt/50' };
    }
    if (session.status === 'pending') {
        return { label: 'Pending', color: 'bg-amber-100 text-amber-800 border-amber-300' };
    }
    return { label: session.status, color: 'bg-courtSlate-100 text-courtSlate-600 border-courtSlate-200' };
};
</script>

<template>
    <Head title="Booking Rutin Saya" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <div class="flex items-center gap-2 mb-1">
                        <span class="court-badge-volt text-[10px] py-0.5"><span>Jadwal Rutin Mingguan</span></span>
                    </div>
                    <h2 class="text-2xl sm:text-3xl font-display font-black tracking-tight text-courtSlate-900 uppercase">
                        Booking Rutin Saya
                    </h2>
                </div>

                <!-- Navigation Switcher -->
                <div class="flex items-center gap-1.5 bg-courtSlate-100 p-1 rounded-xl border border-courtSlate-200">
                    <Link
                        :href="route('my-bookings.index')"
                        class="px-4 py-2 rounded-lg text-xs font-display font-bold uppercase tracking-wider text-courtSlate-500 hover:text-courtSlate-800 transition"
                    >
                        Booking Satuan
                    </Link>
                    <Link
                        :href="route('recurring-bookings.index')"
                        class="px-4 py-2 rounded-lg text-xs font-display font-black uppercase tracking-wider bg-arena-base text-volt shadow-sm"
                    >
                        🔁 Booking Rutin
                    </Link>
                </div>
            </div>
        </template>

        <div class="py-8">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

                <!-- Filter Tabs -->
                <div class="flex items-center gap-2 border-b-2 border-courtSlate-200 pb-4 overflow-x-auto">
                    <button
                        v-for="tab in filterTabs"
                        :key="tab.value"
                        @click="applyFilter(tab.value)"
                        class="px-4 py-2 rounded-xl text-xs font-display font-black uppercase tracking-wider transition whitespace-nowrap"
                        :class="filters.status === tab.value ? 'bg-arena-base text-volt shadow-sm' : 'bg-white text-courtSlate-500 hover:text-courtSlate-800 border border-courtSlate-200'"
                    >
                        {{ tab.label }}
                    </button>
                </div>

                <!-- Recurring Bookings List -->
                <div v-if="recurringBookings.length > 0" class="space-y-6">
                    <div
                        v-for="item in recurringBookings"
                        :key="item.id"
                        class="rounded-2xl border-2 bg-white overflow-hidden shadow-card-elevated transition-all"
                        :class="item.status === 'active' ? 'border-courtSlate-200' : 'border-courtSlate-200 opacity-75'"
                    >
                        <!-- Card Header -->
                        <div class="p-5 sm:p-6 flex flex-col lg:flex-row lg:items-center justify-between gap-5 border-b border-courtSlate-100">
                            <div class="flex items-start gap-4">
                                <!-- Court Image Thumbnail -->
                                <div class="w-14 h-14 rounded-xl bg-arena-card border border-arena-border flex items-center justify-center shrink-0 overflow-hidden">
                                    <img
                                        v-if="item.court.image_url"
                                        :src="item.court.image_url"
                                        :alt="item.court.name"
                                        class="w-full h-full object-cover"
                                    />
                                    <span v-else class="text-courtSlate-400 text-xl">🏸</span>
                                </div>
                                <div>
                                    <div class="flex flex-wrap items-center gap-2 mb-1">
                                        <span class="inline-flex items-center -skew-x-6 bg-arena-base text-volt px-2 py-0.5 rounded text-[10px] font-display font-black tracking-wider uppercase">
                                            <span class="skew-x-6">SETIAP {{ item.day_name.toUpperCase() }}</span>
                                        </span>
                                        <span
                                            v-if="item.status === 'active'"
                                            class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded text-[10px] font-display font-black uppercase tracking-wider bg-emerald-100 text-emerald-800 border border-emerald-300"
                                        >
                                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                            AKTIF
                                        </span>
                                        <span
                                            v-else
                                            class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded text-[10px] font-display font-black uppercase tracking-wider bg-courtOrange/10 text-courtOrange border border-courtOrange/30"
                                        >
                                            DIBATALKAN
                                        </span>

                                        <span
                                            v-if="item.is_all_paid"
                                            class="court-badge-volt text-[9px] py-0.5"
                                        >
                                            <span>✓ LUNAS</span>
                                        </span>
                                        <span
                                            v-else-if="item.pending_payment_id"
                                            class="court-badge-orange text-[9px] py-0.5 animate-pulse"
                                        >
                                            <span>MENUNGGU BAYAR</span>
                                        </span>
                                    </div>

                                    <h3 class="text-lg sm:text-xl font-display font-black text-courtSlate-900 uppercase tracking-tight">
                                        {{ item.court.name }}
                                    </h3>
                                    <p class="text-xs text-courtSlate-500 mt-0.5">
                                        Pukul <span class="font-display font-extrabold text-courtSlate-900">{{ item.start_time }} - {{ item.end_time }} WIB</span> &bull; 
                                        Periode: <span class="text-courtSlate-700 font-semibold">{{ item.start_date_formatted }} s/d {{ item.end_date_formatted }}</span>
                                    </p>
                                </div>
                            </div>

                            <!-- Right Stats & Action Buttons -->
                            <div class="flex flex-col sm:flex-row sm:items-center gap-4 lg:self-center">
                                <div class="text-left sm:text-right">
                                    <div class="text-[10px] font-display font-black uppercase tracking-widest text-courtSlate-500">
                                        Total Paket ({{ item.total_sessions_count }} Sesi)
                                    </div>
                                    <div class="athletic-number text-xl font-black text-courtSlate-900 tracking-tight">
                                        {{ formatPrice(item.total_price) }}
                                    </div>
                                    <div class="text-[11px] text-courtSlate-500 mt-0.5">
                                        <span class="font-semibold text-volt-deep">{{ item.upcoming_sessions_count }} mendatang</span> &bull; {{ item.completed_sessions_count }} selesai
                                    </div>
                                </div>

                                <div class="flex items-center gap-2">
                                    <!-- Pending Payment Button -->
                                    <Link
                                        v-if="item.pending_payment_id && item.status === 'active'"
                                        :href="route('payments.show', item.pending_payment_id)"
                                        class="inline-flex items-center gap-1.5 px-4 py-2.5 rounded-xl bg-volt text-arena-base font-display font-black text-xs uppercase tracking-wider shadow-volt-glow-sm hover:bg-volt-hover transition-all active:scale-[0.98] -skew-x-3 cursor-pointer"
                                    >
                                        <span class="inline-flex items-center gap-1.5 skew-x-3">
                                            <span>Bayar Paket</span>
                                        </span>
                                    </Link>

                                    <!-- Cancel Series Button -->
                                    <button
                                        v-if="item.status === 'active' && item.upcoming_sessions_count > 0"
                                        type="button"
                                        @click="openCancelModal(item)"
                                        class="px-4 py-2.5 rounded-xl text-xs font-display font-bold uppercase tracking-wider bg-courtOrange/10 hover:bg-courtOrange/20 text-courtOrange border border-courtOrange/30 transition"
                                    >
                                        Batalkan
                                    </button>

                                    <!-- Accordion Toggle Button -->
                                    <button
                                        type="button"
                                        @click="toggleExpand(item.id)"
                                        class="p-2.5 rounded-xl bg-courtSlate-100 hover:bg-courtSlate-200 text-courtSlate-600 border border-courtSlate-200 transition"
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

                        <!-- Accordion: Timeline Sessions Detail -->
                        <div v-if="expandedItems.includes(item.id)" class="p-5 sm:p-6 bg-courtSlate-50/50 border-t border-courtSlate-100">
                            <h4 class="text-[10px] font-display font-black uppercase tracking-widest text-courtSlate-500 mb-4 flex items-center gap-2">
                                <span>📅 Timeline Sesi Mingguan ({{ item.sessions.length }} Sesi)</span>
                            </h4>

                            <!-- Timeline View -->
                            <div class="relative">
                                <!-- Vertical timeline line -->
                                <div class="absolute left-[17px] top-2 bottom-2 w-0.5 bg-courtSlate-200"></div>

                                <div class="space-y-3">
                                    <div
                                        v-for="(session, sIdx) in item.sessions"
                                        :key="session.id"
                                        class="relative flex items-start gap-4 pl-1"
                                    >
                                        <!-- Timeline dot -->
                                        <div class="relative z-10 shrink-0 mt-1">
                                            <!-- Past session: filled neutral -->
                                            <div
                                                v-if="session.status === 'cancelled'"
                                                class="w-[14px] h-[14px] rounded-full bg-courtOrange/20 border-2 border-courtOrange flex items-center justify-center"
                                            >
                                                <svg class="w-2 h-2 text-courtOrange" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="3">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                                </svg>
                                            </div>
                                            <div
                                                v-else-if="session.status === 'confirmed' && isSessionPast(session)"
                                                class="w-[14px] h-[14px] rounded-full bg-emerald-500 border-2 border-emerald-600 flex items-center justify-center"
                                            >
                                                <svg class="w-2 h-2 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="3">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                                </svg>
                                            </div>
                                            <div
                                                v-else-if="session.status === 'confirmed'"
                                                class="w-[14px] h-[14px] rounded-full bg-volt border-2 border-volt-deep shadow-volt-glow-sm"
                                            ></div>
                                            <div
                                                v-else
                                                class="w-[14px] h-[14px] rounded-full bg-amber-300 border-2 border-amber-500"
                                            ></div>
                                        </div>

                                        <!-- Session content card -->
                                        <div
                                            class="flex-1 rounded-xl border p-3.5 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 transition"
                                            :class="[
                                                session.status === 'cancelled'
                                                    ? 'bg-courtOrange-light/50 border-courtOrange/20 opacity-60'
                                                    : isSessionPast(session)
                                                        ? 'bg-white border-courtSlate-200 opacity-70'
                                                        : 'bg-white border-courtSlate-200 hover:border-volt/50 hover:shadow-sm',
                                            ]"
                                        >
                                            <div>
                                                <div class="flex items-center gap-2 flex-wrap">
                                                    <span class="text-[10px] font-display font-black text-courtSlate-500 uppercase tracking-wider">
                                                        Minggu {{ sIdx + 1 }}
                                                    </span>
                                                    <span class="text-xs font-display font-extrabold text-courtSlate-900">
                                                        {{ session.booking_date_formatted }}
                                                    </span>
                                                    <span
                                                        v-if="isSessionPast(session) && session.status !== 'cancelled'"
                                                        class="text-[9px] font-display font-bold text-courtSlate-400 uppercase"
                                                    >
                                                        (Sudah lewat)
                                                    </span>
                                                </div>
                                                <div class="text-[11px] text-courtSlate-500 mt-0.5">
                                                    {{ session.start_time }} - {{ session.end_time }} WIB &bull; {{ formatPrice(session.total_price) }}
                                                </div>
                                            </div>

                                            <div class="flex items-center gap-2">
                                                <span
                                                    :class="[
                                                        'inline-flex items-center px-2 py-0.5 rounded text-[10px] font-display font-black uppercase tracking-wider border',
                                                        getSessionBadge(session).color
                                                    ]"
                                                >
                                                    {{ getSessionBadge(session).label }}
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Empty State -->
                <div v-else class="text-center py-16 rounded-2xl border-2 border-dashed border-courtSlate-300 bg-courtSlate-50 p-8">
                    <div class="w-16 h-16 mx-auto mb-4 rounded-2xl bg-courtSlate-200 flex items-center justify-center text-courtSlate-400 text-3xl">
                        🔁
                    </div>
                    <h3 class="text-lg font-display font-black uppercase tracking-tight text-courtSlate-900">
                        Belum Ada Booking Rutin
                    </h3>
                    <p class="text-xs sm:text-sm text-courtSlate-500 max-w-md mx-auto mt-1 mb-6">
                        Anda belum memiliki rangkaian jadwal booking rutin mingguan. Pilih slot lapangan dan aktifkan opsi "Jadikan Booking Rutin" saat memesan.
                    </p>
                    <Link
                        :href="route('bookings.create')"
                        class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-arena-base text-volt font-display font-black text-xs uppercase tracking-wider shadow-sm hover:bg-arena-surface hover:shadow-volt-glow-sm transition-all -skew-x-3 cursor-pointer"
                    >
                        <span class="inline-flex items-center gap-2 skew-x-3">
                            <span>🏸 Buat Booking Sekarang</span>
                        </span>
                    </Link>
                </div>

            </div>
        </div>

        <!-- Modal Konfirmasi Pembatalan Rangkaian Rutin -->
        <Modal :show="confirmingCancellation" @close="closeCancelModal">
            <div class="p-6 bg-white rounded-2xl">
                <div class="flex items-center gap-3 text-courtOrange mb-4">
                    <div class="w-10 h-10 rounded-xl bg-courtOrange/10 border-2 border-courtOrange/20 flex items-center justify-center shrink-0">
                        <svg class="w-5 h-5 text-courtOrange" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                    </div>
                    <h3 class="text-lg font-display font-black uppercase tracking-tight text-courtSlate-900">
                        Batalkan Rangkaian Booking Rutin?
                    </h3>
                </div>

                <p class="text-sm text-courtSlate-600 leading-relaxed">
                    Anda akan membatalkan rangkaian booking rutin untuk
                    <strong class="text-courtSlate-900">{{ itemToCancel?.court.name }}</strong> (Setiap {{ itemToCancel?.day_name }}).
                </p>

                <div class="mt-4 p-3.5 rounded-xl bg-courtOrange-light border border-courtOrange/20 text-xs text-courtOrange leading-relaxed">
                    ⚠️ <strong>Perhatian:</strong> Tindakan ini akan membatalkan seluruh <strong>{{ itemToCancel?.upcoming_sessions_count }} sesi mendatang</strong> yang belum lewat, dan slot lapangan tersebut akan langsung dibebaskan untuk dipesan pengguna lain.
                </div>

                <div class="mt-6 flex justify-end gap-3">
                    <SecondaryButton @click="closeCancelModal" :disabled="cancelling" class="rounded-xl border-2 border-courtSlate-200 text-courtSlate-700 hover:bg-courtSlate-100">
                        Kembali
                    </SecondaryButton>

                    <DangerButton @click="submitCancel" :disabled="cancelling" class="rounded-xl bg-courtOrange hover:bg-courtOrange-hover text-white">
                        <span v-if="cancelling">Membatalkan...</span>
                        <span v-else>Ya, Batalkan Rangkaian</span>
                    </DangerButton>
                </div>
            </div>
        </Modal>
    </AuthenticatedLayout>
</template>
