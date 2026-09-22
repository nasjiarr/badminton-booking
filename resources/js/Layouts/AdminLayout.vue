<script setup>
import { ref } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import Toast from '@/Components/Toast.vue';
import PwaInstallPrompt from '@/Components/PwaInstallPrompt.vue';
import Dropdown from '@/Components/Dropdown.vue';
import DropdownLink from '@/Components/DropdownLink.vue';
import SidebarLink from '@/Components/SidebarLink.vue';

const sidebarOpen = ref(false);

const page = usePage();

const navigation = [
    {
        name: 'Dashboard',
        href: route('admin.dashboard'),
        icon: 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6',
        active: route().current('admin.dashboard'),
    },
    {
        name: 'Manajemen Lapangan',
        href: route('admin.courts.index'),
        icon: 'M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4',
        active: route().current('admin.courts.*'),
    },
    {
        name: 'Booking',
        href: '#',
        icon: 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z',
        active: false,
        disabled: true,
        badge: 'Segera',
    },
    {
        name: 'Laporan',
        href: route('admin.reports.index'),
        icon: 'M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z',
        active: route().current('admin.reports.*'),
        disabled: false,
    },
    {
        name: 'Pengaturan',
        href: '#',
        icon: 'M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.066 2.573c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.573 1.066c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.066-2.573c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z',
        active: false,
        disabled: true,
        badge: 'Segera',
    },
];
</script>

<template>
    <div>
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

        <!-- Mobile sidebar with smooth slide transition -->
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
                class="fixed inset-y-0 left-0 z-50 flex w-64 flex-col bg-arena-base border-r border-arena-border lg:hidden shadow-2xl"
            >
                <div class="flex h-16 items-center justify-between px-5 border-b border-arena-border bg-arena-card/50">
                    <span class="text-xl font-display font-black tracking-wider text-white uppercase flex items-center gap-1.5">
                        🏸 SMASH <span class="text-volt">ARENA</span>
                        <span class="ml-1 text-[10px] font-display font-black tracking-widest uppercase bg-volt text-volt-contrast px-1.5 py-0.2 rounded">ADMIN</span>
                    </span>
                    <button @click="sidebarOpen = false" class="text-courtSlate-400 hover:text-white p-1 rounded-lg">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
                <nav class="flex-1 space-y-1.5 px-3 py-4 overflow-y-auto">
                    <SidebarLink
                        v-for="item in navigation"
                        :key="item.name"
                        :href="item.href"
                        :name="item.name"
                        :icon="item.icon"
                        :active="item.active"
                        :disabled="item.disabled"
                        :badge="item.badge"
                        @navigate="sidebarOpen = false"
                    />

                    <!-- Member Panel Switcher -->
                    <div class="pt-3 mt-3 border-t border-arena-border/60">
                        <span class="px-3 text-[10px] font-display font-black uppercase tracking-widest text-courtSlate-500 block mb-1">
                            Akses Pengguna
                        </span>
                        <Link
                            :href="route('dashboard')"
                            @click="sidebarOpen = false"
                            class="group flex items-center rounded-r-xl px-3 py-2 text-xs font-display font-bold uppercase tracking-wider text-courtSlate-400 hover:bg-arena-card hover:text-volt transition"
                        >
                            <svg class="mr-3 h-4 w-4 text-courtSlate-500 group-hover:text-volt" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z" />
                            </svg>
                            <span>🏸 Area Member</span>
                        </Link>
                    </div>
                </nav>
            </div>
        </Transition>

        <!-- Desktop sidebar -->
        <div class="hidden lg:fixed lg:inset-y-0 lg:flex lg:w-64 lg:flex-col">
            <div class="flex min-h-0 flex-1 flex-col bg-arena-base border-r border-arena-border">
                <div class="flex h-16 items-center px-5 border-b border-arena-border bg-arena-card/40">
                    <span class="text-xl font-display font-black tracking-wider text-white uppercase flex items-center gap-1.5">
                        🏸 SMASH <span class="text-volt">ARENA</span>
                        <span class="ml-1 text-[10px] font-display font-black tracking-widest uppercase bg-volt text-volt-contrast px-1.5 py-0.2 rounded">ADMIN</span>
                    </span>
                </div>
                <nav class="flex-1 space-y-1.5 px-3 py-5 overflow-y-auto">
                    <SidebarLink
                        v-for="item in navigation"
                        :key="item.name"
                        :href="item.href"
                        :name="item.name"
                        :icon="item.icon"
                        :active="item.active"
                        :disabled="item.disabled"
                        :badge="item.badge"
                    />

                    <!-- Member Panel Switcher -->
                    <div class="pt-3 mt-3 border-t border-arena-border/60">
                        <span class="px-3 text-[10px] font-display font-black uppercase tracking-widest text-courtSlate-500 block mb-1">
                            Akses Pengguna
                        </span>
                        <Link
                            :href="route('dashboard')"
                            class="group flex items-center rounded-r-xl px-3 py-2 text-xs font-display font-bold uppercase tracking-wider text-courtSlate-400 hover:bg-arena-card hover:text-volt transition"
                        >
                            <svg class="mr-3 h-4 w-4 text-courtSlate-500 group-hover:text-volt" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z" />
                            </svg>
                            <span>🏸 Area Member</span>
                        </Link>
                    </div>
                </nav>
            </div>
        </div>

        <!-- Main content -->
        <div class="flex flex-col lg:pl-64">
            <!-- Top header bar -->
            <div class="sticky top-0 z-30 flex h-16 shrink-0 items-center border-b border-gray-200 bg-white px-4 shadow-sm sm:px-6 lg:px-8">
                <!-- Mobile hamburger -->
                <button
                    type="button"
                    class="-m-2.5 p-2.5 text-gray-700 lg:hidden"
                    @click="sidebarOpen = true"
                    aria-label="Buka menu navigasi"
                >
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" />
                    </svg>
                </button>

                <div class="flex flex-1 justify-end gap-x-4">
                    <div class="relative">
                        <Dropdown align="right" width="48">
                            <template #trigger>
                                <button
                                    type="button"
                                    class="inline-flex items-center rounded-md border border-transparent bg-white px-3 py-2 text-sm font-medium text-gray-500 transition hover:text-gray-700 focus:outline-none"
                                >
                                    {{ $page.props.auth.user.name }}
                                    <svg class="-me-0.5 ms-2 h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                    </svg>
                                </button>
                            </template>
                            <template #content>
                                <DropdownLink :href="route('dashboard')">🏸 Area Member</DropdownLink>
                                <DropdownLink :href="route('profile.edit')">Profile</DropdownLink>
                                <DropdownLink :href="route('logout')" method="post" as="button">Log Out</DropdownLink>
                            </template>
                        </Dropdown>
                    </div>
                </div>
            </div>

            <!-- Page heading -->
            <header v-if="$slots.header" class="bg-white border-b border-courtSlate-200">
                <div class="px-4 py-5 sm:px-6 lg:px-8">
                    <slot name="header" />
                </div>
            </header>

            <!-- Page content -->
            <main class="flex-1 bg-courtSlate-100 min-h-[calc(100vh-8rem)]">
                <slot />
            </main>
        </div>
    </div>
</template>
