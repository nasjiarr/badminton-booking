<script setup>
import { Head, Link } from '@inertiajs/vue3';

defineProps({
    canLogin: {
        type: Boolean,
        default: true,
    },
    canRegister: {
        type: Boolean,
        default: true,
    },
});
</script>

<template>
    <Head title="Smash Arena — Gelanggang Badminton Kompetisi & Booking Online" />

    <div class="min-h-screen bg-arena-base text-white flex flex-col font-sans selection:bg-volt selection:text-arena-base">
        <!-- Top Navigation Bar -->
        <header class="border-b border-arena-border bg-arena-base/80 backdrop-blur-md sticky top-0 z-50">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-20 flex items-center justify-between">
                <!-- Brand Logo -->
                <div class="flex items-center gap-3">
                    <div class="w-11 h-11 rounded-2xl bg-volt flex items-center justify-center font-display font-black text-2xl text-arena-base shadow-volt-glow-sm -skew-x-6">
                        <span class="transform skew-x-6">🏸</span>
                    </div>
                    <div>
                        <div class="flex items-center gap-2">
                            <span class="font-display font-black text-2xl tracking-tight uppercase text-white">
                                SMASH ARENA
                            </span>
                            <span class="court-badge-volt text-[10px] py-0.2"><span>BWF CLASS</span></span>
                        </div>
                        <p class="text-[10px] text-courtSlate-400 font-display uppercase tracking-widest -mt-1">
                            Badminton Hall & Academy
                        </p>
                    </div>
                </div>

                <!-- Auth Buttons -->
                <nav v-if="canLogin" class="flex items-center gap-3">
                    <Link
                        v-if="$page.props.auth.user"
                        :href="route('dashboard')"
                        class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-volt text-volt-contrast font-display font-black text-xs uppercase tracking-wider shadow-volt-glow-sm hover:bg-volt-hover transition active:scale-[0.98] -skew-x-3"
                    >
                        <span class="skew-x-3">Buka Dashboard &rarr;</span>
                    </Link>

                    <template v-else>
                        <Link
                            :href="route('login')"
                            class="px-4 py-2.5 rounded-xl border border-arena-border text-xs font-display font-bold uppercase tracking-wider text-courtSlate-300 hover:text-white hover:border-courtSlate-400 transition"
                        >
                            Masuk
                        </Link>
                        <Link
                            v-if="canRegister"
                            :href="route('register')"
                            class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-volt text-volt-contrast font-display font-black text-xs uppercase tracking-wider shadow-volt-glow-sm hover:bg-volt-hover transition active:scale-[0.98] -skew-x-3"
                        >
                            <span class="skew-x-3">Daftar Member</span>
                        </Link>
                    </template>
                </nav>
            </div>
        </header>

        <!-- Main Hero Section -->
        <main class="flex-1">
            <section class="relative overflow-hidden pt-12 pb-20 sm:pt-20 sm:pb-32">
                <!-- Ambient glow decorations -->
                <div class="absolute -top-40 left-1/2 -translate-x-1/2 w-[800px] h-[500px] bg-volt/10 rounded-full blur-[140px] pointer-events-none"></div>
                <div class="absolute top-1/2 -right-40 w-[400px] h-[400px] bg-courtOrange/10 rounded-full blur-[120px] pointer-events-none"></div>

                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative">
                    <div class="max-w-3xl mx-auto text-center space-y-6">
                        <!-- Hero Tag -->
                        <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-arena-card border border-arena-border shadow-xs">
                            <span class="w-2 h-2 rounded-full bg-volt animate-ping"></span>
                            <span class="text-xs font-display font-bold uppercase tracking-wider text-courtSlate-300">
                                4 Gelanggang Standar Internasional BWF Tersedia
                            </span>
                        </div>

                        <!-- Big Display Heading -->
                        <h1 class="font-display font-black text-4xl sm:text-6xl lg:text-7xl uppercase tracking-tight text-white leading-[0.95]">
                            GELANGGANG BADMINTON <br />
                            <span class="text-volt drop-shadow-sm">BERSTANDAR RESMI PBSI</span>
                        </h1>

                        <!-- Subtitle -->
                        <p class="text-sm sm:text-base text-courtSlate-300 max-w-2xl mx-auto leading-relaxed">
                            Booking lapangan badminton modern secara instan dengan sistem ketersediaan slot real-time, opsi langganan mingguan rutin, dan diskon otomatis melalui keanggotaan loyalty.
                        </p>

                        <!-- Call to Action Buttons -->
                        <div class="flex flex-col sm:flex-row items-center justify-center gap-4 pt-4">
                            <Link
                                :href="route('courts.search')"
                                class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-8 py-4 rounded-2xl bg-volt text-volt-contrast font-display font-black text-sm uppercase tracking-wider shadow-volt-glow hover:bg-volt-hover transition-all active:scale-[0.98] -skew-x-3"
                            >
                                <span class="inline-flex items-center gap-2 skew-x-3">
                                    <span>🔍 Cari Lapangan Kosong</span>
                                </span>
                            </Link>

                            <Link
                                :href="$page.props.auth.user ? route('bookings.create') : route('register')"
                                class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-8 py-4 rounded-2xl border-2 border-arena-border bg-arena-card hover:bg-arena-surface text-white font-display font-black text-sm uppercase tracking-wider transition-all -skew-x-3"
                            >
                                <span class="inline-flex items-center gap-2 skew-x-3">
                                    <span>🏸 Booking Lapangan</span>
                                </span>
                            </Link>
                        </div>
                    </div>

                    <!-- Highlight Features Grid -->
                    <div class="mt-20 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                        <div class="rounded-2xl border-2 border-arena-border bg-arena-card p-6 relative overflow-hidden group hover:border-volt transition-all">
                            <div class="text-3xl mb-3">⚡</div>
                            <h3 class="font-display font-black text-lg uppercase text-white tracking-tight">
                                Anti-Bentrok Real-Time
                            </h3>
                            <p class="text-xs text-courtSlate-400 mt-2 leading-relaxed">
                                Kunci slot jam langsung dengan atomic transaction database. Pembaruan ketersediaan live via WebSocket.
                            </p>
                        </div>

                        <div class="rounded-2xl border-2 border-arena-border bg-arena-card p-6 relative overflow-hidden group hover:border-volt transition-all">
                            <div class="text-3xl mb-3">🔁</div>
                            <h3 class="font-display font-black text-lg uppercase text-white tracking-tight">
                                Booking Rutin Mingguan
                            </h3>
                            <p class="text-xs text-courtSlate-400 mt-2 leading-relaxed">
                                Jadwal latihan tetap tiap minggu otomatis terjadwal hingga 3 bulan dengan proteksi anti-bentrok parsial.
                            </p>
                        </div>

                        <div class="rounded-2xl border-2 border-arena-border bg-arena-card p-6 relative overflow-hidden group hover:border-volt transition-all">
                            <div class="text-3xl mb-3">⭐</div>
                            <h3 class="font-display font-black text-lg uppercase text-white tracking-tight">
                                Loyalty Poin & Diskon
                            </h3>
                            <p class="text-xs text-courtSlate-400 mt-2 leading-relaxed">
                                Kumpulkan poin dari tiap transaksi. Naik ke tier Silver (5%) dan Gold (10%) untuk potongan harga otomatis.
                            </p>
                        </div>

                        <div class="rounded-2xl border-2 border-arena-border bg-arena-card p-6 relative overflow-hidden group hover:border-volt transition-all">
                            <div class="text-3xl mb-3">🧾</div>
                            <h3 class="font-display font-black text-lg uppercase text-white tracking-tight">
                                Invoice & PWA Offline
                            </h3>
                            <p class="text-xs text-courtSlate-400 mt-2 leading-relaxed">
                                Bukti invoice digital resmi dan aplikasi yang dapat diinstall sebagai PWA di smartphone Anda.
                            </p>
                        </div>
                    </div>
                </div>
            </section>
        </main>

        <!-- Footer -->
        <footer class="border-t border-arena-border bg-arena-base py-8 text-center text-xs text-courtSlate-500">
            <div class="max-w-7xl mx-auto px-4 flex flex-col sm:flex-row items-center justify-between gap-4">
                <div class="flex items-center gap-2">
                    <span class="font-display font-black text-white uppercase tracking-wider">SMASH ARENA</span>
                    <span>&bull;</span>
                    <span>Sistem Reservasi Gelanggang Badminton</span>
                </div>
                <div class="text-[11px] text-courtSlate-500">
                    &copy; 2026 Smash Arena Sports Center. All rights reserved.
                </div>
            </div>
        </footer>
    </div>
</template>
