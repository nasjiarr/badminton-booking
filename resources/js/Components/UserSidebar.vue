<script setup>
import { computed } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import SidebarLink from '@/Components/SidebarLink.vue';

defineProps({
    isMobile: {
        type: Boolean,
        default: false,
    },
});

const emit = defineEmits(['navigate', 'close']);

const page = usePage();
const user = computed(() => page.props.auth.user);

const navigation = computed(() => [
    {
        name: 'Dashboard',
        href: route('dashboard'),
        icon: 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6',
        active: route().current('dashboard'),
    },
    {
        name: 'Booking Lapangan',
        href: route('bookings.create'),
        icon: 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z',
        active: route().current('bookings.*'),
    },
    {
        name: 'Booking Saya',
        href: route('my-bookings.index'),
        icon: 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01',
        active: route().current('my-bookings.*'),
    },
    {
        name: 'Cari Lapangan',
        href: route('courts.search'),
        icon: 'M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z',
        active: route().current('courts.search'),
    },
    {
        name: 'Booking Rutin Saya',
        href: route('recurring-bookings.index'),
        icon: 'M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15',
        active: route().current('recurring-bookings.*'),
    },
    {
        name: 'Membership Saya',
        href: route('membership.index'),
        icon: 'M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z',
        active: route().current('membership.*'),
        badge: user.value?.membership ? `Tier ${user.value.membership.tier}` : null,
    },
    {
        name: 'Pengaturan Profil',
        href: route('profile.edit'),
        icon: 'M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z',
        active: route().current('profile.edit'),
    },
]);
</script>

<template>
    <div class="flex h-full min-h-0 flex-1 flex-col bg-arena-base border-r border-arena-border">
        <!-- Brand Header Section -->
        <div class="flex h-16 items-center justify-between px-5 border-b border-arena-border bg-arena-card/40">
            <Link :href="route('dashboard')" @click="$emit('navigate')" class="flex items-center gap-2">
                <div class="w-8 h-8 rounded-xl bg-volt flex items-center justify-center font-display font-black text-lg text-arena-base shadow-volt-glow-sm -skew-x-6">
                    <span class="transform skew-x-6">🏸</span>
                </div>
                <span class="text-xl font-display font-black tracking-wider text-white uppercase flex items-center gap-1.5">
                    SMASH <span class="text-volt">ARENA</span>
                </span>
                <span class="ml-1 text-[9px] font-display font-black tracking-widest uppercase bg-arena-surface text-courtSlate-300 border border-arena-border px-1.5 py-0.2 rounded -skew-x-6">
                    <span class="skew-x-6">MEMBER</span>
                </span>
            </Link>

            <!-- Close button for mobile drawer -->
            <button
                v-if="isMobile"
                type="button"
                @click="$emit('close')"
                class="rounded-lg p-1.5 text-courtSlate-400 hover:bg-arena-surface hover:text-white transition lg:hidden"
                aria-label="Tutup menu"
            >
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>

        <!-- User Identity Mini Card in Sidebar -->
        <div class="px-4 py-3.5 border-b border-arena-border/70 bg-arena-card/20">
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-xl bg-arena-surface border border-arena-border flex items-center justify-center font-display font-black text-sm text-volt shrink-0 shadow-xs">
                    {{ user?.name ? user.name.charAt(0).toUpperCase() : 'U' }}
                </div>
                <div class="min-w-0 flex-1">
                    <div class="truncate text-xs font-bold text-white leading-tight">
                        {{ user?.name }}
                    </div>
                    <div class="flex items-center gap-1.5 mt-0.5">
                        <span
                            v-if="user?.membership"
                            :class="[
                                'inline-flex items-center px-1.5 py-0.2 rounded text-[9px] font-display font-black tracking-wider uppercase border -skew-x-6',
                                user.membership.tier === 'gold'
                                    ? 'bg-amber-100 text-amber-900 border-amber-300'
                                    : user.membership.tier === 'silver'
                                    ? 'bg-slate-100 text-slate-800 border-slate-300'
                                    : 'bg-orange-100 text-orange-900 border-orange-200'
                            ]"
                        >
                            <span class="skew-x-6">{{ user.membership.tier }}</span>
                        </span>
                        <span class="text-[10px] text-courtSlate-400 font-display font-bold">
                            {{ user?.membership?.points ?? 0 }} Pts
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Navigation Items -->
        <nav class="flex-1 space-y-1 px-3 py-4 overflow-y-auto">
            <SidebarLink
                v-for="item in navigation"
                :key="item.name"
                :href="item.href"
                :name="item.name"
                :icon="item.icon"
                :active="item.active"
                :badge="item.badge"
                @navigate="$emit('navigate')"
            />

            <!-- Admin Switcher Link (if user is admin) -->
            <div v-if="user?.is_admin" class="pt-3 mt-3 border-t border-arena-border/60">
                <span class="px-3 text-[10px] font-display font-black uppercase tracking-widest text-courtSlate-500 block mb-1">
                    Akses Khusus
                </span>
                <Link
                    :href="route('admin.dashboard')"
                    @click="$emit('navigate')"
                    class="group flex items-center rounded-r-xl px-3 py-2 text-xs font-display font-bold uppercase tracking-wider text-courtSlate-400 hover:bg-arena-card hover:text-volt transition"
                >
                    <svg class="mr-3 h-4 w-4 text-courtSlate-500 group-hover:text-volt" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 6h9.75M10.5 6a1.5 1.5 0 11-3 0m3 0a1.5 1.5 0 10-3 0M3.75 6H7.5m3 12h9.75m-9.75 0a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m-3.75 0H7.5m9-6h3.75m-3.75 0a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m-9.75 0h9.75" />
                    </svg>
                    <span>🏸 Panel Admin</span>
                </Link>
            </div>
        </nav>

        <!-- Bottom Action: Logout Button -->
        <div class="p-3 border-t border-arena-border bg-arena-card/30">
            <Link
                :href="route('logout')"
                method="post"
                as="button"
                @click="$emit('navigate')"
                class="group flex w-full items-center rounded-xl px-3 py-2.5 text-xs font-display font-black uppercase tracking-wider text-courtSlate-400 hover:bg-courtOrange/15 hover:text-courtOrange transition-all duration-150 cursor-pointer"
            >
                <svg
                    class="mr-3 h-4 w-4 text-courtSlate-500 group-hover:text-courtOrange transition-colors"
                    fill="none"
                    viewBox="0 0 24 24"
                    stroke="currentColor"
                    stroke-width="2"
                >
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15M12 9l-3 3m0 0l3 3m-3-3h12.75" />
                </svg>
                <span>Keluar (Log Out)</span>
            </Link>
        </div>
    </div>
</template>

