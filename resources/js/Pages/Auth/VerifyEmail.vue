<script setup>
import { computed } from 'vue';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const props = defineProps({
    status: {
        type: String,
    },
});

const form = useForm({});

const submit = () => {
    form.post(route('verification.send'));
};

const verificationLinkSent = computed(
    () => props.status === 'verification-link-sent',
);
</script>

<template>
    <GuestLayout>
        <Head title="Verifikasi Email" />

        <!-- Header Info -->
        <div class="mb-6">
            <div class="flex items-center gap-2 mb-2">
                <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-display font-black tracking-wider uppercase bg-volt/10 text-volt border border-volt/30 -skew-x-6">
                    <span class="transform skew-x-6">AKTIVASI AKUN</span>
                </span>
                <span class="text-xs text-slate-400 font-medium">Langkah Terakhir</span>
            </div>
            <h2 class="text-2xl sm:text-3xl font-display font-black tracking-tight text-white uppercase italic">
                Verifikasi Alamat Email
            </h2>
            <p class="text-xs sm:text-sm text-slate-400 mt-1 font-medium leading-relaxed">
                Terima kasih telah bergabung! Silakan klik tautan verifikasi yang baru saja kami kirimkan ke alamat email Anda untuk mengaktifkan seluruh fitur pemesanan lapangan.
            </p>
        </div>

        <div
            v-if="verificationLinkSent"
            class="mb-5 flex items-center gap-2.5 rounded-xl border border-emerald-500/30 bg-emerald-500/10 p-3 text-xs font-semibold text-emerald-400"
        >
            <svg class="h-4 w-4 shrink-0 text-emerald-400" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
            </svg>
            <span>Tautan verifikasi baru telah berhasil dikirim ke alamat email Anda.</span>
        </div>

        <form @submit.prevent="submit" class="space-y-4">
            <div>
                <PrimaryButton
                    class="w-full py-3 text-sm justify-center"
                    :class="{ 'opacity-50 cursor-not-allowed': form.processing }"
                    :disabled="form.processing"
                >
                    <span v-if="form.processing">Mengirim Ulang...</span>
                    <span v-else>📧 KIRIM ULANG EMAIL VERIFIKASI</span>
                </PrimaryButton>
            </div>

            <div class="pt-3 text-center">
                <Link
                    :href="route('logout')"
                    method="post"
                    as="button"
                    class="text-xs font-display font-bold uppercase tracking-wider text-slate-400 hover:text-courtOrange transition"
                >
                    Keluar dari Sesi (Log Out)
                </Link>
            </div>
        </form>
    </GuestLayout>
</template>
