<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
</script>

<template>
    <Head title="Dashboard" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div class="flex items-center gap-3">
                    <h2 class="font-display font-black text-2xl sm:text-3xl uppercase tracking-tight text-courtSlate-900">
                        Selamat Datang, {{ $page.props.auth.user.name }}!
                    </h2>
                    <span class="court-badge-volt text-xs"><span>Member Area</span></span>
                </div>

                <div class="flex items-center gap-2">
                    <Link
                        :href="route('bookings.create')"
                        class="inline-flex items-center gap-2 px-5 py-2.5 bg-volt text-volt-contrast font-display font-black text-xs uppercase tracking-wider rounded-xl shadow-volt-glow-sm hover:bg-volt-hover transition active:scale-[0.98] -skew-x-3 cursor-pointer"
                    >
                        <span class="inline-flex items-center gap-1.5 skew-x-3">
                            <span>🏸 Pesan Lapangan</span>
                        </span>
                    </Link>
                </div>
            </div>
        </template>

        <div class="py-8 bg-arena-light min-h-screen">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-8">
                <!-- Member Status Card -->
                <div
                    v-if="$page.props.auth.user.membership"
                    class="relative overflow-hidden rounded-2xl bg-arena-card border-2 border-arena-border p-6 text-white shadow-card-elevated"
                >
                    <div class="absolute inset-0 bg-gradient-to-r from-volt/10 via-transparent to-transparent pointer-events-none"></div>
                    <div class="relative flex flex-col md:flex-row md:items-center justify-between gap-6">
                        <div class="flex items-center gap-4">
                            <div class="w-14 h-14 rounded-2xl bg-arena-surface border border-arena-border flex items-center justify-center text-3xl shrink-0">
                                <span v-if="$page.props.auth.user.membership.tier === 'gold'">🏆</span>
                                <span v-else-if="$page.props.auth.user.membership.tier === 'silver'">🥈</span>
                                <span v-else>🥉</span>
                            </div>
                            <div>
                                <div class="flex items-center gap-2 mb-1">
                                    <span class="text-[10px] font-display font-black tracking-widest text-courtSlate-400 uppercase">
                                        Status Keanggotaan
                                    </span>
                                    <span
                                        :class="[
                                            'inline-flex items-center px-2 py-0.2 rounded text-[10px] font-display font-black uppercase tracking-wider border -skew-x-6',
                                            $page.props.auth.user.membership.tier === 'gold'
                                                ? 'bg-amber-100 text-amber-900 border-amber-300'
                                                : $page.props.auth.user.membership.tier === 'silver'
                                                ? 'bg-slate-100 text-slate-800 border-slate-300'
                                                : 'bg-orange-100 text-orange-900 border-orange-200'
                                        ]"
                                    >
                                        <span class="skew-x-6">Tier {{ $page.props.auth.user.membership.tier }}</span>
                                    </span>
                                </div>
                                <h3 class="font-display font-black text-2xl uppercase tracking-tight text-white">
                                    {{ $page.props.auth.user.membership.tier }} Member
                                </h3>
                                <p class="text-xs text-courtSlate-400 mt-0.5">
                                    Diskon Aktif: <strong class="text-volt font-display font-black">{{ $page.props.auth.user.membership.discount_percentage }}%</strong> untuk setiap booking lapangan.
                                </p>
                            </div>
                        </div>

                        <div class="flex items-center gap-6">
                            <div class="text-left md:text-right">
                                <span class="text-[10px] font-display font-black uppercase tracking-widest text-courtSlate-400 block">
                                    Poin Loyalitas
                                </span>
                                <div class="flex items-baseline md:justify-end gap-1.5">
                                    <span class="athletic-number font-black text-3xl sm:text-4xl text-volt leading-none">
                                        {{ $page.props.auth.user.membership.points }}
                                    </span>
                                    <span class="font-display font-extrabold text-xs text-courtSlate-400 uppercase">
                                        Poin
                                    </span>
                                </div>
                            </div>

                            <Link
                                :href="route('membership.index')"
                                class="inline-flex items-center gap-1.5 px-4 py-2.5 rounded-xl border border-arena-border bg-arena-surface hover:bg-arena-surface/80 text-white font-display font-black text-xs uppercase tracking-wider transition"
                            >
                                <span>Lihat Benefit</span>
                                <svg class="w-3.5 h-3.5 text-volt" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                                </svg>
                            </Link>
                        </div>
                    </div>
                </div>

                <!-- Quick Action Navigation Hub -->
                <div>
                    <h3 class="font-display font-black text-lg text-courtSlate-900 uppercase tracking-tight mb-4">
                        Menu Utama Smash Arena
                    </h3>

                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
                        <Link
                            :href="route('bookings.create')"
                            class="bg-white rounded-2xl border-2 border-courtSlate-200 p-6 hover:border-volt hover:shadow-card-active hover:-translate-y-1 transition-all duration-200 cursor-pointer group flex flex-col justify-between court-stripe-accent"
                        >
                            <div>
                                <div class="w-12 h-12 rounded-xl bg-volt/10 border border-volt/20 flex items-center justify-center text-2xl mb-4 group-hover:scale-110 transition-transform">
                                    🏸
                                </div>
                                <h4 class="font-display font-black uppercase text-xl text-courtSlate-900 group-hover:text-volt-deep transition-colors tracking-tight">
                                    Booking Lapangan
                                </h4>
                                <p class="text-xs text-courtSlate-500 font-sans mt-1.5 leading-relaxed">
                                    Pesan lapangan badminton langsung dengan sistem pemilihan slot real-time.
                                </p>
                            </div>
                            <div class="mt-4 pt-4 border-t border-courtSlate-100 flex items-center justify-between text-xs font-display font-bold text-volt-deep">
                                <span>Pesan Sekarang</span>
                                <span class="group-hover:translate-x-1 transition-transform">&rarr;</span>
                            </div>
                        </Link>

                        <Link
                            :href="route('courts.search')"
                            class="bg-white rounded-2xl border-2 border-courtSlate-200 p-6 hover:border-volt hover:shadow-card-active hover:-translate-y-1 transition-all duration-200 cursor-pointer group flex flex-col justify-between court-stripe-accent"
                        >
                            <div>
                                <div class="w-12 h-12 rounded-xl bg-courtSlate-100 border border-courtSlate-200 flex items-center justify-center text-2xl mb-4 group-hover:scale-110 transition-transform">
                                    🔍
                                </div>
                                <h4 class="font-display font-black uppercase text-xl text-courtSlate-900 group-hover:text-volt-deep transition-colors tracking-tight">
                                    Cari Lapangan
                                </h4>
                                <p class="text-xs text-courtSlate-500 font-sans mt-1.5 leading-relaxed">
                                    Cari lapangan kosong pada rentang waktu dan tanggal tertentu tanpa bentrok.
                                </p>
                            </div>
                            <div class="mt-4 pt-4 border-t border-courtSlate-100 flex items-center justify-between text-xs font-display font-bold text-volt-deep">
                                <span>Cari Slot Kosong</span>
                                <span class="group-hover:translate-x-1 transition-transform">&rarr;</span>
                            </div>
                        </Link>

                        <Link
                            :href="route('my-bookings.index')"
                            class="bg-white rounded-2xl border-2 border-courtSlate-200 p-6 hover:border-volt hover:shadow-card-active hover:-translate-y-1 transition-all duration-200 cursor-pointer group flex flex-col justify-between court-stripe-accent"
                        >
                            <div>
                                <div class="w-12 h-12 rounded-xl bg-courtSlate-100 border border-courtSlate-200 flex items-center justify-center text-2xl mb-4 group-hover:scale-110 transition-transform">
                                    📋
                                </div>
                                <h4 class="font-display font-black uppercase text-xl text-courtSlate-900 group-hover:text-volt-deep transition-colors tracking-tight">
                                    Booking Saya
                                </h4>
                                <p class="text-xs text-courtSlate-500 font-sans mt-1.5 leading-relaxed">
                                    Kelola jadwal booking, bayar tagihan tertunda, dan lihat bukti invoice.
                                </p>
                            </div>
                            <div class="mt-4 pt-4 border-t border-courtSlate-100 flex items-center justify-between text-xs font-display font-bold text-volt-deep">
                                <span>Lihat Riwayat</span>
                                <span class="group-hover:translate-x-1 transition-transform">&rarr;</span>
                            </div>
                        </Link>

                        <Link
                            :href="route('membership.index')"
                            class="bg-white rounded-2xl border-2 border-courtSlate-200 p-6 hover:border-volt hover:shadow-card-active hover:-translate-y-1 transition-all duration-200 cursor-pointer group flex flex-col justify-between court-stripe-accent"
                        >
                            <div>
                                <div class="w-12 h-12 rounded-xl bg-courtOrange/10 border border-courtOrange/20 flex items-center justify-center text-2xl mb-4 group-hover:scale-110 transition-transform">
                                    ⭐
                                </div>
                                <h4 class="font-display font-black uppercase text-xl text-courtSlate-900 group-hover:text-courtOrange transition-colors tracking-tight">
                                    Membership & Poin
                                </h4>
                                <p class="text-xs text-courtSlate-500 font-sans mt-1.5 leading-relaxed">
                                    Cek akumulasi poin loyalitas, progress tier VIP, dan klaim diskon booking.
                                </p>
                            </div>
                            <div class="mt-4 pt-4 border-t border-courtSlate-100 flex items-center justify-between text-xs font-display font-bold text-courtOrange">
                                <span>Lihat Poin</span>
                                <span class="group-hover:translate-x-1 transition-transform">&rarr;</span>
                            </div>
                        </Link>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
