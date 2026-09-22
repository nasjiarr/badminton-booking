<script setup>
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Link, useForm, usePage } from '@inertiajs/vue3';

defineProps({
    mustVerifyEmail: {
        type: Boolean,
    },
    status: {
        type: String,
    },
});

const user = usePage().props.auth.user;

const form = useForm({
    name: user.name,
    email: user.email,
});
</script>

<template>
    <section>
        <header class="mb-6">
            <div class="flex items-center gap-2 mb-2">
                <span class="court-badge-volt text-[10px] py-0.5">
                    <span>IDENTITAS PENGGUNA</span>
                </span>
                <span class="text-xs text-courtSlate-400 font-medium">Akun Member</span>
            </div>
            <h2 class="text-xl sm:text-2xl font-display font-black tracking-tight text-courtSlate-900 uppercase">
                Informasi Profil Member
            </h2>
            <p class="mt-1 text-xs sm:text-sm text-courtSlate-500 font-medium leading-relaxed">
                Perbarui nama akun dan alamat email Anda untuk menerima konfirmasi booking dan bukti invoice pembayaran.
            </p>
        </header>

        <form
            @submit.prevent="form.patch(route('profile.update'))"
            class="space-y-5 max-w-2xl"
        >
            <div>
                <InputLabel for="name" value="Nama Lengkap" variant="light" />

                <TextInput
                    id="name"
                    type="text"
                    class="mt-1 block w-full"
                    v-model="form.name"
                    required
                    autofocus
                    autocomplete="name"
                    placeholder="Nama Lengkap Anda"
                    variant="light"
                />

                <InputError class="mt-1.5" :message="form.errors.name" />
            </div>

            <div>
                <InputLabel for="email" value="Alamat Email" variant="light" />

                <TextInput
                    id="email"
                    type="email"
                    class="mt-1 block w-full"
                    v-model="form.email"
                    required
                    autocomplete="username"
                    placeholder="nama@email.com"
                    variant="light"
                />

                <InputError class="mt-1.5" :message="form.errors.email" />
            </div>

            <div v-if="mustVerifyEmail && user.email_verified_at === null">
                <div class="rounded-xl border border-amber-300 bg-amber-50 p-4 text-xs text-amber-900">
                    <p class="font-semibold">
                        Alamat email Anda belum terverifikasi.
                        <Link
                            :href="route('verification.send')"
                            method="post"
                            as="button"
                            class="ml-1 text-courtOrange underline hover:text-courtOrange-hover font-bold transition"
                        >
                            Klik di sini untuk mengirim ulang email verifikasi.
                        </Link>
                    </p>

                    <div
                        v-show="status === 'verification-link-sent'"
                        class="mt-2 font-medium text-emerald-700"
                    >
                        ✓ Tautan verifikasi baru telah dikirimkan ke alamat email Anda.
                    </div>
                </div>
            </div>

            <div class="flex items-center gap-4 pt-2">
                <PrimaryButton :disabled="form.processing">
                    <span v-if="form.processing">Menyimpan...</span>
                    <span v-else>💾 SIMPAN PERUBAHAN</span>
                </PrimaryButton>

                <Transition
                    enter-active-class="transition ease-in-out duration-200"
                    enter-from-class="opacity-0"
                    leave-active-class="transition ease-in-out duration-300"
                    leave-to-class="opacity-0"
                >
                    <span
                        v-if="form.recentlySuccessful"
                        class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-emerald-100 text-emerald-800 border border-emerald-300 text-xs font-bold"
                    >
                        ✓ Perubahan berhasil disimpan.
                    </span>
                </Transition>
            </div>
        </form>
    </section>
</template>
