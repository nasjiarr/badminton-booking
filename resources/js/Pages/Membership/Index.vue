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

// Tier visual system: Bronze → warm amber, Silver → cool slate, Gold → rich amber-gold
// All harmonized with the main design palette (arena-base, volt, courtOrange)
const getTierVisuals = (tier) => {
    switch (tier) {
        case 'gold':
            return {
                icon: '🏆',
                label: 'Gold Champion',
                cardBg: 'bg-gradient-to-br from-amber-400/20 via-yellow-300/10 to-arena-card',
                cardBorder: 'border-amber-400/60',
                cardGlow: 'shadow-[0_0_30px_rgba(245,158,11,0.20)]',
                progressTrack: 'bg-gradient-to-r from-amber-400 via-yellow-400 to-amber-500',
                badgeBg: 'bg-amber-100 text-amber-900 border-amber-300',
                accentText: 'text-amber-400',
                accentBg: 'bg-amber-400',
                benefitRing: 'ring-amber-400/50 border-amber-300',
                dotColor: 'bg-amber-400',
            };
        case 'silver':
            return {
                icon: '🥈',
                label: 'Silver Pro',
                cardBg: 'bg-gradient-to-br from-slate-300/20 via-slate-200/10 to-arena-card',
                cardBorder: 'border-slate-400/60',
                cardGlow: 'shadow-[0_0_30px_rgba(148,163,184,0.20)]',
                progressTrack: 'bg-gradient-to-r from-slate-400 via-slate-300 to-slate-200',
                badgeBg: 'bg-slate-100 text-slate-800 border-slate-300',
                accentText: 'text-slate-300',
                accentBg: 'bg-slate-400',
                benefitRing: 'ring-slate-400/50 border-slate-300',
                dotColor: 'bg-slate-400',
            };
        default: // bronze
            return {
                icon: '🥉',
                label: 'Bronze Rookie',
                cardBg: 'bg-gradient-to-br from-amber-700/20 via-amber-600/10 to-arena-card',
                cardBorder: 'border-amber-600/60',
                cardGlow: 'shadow-[0_0_30px_rgba(180,83,9,0.15)]',
                progressTrack: 'bg-gradient-to-r from-amber-700 via-amber-600 to-amber-500',
                badgeBg: 'bg-orange-50 text-orange-900 border-orange-200',
                accentText: 'text-amber-600',
                accentBg: 'bg-amber-700',
                benefitRing: 'ring-amber-600/50 border-amber-500',
                dotColor: 'bg-amber-600',
            };
    }
};

const tierV = getTierVisuals(props.membership.tier);
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
                                tierV.badgeBg
                            ]"
                        >
                            <span class="inline-block transform skew-x-6">
                                {{ tierV.icon }} {{ props.membership.tier }}
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
                        class="inline-flex items-center gap-2 px-5 py-2.5 bg-arena-base text-volt font-display font-black text-xs uppercase tracking-wider rounded-xl shadow-sm hover:bg-arena-surface hover:shadow-volt-glow-sm transition-all -skew-x-3 cursor-pointer"
                    >
                        <span class="inline-block transform skew-x-3">
                            🏸 Booking Lapangan
                        </span>
                    </Link>
                </div>
            </div>
        </template>

        <div class="py-8">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-8">

                <!-- HERO STATUS CARD -->
                <div
                    :class="[
                        'relative overflow-hidden rounded-2xl border-2 text-white',
                        tierV.cardBg, tierV.cardBorder, tierV.cardGlow
                    ]"
                >
                    <!-- Ambient background decoration -->
                    <div class="absolute inset-0 pointer-events-none opacity-10">
                        <div class="absolute -top-10 -right-10 w-64 h-64 rounded-full" :class="tierV.accentBg"></div>
                        <div class="absolute -bottom-16 -left-16 w-48 h-48 rounded-full" :class="tierV.accentBg"></div>
                    </div>

                    <div class="relative p-6 sm:p-8">
                        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 items-center">
                            
                            <!-- Col 1: Tier Badge & Identity -->
                            <div class="space-y-4">
                                <span class="text-[10px] font-display uppercase tracking-widest text-courtSlate-400 font-bold">
                                    Status Keanggotaan
                                </span>
                                <div class="flex items-center gap-4">
                                    <div
                                        :class="[
                                            'w-16 h-16 rounded-2xl flex items-center justify-center text-3xl border-2',
                                            tierV.cardBorder, tierV.cardGlow
                                        ]"
                                        class="bg-arena-surface/80"
                                    >
                                        {{ tierV.icon }}
                                    </div>
                                    <div>
                                        <h3 class="font-display font-black text-3xl sm:text-4xl uppercase tracking-tight text-white">
                                            {{ tierV.label }}
                                        </h3>
                                        <p class="text-sm text-courtSlate-400 font-medium mt-0.5">
                                            Diskon Aktif: 
                                            <span class="font-display font-black text-lg text-volt">
                                                {{ props.membership.discount_percentage }}%
                                            </span>
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <!-- Col 2: Total Points — big athletic number -->
                            <div class="bg-arena-surface/60 rounded-2xl p-5 border border-arena-border backdrop-blur-sm">
                                <span class="text-[10px] font-display uppercase tracking-widest text-courtSlate-400 font-bold block mb-2">
                                    Total Poin Tersedia
                                </span>
                                <div class="flex items-baseline gap-2">
                                    <span class="athletic-number font-black text-5xl sm:text-6xl text-volt leading-none">
                                        {{ props.membership.points }}
                                    </span>
                                    <span class="font-display font-extrabold text-base text-courtSlate-400 uppercase">
                                        Poin
                                    </span>
                                </div>
                                <p class="mt-3 text-xs text-courtSlate-400 flex items-center gap-1.5">
                                    <span class="w-1.5 h-1.5 rounded-full bg-volt"></span>
                                    1 Poin per kelipatan <span class="text-white font-semibold">Rp 10.000</span> dari transaksi berhasil.
                                </p>
                            </div>

                            <!-- Col 3: Progress to Next Tier -->
                            <div class="bg-arena-surface/60 rounded-2xl p-5 border border-arena-border backdrop-blur-sm space-y-4">
                                <div class="flex justify-between items-center text-xs">
                                    <span class="font-display uppercase tracking-widest text-courtSlate-400 font-bold text-[10px]">
                                        Progress Tier
                                    </span>
                                    <span v-if="props.membership.next_tier" class="font-semibold text-courtSlate-300">
                                        Target: {{ props.membership.target_points }} Poin
                                    </span>
                                    <span v-else class="font-bold text-volt flex items-center gap-1">
                                        <span>⭐</span> Tier Maksimal!
                                    </span>
                                </div>

                                <!-- Animated Progress Bar with tier-specific color -->
                                <div class="relative w-full bg-arena-base rounded-full h-4 p-0.5 border border-arena-border overflow-hidden">
                                    <div
                                        class="h-full rounded-full transition-all duration-1000 ease-out relative overflow-hidden"
                                        :class="tierV.progressTrack"
                                        :style="{ width: `${props.membership.progress_percentage}%` }"
                                    >
                                        <!-- Shimmer animation -->
                                        <div class="absolute inset-0 bg-gradient-to-r from-transparent via-white/30 to-transparent animate-pulse"></div>
                                    </div>
                                    
                                    <!-- Percentage indicator on the bar -->
                                    <div 
                                        v-if="props.membership.progress_percentage > 15"
                                        class="absolute inset-y-0 flex items-center text-[9px] font-display font-black text-arena-base"
                                        :style="{ left: `${Math.min(props.membership.progress_percentage - 5, 88)}%` }"
                                    >
                                        {{ props.membership.progress_percentage }}%
                                    </div>
                                </div>

                                <!-- Progress text -->
                                <div class="flex justify-between items-center text-xs text-courtSlate-400">
                                    <span>{{ props.membership.progress_percentage }}% tercapai</span>
                                    <span v-if="props.membership.next_tier">
                                        Butuh <strong class="text-white">{{ props.membership.points_needed }}</strong> poin lagi ke
                                        <span :class="['uppercase font-display font-black ml-0.5', getTierVisuals(props.membership.next_tier).accentText]">
                                            {{ props.membership.next_tier }}
                                        </span>
                                    </span>
                                    <span v-else :class="[tierV.accentText, 'font-bold']">
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

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
                        <div
                            v-for="benefit in props.tierBenefits"
                            :key="benefit.tier"
                            :class="[
                                'relative rounded-2xl p-6 border-2 transition-all duration-200',
                                props.membership.tier === benefit.tier
                                    ? `bg-white ${getTierVisuals(benefit.tier).benefitRing} ring-2 shadow-card-active`
                                    : 'bg-white border-courtSlate-200 shadow-sm hover:border-courtSlate-300'
                            ]"
                        >
                            <!-- Selected Tier Tag -->
                            <div
                                v-if="props.membership.tier === benefit.tier"
                                class="absolute -top-3 right-4 px-3 py-0.5 bg-volt text-volt-contrast text-[10px] font-display font-black uppercase tracking-wider rounded -skew-x-6 shadow-volt-glow-sm"
                            >
                                <span class="inline-block transform skew-x-6">Tier Anda</span>
                            </div>

                            <div class="flex items-center gap-3 mb-4">
                                <div
                                    :class="[
                                        'w-10 h-10 rounded-xl flex items-center justify-center text-lg border',
                                        getTierVisuals(benefit.tier).badgeBg
                                    ]"
                                >
                                    {{ getTierVisuals(benefit.tier).icon }}
                                </div>
                                <div>
                                    <h4 class="font-display font-black text-base text-courtSlate-900 uppercase">
                                        {{ benefit.name }}
                                    </h4>
                                    <p class="text-[11px] text-courtSlate-500 font-medium">
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
                                    <span class="athletic-number font-black text-3xl text-courtSlate-900">
                                        {{ benefit.discount }}%
                                    </span>
                                    <span class="text-xs text-courtSlate-500 uppercase font-display font-bold">
                                        Diskon
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
                                        class="h-4 w-4 shrink-0 mt-0.5"
                                        :class="getTierVisuals(benefit.tier).accentText"
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
                <div class="bg-white rounded-2xl border-2 border-courtSlate-200 shadow-card-elevated overflow-hidden">
                    <div class="p-5 sm:p-6 border-b-2 border-courtSlate-200 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
                        <div>
                            <h3 class="font-display font-black text-xl text-courtSlate-900 uppercase tracking-tight">
                                Riwayat Perolehan Poin
                            </h3>
                            <p class="text-xs text-courtSlate-500 mt-0.5">
                                Catatan seluruh mutasi poin loyalitas Anda
                            </p>
                        </div>
                        <span class="text-xs text-courtSlate-400 font-display font-bold uppercase tracking-wider">
                            {{ props.pointHistories.length }} Catatan
                        </span>
                    </div>

                    <!-- Point Histories as List Cards (not table, better mobile) -->
                    <div v-if="props.pointHistories.length > 0" class="divide-y divide-courtSlate-100">
                        <div
                            v-for="item in props.pointHistories"
                            :key="item.id"
                            class="flex items-center gap-4 px-5 sm:px-6 py-4 hover:bg-courtSlate-50/50 transition"
                        >
                            <!-- +/- Icon -->
                            <div class="shrink-0">
                                <div
                                    v-if="item.points_earned > 0"
                                    class="w-9 h-9 rounded-xl bg-emerald-100 border border-emerald-300 flex items-center justify-center"
                                >
                                    <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                                    </svg>
                                </div>
                                <div
                                    v-else-if="item.points_used > 0"
                                    class="w-9 h-9 rounded-xl bg-courtOrange/10 border border-courtOrange/30 flex items-center justify-center"
                                >
                                    <svg class="w-4 h-4 text-courtOrange" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M20 12H4" />
                                    </svg>
                                </div>
                                <div
                                    v-else
                                    class="w-9 h-9 rounded-xl bg-courtSlate-100 border border-courtSlate-200 flex items-center justify-center"
                                >
                                    <span class="text-courtSlate-400 text-xs font-bold">0</span>
                                </div>
                            </div>

                            <!-- Description & Court -->
                            <div class="flex-1 min-w-0">
                                <div class="text-sm font-semibold text-courtSlate-900 truncate">
                                    {{ item.description }}
                                </div>
                                <div class="flex items-center gap-2 mt-0.5">
                                    <span class="text-[11px] text-courtSlate-500 tabular-nums">
                                        {{ formatDate(item.created_at) }}
                                    </span>
                                    <span v-if="item.booking && item.booking.court" class="inline-flex items-center gap-1 px-1.5 py-0.5 bg-courtSlate-100 text-courtSlate-700 rounded text-[10px] font-display font-bold">
                                        🏸 {{ item.booking.court.name }}
                                    </span>
                                </div>
                            </div>

                            <!-- Points Value -->
                            <div class="shrink-0 text-right">
                                <span
                                    v-if="item.points_earned > 0"
                                    class="inline-flex items-center gap-0.5 font-display font-black text-sm text-emerald-600"
                                >
                                    +{{ item.points_earned }}
                                    <span class="text-[10px] font-bold text-emerald-500 ml-0.5">Poin</span>
                                </span>
                                <span
                                    v-else-if="item.points_used > 0"
                                    class="inline-flex items-center gap-0.5 font-display font-black text-sm text-courtOrange"
                                >
                                    -{{ item.points_used }}
                                    <span class="text-[10px] font-bold text-courtOrange/80 ml-0.5">Poin</span>
                                </span>
                                <span v-else class="text-courtSlate-400 font-display font-bold text-sm">
                                    0
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Empty State -->
                    <div v-else class="p-12 text-center">
                        <div class="w-14 h-14 mx-auto mb-4 rounded-2xl bg-courtSlate-100 border border-courtSlate-200 flex items-center justify-center text-2xl text-courtSlate-400">
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
                                class="inline-flex items-center gap-2 px-5 py-2.5 bg-arena-base text-volt font-display font-black text-xs uppercase tracking-wider rounded-xl shadow-sm hover:bg-arena-surface hover:shadow-volt-glow-sm transition-all -skew-x-3 cursor-pointer"
                            >
                                <span class="inline-block transform skew-x-3">
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
