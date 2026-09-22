<script setup>
import Checkbox from '@/Components/Checkbox.vue';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

defineProps({
    canResetPassword: {
        type: Boolean,
    },
    status: {
        type: String,
    },
});

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const submit = () => {
    form.post(route('login'), {
        onFinish: () => form.reset('password'),
    });
};
</script>

<template>
    <GuestLayout>
        <Head title="Masuk ke Arena" />

        <!-- Header Info -->
        <div class="mb-6">
            <div class="flex items-center gap-2 mb-2">
                <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-display font-black tracking-wider uppercase bg-volt/10 text-volt border border-volt/30 -skew-x-6">
                    <span class="transform skew-x-6">PORTAL MEMBER</span>
                </span>
                <span class="text-xs text-slate-400 font-medium">Smash Arena Access</span>
            </div>
            <h2 class="text-2xl sm:text-3xl font-display font-black tracking-tight text-white uppercase italic">
                Masuk ke Arena
            </h2>
            <p class="text-xs sm:text-sm text-slate-400 mt-1 font-medium">
                Masuk untuk melanjutkan reservasi lapangan dan akses poin loyalitas Anda.
            </p>
        </div>

        <!-- Status Notification -->
        <div v-if="status" class="mb-5 flex items-center gap-2.5 rounded-xl border border-emerald-500/30 bg-emerald-500/10 p-3 text-xs font-semibold text-emerald-400">
            <svg class="h-4 w-4 shrink-0 text-emerald-400" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
            </svg>
            <span>{{ status }}</span>
        </div>

        <form @submit.prevent="submit" class="space-y-5">
            <div>
                <InputLabel for="email" value="Alamat Email" />

                <TextInput
                    id="email"
                    type="email"
                    class="mt-1 block w-full"
                    v-model="form.email"
                    required
                    autofocus
                    autocomplete="username"
                    placeholder="nama@email.com"
                />

                <InputError class="mt-2" :message="form.errors.email" />
            </div>

            <div>
                <div class="flex items-center justify-between mb-1.5">
                    <InputLabel for="password" value="Kata Sandi" class="!mb-0" />
                    <Link
                        v-if="canResetPassword"
                        :href="route('password.request')"
                        class="text-xs font-bold text-slate-400 hover:text-volt transition"
                    >
                        Lupa kata sandi?
                    </Link>
                </div>

                <TextInput
                    id="password"
                    type="password"
                    class="mt-1 block w-full"
                    v-model="form.password"
                    required
                    autocomplete="current-password"
                    placeholder="••••••••"
                />

                <InputError class="mt-2" :message="form.errors.password" />
            </div>

            <div class="flex items-center justify-between pt-1">
                <label class="flex items-center gap-2 cursor-pointer">
                    <Checkbox name="remember" v-model:checked="form.remember" />
                    <span class="text-xs font-semibold text-slate-300">
                        Ingat sesi saya
                    </span>
                </label>
            </div>

            <div class="pt-2">
                <PrimaryButton
                    class="w-full py-3 text-sm justify-center"
                    :class="{ 'opacity-50 cursor-not-allowed': form.processing }"
                    :disabled="form.processing"
                >
                    <span v-if="form.processing">Memverifikasi Akun...</span>
                    <span v-else>🏸 MASUK KE ARENA</span>
                </PrimaryButton>
            </div>

            <!-- Register prompt -->
            <div class="pt-4 border-t border-slate-800 text-center text-xs text-slate-400">
                Belum terdaftar sebagai member?
                <Link
                    :href="route('register')"
                    class="font-display font-black uppercase tracking-wider text-volt hover:underline ml-1"
                >
                    Daftar Sekarang →
                </Link>
            </div>
        </form>
    </GuestLayout>
</template>
