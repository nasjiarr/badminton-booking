<script setup>
import { ref, onMounted, onUnmounted } from 'vue';

const isOnline = ref(true);
const showInstallPrompt = ref(false);
let deferredPrompt = null;

// Track online/offline status
const updateOnlineStatus = () => {
    isOnline.value = navigator.onLine;
};

// Check if dismissed previously
const isDismissed = () => {
    const dismissedAt = localStorage.getItem('smash_pwa_dismissed_at');
    if (!dismissedAt) return false;
    // Don't show again for 2 days after dismiss
    const twoDays = 2 * 24 * 60 * 60 * 1000;
    return (Date.now() - parseInt(dismissedAt, 10)) < twoDays;
};

const handleBeforeInstallPrompt = (e) => {
    // Prevent the default mini-infobar or browser prompt
    e.preventDefault();
    deferredPrompt = e;

    // Show custom prompt after small delay if not dismissed
    if (!isDismissed()) {
        setTimeout(() => {
            showInstallPrompt.value = true;
        }, 3000);
    }
};

const installPwa = async () => {
    if (!deferredPrompt) return;

    // Show native prompt
    deferredPrompt.prompt();
    const { outcome } = await deferredPrompt.userChoice;

    if (outcome === 'accepted') {
        showInstallPrompt.value = false;
        deferredPrompt = null;
    }
};

const dismissPrompt = () => {
    showInstallPrompt.value = false;
    localStorage.setItem('smash_pwa_dismissed_at', Date.now().toString());
};

onMounted(() => {
    if (typeof window !== 'undefined') {
        isOnline.value = navigator.onLine;
        window.addEventListener('online', updateOnlineStatus);
        window.addEventListener('offline', updateOnlineStatus);
        window.addEventListener('beforeinstallprompt', handleBeforeInstallPrompt);

        // Also check if app is already running standalone
        const isStandalone = window.matchMedia('(display-mode: standalone)').matches || window.navigator.standalone;
        if (isStandalone) {
            showInstallPrompt.value = false;
        }
    }
});

onUnmounted(() => {
    if (typeof window !== 'undefined') {
        window.removeEventListener('online', updateOnlineStatus);
        window.removeEventListener('offline', updateOnlineStatus);
        window.removeEventListener('beforeinstallprompt', handleBeforeInstallPrompt);
    }
});
</script>

<template>
    <div>
        <!-- OFFLINE NOTICE BANNER -->
        <Transition
            enter-active-class="transition ease-out duration-300 transform"
            enter-from-class="-translate-y-full opacity-0"
            enter-to-class="translate-y-0 opacity-100"
            leave-active-class="transition ease-in duration-200 transform"
            leave-from-class="translate-y-0 opacity-100"
            leave-to-class="-translate-y-full opacity-0"
        >
            <div
                v-if="!isOnline"
                class="fixed top-0 inset-x-0 z-50 bg-courtOrange text-white px-4 py-2 text-xs sm:text-sm font-medium shadow-md flex items-center justify-between"
            >
                <div class="flex items-center gap-2 max-w-7xl mx-auto w-full">
                    <span class="inline-block animate-pulse text-base">⚠️</span>
                    <span>
                        <strong>Mode Offline Terdeteksi:</strong> Anda sedang tidak terhubung ke internet. Ketersediaan slot dan pemesanan real-time membutuhkan koneksi online.
                    </span>
                </div>
            </div>
        </Transition>

        <!-- CUSTOM PWA INSTALL PROMPT (FLOATING ATHLETIC CARD) -->
        <Transition
            enter-active-class="transition ease-out duration-300 transform"
            enter-from-class="translate-y-full opacity-0 sm:translate-y-4"
            enter-to-class="translate-y-0 opacity-100"
            leave-active-class="transition ease-in duration-200 transform"
            leave-from-class="translate-y-0 opacity-100"
            leave-to-class="translate-y-full opacity-0 sm:translate-y-4"
        >
            <div
                v-if="showInstallPrompt"
                class="fixed bottom-4 left-4 right-4 sm:left-auto sm:right-6 sm:max-w-md z-50 bg-arena-card border-2 border-volt/60 rounded-xl p-4 shadow-volt-glow text-white"
            >
                <div class="flex items-start gap-3.5">
                    <!-- Icon -->
                    <div class="w-12 h-12 rounded-xl bg-arena-surface border border-arena-border flex items-center justify-center shrink-0">
                        <img src="/icons/icon-192x192.png" alt="Smash Arena" class="w-8 h-8 rounded-lg" />
                    </div>

                    <!-- Content -->
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2">
                            <h4 class="font-display font-black text-sm uppercase tracking-wider text-white">
                                Pasang Aplikasi Smash Arena
                            </h4>
                            <span class="text-[9px] font-display font-extrabold uppercase px-1.5 py-0.5 rounded bg-volt text-volt-contrast -skew-x-6">
                                PWA
                            </span>
                        </div>
                        <p class="text-xs text-courtSlate-400 mt-0.5 leading-snug">
                            Pasang di layar utama untuk pemesanan lebih cepat, notifikasi jadwal, dan akses mudah tanpa browser.
                        </p>

                        <!-- Action Buttons -->
                        <div class="mt-3 flex items-center gap-2">
                            <button
                                type="button"
                                @click="installPwa"
                                class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-volt hover:bg-volt-hover text-volt-contrast font-display font-black text-xs uppercase tracking-wider rounded-lg shadow-volt-glow-sm transition -skew-x-6 cursor-pointer"
                            >
                                <span class="inline-block transform skew-x-6">
                                    🏸 Pasang Sekarang
                                </span>
                            </button>
                            <button
                                type="button"
                                @click="dismissPrompt"
                                class="px-2.5 py-1.5 text-xs font-medium text-courtSlate-400 hover:text-white transition cursor-pointer"
                            >
                                Nanti Saja
                            </button>
                        </div>
                    </div>

                    <!-- Close X button -->
                    <button
                        type="button"
                        @click="dismissPrompt"
                        class="text-courtSlate-500 hover:text-white p-1 text-sm leading-none"
                    >
                        ✕
                    </button>
                </div>
            </div>
        </Transition>
    </div>
</template>

