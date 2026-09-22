<script setup>
import { ref } from 'vue';
import { Link } from '@inertiajs/vue3';
import Toast from '@/Components/Toast.vue';
import PwaInstallPrompt from '@/Components/PwaInstallPrompt.vue';
import Dropdown from '@/Components/Dropdown.vue';
import DropdownLink from '@/Components/DropdownLink.vue';
import UserSidebar from '@/Components/UserSidebar.vue';

const sidebarOpen = ref(false);
</script>

<template>
    <div class="min-h-screen bg-courtSlate-50">
        <Toast />
        <PwaInstallPrompt />

        <!-- Mobile sidebar overlay with smooth fade animation -->
        <Transition
            enter-active-class="transition-opacity ease-linear duration-300"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition-opacity ease-linear duration-300"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div
                v-if="sidebarOpen"
                class="fixed inset-0 z-40 bg-arena-base/80 backdrop-blur-xs lg:hidden"
                @click="sidebarOpen = false"
            />
        </Transition>

        <!-- Mobile sidebar drawer with smooth slide animation -->
        <Transition
            enter-active-class="transition ease-in-out duration-300 transform"
            enter-from-class="-translate-x-full"
            enter-to-class="translate-x-0"
            leave-active-class="transition ease-in-out duration-300 transform"
            leave-from-class="translate-x-0"
            leave-to-class="-translate-x-full"
        >
            <div
                v-if="sidebarOpen"
                class="fixed inset-y-0 left-0 z-50 flex w-64 flex-col lg:hidden shadow-2xl"
            >
                <UserSidebar
                    :is-mobile="true"
                    @close="sidebarOpen = false"
                    @navigate="sidebarOpen = false"
                />
            </div>
        </Transition>

        <!-- Desktop sidebar (fixed left) -->
        <div class="hidden lg:fixed lg:inset-y-0 lg:flex lg:w-64 lg:flex-col">
            <UserSidebar />
        </div>

        <!-- Main content area -->
        <div class="flex flex-col lg:pl-64 min-h-screen">
            <!-- Top header bar -->
            <div class="sticky top-0 z-30 flex h-16 shrink-0 items-center justify-between border-b border-courtSlate-200 bg-white px-4 sm:px-6 lg:px-8 shadow-xs">
                <!-- Mobile hamburger button -->
                <div class="flex items-center gap-3">
                    <button
                        type="button"
                        class="-m-2 p-2 rounded-xl text-courtSlate-700 hover:bg-courtSlate-100 lg:hidden transition"
                        @click="sidebarOpen = true"
                        aria-label="Buka menu navigasi"
                    >
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" />
                        </svg>
                    </button>

                    <div class="flex items-center gap-2 lg:hidden">
                        <span class="font-display font-black text-lg tracking-tight uppercase text-courtSlate-900">
                            SMASH <span class="text-volt-deep">ARENA</span>
                        </span>
                    </div>
                </div>

                <!-- Right items: Quick badges & Profile dropdown -->
                <div class="flex items-center gap-3">
                    <!-- Membership Points Badge -->
                    <Link
                        v-if="$page.props.auth.user.membership"
                        :href="route('membership.index')"
                        :class="[
                            'hidden sm:inline-flex items-center gap-1.5 px-3 py-1 text-xs font-display font-black tracking-wider uppercase rounded -skew-x-6 border transition hover:opacity-90',
                            $page.props.auth.user.membership.tier === 'gold'
                                ? 'bg-amber-100 text-amber-900 border-amber-300'
                                : $page.props.auth.user.membership.tier === 'silver'
                                ? 'bg-slate-100 text-slate-700 border-slate-300'
                                : 'bg-orange-50 text-orange-800 border-orange-200'
                        ]"
                    >
                        <span class="inline-block transform skew-x-6">
                            ⭐ {{ $page.props.auth.user.membership.points }} Pts &bull; Tier {{ $page.props.auth.user.membership.tier }}
                        </span>
                    </Link>

                    <!-- Quick Booking Button -->
                    <Link
                        :href="route('bookings.create')"
                        class="hidden md:inline-flex items-center gap-1.5 px-3.5 py-1.5 bg-volt text-volt-contrast font-display font-black text-xs uppercase tracking-wider rounded-lg shadow-volt-glow-sm hover:bg-volt-hover transition -skew-x-3 cursor-pointer"
                    >
                        <span class="inline-block transform skew-x-3">🏸 Booking</span>
                    </Link>

                    <!-- Profile Dropdown -->
                    <div class="relative">
                        <Dropdown align="right" width="48">
                            <template #trigger>
                                <button
                                    type="button"
                                    class="inline-flex items-center gap-2 rounded-xl border border-courtSlate-200 bg-courtSlate-50 px-3 py-1.5 text-xs font-display font-bold uppercase tracking-wider text-courtSlate-700 transition hover:bg-courtSlate-100 hover:text-courtSlate-900 focus:outline-none"
                                >
                                    <div class="w-6 h-6 rounded-lg bg-arena-base text-volt flex items-center justify-center text-xs font-black">
                                        {{ $page.props.auth.user.name.charAt(0).toUpperCase() }}
                                    </div>
                                    <span class="max-w-[120px] truncate">{{ $page.props.auth.user.name }}</span>
                                    <svg class="h-3.5 w-3.5 text-courtSlate-400" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                    </svg>
                                </button>
                            </template>

                            <template #content>
                                <div class="px-4 py-2 border-b border-courtSlate-100 text-xs">
                                    <div class="font-bold text-courtSlate-800">{{ $page.props.auth.user.name }}</div>
                                    <div class="text-[11px] text-courtSlate-400 truncate">{{ $page.props.auth.user.email }}</div>
                                </div>
                                <DropdownLink
                                    v-if="$page.props.auth.user.is_admin"
                                    :href="route('admin.dashboard')"
                                >
                                    🏸 Panel Admin
                                </DropdownLink>
                                <DropdownLink :href="route('membership.index')">
                                    ⭐ Membership Saya
                                </DropdownLink>
                                <DropdownLink :href="route('profile.edit')">
                                    Pengaturan Profil
                                </DropdownLink>
                                <DropdownLink :href="route('logout')" method="post" as="button">
                                    Log Out
                                </DropdownLink>
                            </template>
                        </Dropdown>
                    </div>
                </div>
            </div>

            <!-- Page Heading Slot (if provided by page) -->
            <header v-if="$slots.header" class="bg-white border-b border-courtSlate-200 shadow-xs">
                <div class="px-4 py-5 sm:px-6 lg:px-8">
                    <slot name="header" />
                </div>
            </header>

            <!-- Page Content -->
            <main class="flex-1 bg-courtSlate-50">
                <slot />
            </main>
        </div>
    </div>
</template>
