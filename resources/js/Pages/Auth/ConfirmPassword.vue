<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, useForm } from '@inertiajs/vue3';

const form = useForm({
    password: '',
});

const submit = () => {
    form.post(route('password.confirm'), {
        onFinish: () => form.reset(),
    });
};
</script>

<template>
    <GuestLayout>
        <Head title="Konfirmasi Kata Sandi" />

        <!-- Header Info -->
        <div class="mb-6">
            <div class="flex items-center gap-2 mb-2">
                <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-display font-black tracking-wider uppercase bg-courtOrange/10 text-courtOrange border border-courtOrange/30 -skew-x-6">
                    <span class="transform skew-x-6">VERIFIKASI KEAMANAN</span>
                </span>
                <span class="text-xs text-slate-400 font-medium">Secure Checkpoint</span>
            </div>
            <h2 class="text-2xl sm:text-3xl font-display font-black tracking-tight text-white uppercase italic">
                Konfirmasi Kata Sandi
            </h2>
            <p class="text-xs sm:text-sm text-slate-400 mt-1 font-medium leading-relaxed">
                Ini adalah area sensitif aplikasi. Harap masukkan kata sandi Anda saat ini sebelum melanjutkan.
            </p>
        </div>

        <form @submit.prevent="submit" class="space-y-5">
            <div>
                <InputLabel for="password" value="Kata Sandi Anda" />
                <TextInput
                    id="password"
                    type="password"
                    class="mt-1 block w-full"
                    v-model="form.password"
                    required
                    autocomplete="current-password"
                    autofocus
                    placeholder="••••••••"
                />
                <InputError class="mt-2" :message="form.errors.password" />
            </div>

            <div class="pt-2">
                <PrimaryButton
                    class="w-full py-3 text-sm justify-center"
                    :class="{ 'opacity-50 cursor-not-allowed': form.processing }"
                    :disabled="form.processing"
                >
                    <span v-if="form.processing">Memverifikasi...</span>
                    <span v-else>🛡️ KONFIRMASI AKSES</span>
                </PrimaryButton>
            </div>
        </form>
    </GuestLayout>
</template>
