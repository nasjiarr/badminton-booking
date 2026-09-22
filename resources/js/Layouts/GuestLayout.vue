<script setup>
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import { Link } from '@inertiajs/vue3';
</script>

<template>
    <div class="relative min-h-screen bg-arena-base text-slate-100 flex items-center justify-center p-4 sm:p-6 lg:p-10 overflow-hidden font-sans">
        <!-- Background Decorative Badminton Court Lighting & Accents -->
        <div class="absolute inset-0 pointer-events-none overflow-hidden">
            <!-- Glow 1: Smash Volt neon light from top-left -->
            <div class="absolute -top-32 -left-32 w-96 h-96 rounded-full bg-volt/10 blur-[120px]"></div>
            <!-- Glow 2: Speed Orange warm light from bottom-right -->
            <div class="absolute -bottom-32 -right-32 w-96 h-96 rounded-full bg-courtOrange/10 blur-[130px]"></div>
            <!-- Diagonal Court Grid Overlay -->
            <svg class="absolute inset-0 w-full h-full opacity-[0.03]" xmlns="http://www.w3.org/2000/svg">
                <defs>
                    <pattern id="court-grid" width="60" height="60" patternUnits="userSpaceOnUse" patternTransform="rotate(25)">
                        <path d="M 60 0 L 0 0 0 60" fill="none" stroke="#FFFFFF" stroke-width="1.5" />
                    </pattern>
                </defs>
                <rect width="100%" height="100%" fill="url(#court-grid)" />
            </svg>
        </div>

        <div class="relative z-10 w-full max-w-6xl mx-auto">
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-center">
                
                <!-- Left Column: Arena Badminton Showcase (Hidden on very small mobile, visible sm+) -->
                <div class="lg:col-span-5 flex flex-col justify-between py-2 sm:py-6">
                    <div>
                        <!-- Logo & Brand Header -->
                        <Link href="/" class="inline-flex items-center gap-3.5 group mb-8">
                            <div class="w-12 h-12 rounded-2xl bg-slate-900 border border-slate-700/80 p-2 shadow-lg shadow-volt/10 group-hover:border-volt/60 transition duration-300">
                                <ApplicationLogo class="w-full h-full text-volt" />
                            </div>
                            <div>
                                <span class="block font-display font-black text-2xl sm:text-3xl tracking-tight uppercase italic text-white group-hover:text-volt transition">
                                    SMASH <span class="text-volt">ARENA</span>
                                </span>
                                <span class="block text-[11px] font-bold text-slate-400 tracking-wider uppercase font-display">
                                    Gelanggang Badminton Modern
                                </span>
                            </div>
                        </Link>

                        <!-- Athletic Badge -->
                        <div class="inline-flex items-center gap-2 px-3 py-1 rounded bg-volt/10 border border-volt/30 text-volt text-xs font-display font-black uppercase tracking-wider -skew-x-6 mb-4">
                            <span class="inline-block transform skew-x-6">🏸 STANDAR RESMI PBSI</span>
                        </div>

                        <!-- Hero Tagline -->
                        <h1 class="text-3xl sm:text-4xl font-display font-black uppercase tracking-tight text-white leading-tight mb-4">
                            Dominasi Lapangan, <br />
                            <span class="text-transparent bg-clip-text bg-gradient-to-r from-volt via-lime-300 to-emerald-400">
                                Jadwal Main Tanpa Ribet.
                            </span>
                        </h1>

                        <p class="text-sm text-slate-400 font-medium leading-relaxed mb-8 max-w-md">
                            Akses sistem reservasi lapangan badminton tercepat dengan ketersediaan slot real-time, poin membership, dan kemudahan booking mingguan.
                        </p>

                        <!-- Feature Badges List -->
                        <div class="space-y-3.5">
                            <div class="flex items-center gap-3 text-xs text-slate-300">
                                <div class="w-6 h-6 rounded-lg bg-emerald-500/20 text-emerald-400 border border-emerald-500/30 flex items-center justify-center shrink-0 font-bold">
                                    ✓
                                </div>
                                <span><strong class="text-white">4 Lapangan Karpet Vinyl:</strong> Standar turnamen internasional & pencahayaan anti-silau.</span>
                            </div>
                            <div class="flex items-center gap-3 text-xs text-slate-300">
                                <div class="w-6 h-6 rounded-lg bg-volt/20 text-volt border border-volt/30 flex items-center justify-center shrink-0 font-bold">
                                    ⚡
                                </div>
                                <span><strong class="text-white">Anti-Bentrok Real-time:</strong> Kunci slot seketika saat reservasi dibuat.</span>
                            </div>
                            <div class="flex items-center gap-3 text-xs text-slate-300">
                                <div class="w-6 h-6 rounded-lg bg-amber-500/20 text-amber-400 border border-amber-500/30 flex items-center justify-center shrink-0 font-bold">
                                    ⭐
                                </div>
                                <span><strong class="text-white">Loyalty & Diskon:</strong> Raih tier Silver & Gold untuk potongan harga otomatis.</span>
                            </div>
                        </div>
                    </div>

                    <!-- Decorative Court Accent Line -->
                    <div class="mt-8 pt-6 border-t border-slate-800/80 flex items-center justify-between text-xs text-slate-500">
                        <span>Pintu Utama Buka: 06:00 - 23:00 WIB</span>
                        <span class="text-volt font-mono font-bold">SMASH v2.0</span>
                    </div>
                </div>

                <!-- Right Column: Form Container -->
                <div class="lg:col-span-7">
                    <div class="relative bg-slate-900/90 border border-slate-800 rounded-3xl p-6 sm:p-10 shadow-2xl shadow-black/80 backdrop-blur-xl overflow-hidden">
                        <!-- Top Court Edge Accent Stripe -->
                        <div class="absolute top-0 left-0 right-0 h-1.5 bg-gradient-to-r from-volt via-emerald-400 to-courtOrange"></div>
                        
                        <!-- Diagonal Court Watermark in Card -->
                        <div class="absolute -right-8 -bottom-8 pointer-events-none opacity-5">
                            <ApplicationLogo class="w-64 h-64 text-white" />
                        </div>

                        <!-- Slot Content -->
                        <div class="relative z-10">
                            <slot />
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
</template>
