<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

const props = defineProps({
    membership: Object,
    pointHistories: Array,
    tierBenefits: Array,
});

const formatDate = (dateString) => {
    if (!dateString) return '-';
    const d = new Date(dateString);
    return new Intl.DateTimeFormat('id-ID', {
        day: 'numeric',
        month: 'short',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    }).format(d);
};

const getTierColor = (tier) => {
    switch (tier) {
        case 'gold':
            return {
                bg: 'bg-amber-500',
                border: 'border-amber-400',
                text: 'text-amber-500',
                lightBg: 'bg-amber-500/10',
                badgeBg: 'bg-amber-100 text-amber-900 border-amber-300',
                gradient: 'from-amber-500/20 via-yellow-500/10 to-transparent',
                accentBar: 'bg-gradient-to-r from-amber-400 via-yellow-400 to-amber-500',
                glow: 'shadow-[0_0_20px_rgba(245,158,11,0.25)]',
            };
        case 'silver':
            return {
                bg: 'bg-slate-400',
                border: 'border-slate-300',
                text: 'text-slate-400',
                lightBg: 'bg-slate-400/10',
                badgeBg: 'bg-slate-100 text-slate-700 border-slate-300',
                gradient: 'from-slate-400/20 via-slate-200/10 to-transparent',
                accentBar: 'bg-gradient-to-r from-slate-400 via-slate-300 to-slate-200',
                glow: 'shadow-[0_0_20px_rgba(148,163,184,0.25)]',
            };
        default:
            return {
                bg: 'bg-amber-700',
                border: 'border-amber-600',
                text: 'text-amber-700',
                lightBg: 'bg-amber-700/10',
                badgeBg: 'bg-orange-50 text-orange-800 border-orange-200',
                gradient: 'from-amber-700/20 via-amber-600/10 to-transparent',
                accentBar: 'bg-gradient-to-r from-amber-700 via-amber-600 to-amber-500',
                glow: 'shadow-[0_0_20px_rgba(180,83,9,0.25)]',
            };
    }
};

const currentTierStyle = getTierColor(props.membership.tier);
</script>

<template>
    <Head title="Membership & Loyalty Poin" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <div class="flex items-center gap-3">
                        <h2 class="font-display font-black text-2xl sm:text-3xl text-courtSlate-900 tracking-tight uppercase">
                            Membership & Loyalty
                        </h2>
                        <span
                            :class="[
                                'inline-flex items-center px-2.5 py-0.5 text-xs font-display font-black tracking-wider uppercase rounded -skew-x-6 border',
                                currentTierStyle.badgeBg
                            ]"
                        >
                            <span class="inline-block transform skew-x-6">
                                Tier {{ props.membership.tier }}
                            </span>
                        </span>
                    </div>
                    <p class="mt-1 text-sm text-courtSlate-600">
                        Kumpulkan poin dari setiap booking lapangan dan nikmati potongan diskon otomatis!
                    </p>
                </div>

                <div>
                    <Link
                        :href="route('bookings.create')"
                        class="inline-flex items-center gap-2 px-5 py-2.5 bg-volt text-volt-contrast font-display font-black text-sm uppercase tracking-wider rounded-lg shadow-volt-glow-sm hover:bg-volt-hover transition -skew-x-6"
                    >
                        <span class="inline-block transform skew-x-6">
                            🏸 Booking Lapangan
                        </span>
                    </Link>
                </div>
            </div>
        </template>

        <div class="py-8 bg-gray-50 min-h-screen">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-8">

                <!-- MAIN STATUS CARD (ATHLETIC VIP HERO) -->
                <div
                    class="relative overflow-hidden rounded-2xl bg-arena-card border border-arena-border text-white shadow-card-elevated"
                >
                    <!-- Background ambient glow -->
                    <div
                        class="absolute inset-0 bg-gradient-to-br opacity-50 pointer-events-none"
                        :class="currentTierStyle.gradient"
                    />

                    <div class="relative p-6 sm:p-8">
                        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 items-center">
                            
                            <!-- Col 1: Tier Badge & Details -->
                            <div class="space-y-4">
                                <div class="inline-flex items-center gap-2">
                                    <span class="text-xs font-display uppercase tracking-widest text-courtSlate-400 font-bold">
                                        Status Keanggotaan
                                    </span>
                                </div>
                                <div class="flex items-center gap-4">
                                    <div
                                        :class="[
                                            'w-16 h-16 rounded-2xl flex items-center justify-center font-display font-black text-2xl uppercase border-2',
                                            currentTierStyle.lightBg,
                                            currentTierStyle.border,
                                            currentTierStyle.text,
                                            currentTierStyle.glow
                                        ]"
                                    >
                                        <span v-if="props.membership.tier === 'gold'">🏆</span>
                                        <span v-else-if="props.membership.tier === 'silver'">🥈</span>
                                        <span v-else>🥉</span>
                                    </div>
                                    <div>
                                        <h3 class="font-display font-black text-3xl sm:text-4xl uppercase tracking-tight text-white">
                                            {{ props.membership.tier }} Member
                                        </h3>
                                        <p class="text-sm text-courtSlate-400 font-medium">
                                            Diskon Booking Aktif: 
                                            <span class="font-display font-black text-base text-volt">
                                                {{ props.membership.discount_percentage }}%
                                            </span>
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <!-- Col 2: Total Points -->
                            <div class="bg-arena-surface/80 rounded-xl p-5 border border-arena-border">
                                <span class="text-xs font-display uppercase tracking-widest text-courtSlate-400 font-bold block mb-1">
                                    Total Poin Tersedia
                                </span>
                                <div class="flex items-baseline gap-2">
                                    <span class="font-display font-black text-4xl sm:text-5xl text-volt tabular-nums">
                                        {{ props.membership.points }}
                                    </span>
                                    <span class="font-display font-extrabold text-lg text-courtSlate-400 uppercase">
                                        Poin
                                    </span>
                                </div>
                                <p class="mt-2 text-xs text-courtSlate-400">
                                    💡 1 Poin per kelipatan <span class="text-white font-semibold">Rp 10.000</span> dari transaksi berhasil.
                                </p>
                            </div>

                            <!-- Col 3: Tier Progress -->
                            <div class="bg-arena-surface/80 rounded-xl p-5 border border-arena-border space-y-3">
                                <div class="flex justify-between items-center text-xs">
                                    <span class="font-display uppercase tracking-widest text-courtSlate-400 font-bold">
                                        Progress Tier
                                    </span>
                                    <span v-if="props.membership.next_tier" class="font-semibold text-courtSlate-300">
                                        Target: {{ props.membership.target_points }} Poin
                                    </span>
                                    <span v-else class="font-semibold text-volt">
                                        Tier Maksimal!
                                    </span>
                                </div>

                                <!-- Progress Bar Track -->
                                <div class="w-full bg-arena-base rounded-full h-3.5 p-0.5 border border-arena-border overflow-hidden">
                                    <div
                                        class="h-full rounded-full transition-all duration-700"
                                        :class="currentTierStyle.accentBar"
                                        :style="{ width: `${props.membership.progress_percentage}%` }"
                                    />
                                </div>

                                <div class="flex justify-between items-center text-xs text-courtSlate-400">
                                    <span>{{ props.membership.progress_percentage }}% selesai</span>
                                    <span v-if="props.membership.next_tier">
                                        Kurang <strong class="text-white">{{ props.membership.points_needed }}</strong> poin ke 
                                        <span class="uppercase font-bold text-volt ms-1">{{ props.membership.next_tier }}</span>
                                    </span>
                                    <span v-else class="text-amber-400 font-medium">
                                        Gold Champion VIP ⭐
                                    </span>
                                </div>
                            </div>

                        </div>
                    </div>
                </div>

                <!-- TIER BENEFITS COMPARISON -->
                <div class="space-y-4">
                    <div class="flex items-center justify-between">
                        <h3 class="font-display font-black text-xl text-courtSlate-900 uppercase tracking-tight">
                            Manfaat & Keuntungan Tier
                        </h3>
                        <span class="text-xs text-courtSlate-500">
                            Diskon langsung dipotong otomatis saat booking
                        </span>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <div
                            v-for="benefit in props.tierBenefits"
                            :key="benefit.tier"
                            :class="[
                                'relative rounded-xl p-6 border transition-all duration-200',
                                props.membership.tier === benefit.tier
                                    ? 'bg-white border-volt ring-2 ring-volt/40 shadow-card-active'
                                    : 'bg-white border-courtSlate-200 shadow-sm hover:border-courtSlate-300'
                            ]"
                        >
                            <!-- Selected Tier Tag -->
                            <div
                                v-if="props.membership.tier === benefit.tier"
                                class="absolute -top-3 right-4 px-3 py-0.5 bg-volt text-volt-contrast text-[11px] font-display font-black uppercase tracking-wider rounded -skew-x-6 shadow-volt-glow-sm"
                            >
                                <span class="inline-block transform skew-x-6">Tier Anda Saat Ini</span>
                            </div>

                            <div class="flex items-center gap-3 mb-4">
                                <span class="text-2xl">
                                    <template v-if="benefit.tier === 'gold'">🏆</template>
                                    <template v-else-if="benefit.tier === 'silver'">🥈</template>
                                    <template v-else>🥉</template>
                                </span>
                                <div>
                                    <h4 class="font-display font-black text-lg text-courtSlate-900 uppercase">
                                        {{ benefit.name }}
                                    </h4>
                                    <p class="text-xs text-courtSlate-500 font-medium">
                                        <template v-if="benefit.max_points">
                                            {{ benefit.min_points }} - {{ benefit.max_points }} Poin
                                        </template>
                                        <template v-else>
                                            ≥ {{ benefit.min_points }} Poin
                                        </template>
                                    </p>
                                </div>
                            </div>

                            <div class="mb-4 pb-4 border-b border-courtSlate-100">
                                <div class="flex items-baseline gap-1">
                                    <span class="font-display font-black text-3xl text-courtSlate-900">
                                        {{ benefit.discount }}%
                                    </span>
                                    <span class="text-xs text-courtSlate-500 uppercase font-semibold">
                                        Diskon Booking
                                    </span>
                                </div>
                            </div>

                            <ul class="space-y-2.5 text-xs text-courtSlate-600">
                                <li
                                    v-for="(perk, idx) in benefit.perks"
                                    :key="idx"
                                    class="flex items-start gap-2"
                                >
                                    <svg
                                        class="h-4 w-4 text-emerald-500 shrink-0 mt-0.5"
                                        fill="none"
                                        viewBox="0 0 24 24"
                                        stroke="currentColor"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2.5"
                                            d="M5 13l4 4L19 7"
                                        />
                                    </svg>
                                    <span>{{ perk }}</span>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>

                <!-- RIWAYAT PEROLEHAN POIN -->
                <div class="bg-white rounded-xl border border-courtSlate-200 shadow-sm overflow-hidden">
                    <div class="p-6 border-b border-courtSlate-200 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
                        <div>
                            <h3 class="font-display font-black text-xl text-courtSlate-900 uppercase tracking-tight">
                                Riwayat Perolehan Poin
                            </h3>
                            <p class="text-xs text-courtSlate-500">
                                Catatan seluruh mutasi poin loyalitas Anda
                            </p>
                        </div>
                        <span class="text-xs text-courtSlate-400 font-medium">
                            Total {{ props.pointHistories.length }} catatan
                        </span>
                    </div>

                    <!-- Point Histories Table -->
                    <div v-if="props.pointHistories.length > 0" class="overflow-x-auto">
                        <table class="w-full text-left text-sm text-courtSlate-600">
                            <thead class="bg-courtSlate-50 text-xs font-display font-extrabold uppercase tracking-wider text-courtSlate-500 border-b border-courtSlate-200">
                                <tr>
                                    <th scope="col" class="px-6 py-3">Tanggal & Waktu</th>
                                    <th scope="col" class="px-6 py-3">Aktivitas / Deskripsi</th>
                                    <th scope="col" class="px-6 py-3">Lapangan / Booking</th>
                                    <th scope="col" class="px-6 py-3 text-right">Poin</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-courtSlate-100 font-medium">
                                <tr
                                    v-for="item in props.pointHistories"
                                    :key="item.id"
                                    class="hover:bg-courtSlate-50/50 transition-colors"
                                >
                                    <td class="px-6 py-4 whitespace-nowrap text-xs text-courtSlate-500 tabular-nums">
                                        {{ formatDate(item.created_at) }}
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="text-courtSlate-900 font-semibold">
                                            {{ item.description }}
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-xs">
                                        <span v-if="item.booking && item.booking.court" class="inline-flex items-center gap-1.5 px-2 py-0.5 bg-courtSlate-100 text-courtSlate-700 rounded font-medium">
                                            🏸 {{ item.booking.court.name }}
                                        </span>
                                        <span v-else class="text-courtSlate-400">-</span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right">
                                        <span
                                            v-if="item.points_earned > 0"
                                            class="inline-flex items-center gap-1 font-display font-black text-sm text-emerald-600"
                                        >
                                            +{{ item.points_earned }} Poin
                                        </span>
                                        <span
                                            v-else-if="item.points_used > 0"
                                            class="inline-flex items-center gap-1 font-display font-black text-sm text-courtOrange"
                                        >
                                            -{{ item.points_used }} Poin
                                        </span>
                                        <span v-else class="text-courtSlate-400 font-display font-bold">
                                            0
                                        </span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Empty State -->
                    <div v-else class="p-12 text-center">
                        <div class="w-16 h-16 mx-auto mb-4 rounded-full bg-courtSlate-100 flex items-center justify-center text-2xl text-courtSlate-400">
                            ⭐
                        </div>
                        <h4 class="font-display font-black text-lg text-courtSlate-900 uppercase">
                            Belum Ada Riwayat Poin
                        </h4>
                        <p class="mt-1 text-sm text-courtSlate-500 max-w-md mx-auto">
                            Lakukan booking lapangan pertama Anda dan selesaikan pembayaran untuk mulai mengumpulkan poin loyalty!
                        </p>
                        <div class="mt-6">
                            <Link
                                :href="route('bookings.create')"
                                class="inline-flex items-center gap-2 px-5 py-2.5 bg-volt text-volt-contrast font-display font-black text-sm uppercase tracking-wider rounded-lg shadow-volt-glow-sm hover:bg-volt-hover transition -skew-x-6"
                            >
                                <span class="inline-block transform skew-x-6">
                                    Mulai Booking Sekarang
                                </span>
                            </Link>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </AuthenticatedLayout>
</template>

