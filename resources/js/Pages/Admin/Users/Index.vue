<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import EmptyState from '@/Components/EmptyState.vue';
import Modal from '@/Components/Modal.vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import { ref, reactive, watch } from 'vue';

const props = defineProps({
    users: {
        type: Object,
        required: true,
    },
    summary: {
        type: Object,
        default: () => ({
            total_users: 0,
            active_users: 0,
            inactive_users: 0,
            admin_count: 0,
            gold_members: 0,
            silver_members: 0,
            bronze_members: 0,
        }),
    },
    filters: {
        type: Object,
        default: () => ({
            search: '',
            role: 'all',
            tier: 'all',
            status: 'all',
        }),
    },
});

// Search & Filter state
const search = ref(props.filters.search || '');
const roleFilter = ref(props.filters.role || 'all');
const tierFilter = ref(props.filters.tier || 'all');
const statusFilter = ref(props.filters.status || 'all');

let searchTimeout = null;
const applyFilters = () => {
    router.get(
        route('admin.users.index'),
        {
            search: search.value || undefined,
            role: roleFilter.value !== 'all' ? roleFilter.value : undefined,
            tier: tierFilter.value !== 'all' ? tierFilter.value : undefined,
            status: statusFilter.value !== 'all' ? statusFilter.value : undefined,
        },
        {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        }
    );
};

const handleSearchInput = () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        applyFilters();
    }, 350);
};

const resetFilters = () => {
    search.value = '';
    roleFilter.value = 'all';
    tierFilter.value = 'all';
    statusFilter.value = 'all';
    applyFilters();
};

// User Detail Modal State
const isDetailModalOpen = ref(false);
const detailLoading = ref(false);
const selectedUserDetail = ref(null);
const activeDetailTab = ref('points'); // 'points', 'bookings', 'history'

// Points Adjustment Form
const adjustPointsForm = useForm({
    type: 'add',
    points: 50,
    description: '',
});

const openUserDetail = async (user) => {
    isDetailModalOpen.value = true;
    detailLoading.value = true;
    activeDetailTab.value = 'points';
    adjustPointsForm.reset();
    adjustPointsForm.clearErrors();

    try {
        const response = await fetch(route('admin.users.show', user.id), {
            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
        });
        const data = await response.json();
        selectedUserDetail.value = data.user;
    } catch (err) {
        console.error('Failed to load user details:', err);
    } finally {
        detailLoading.value = false;
    }
};

const closeDetailModal = () => {
    isDetailModalOpen.value = false;
    selectedUserDetail.value = null;
};

const submitPointsAdjustment = () => {
    if (!selectedUserDetail.value) return;

    adjustPointsForm.post(route('admin.users.adjust-points', selectedUserDetail.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            adjustPointsForm.reset();
            // Refresh modal data
            openUserDetail(selectedUserDetail.value);
        },
    });
};

// Role Change Modal
const isRoleModalOpen = ref(false);
const userToChangeRole = ref(null);
const targetRole = ref('admin');

const openRoleModal = (user) => {
    userToChangeRole.value = user;
    targetRole.value = user.role === 'admin' ? 'user' : 'admin';
    isRoleModalOpen.value = true;
};

const confirmRoleChange = () => {
    if (!userToChangeRole.value) return;

    router.patch(
        route('admin.users.update-role', userToChangeRole.value.id),
        { role: targetRole.value },
        {
            preserveScroll: true,
            onSuccess: () => {
                isRoleModalOpen.value = false;
                userToChangeRole.value = null;
            },
        }
    );
};

// Status Toggle Modal
const isStatusModalOpen = ref(false);
const userToToggleStatus = ref(null);

const openStatusModal = (user) => {
    userToToggleStatus.value = user;
    isStatusModalOpen.value = true;
};

const confirmStatusToggle = () => {
    if (!userToToggleStatus.value) return;

    router.patch(
        route('admin.users.toggle-status', userToToggleStatus.value.id),
        {},
        {
            preserveScroll: true,
            onSuccess: () => {
                isStatusModalOpen.value = false;
                userToToggleStatus.value = null;
            },
        }
    );
};

// Delete User Modal
const isDeleteModalOpen = ref(false);
const userToDelete = ref(null);

const openDeleteModal = (user) => {
    userToDelete.value = user;
    isDeleteModalOpen.value = true;
};

const confirmDeleteUser = () => {
    if (!userToDelete.value) return;

    router.delete(route('admin.users.destroy', userToDelete.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            isDeleteModalOpen.value = false;
            userToDelete.value = null;
        },
    });
};

// Formatting Helpers
const formatCurrency = (val) => {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        maximumFractionDigits: 0,
    }).format(val || 0);
};

const getTierBadgeClass = (tier) => {
    switch (tier) {
        case 'gold':
            return 'bg-amber-400/15 text-amber-700 border-amber-400/40 ring-amber-400/30';
        case 'silver':
            return 'bg-slate-200 text-slate-700 border-slate-300 ring-slate-300';
        default:
            return 'bg-amber-900/10 text-amber-800 border-amber-700/30 ring-amber-700/20';
    }
};

const getTierEmoji = (tier) => {
    switch (tier) {
        case 'gold':
            return '🥇 Gold (Diskon 10%)';
        case 'silver':
            return '🥈 Silver (Diskon 5%)';
        default:
            return '🥉 Bronze (Standar)';
    }
};
</script>

<template>
    <Head title="Manajemen Member & Pengguna - Admin" />

    <AdminLayout>
        <div class="space-y-8 p-4 sm:p-6 lg:p-8 max-w-7xl mx-auto">
            
            <!-- Page Header -->
            <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4">
                <div>
                    <div class="flex items-center gap-2 mb-2">
                        <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-display font-black tracking-wider uppercase bg-volt/20 text-arena-base border border-volt/40 -skew-x-6">
                            <span class="transform skew-x-6">DIREKTORI PENGGUNA</span>
                        </span>
                        <span class="text-xs text-courtSlate-500 font-semibold">User & Loyalty Program Management</span>
                    </div>
                    <h1 class="font-display font-black text-2xl sm:text-3xl lg:text-4xl text-courtSlate-900 tracking-tight uppercase">
                        Manajemen Member & Pengguna
                    </h1>
                    <p class="mt-1.5 text-xs sm:text-sm text-courtSlate-600 max-w-2xl">
                        Kelola akun pelanggan, peran staf (role admin/user), status keaktifan, tingkatan tier membership, serta penyesuaian poin royalti.
                    </p>
                </div>
            </div>

            <!-- KPI Metric Cards (4 Cards) -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
                
                <!-- Card 1: Total Users -->
                <div class="bg-white rounded-2xl border-2 border-courtSlate-200 p-5 sm:p-6 shadow-xs flex items-center justify-between">
                    <div>
                        <span class="text-[10px] font-display font-black uppercase tracking-widest text-courtSlate-400 block mb-1">
                            Total Pengguna
                        </span>
                        <div class="flex items-baseline gap-2">
                            <span class="font-display font-black text-3xl text-courtSlate-900 tracking-tight">
                                {{ summary.total_users }}
                            </span>
                            <span class="text-xs font-display font-bold text-courtSlate-500 uppercase">Akun</span>
                        </div>
                    </div>
                    <div class="w-12 h-12 rounded-2xl bg-courtSlate-100 border border-courtSlate-200 flex items-center justify-center text-2xl text-courtSlate-700 shadow-xs">
                        👥
                    </div>
                </div>

                <!-- Card 2: Active vs Inactive Users -->
                <div class="bg-white rounded-2xl border-2 border-courtSlate-200 p-5 sm:p-6 shadow-xs flex items-center justify-between">
                    <div>
                        <span class="text-[10px] font-display font-black uppercase tracking-widest text-courtSlate-400 block mb-1">
                            Status Member
                        </span>
                        <div class="flex items-baseline gap-2">
                            <span class="font-display font-black text-3xl text-emerald-600 tracking-tight">
                                {{ summary.active_users }}
                            </span>
                            <span class="text-xs font-display font-bold text-courtSlate-500 uppercase">Aktif</span>
                        </div>
                        <span v-if="summary.inactive_users > 0" class="text-[11px] font-sans text-rose-500 font-medium block mt-0.5">
                            {{ summary.inactive_users }} akun dinonaktifkan
                        </span>
                    </div>
                    <div class="w-12 h-12 rounded-2xl bg-emerald-50 border border-emerald-200 flex items-center justify-center text-2xl text-emerald-600 shadow-xs">
                        🟢
                    </div>
                </div>

                <!-- Card 3: Gold & Silver Members -->
                <div class="bg-white rounded-2xl border-2 border-courtSlate-200 p-5 sm:p-6 shadow-xs flex items-center justify-between">
                    <div>
                        <span class="text-[10px] font-display font-black uppercase tracking-widest text-courtSlate-400 block mb-1">
                            Member VIP (Gold & Silver)
                        </span>
                        <div class="flex items-baseline gap-2">
                            <span class="font-display font-black text-3xl text-amber-600 tracking-tight">
                                {{ summary.gold_members + summary.silver_members }}
                            </span>
                            <span class="text-xs font-display font-bold text-courtSlate-500 uppercase">Member</span>
                        </div>
                        <span class="text-[11px] font-sans text-courtSlate-500 block mt-0.5">
                            {{ summary.gold_members }} Gold • {{ summary.silver_members }} Silver
                        </span>
                    </div>
                    <div class="w-12 h-12 rounded-2xl bg-amber-50 border border-amber-200 flex items-center justify-center text-2xl text-amber-600 shadow-xs">
                        ⭐
                    </div>
                </div>

                <!-- Card 4: Staf / Admin -->
                <div class="bg-white rounded-2xl border-2 border-courtSlate-200 p-5 sm:p-6 shadow-xs flex items-center justify-between">
                    <div>
                        <span class="text-[10px] font-display font-black uppercase tracking-widest text-courtSlate-400 block mb-1">
                            Staf Pengelola (Admin)
                        </span>
                        <div class="flex items-baseline gap-2">
                            <span class="font-display font-black text-3xl text-courtOrange tracking-tight">
                                {{ summary.admin_count }}
                            </span>
                            <span class="text-xs font-display font-bold text-courtSlate-500 uppercase">Admin</span>
                        </div>
                    </div>
                    <div class="w-12 h-12 rounded-2xl bg-courtOrange-light border border-courtOrange/30 flex items-center justify-center text-2xl text-courtOrange shadow-xs">
                        🛡️
                    </div>
                </div>
            </div>

            <!-- Table & Filtering Container -->
            <div class="bg-white rounded-2xl border-2 border-courtSlate-200 shadow-card-elevated overflow-hidden">
                
                <!-- Filter Controls Bar -->
                <div class="p-5 sm:p-6 border-b-2 border-courtSlate-200 flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4 bg-courtSlate-50/70">
                    
                    <!-- Search Input -->
                    <div class="relative flex-1 max-w-md">
                        <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-courtSlate-400">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                        </div>
                        <input
                            v-model="search"
                            type="text"
                            placeholder="Cari nama, email, atau no telepon..."
                            @input="handleSearchInput"
                            class="w-full pl-9 pr-4 py-2.5 bg-white border-2 border-courtSlate-200 rounded-xl text-sm font-sans text-courtSlate-900 placeholder-courtSlate-400 focus:outline-none focus:border-volt focus:ring-1 focus:ring-volt transition-colors"
                        />
                    </div>

                    <!-- Dropdown Filters -->
                    <div class="flex flex-wrap items-center gap-3">
                        
                        <!-- Role Filter -->
                        <div class="flex items-center gap-2">
                            <label class="text-xs font-display font-bold uppercase text-courtSlate-500">Role:</label>
                            <select
                                v-model="roleFilter"
                                @change="applyFilters"
                                class="bg-white border-2 border-courtSlate-200 rounded-xl px-3 py-2 text-xs font-sans text-courtSlate-800 focus:outline-none focus:border-volt focus:ring-1 focus:ring-volt transition-colors"
                            >
                                <option value="all">Semua Role</option>
                                <option value="user">User Biasa</option>
                                <option value="admin">Administrator</option>
                            </select>
                        </div>

                        <!-- Tier Filter -->
                        <div class="flex items-center gap-2">
                            <label class="text-xs font-display font-bold uppercase text-courtSlate-500">Tier:</label>
                            <select
                                v-model="tierFilter"
                                @change="applyFilters"
                                class="bg-white border-2 border-courtSlate-200 rounded-xl px-3 py-2 text-xs font-sans text-courtSlate-800 focus:outline-none focus:border-volt focus:ring-1 focus:ring-volt transition-colors"
                            >
                                <option value="all">Semua Tier</option>
                                <option value="gold">🥇 Gold</option>
                                <option value="silver">🥈 Silver</option>
                                <option value="bronze">🥉 Bronze</option>
                            </select>
                        </div>

                        <!-- Status Filter -->
                        <div class="flex items-center gap-2">
                            <label class="text-xs font-display font-bold uppercase text-courtSlate-500">Status:</label>
                            <select
                                v-model="statusFilter"
                                @change="applyFilters"
                                class="bg-white border-2 border-courtSlate-200 rounded-xl px-3 py-2 text-xs font-sans text-courtSlate-800 focus:outline-none focus:border-volt focus:ring-1 focus:ring-volt transition-colors"
                            >
                                <option value="all">Semua Status</option>
                                <option value="active">🟢 Aktif</option>
                                <option value="inactive">🔴 Nonaktif</option>
                            </select>
                        </div>

                        <!-- Reset Button -->
                        <button
                            v-if="search || roleFilter !== 'all' || tierFilter !== 'all' || statusFilter !== 'all'"
                            @click="resetFilters"
                            class="px-3 py-2 text-xs font-display font-black uppercase tracking-wider text-courtOrange hover:text-courtOrange-dark transition-colors"
                        >
                            Reset
                        </button>
                    </div>
                </div>

                <!-- Table Content with Dark Athletic Header and Comfortable Spacing -->
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y-2 divide-courtSlate-200 text-left border-collapse">
                        <thead class="bg-arena-base text-courtSlate-200">
                            <tr>
                                <th class="py-4 px-6 font-display font-black text-xs uppercase tracking-wider text-courtSlate-300 min-w-[240px]">
                                    Pengguna
                                </th>
                                <th class="py-4 px-6 font-display font-black text-xs uppercase tracking-wider text-courtSlate-300 min-w-[150px]">
                                    Hak Akses (Role)
                                </th>
                                <th class="py-4 px-6 font-display font-black text-xs uppercase tracking-wider text-courtSlate-300 min-w-[170px]">
                                    Tier & Poin
                                </th>
                                <th class="py-4 px-6 font-display font-black text-xs uppercase tracking-wider text-courtSlate-300 min-w-[160px]">
                                    Aktivitas Booking
                                </th>
                                <th class="py-4 px-6 font-display font-black text-xs uppercase tracking-wider text-courtSlate-300 min-w-[120px]">
                                    Status Akun
                                </th>
                                <th class="py-4 px-6 font-display font-black text-xs uppercase tracking-wider text-courtSlate-300 min-w-[140px]">
                                    Bergabung
                                </th>
                                <th class="py-4 px-6 font-display font-black text-xs uppercase tracking-wider text-courtSlate-300 text-right min-w-[170px]">
                                    Aksi
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-courtSlate-200 text-sm font-sans bg-white">
                            <tr
                                v-for="user in users.data"
                                :key="user.id"
                                class="hover:bg-courtSlate-50/80 transition-colors group"
                            >
                                <!-- User Identity -->
                                <td class="py-4 px-6">
                                    <div class="flex items-center gap-3.5">
                                        <div class="w-10 h-10 rounded-xl bg-courtSlate-900 text-volt font-display font-black flex items-center justify-center text-sm shadow-xs uppercase shrink-0">
                                            {{ user.name.charAt(0) }}
                                        </div>
                                        <div>
                                            <div class="font-display font-black text-base text-courtSlate-900 group-hover:text-courtOrange transition-colors">
                                                {{ user.name }}
                                            </div>
                                            <div class="text-xs text-courtSlate-500 font-sans flex items-center gap-2 mt-0.5">
                                                <span>{{ user.email }}</span>
                                                <span v-if="user.phone !== '-'" class="text-courtSlate-400">• {{ user.phone }}</span>
                                            </div>
                                        </div>
                                    </div>
                                </td>

                                <!-- Role Badge -->
                                <td class="py-4 px-6">
                                    <span
                                        v-if="user.role === 'admin'"
                                        class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[11px] font-display font-black uppercase tracking-wider bg-courtSlate-900 text-volt border border-volt/30 shadow-xs"
                                    >
                                        🛡️ Administrator
                                    </span>
                                    <span
                                        v-else
                                        class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[11px] font-display font-bold uppercase tracking-wider bg-courtSlate-100 text-courtSlate-600 border border-courtSlate-200"
                                    >
                                        👤 Member
                                    </span>
                                </td>

                                <!-- Membership Tier & Points -->
                                <td class="py-4 px-6">
                                    <div class="flex flex-col gap-1">
                                        <span
                                            :class="[
                                                'inline-flex items-center gap-1.5 px-3 py-0.5 rounded-full text-[11px] font-display font-black uppercase tracking-wider border w-fit shadow-2xs',
                                                getTierBadgeClass(user.tier)
                                            ]"
                                        >
                                            {{ getTierEmoji(user.tier) }}
                                        </span>
                                        <span class="text-xs font-display font-black text-courtSlate-800 tracking-wide mt-0.5">
                                            ⭐ {{ user.points }} Poin Loyalti
                                        </span>
                                    </div>
                                </td>

                                <!-- Booking Activity & Spending -->
                                <td class="py-4 px-6">
                                    <div class="flex flex-col">
                                        <span class="font-display font-black text-sm text-courtSlate-900">
                                            {{ user.bookings_count }} Sesi Booking
                                        </span>
                                        <span class="text-xs text-courtSlate-500 font-sans mt-0.5">
                                            Total: {{ formatCurrency(user.total_spent) }}
                                        </span>
                                    </div>
                                </td>

                                <!-- Status Badge -->
                                <td class="py-4 px-6">
                                    <span
                                        v-if="user.is_active"
                                        class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[11px] font-display font-black uppercase tracking-wider bg-emerald-50 text-emerald-700 border border-emerald-200"
                                    >
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                                        Aktif
                                    </span>
                                    <span
                                        v-else
                                        class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[11px] font-display font-black uppercase tracking-wider bg-rose-50 text-rose-700 border border-rose-200"
                                    >
                                        <span class="w-1.5 h-1.5 rounded-full bg-rose-500"></span>
                                        Nonaktif
                                    </span>
                                </td>

                                <!-- Registered Date -->
                                <td class="py-4 px-6 text-xs text-courtSlate-500">
                                    <div class="font-medium text-courtSlate-700">{{ user.created_at }}</div>
                                    <div class="text-[11px] text-courtSlate-400 mt-0.5">{{ user.created_at_relative }}</div>
                                </td>

                                <!-- Action Buttons with Clean Borders and Spacing -->
                                <td class="py-4 px-6 text-right">
                                    <div class="flex items-center justify-end gap-2">
                                        
                                        <!-- Detail Button -->
                                        <button
                                            @click="openUserDetail(user)"
                                            title="Lihat Detail & Riwayat Pengguna"
                                            class="w-9 h-9 rounded-xl border border-courtSlate-200 bg-white text-courtSlate-600 hover:text-courtSlate-900 hover:border-courtSlate-300 hover:bg-courtSlate-100 flex items-center justify-center transition-all shadow-xs"
                                        >
                                            <span class="text-base">👁️</span>
                                        </button>

                                        <!-- Toggle Role Button -->
                                        <button
                                            @click="openRoleModal(user)"
                                            :disabled="user.id === $page.props.auth.user.id"
                                            :title="user.id === $page.props.auth.user.id ? 'Tidak dapat mengubah role sendiri' : 'Ubah Role Akses'"
                                            class="w-9 h-9 rounded-xl border border-courtSlate-200 bg-white text-courtSlate-600 hover:text-courtOrange hover:border-courtOrange/40 hover:bg-courtOrange-light flex items-center justify-center disabled:opacity-30 disabled:cursor-not-allowed transition-all shadow-xs"
                                        >
                                            <span class="text-base">🛡️</span>
                                        </button>

                                        <!-- Toggle Status Button -->
                                        <button
                                            @click="openStatusModal(user)"
                                            :disabled="user.id === $page.props.auth.user.id"
                                            :title="user.id === $page.props.auth.user.id ? 'Tidak dapat menonaktifkan akun sendiri' : (user.is_active ? 'Nonaktifkan Akun' : 'Aktifkan Akun')"
                                            class="w-9 h-9 rounded-xl border border-courtSlate-200 bg-white text-courtSlate-600 hover:text-amber-600 hover:border-amber-300 hover:bg-amber-50 flex items-center justify-center disabled:opacity-30 disabled:cursor-not-allowed transition-all shadow-xs"
                                        >
                                            <span class="text-base">{{ user.is_active ? '⏸️' : '▶️' }}</span>
                                        </button>

                                        <!-- Delete User Button -->
                                        <button
                                            @click="openDeleteModal(user)"
                                            :disabled="user.id === $page.props.auth.user.id"
                                            :title="user.id === $page.props.auth.user.id ? 'Tidak dapat menghapus akun sendiri' : 'Hapus Pengguna'"
                                            class="w-9 h-9 rounded-xl border border-courtSlate-200 bg-white text-courtSlate-400 hover:text-rose-600 hover:border-rose-300 hover:bg-rose-50 flex items-center justify-center disabled:opacity-30 disabled:cursor-not-allowed transition-all shadow-xs"
                                        >
                                            <span class="text-base">🗑️</span>
                                        </button>
                                    </div>
                                </td>
                            </tr>

                            <!-- Empty State -->
                            <tr v-if="users.data.length === 0">
                                <td colspan="7" class="py-12 px-6">
                                    <EmptyState
                                        icon="👥"
                                        title="Pengguna Tidak Ditemukan"
                                        description="Tidak ada pengguna atau member yang sesuai dengan kriteria pencarian dan filter saat ini."
                                    >
                                        <button
                                            @click="resetFilters"
                                            class="mt-4 px-4 py-2 bg-arena-base text-volt font-display font-black text-xs uppercase tracking-wider rounded-xl hover:bg-arena-surface transition-colors"
                                        >
                                            Reset Filter Pencarian
                                        </button>
                                    </EmptyState>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination Footer -->
                <div
                    v-if="users.links && users.links.length > 3"
                    class="p-4 sm:p-5 border-t-2 border-courtSlate-200 flex flex-col sm:flex-row items-center justify-between gap-4 bg-courtSlate-50"
                >
                    <div class="text-xs text-courtSlate-500 font-sans">
                        Menampilkan <span class="font-bold text-courtSlate-800">{{ users.from || 0 }}</span> sampai
                        <span class="font-bold text-courtSlate-800">{{ users.to || 0 }}</span> dari
                        <span class="font-bold text-courtSlate-800">{{ users.total }}</span> total member
                    </div>

                    <div class="flex items-center gap-1.5 flex-wrap">
                        <template v-for="(link, index) in users.links" :key="index">
                            <span
                                v-if="!link.url"
                                class="px-3 py-1.5 text-xs text-courtSlate-400 rounded-lg select-none"
                                v-html="link.label"
                            />
                            <a
                                v-else
                                :href="link.url"
                                :class="[
                                    'px-3 py-1.5 text-xs rounded-lg font-display font-black transition-colors',
                                    link.active
                                        ? 'bg-arena-base text-volt shadow-xs'
                                        : 'bg-white text-courtSlate-700 hover:bg-courtSlate-200 border border-courtSlate-200'
                                ]"
                                v-html="link.label"
                            />
                        </template>
                    </div>
                </div>
            </div>

            <!-- MODAL 1: Detail & Inspeksi Member -->
            <Modal :show="isDetailModalOpen" @close="closeDetailModal" maxWidth="3xl">
                <div class="p-6 sm:p-8">
                    
                    <!-- Loading Spinner -->
                    <div v-if="detailLoading" class="py-16 text-center">
                        <div class="inline-block animate-spin text-3xl mb-3">🏸</div>
                        <p class="text-sm font-display font-bold uppercase tracking-wider text-courtSlate-500">
                            Memuat data pengguna & riwayat...
                        </p>
                    </div>

                    <!-- User Detail Body -->
                    <div v-else-if="selectedUserDetail" class="space-y-6">
                        
                        <!-- Header Profile Card -->
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-5 border-b-2 border-courtSlate-200">
                            <div class="flex items-center gap-4">
                                <div class="w-14 h-14 rounded-2xl bg-arena-base text-volt font-display font-black flex items-center justify-center text-2xl shadow-md uppercase">
                                    {{ selectedUserDetail.name.charAt(0) }}
                                </div>
                                <div>
                                    <div class="flex items-center gap-2">
                                        <h3 class="font-display font-black text-xl text-courtSlate-900 uppercase">
                                            {{ selectedUserDetail.name }}
                                        </h3>
                                        <span
                                            v-if="selectedUserDetail.role === 'admin'"
                                            class="px-2 py-0.5 rounded-full text-[10px] font-display font-black uppercase bg-courtSlate-900 text-volt border border-volt/30"
                                        >
                                            Admin
                                        </span>
                                    </div>
                                    <p class="text-xs text-courtSlate-500 font-sans mt-0.5">
                                        {{ selectedUserDetail.email }} • Telp: {{ selectedUserDetail.phone }}
                                    </p>
                                    <p class="text-[11px] text-courtSlate-400 font-sans">
                                        Bergabung sejak {{ selectedUserDetail.created_at }}
                                    </p>
                                </div>
                            </div>

                            <!-- Tier Info Badge -->
                            <div class="flex flex-col items-start sm:items-end">
                                <span :class="['px-3 py-1 rounded-full text-xs font-display font-black uppercase tracking-wider border', getTierBadgeClass(selectedUserDetail.tier)]">
                                    {{ getTierEmoji(selectedUserDetail.tier) }}
                                </span>
                                <span class="font-display font-black text-xl text-courtSlate-900 mt-1">
                                    ⭐ {{ selectedUserDetail.points }} Poin
                                </span>
                            </div>
                        </div>

                        <!-- Modal Tabs -->
                        <div class="flex border-b border-courtSlate-200 gap-2">
                            <button
                                @click="activeDetailTab = 'points'"
                                :class="[
                                    'pb-2 px-3 text-xs font-display font-black uppercase tracking-wider transition-colors border-b-2 -mb-[2px]',
                                    activeDetailTab === 'points'
                                        ? 'border-courtOrange text-courtOrange'
                                        : 'border-transparent text-courtSlate-500 hover:text-courtSlate-800'
                                ]"
                            >
                                🎁 Penyesuaian Poin
                            </button>
                            <button
                                @click="activeDetailTab = 'bookings'"
                                :class="[
                                    'pb-2 px-3 text-xs font-display font-black uppercase tracking-wider transition-colors border-b-2 -mb-[2px]',
                                    activeDetailTab === 'bookings'
                                        ? 'border-courtOrange text-courtOrange'
                                        : 'border-transparent text-courtSlate-500 hover:text-courtSlate-800'
                                ]"
                            >
                                📋 Riwayat Booking ({{ selectedUserDetail.bookings_count }})
                            </button>
                            <button
                                @click="activeDetailTab = 'history'"
                                :class="[
                                    'pb-2 px-3 text-xs font-display font-black uppercase tracking-wider transition-colors border-b-2 -mb-[2px]',
                                    activeDetailTab === 'history'
                                        ? 'border-courtOrange text-courtOrange'
                                        : 'border-transparent text-courtSlate-500 hover:text-courtSlate-800'
                                ]"
                            >
                                📜 Log Poin Loyalti
                            </button>
                        </div>

                        <!-- TAB 1: Points Adjustment Form -->
                        <div v-if="activeDetailTab === 'points'" class="space-y-4">
                            <div class="bg-courtSlate-50 rounded-xl p-4 border border-courtSlate-200">
                                <h4 class="font-display font-black text-sm uppercase text-courtSlate-900 mb-1">
                                    Atur Poin Loyalti Member
                                </h4>
                                <p class="text-xs text-courtSlate-500 mb-4">
                                    Gunakan form ini untuk memberikan bonus poin apresiasi, reward promosi, atau koreksi poin secara manual. Tingkatan tier akan otomatis diperbarui.
                                </p>

                                <form @submit.prevent="submitPointsAdjustment" class="space-y-3">
                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                        <div>
                                            <label class="block text-[11px] font-display font-bold uppercase text-courtSlate-600 mb-1">
                                                Tipe Penyesuaian:
                                            </label>
                                            <div class="grid grid-cols-2 gap-2">
                                                <button
                                                    type="button"
                                                    @click="adjustPointsForm.type = 'add'"
                                                    :class="[
                                                        'py-2 text-xs font-display font-black uppercase rounded-lg border transition-all',
                                                        adjustPointsForm.type === 'add'
                                                            ? 'bg-emerald-600 text-white border-emerald-600 shadow-xs'
                                                            : 'bg-white text-courtSlate-700 border-courtSlate-300'
                                                    ]"
                                                >
                                                    + Tambah Poin
                                                </button>
                                                <button
                                                    type="button"
                                                    @click="adjustPointsForm.type = 'subtract'"
                                                    :class="[
                                                        'py-2 text-xs font-display font-black uppercase rounded-lg border transition-all',
                                                        adjustPointsForm.type === 'subtract'
                                                            ? 'bg-rose-600 text-white border-rose-600 shadow-xs'
                                                            : 'bg-white text-courtSlate-700 border-courtSlate-300'
                                                    ]"
                                                >
                                                    - Kurangi Poin
                                                </button>
                                            </div>
                                        </div>

                                        <div>
                                            <label class="block text-[11px] font-display font-bold uppercase text-courtSlate-600 mb-1">
                                                Jumlah Poin:
                                            </label>
                                            <input
                                                v-model="adjustPointsForm.points"
                                                type="number"
                                                min="1"
                                                step="1"
                                                required
                                                class="w-full px-3 py-2 border border-courtSlate-300 rounded-lg text-sm font-sans focus:outline-none focus:border-volt"
                                            />
                                        </div>
                                    </div>

                                    <div>
                                        <label class="block text-[11px] font-display font-bold uppercase text-courtSlate-600 mb-1">
                                            Keterangan / Alasan Penyesuaian:
                                        </label>
                                        <input
                                            v-model="adjustPointsForm.description"
                                            type="text"
                                            placeholder="Contoh: Bonus partisipasi turnamen, reward loyalitas, dsb."
                                            required
                                            class="w-full px-3 py-2 border border-courtSlate-300 rounded-lg text-sm font-sans focus:outline-none focus:border-volt"
                                        />
                                    </div>

                                    <div class="flex justify-end pt-2">
                                        <button
                                            type="submit"
                                            :disabled="adjustPointsForm.processing"
                                            class="px-5 py-2.5 bg-arena-base text-volt font-display font-black text-xs uppercase tracking-wider rounded-xl hover:bg-arena-surface hover:shadow-volt-glow-sm transition-all disabled:opacity-50"
                                        >
                                            {{ adjustPointsForm.processing ? 'Menyimpan...' : 'Simpan Penyesuaian Poin' }}
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>

                        <!-- TAB 2: Recent Bookings -->
                        <div v-if="activeDetailTab === 'bookings'" class="space-y-3">
                            <div v-if="selectedUserDetail.recent_bookings.length > 0" class="overflow-x-auto">
                                <table class="w-full text-left text-xs">
                                    <thead>
                                        <tr class="bg-courtSlate-100 font-display font-black uppercase text-courtSlate-600">
                                            <th class="p-2.5">Lapangan</th>
                                            <th class="p-2.5">Tanggal & Jam</th>
                                            <th class="p-2.5">Total Harga</th>
                                            <th class="p-2.5">Status</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-courtSlate-200">
                                        <tr v-for="b in selectedUserDetail.recent_bookings" :key="b.id">
                                            <td class="p-2.5 font-bold text-courtSlate-900">
                                                {{ b.court_name }}
                                                <span v-if="b.is_recurring" class="text-[10px] text-courtOrange block">Rutin</span>
                                            </td>
                                            <td class="p-2.5 text-courtSlate-600">
                                                {{ b.booking_date }} • {{ b.start_time }} - {{ b.end_time }}
                                            </td>
                                            <td class="p-2.5 font-bold text-courtSlate-800">
                                                {{ formatCurrency(b.total_price) }}
                                            </td>
                                            <td class="p-2.5">
                                                <span :class="[
                                                    'px-2 py-0.5 rounded-full text-[10px] font-display font-bold uppercase',
                                                    b.status === 'confirmed' ? 'bg-emerald-100 text-emerald-800' :
                                                    b.status === 'pending' ? 'bg-amber-100 text-amber-800' : 'bg-rose-100 text-rose-800'
                                                ]">
                                                    {{ b.status }}
                                                </span>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                            <div v-else class="text-center py-8 text-xs text-courtSlate-500 font-sans">
                                Belum ada riwayat booking untuk pengguna ini.
                            </div>
                        </div>

                        <!-- TAB 3: Loyalty Point Logs -->
                        <div v-if="activeDetailTab === 'history'" class="space-y-3">
                            <div v-if="selectedUserDetail.recent_point_histories.length > 0" class="overflow-x-auto">
                                <table class="w-full text-left text-xs">
                                    <thead>
                                        <tr class="bg-courtSlate-100 font-display font-black uppercase text-courtSlate-600">
                                            <th class="p-2.5">Tanggal</th>
                                            <th class="p-2.5">Poin</th>
                                            <th class="p-2.5">Keterangan</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-courtSlate-200">
                                        <tr v-for="ph in selectedUserDetail.recent_point_histories" :key="ph.id">
                                            <td class="p-2.5 text-courtSlate-500">{{ ph.created_at }}</td>
                                            <td class="p-2.5 font-display font-black">
                                                <span v-if="ph.points_earned > 0" class="text-emerald-600">+{{ ph.points_earned }} ⭐</span>
                                                <span v-else class="text-rose-600">-{{ ph.points_used }} ⭐</span>
                                            </td>
                                            <td class="p-2.5 text-courtSlate-700">{{ ph.description }}</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                            <div v-else class="text-center py-8 text-xs text-courtSlate-500 font-sans">
                                Belum ada log transaksi poin royalti.
                            </div>
                        </div>

                        <!-- Modal Footer -->
                        <div class="flex justify-end pt-4 border-t border-courtSlate-200">
                            <button
                                @click="closeDetailModal"
                                class="px-5 py-2 bg-courtSlate-200 hover:bg-courtSlate-300 text-courtSlate-800 font-display font-bold text-xs uppercase rounded-xl transition-colors"
                            >
                                Tutup
                            </button>
                        </div>
                    </div>
                </div>
            </Modal>

            <!-- MODAL 2: Ubah Role Akses (Admin / User) -->
            <Modal :show="isRoleModalOpen" @close="isRoleModalOpen = false" maxWidth="md">
                <div class="p-6 sm:p-8">
                    <div class="text-center mb-5">
                        <div class="w-12 h-12 rounded-2xl bg-amber-50 border border-amber-200 text-2xl flex items-center justify-center mx-auto mb-3 text-amber-600">
                            🛡️
                        </div>
                        <h3 class="font-display font-black text-lg text-courtSlate-900 uppercase">
                            Ubah Hak Akses (Role)
                        </h3>
                        <p class="text-xs text-courtSlate-600 font-sans mt-1">
                            Anda akan mengubah hak akses akun <span class="font-bold text-courtSlate-900">{{ userToChangeRole?.name }}</span> menjadi:
                        </p>
                    </div>

                    <div class="bg-courtSlate-50 p-4 rounded-xl border border-courtSlate-200 mb-5">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-display font-bold uppercase text-courtSlate-500">Target Role:</span>
                            <span :class="[
                                'px-3 py-1 rounded-full text-xs font-display font-black uppercase tracking-wider',
                                targetRole === 'admin' ? 'bg-courtSlate-900 text-volt border border-volt/30' : 'bg-courtSlate-200 text-courtSlate-800'
                            ]">
                                {{ targetRole === 'admin' ? '🛡️ Administrator' : '👤 User Biasa' }}
                            </span>
                        </div>
                        <p v-if="targetRole === 'admin'" class="text-[11px] text-courtSlate-500 font-sans mt-2">
                            ⚠️ Perhatian: Administrator memiliki akses penuh ke Dashboard, Manajemen Lapangan, Jadwal Turnamen, dan Laporan Keuangan.
                        </p>
                    </div>

                    <div class="flex items-center justify-end gap-2.5">
                        <button
                            @click="isRoleModalOpen = false"
                            class="px-4 py-2 bg-courtSlate-100 hover:bg-courtSlate-200 text-courtSlate-700 font-display font-bold text-xs uppercase rounded-xl transition-colors"
                        >
                            Batal
                        </button>
                        <button
                            @click="confirmRoleChange"
                            class="px-5 py-2 bg-arena-base text-volt font-display font-black text-xs uppercase tracking-wider rounded-xl hover:bg-arena-surface transition-all"
                        >
                            Konfirmasi Perubahan Role
                        </button>
                    </div>
                </div>
            </Modal>

            <!-- MODAL 3: Nonaktifkan / Aktifkan User -->
            <Modal :show="isStatusModalOpen" @close="isStatusModalOpen = false" maxWidth="md">
                <div class="p-6 sm:p-8">
                    <div class="text-center mb-5">
                        <div :class="[
                            'w-12 h-12 rounded-2xl flex items-center justify-center mx-auto mb-3 text-2xl border',
                            userToToggleStatus?.is_active ? 'bg-rose-50 border-rose-200 text-rose-600' : 'bg-emerald-50 border-emerald-200 text-emerald-600'
                        ]">
                            {{ userToToggleStatus?.is_active ? '⏸️' : '▶️' }}
                        </div>
                        <h3 class="font-display font-black text-lg text-courtSlate-900 uppercase">
                            {{ userToToggleStatus?.is_active ? 'Nonaktifkan Akun Pengguna?' : 'Aktifkan Kembali Akun Pengguna?' }}
                        </h3>
                        <p class="text-xs text-courtSlate-600 font-sans mt-1">
                            Akun: <span class="font-bold text-courtSlate-900">{{ userToToggleStatus?.name }}</span> ({{ userToToggleStatus?.email }})
                        </p>
                    </div>

                    <div class="bg-courtSlate-50 p-4 rounded-xl border border-courtSlate-200 mb-5 text-xs text-courtSlate-600 font-sans">
                        <p v-if="userToToggleStatus?.is_active">
                            Pengguna yang dinonaktifkan tidak akan bisa login ke dalam aplikasi maupun membuat pemesanan lapangan baru sampai diaktifkan kembali oleh admin.
                        </p>
                        <p v-else>
                            Pengguna akan dapat kembali login dan memesan lapangan seperti biasa.
                        </p>
                    </div>

                    <div class="flex items-center justify-end gap-2.5">
                        <button
                            @click="isStatusModalOpen = false"
                            class="px-4 py-2 bg-courtSlate-100 hover:bg-courtSlate-200 text-courtSlate-700 font-display font-bold text-xs uppercase rounded-xl transition-colors"
                        >
                            Batal
                        </button>
                        <button
                            @click="confirmStatusToggle"
                            :class="[
                                'px-5 py-2 font-display font-black text-xs uppercase tracking-wider rounded-xl transition-all',
                                userToToggleStatus?.is_active
                                    ? 'bg-rose-600 hover:bg-rose-700 text-white'
                                    : 'bg-emerald-600 hover:bg-emerald-700 text-white'
                            ]"
                        >
                            {{ userToToggleStatus?.is_active ? 'Ya, Nonaktifkan Akun' : 'Ya, Aktifkan Akun' }}
                        </button>
                    </div>
                </div>
            </Modal>

            <!-- MODAL 4: Hapus Akun User -->
            <Modal :show="isDeleteModalOpen" @close="isDeleteModalOpen = false" maxWidth="md">
                <div class="p-6 sm:p-8">
                    <div class="text-center mb-5">
                        <div class="w-12 h-12 rounded-2xl bg-rose-50 border border-rose-200 text-rose-600 text-2xl flex items-center justify-center mx-auto mb-3">
                            ⚠️
                        </div>
                        <h3 class="font-display font-black text-lg text-courtSlate-900 uppercase">
                            Hapus Pengguna Permanen?
                        </h3>
                        <p class="text-xs text-courtSlate-600 font-sans mt-1">
                            Anda akan menghapus data <span class="font-bold text-courtSlate-900">{{ userToDelete?.name }}</span> secara permanen dari sistem.
                        </p>
                    </div>

                    <div class="bg-rose-50 p-4 rounded-xl border border-rose-200 mb-5 text-xs text-rose-800 font-sans">
                        Tindakan ini tidak dapat dibatalkan. Riwayat poin dan membership akun ini akan ikut terhapus.
                    </div>

                    <div class="flex items-center justify-end gap-2.5">
                        <button
                            @click="isDeleteModalOpen = false"
                            class="px-4 py-2 bg-courtSlate-100 hover:bg-courtSlate-200 text-courtSlate-700 font-display font-bold text-xs uppercase rounded-xl transition-colors"
                        >
                            Batal
                        </button>
                        <button
                            @click="confirmDeleteUser"
                            class="px-5 py-2 bg-rose-600 hover:bg-rose-700 text-white font-display font-black text-xs uppercase tracking-wider rounded-xl transition-colors"
                        >
                            Ya, Hapus Pengguna
                        </button>
                    </div>
                </div>
            </Modal>

        </div>
    </AdminLayout>
</template>
