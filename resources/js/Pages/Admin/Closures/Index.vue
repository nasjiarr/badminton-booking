<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import EmptyState from '@/Components/EmptyState.vue';
import Modal from '@/Components/Modal.vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';

const props = defineProps({
    closures: {
        type: Array,
        default: () => [],
    },
    courts: {
        type: Array,
        default: () => [],
    },
    summary: {
        type: Object,
        default: () => ({
            total_closures: 0,
            active_count: 0,
            upcoming_count: 0,
            tournaments_count: 0,
        }),
    },
});

const isModalOpen = ref(false);
const isDeleteModalOpen = ref(false);
const closureToDelete = ref(null);
const activeTab = ref('active_upcoming'); // 'all', 'active_upcoming', 'past'

const isFullDay = ref(true);
const applyToAllCourts = ref(true);

const form = useForm({
    name: '',
    type: 'tournament',
    court_id: null,
    start_date: new Date().toISOString().split('T')[0],
    end_date: new Date().toISOString().split('T')[0],
    start_time: '06:00',
    end_time: '22:00',
    notes: '',
});

const openCreateModal = () => {
    form.reset();
    form.clearErrors();
    form.start_date = new Date().toISOString().split('T')[0];
    form.end_date = new Date().toISOString().split('T')[0];
    form.start_time = '06:00';
    form.end_time = '22:00';
    isFullDay.value = true;
    applyToAllCourts.value = true;
    form.court_id = null;
    isModalOpen.value = true;
};

const closeModal = () => {
    isModalOpen.value = false;
    form.reset();
    form.clearErrors();
};

const handleFullDayToggle = () => {
    if (isFullDay.value) {
        form.start_time = '06:00';
        form.end_time = '22:00';
    }
};

const handleCourtToggle = () => {
    if (applyToAllCourts.value) {
        form.court_id = null;
    } else if (props.courts.length > 0) {
        form.court_id = props.courts[0].id;
    }
};

const submitForm = () => {
    if (isFullDay.value) {
        form.start_time = '06:00';
        form.end_time = '22:00';
    }

    if (applyToAllCourts.value) {
        form.court_id = null;
    }

    form.post(route('admin.closures.store'), {
        onSuccess: () => {
            closeModal();
        },
    });
};

const confirmDelete = (closure) => {
    closureToDelete.value = closure;
    isDeleteModalOpen.value = true;
};

const closeDeleteModal = () => {
    closureToDelete.value = null;
    isDeleteModalOpen.value = false;
};

const deleteClosure = () => {
    if (!closureToDelete.value) return;

    router.delete(route('admin.closures.destroy', closureToDelete.value.id), {
        onSuccess: () => {
            closeDeleteModal();
        },
    });
};

// Filtered closures by active tab
const filteredClosures = computed(() => {
    if (activeTab.value === 'active_upcoming') {
        return props.closures.filter((c) => c.status === 'active' || c.status === 'upcoming');
    }
    if (activeTab.value === 'past') {
        return props.closures.filter((c) => c.status === 'past');
    }
    return props.closures;
});

const formatDate = (dateStr) => {
    if (!dateStr) return '-';
    try {
        const cleanStr = String(dateStr).split('T')[0];
        const parts = cleanStr.split('-');
        if (parts.length === 3) {
            const d = new Date(Number(parts[0]), Number(parts[1]) - 1, Number(parts[2]));
            return new Intl.DateTimeFormat('id-ID', {
                day: 'numeric',
                month: 'short',
                year: 'numeric',
            }).format(d);
        }
    } catch {
        // fallback
    }
    return dateStr;
};

const getTypeIcon = (type) => {
    switch (type) {
        case 'tournament':
            return '🏆';
        case 'maintenance':
            return '🛠️';
        case 'holiday':
            return '🏖️';
        default:
            return '📅';
    }
};
</script>

<template>
    <Head title="Jadwal Penutupan & Turnamen — Admin Smash Arena" />

    <AdminLayout>
        <div class="space-y-8 p-4 sm:p-6 lg:p-8 max-w-7xl mx-auto">
            
            <!-- Page Header -->
            <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4">
                <div>
                    <div class="flex items-center gap-2 mb-2">
                        <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-display font-black tracking-wider uppercase bg-volt/20 text-arena-base border border-volt/40 -skew-x-6">
                            <span class="transform skew-x-6">KONTROL OPERASIONAL</span>
                        </span>
                        <span class="text-xs text-courtSlate-500 font-semibold">Special Events & Closures</span>
                    </div>
                    <h1 class="font-display font-black text-2xl sm:text-3xl lg:text-4xl text-courtSlate-900 tracking-tight uppercase">
                        Jadwal Penutupan & Turnamen
                    </h1>
                    <p class="mt-1.5 text-xs sm:text-sm text-courtSlate-600 max-w-2xl">
                        Kunci jadwal lapangan untuk turnamen resmi, pemeliharaan rutin karpet vinil, atau hari libur agar slot tidak dapat dipesan oleh member umum.
                    </p>
                </div>

                <button
                    type="button"
                    @click="openCreateModal"
                    class="inline-flex items-center justify-center gap-2 rounded-xl bg-volt px-5 py-3 text-xs sm:text-sm font-display font-black uppercase tracking-wider text-volt-contrast shadow-sm hover:bg-volt-hover hover:shadow-volt-glow-sm transition active:scale-[0.98] -skew-x-3 shrink-0 cursor-pointer"
                >
                    <span class="inline-flex items-center gap-2 skew-x-3">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                        </svg>
                        <span>Tambah Jadwal Turnamen / Tutup</span>
                    </span>
                </button>
            </div>

            <!-- Summary KPI Cards -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-5">
                <!-- Card 1: Active Closures -->
                <div class="bg-white rounded-2xl border-2 border-courtSlate-200 p-5 shadow-xs flex items-center justify-between">
                    <div>
                        <span class="text-[10px] font-display font-black uppercase tracking-widest text-courtSlate-400 block mb-1">
                            Sedang Berlangsung Hari Ini
                        </span>
                        <div class="flex items-baseline gap-2">
                            <span class="font-display font-black text-3xl text-courtOrange tracking-tight">
                                {{ props.summary.active_count }}
                            </span>
                            <span class="text-xs font-display font-bold text-courtSlate-500 uppercase">Jadwal</span>
                        </div>
                    </div>
                    <div class="w-11 h-11 rounded-xl bg-courtOrange-light border border-courtOrange/30 flex items-center justify-center text-xl text-courtOrange">
                        ⚡
                    </div>
                </div>

                <!-- Card 2: Upcoming Tournaments -->
                <div class="bg-white rounded-2xl border-2 border-courtSlate-200 p-5 shadow-xs flex items-center justify-between">
                    <div>
                        <span class="text-[10px] font-display font-black uppercase tracking-widest text-courtSlate-400 block mb-1">
                            Turnamen Terjadwal
                        </span>
                        <div class="flex items-baseline gap-2">
                            <span class="font-display font-black text-3xl text-courtSlate-900 tracking-tight">
                                {{ props.summary.tournaments_count }}
                            </span>
                            <span class="text-xs font-display font-bold text-courtSlate-500 uppercase">Event</span>
                        </div>
                    </div>
                    <div class="w-11 h-11 rounded-xl bg-amber-50 border border-amber-200 flex items-center justify-center text-xl text-amber-600">
                        🏆
                    </div>
                </div>

                <!-- Card 3: Total Upcoming / Scheduled -->
                <div class="bg-white rounded-2xl border-2 border-courtSlate-200 p-5 shadow-xs flex items-center justify-between">
                    <div>
                        <span class="text-[10px] font-display font-black uppercase tracking-widest text-courtSlate-400 block mb-1">
                            Total Jadwal Mendatang
                        </span>
                        <div class="flex items-baseline gap-2">
                            <span class="font-display font-black text-3xl text-emerald-700 tracking-tight">
                                {{ props.summary.upcoming_count }}
                            </span>
                            <span class="text-xs font-display font-bold text-courtSlate-500 uppercase">Jadwal</span>
                        </div>
                    </div>
                    <div class="w-11 h-11 rounded-xl bg-emerald-50 border border-emerald-200 flex items-center justify-center text-xl text-emerald-600">
                        📅
                    </div>
                </div>
            </div>

            <!-- Tab Navigation & Table Container -->
            <div class="bg-white rounded-2xl border-2 border-courtSlate-200 shadow-card-elevated overflow-hidden">
                
                <!-- Table Header Bar & Filter Tabs -->
                <div class="p-5 sm:p-6 border-b-2 border-courtSlate-200 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                    <div>
                        <h2 class="font-display font-black text-lg text-courtSlate-900 uppercase tracking-tight flex items-center gap-2">
                            <span>🛡️</span>
                            <span>Daftar Agenda Penutupan Lapangan</span>
                        </h2>
                        <p class="text-xs text-courtSlate-500 mt-0.5">
                            Menampilkan seluruh jadwal turnamen, perawatan, dan penutupan khusus gelanggang.
                        </p>
                    </div>

                    <!-- Filter Tabs -->
                    <div class="inline-flex rounded-xl border-2 border-courtSlate-200 bg-courtSlate-100/70 p-1 gap-1 self-start md:self-auto">
                        <button
                            type="button"
                            @click="activeTab = 'active_upcoming'"
                            :class="[
                                'px-3 py-1.5 text-xs font-display uppercase tracking-wider rounded-lg transition-all cursor-pointer',
                                activeTab === 'active_upcoming'
                                    ? 'bg-arena-base text-volt font-black shadow-xs'
                                    : 'text-courtSlate-600 hover:text-courtSlate-900 hover:bg-white font-bold',
                            ]"
                        >
                            Aktif & Mendatang ({{ props.summary.active_count + props.summary.upcoming_count }})
                        </button>
                        <button
                            type="button"
                            @click="activeTab = 'all'"
                            :class="[
                                'px-3 py-1.5 text-xs font-display uppercase tracking-wider rounded-lg transition-all cursor-pointer',
                                activeTab === 'all'
                                    ? 'bg-arena-base text-volt font-black shadow-xs'
                                    : 'text-courtSlate-600 hover:text-courtSlate-900 hover:bg-white font-bold',
                            ]"
                        >
                            Semua ({{ props.summary.total_closures }})
                        </button>
                        <button
                            type="button"
                            @click="activeTab = 'past'"
                            :class="[
                                'px-3 py-1.5 text-xs font-display uppercase tracking-wider rounded-lg transition-all cursor-pointer',
                                activeTab === 'past'
                                    ? 'bg-arena-base text-volt font-black shadow-xs'
                                    : 'text-courtSlate-600 hover:text-courtSlate-900 hover:bg-white font-bold',
                            ]"
                        >
                            Riwayat / Selesai
                        </button>
                    </div>
                </div>

                <!-- Table Content -->
                <div v-if="filteredClosures.length > 0" class="overflow-x-auto">
                    <table class="w-full text-left text-xs text-courtSlate-600 min-w-[800px]">
                        <thead class="bg-arena-base text-[11px] font-display font-black uppercase tracking-wider text-volt/90 border-b border-arena-border">
                            <tr>
                                <th scope="col" class="px-5 py-3.5">Kegiatan / Event</th>
                                <th scope="col" class="px-5 py-3.5">Cakupan Lapangan</th>
                                <th scope="col" class="px-5 py-3.5">Tanggal Pelaksanaan</th>
                                <th scope="col" class="px-5 py-3.5">Alokasi Jam</th>
                                <th scope="col" class="px-5 py-3.5 text-center">Status</th>
                                <th scope="col" class="px-5 py-3.5">Catatan</th>
                                <th scope="col" class="px-5 py-3.5 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-courtSlate-100 font-medium">
                            <tr
                                v-for="closure in filteredClosures"
                                :key="closure.id"
                                class="hover:bg-courtSlate-50/80 transition-colors"
                            >
                                <!-- Nama & Tipe -->
                                <td class="px-5 py-4">
                                    <div class="flex items-start gap-2.5">
                                        <div class="w-8 h-8 rounded-lg bg-courtSlate-100 border border-courtSlate-200 flex items-center justify-center text-base shrink-0 mt-0.5">
                                            {{ getTypeIcon(closure.type) }}
                                        </div>
                                        <div>
                                            <div class="font-display font-black text-sm text-courtSlate-900 uppercase leading-tight">
                                                {{ closure.name }}
                                            </div>
                                            <span
                                                :class="[
                                                    'inline-flex items-center px-2 py-0.5 rounded text-[10px] font-display font-bold uppercase mt-1 border',
                                                    closure.type === 'tournament'
                                                        ? 'bg-amber-50 text-amber-800 border-amber-300'
                                                        : closure.type === 'maintenance'
                                                        ? 'bg-blue-50 text-blue-800 border-blue-300'
                                                        : 'bg-courtSlate-100 text-courtSlate-700 border-courtSlate-300',
                                                ]"
                                            >
                                                {{ closure.type_label }}
                                            </span>
                                        </div>
                                    </div>
                                </td>

                                <!-- Lapangan -->
                                <td class="px-5 py-4 whitespace-nowrap">
                                    <span
                                        :class="[
                                            'inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-display font-black border',
                                            closure.court_id === null
                                                ? 'bg-arena-base text-volt border-arena-border'
                                                : 'bg-courtSlate-100 text-courtSlate-800 border-courtSlate-200',
                                        ]"
                                    >
                                        <span>🏸</span>
                                        <span>{{ closure.court_name }}</span>
                                    </span>
                                </td>

                                <!-- Tanggal -->
                                <td class="px-5 py-4 whitespace-nowrap">
                                    <div class="text-courtSlate-900 font-bold">
                                        <template v-if="closure.start_date === closure.end_date">
                                            {{ formatDate(closure.start_date) }}
                                        </template>
                                        <template v-else>
                                            {{ formatDate(closure.start_date) }} – {{ formatDate(closure.end_date) }}
                                        </template>
                                    </div>
                                    <div class="text-[11px] text-courtSlate-400 font-semibold mt-0.5">
                                        Durasi: {{ closure.days_count }} Hari
                                    </div>
                                </td>

                                <!-- Jam -->
                                <td class="px-5 py-4 whitespace-nowrap">
                                    <div class="flex items-center gap-1 font-mono text-xs font-semibold text-courtSlate-800">
                                        <svg class="w-3.5 h-3.5 text-courtSlate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                        <span>{{ closure.start_time }} – {{ closure.end_time }}</span>
                                    </div>
                                    <span v-if="closure.is_full_day" class="text-[10px] text-courtSlate-400 font-medium block mt-0.5">
                                        (Seharian Penuh)
                                    </span>
                                </td>

                                <!-- Status -->
                                <td class="px-5 py-4 whitespace-nowrap text-center">
                                    <span
                                        v-if="closure.status === 'active'"
                                        class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[10px] font-display font-black uppercase tracking-wider bg-courtOrange/15 text-courtOrange border border-courtOrange/30 animate-pulse"
                                    >
                                        <span class="w-1.5 h-1.5 rounded-full bg-courtOrange"></span>
                                        Berlangsung
                                    </span>
                                    <span
                                        v-else-if="closure.status === 'upcoming'"
                                        class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[10px] font-display font-bold uppercase tracking-wider bg-emerald-50 text-emerald-700 border border-emerald-300"
                                    >
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                        Mendatang
                                    </span>
                                    <span
                                        v-else
                                        class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-display font-bold uppercase tracking-wider bg-courtSlate-100 text-courtSlate-500 border border-courtSlate-200"
                                    >
                                        Selesai
                                    </span>
                                </td>

                                <!-- Catatan -->
                                <td class="px-5 py-4 max-w-[200px] truncate text-courtSlate-500 text-xs">
                                    {{ closure.notes || '-' }}
                                </td>

                                <!-- Aksi -->
                                <td class="px-5 py-4 whitespace-nowrap text-right">
                                    <button
                                        type="button"
                                        @click="confirmDelete(closure)"
                                        class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg border border-rose-200 text-xs font-display font-bold uppercase text-rose-700 hover:bg-rose-50 hover:border-rose-300 transition cursor-pointer"
                                        title="Hapus jadwal penutupan ini"
                                    >
                                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                        </svg>
                                        <span>Hapus</span>
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Empty State -->
                <div v-else class="p-8">
                    <EmptyState
                        icon="🏆"
                        title="Tidak Ada Jadwal Penutupan"
                        description="Belum ada jadwal turnamen atau penutupan lapangan pada kategori ini. Seluruh lapangan beroperasi normal sesuai jadwal reguler."
                    >
                        <button
                            type="button"
                            @click="openCreateModal"
                            class="mt-4 px-6 py-2.5 bg-arena-base text-volt font-display font-black text-xs uppercase tracking-wider rounded-xl shadow-sm hover:bg-arena-surface hover:shadow-volt-glow-sm -skew-x-3 transition-all duration-200 inline-flex items-center justify-center cursor-pointer"
                        >
                            <span class="skew-x-3">+ Tambah Jadwal Turnamen / Tutup</span>
                        </button>
                    </EmptyState>
                </div>
            </div>

            <!-- MODAL: TAMBAH JADWAL PENUTUPAN / TURNAMEN -->
            <Modal :show="isModalOpen" @close="closeModal" max-width="lg">
                <div class="p-6">
                    <div class="flex items-center justify-between pb-4 border-b border-courtSlate-200">
                        <div class="flex items-center gap-2.5">
                            <span class="w-8 h-8 rounded-lg bg-amber-100 border border-amber-200 flex items-center justify-center text-lg">
                                🏆
                            </span>
                            <div>
                                <h3 class="font-display font-black text-xl text-courtSlate-900 uppercase tracking-tight">
                                    Tambah Jadwal Turnamen / Penutupan
                                </h3>
                                <p class="text-xs text-courtSlate-500">
                                    Kunci jadwal agar slot lapangan tidak dapat dibooking pelanggan.
                                </p>
                            </div>
                        </div>
                        <button
                            type="button"
                            @click="closeModal"
                            class="text-courtSlate-400 hover:text-courtSlate-600 p-1 rounded-lg"
                        >
                            &times;
                        </button>
                    </div>

                    <form @submit.prevent="submitForm" class="space-y-4.5 mt-5">
                        <!-- Nama Kegiatan -->
                        <div>
                            <label class="block text-xs font-display font-black uppercase tracking-wider text-courtSlate-700 mb-1">
                                Nama Kegiatan / Turnamen <span class="text-rose-500">*</span>
                            </label>
                            <input
                                type="text"
                                v-model="form.name"
                                required
                                placeholder="Contoh: Kejuaraan Kota Smash Arena Cup 2026"
                                class="w-full text-xs rounded-xl border-2 border-courtSlate-200 bg-courtSlate-50 p-2.5 focus:border-volt focus:ring-2 focus:ring-volt/30 font-semibold text-courtSlate-900"
                            />
                            <div v-if="form.errors.name" class="text-rose-600 text-[11px] mt-1 font-semibold">
                                {{ form.errors.name }}
                            </div>
                        </div>

                        <!-- Tipe Penutupan -->
                        <div>
                            <label class="block text-xs font-display font-black uppercase tracking-wider text-courtSlate-700 mb-1.5">
                                Jenis Kegiatan
                            </label>
                            <div class="grid grid-cols-2 gap-2">
                                <label
                                    :class="[
                                        'flex items-center gap-2 p-2.5 rounded-xl border-2 cursor-pointer transition-all text-xs font-bold',
                                        form.type === 'tournament'
                                            ? 'border-amber-400 bg-amber-50 text-amber-900 shadow-xs'
                                            : 'border-courtSlate-200 bg-courtSlate-50 text-courtSlate-700 hover:bg-white',
                                    ]"
                                >
                                    <input type="radio" value="tournament" v-model="form.type" class="sr-only" />
                                    <span>🏆</span>
                                    <span>Turnamen Resmi</span>
                                </label>
                                <label
                                    :class="[
                                        'flex items-center gap-2 p-2.5 rounded-xl border-2 cursor-pointer transition-all text-xs font-bold',
                                        form.type === 'maintenance'
                                            ? 'border-blue-400 bg-blue-50 text-blue-900 shadow-xs'
                                            : 'border-courtSlate-200 bg-courtSlate-50 text-courtSlate-700 hover:bg-white',
                                    ]"
                                >
                                    <input type="radio" value="maintenance" v-model="form.type" class="sr-only" />
                                    <span>🛠️</span>
                                    <span>Perawatan Lapangan</span>
                                </label>
                                <label
                                    :class="[
                                        'flex items-center gap-2 p-2.5 rounded-xl border-2 cursor-pointer transition-all text-xs font-bold',
                                        form.type === 'holiday'
                                            ? 'border-courtSlate-400 bg-courtSlate-100 text-courtSlate-900 shadow-xs'
                                            : 'border-courtSlate-200 bg-courtSlate-50 text-courtSlate-700 hover:bg-white',
                                    ]"
                                >
                                    <input type="radio" value="holiday" v-model="form.type" class="sr-only" />
                                    <span>🏖️</span>
                                    <span>Libur Gelanggang</span>
                                </label>
                                <label
                                    :class="[
                                        'flex items-center gap-2 p-2.5 rounded-xl border-2 cursor-pointer transition-all text-xs font-bold',
                                        form.type === 'special_event'
                                            ? 'border-volt-deep bg-emerald-50 text-emerald-900 shadow-xs'
                                            : 'border-courtSlate-200 bg-courtSlate-50 text-courtSlate-700 hover:bg-white',
                                    ]"
                                >
                                    <input type="radio" value="special_event" v-model="form.type" class="sr-only" />
                                    <span>⭐</span>
                                    <span>Acara Khusus Lain</span>
                                </label>
                            </div>
                        </div>

                        <!-- Cakupan Lapangan -->
                        <div class="p-3.5 bg-courtSlate-50 rounded-xl border-2 border-courtSlate-200">
                            <label class="flex items-center gap-2 cursor-pointer mb-2">
                                <input
                                    type="checkbox"
                                    v-model="applyToAllCourts"
                                    @change="handleCourtToggle"
                                    class="rounded border-courtSlate-300 text-arena-base focus:ring-volt"
                                />
                                <span class="text-xs font-display font-black uppercase tracking-wider text-courtSlate-900">
                                    Tutup Semua Lapangan Sekaligus (4 Lapangan)
                                </span>
                            </label>

                            <div v-if="!applyToAllCourts" class="mt-2.5 pt-2.5 border-t border-courtSlate-200">
                                <label class="block text-[11px] font-display font-bold uppercase text-courtSlate-600 mb-1">
                                    Pilih Lapangan yang Ditutup:
                                </label>
                                <select
                                    v-model="form.court_id"
                                    class="w-full text-xs rounded-xl border-2 border-courtSlate-200 bg-white p-2 font-semibold text-courtSlate-800"
                                >
                                    <option v-for="court in props.courts" :key="court.id" :value="court.id">
                                        🏸 {{ court.name }}
                                    </option>
                                </select>
                            </div>
                        </div>

                        <!-- Rentang Tanggal (Mendukung 1 sampai beberapa hari) -->
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-display font-black uppercase tracking-wider text-courtSlate-700 mb-1">
                                    Tanggal Mulai <span class="text-rose-500">*</span>
                                </label>
                                <input
                                    type="date"
                                    v-model="form.start_date"
                                    required
                                    class="w-full text-xs rounded-xl border-2 border-courtSlate-200 bg-courtSlate-50 p-2 font-semibold text-courtSlate-800 focus:border-volt"
                                />
                                <div v-if="form.errors.start_date" class="text-rose-600 text-[11px] mt-1 font-semibold">
                                    {{ form.errors.start_date }}
                                </div>
                            </div>
                            <div>
                                <label class="block text-xs font-display font-black uppercase tracking-wider text-courtSlate-700 mb-1">
                                    Tanggal Selesai <span class="text-rose-500">*</span>
                                </label>
                                <input
                                    type="date"
                                    v-model="form.end_date"
                                    :min="form.start_date"
                                    required
                                    class="w-full text-xs rounded-xl border-2 border-courtSlate-200 bg-courtSlate-50 p-2 font-semibold text-courtSlate-800 focus:border-volt"
                                />
                                <div v-if="form.errors.end_date" class="text-rose-600 text-[11px] mt-1 font-semibold">
                                    {{ form.errors.end_date }}
                                </div>
                            </div>
                        </div>

                        <!-- Pilihan Jam -->
                        <div class="p-3.5 bg-courtSlate-50 rounded-xl border-2 border-courtSlate-200">
                            <label class="flex items-center gap-2 cursor-pointer mb-2">
                                <input
                                    type="checkbox"
                                    v-model="isFullDay"
                                    @change="handleFullDayToggle"
                                    class="rounded border-courtSlate-300 text-arena-base focus:ring-volt"
                                />
                                <span class="text-xs font-display font-black uppercase tracking-wider text-courtSlate-900">
                                    Tutup Seharian Penuh (06:00 – 22:00)
                                </span>
                            </label>

                            <div v-if="!isFullDay" class="grid grid-cols-2 gap-3 mt-2.5 pt-2.5 border-t border-courtSlate-200">
                                <div>
                                    <label class="block text-[11px] font-display font-bold uppercase text-courtSlate-600 mb-1">
                                        Jam Mulai
                                    </label>
                                    <input
                                        type="time"
                                        v-model="form.start_time"
                                        class="w-full text-xs rounded-lg border-2 border-courtSlate-200 bg-white p-2 font-semibold text-courtSlate-800"
                                    />
                                </div>
                                <div>
                                    <label class="block text-[11px] font-display font-bold uppercase text-courtSlate-600 mb-1">
                                        Jam Selesai
                                    </label>
                                    <input
                                        type="time"
                                        v-model="form.end_time"
                                        class="w-full text-xs rounded-lg border-2 border-courtSlate-200 bg-white p-2 font-semibold text-courtSlate-800"
                                    />
                                </div>
                            </div>
                        </div>

                        <!-- Catatan -->
                        <div>
                            <label class="block text-xs font-display font-black uppercase tracking-wider text-courtSlate-700 mb-1">
                                Catatan / Keterangan Tambahan
                            </label>
                            <textarea
                                v-model="form.notes"
                                rows="2"
                                placeholder="Contoh: Turnamen PBSI Cabang Kota, kontak panitia: 08123456789"
                                class="w-full text-xs rounded-xl border-2 border-courtSlate-200 bg-courtSlate-50 p-2.5 font-medium text-courtSlate-800 focus:border-volt"
                            ></textarea>
                            <div v-if="form.errors.notes" class="text-rose-600 text-[11px] mt-1 font-semibold">
                                {{ form.errors.notes }}
                            </div>
                        </div>

                        <!-- Actions -->
                        <div class="flex items-center justify-end gap-3 pt-3 border-t border-courtSlate-200">
                            <button
                                type="button"
                                @click="closeModal"
                                class="px-4 py-2.5 text-xs font-display font-bold uppercase text-courtSlate-600 hover:bg-courtSlate-100 rounded-xl transition cursor-pointer"
                            >
                                Batal
                            </button>
                            <button
                                type="submit"
                                :disabled="form.processing"
                                class="inline-flex items-center gap-2 px-5 py-2.5 bg-arena-base text-volt text-xs font-display font-black uppercase tracking-wider rounded-xl hover:bg-arena-surface hover:shadow-volt-glow-sm transition disabled:opacity-50 cursor-pointer -skew-x-3 active:scale-95"
                            >
                                <span class="skew-x-3">
                                    {{ form.processing ? 'Menyimpan...' : 'Simpan & Kunci Lapangan' }}
                                </span>
                            </button>
                        </div>
                    </form>
                </div>
            </Modal>

            <!-- MODAL: KONFIRMASI HAPUS JADWAL -->
            <Modal :show="isDeleteModalOpen" @close="closeDeleteModal" max-width="md">
                <div class="p-6">
                    <div class="w-12 h-12 rounded-2xl bg-rose-50 border border-rose-200 text-rose-600 flex items-center justify-center text-2xl mx-auto mb-4">
                        ⚠️
                    </div>

                    <h3 class="font-display font-black text-lg text-center text-courtSlate-900 uppercase tracking-tight">
                        Hapus Jadwal Penutupan?
                    </h3>

                    <p class="text-xs text-center text-courtSlate-600 mt-2 leading-relaxed">
                        Apakah Anda yakin ingin menghapus jadwal <strong class="text-courtSlate-900">"{{ closureToDelete?.name }}"</strong>?
                        Setelah dihapus, slot lapangan pada rentang tanggal tersebut akan kembali terbuka dan dapat dibooking oleh member.
                    </p>

                    <div class="flex items-center justify-center gap-3 mt-6">
                        <button
                            type="button"
                            @click="closeDeleteModal"
                            class="px-4 py-2 rounded-xl text-xs font-display font-bold uppercase text-courtSlate-600 hover:bg-courtSlate-100 transition cursor-pointer"
                        >
                            Batal
                        </button>
                        <button
                            type="button"
                            @click="deleteClosure"
                            class="px-5 py-2 rounded-xl bg-rose-600 hover:bg-rose-700 text-white text-xs font-display font-black uppercase tracking-wider transition shadow-sm cursor-pointer"
                        >
                            Ya, Hapus Jadwal
                        </button>
                    </div>
                </div>
            </Modal>

        </div>
    </AdminLayout>
</template>
