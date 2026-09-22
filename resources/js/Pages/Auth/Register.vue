<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const form = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
});

const submit = () => {
    form.post(route('register'), {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};
</script>

<template>
    <GuestLayout>
        <Head title="Daftar Member Baru" />

        <!-- Header Info -->
        <div class="mb-6">
            <div class="flex items-center gap-2 mb-2">
                <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-display font-black tracking-wider uppercase bg-volt/10 text-volt border border-volt/30 -skew-x-6">
                    <span class="transform skew-x-6">REGISTRASI MEMBER</span>
                </span>
                <span class="text-xs text-slate-400 font-medium">Auto Tier Bronze</span>
            </div>
            <h2 class="text-2xl sm:text-3xl font-display font-black tracking-tight text-white uppercase italic">
                Daftar Member Baru
            </h2>
            <p class="text-xs sm:text-sm text-slate-400 mt-1 font-medium">
                Buat akun untuk memesan lapangan secara instan dan raih poin loyalitas di setiap transaksi.
            </p>
        </div>

        <form @submit.prevent="submit" class="space-y-4">
            <div>
                <InputLabel for="name" value="Nama Lengkap" />

                <TextInput
                    id="name"
                    type="text"
                    class="mt-1 block w-full"
                    v-model="form.name"
                    required
                    autofocus
                    autocomplete="name"
                    placeholder="Contoh: Budi Santoso"
                />

                <InputError class="mt-2" :message="form.errors.name" />
            </div>

            <div>
                <InputLabel for="email" value="Alamat Email" />

                <TextInput
                    id="email"
                    type="email"
                    class="mt-1 block w-full"
                    v-model="form.email"
                    required
                    autocomplete="username"
                    placeholder="nama@email.com"
                />

                <InputError class="mt-2" :message="form.errors.email" />
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <InputLabel for="password" value="Kata Sandi" />

                    <TextInput
                        id="password"
                        type="password"
                        class="mt-1 block w-full"
                        v-model="form.password"
                        required
                        autocomplete="new-password"
                        placeholder="••••••••"
                    />

                    <InputError class="mt-2" :message="form.errors.password" />
                </div>

                <div>
                    <InputLabel
                        for="password_confirmation"
                        value="Konfirmasi Kata Sandi"
                    />

                    <TextInput
                        id="password_confirmation"
                        type="password"
                        class="mt-1 block w-full"
                        v-model="form.password_confirmation"
                        required
                        autocomplete="new-password"
                        placeholder="••••••••"
                    />

                    <InputError
                        class="mt-2"
                        :message="form.errors.password_confirmation"
                    />
                </div>
            </div>

            <div class="pt-3">
                <PrimaryButton
                    class="w-full py-3 text-sm justify-center"
                    :class="{ 'opacity-50 cursor-not-allowed': form.processing }"
                    :disabled="form.processing"
                >
                    <span v-if="form.processing">Membuat Akun...</span>
                    <span v-else>🏸 DAFTAR SEKARANG</span>
                </PrimaryButton>
            </div>

            <!-- Login prompt -->
            <div class="pt-4 border-t border-slate-800 text-center text-xs text-slate-400">
                Sudah memiliki akun member?
                <Link
                    :href="route('login')"
                    class="font-display font-black uppercase tracking-wider text-volt hover:underline ml-1"
                >
                    Masuk di sini →
                </Link>
            </div>
        </form>
    </GuestLayout>
</template>
