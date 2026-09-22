<script setup>
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

const passwordInput = ref(null);
const currentPasswordInput = ref(null);

const form = useForm({
    current_password: '',
    password: '',
    password_confirmation: '',
});

const updatePassword = () => {
    form.put(route('password.update'), {
        preserveScroll: true,
        onSuccess: () => form.reset(),
        onError: () => {
            if (form.errors.password) {
                form.reset('password', 'password_confirmation');
                passwordInput.value?.focus();
            }
            if (form.errors.current_password) {
                form.reset('current_password');
                currentPasswordInput.value?.focus();
            }
        },
    });
};
</script>

<template>
    <section>
        <header class="mb-6">
            <div class="flex items-center gap-2 mb-2">
                <span class="court-badge-volt text-[10px] py-0.5">
                    <span>KEAMANAN AKUN</span>
                </span>
                <span class="text-xs text-courtSlate-400 font-medium">Manajemen Sandi</span>
            </div>
            <h2 class="text-xl sm:text-2xl font-display font-black tracking-tight text-courtSlate-900 uppercase">
                Perbarui Kata Sandi
            </h2>
            <p class="mt-1 text-xs sm:text-sm text-courtSlate-500 font-medium leading-relaxed">
                Pastikan akun Anda terlindungi dengan menggunakan kata sandi yang kuat dan aman.
            </p>
        </header>

        <form @submit.prevent="updatePassword" class="space-y-5 max-w-2xl">
            <div>
                <InputLabel for="current_password" value="Kata Sandi Saat Ini" variant="light" />

                <TextInput
                    id="current_password"
                    ref="currentPasswordInput"
                    v-model="form.current_password"
                    type="password"
                    class="mt-1 block w-full"
                    autocomplete="current-password"
                    placeholder="••••••••"
                    variant="light"
                />

                <InputError
                    :message="form.errors.current_password"
                    class="mt-1.5"
                />
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <InputLabel for="password" value="Kata Sandi Baru" variant="light" />

                    <TextInput
                        id="password"
                        ref="passwordInput"
                        v-model="form.password"
                        type="password"
                        class="mt-1 block w-full"
                        autocomplete="new-password"
                        placeholder="••••••••"
                        variant="light"
                    />

                    <InputError :message="form.errors.password" class="mt-1.5" />
                </div>

                <div>
                    <InputLabel
                        for="password_confirmation"
                        value="Konfirmasi Kata Sandi Baru"
                        variant="light"
                    />

                    <TextInput
                        id="password_confirmation"
                        v-model="form.password_confirmation"
                        type="password"
                        class="mt-1 block w-full"
                        autocomplete="new-password"
                        placeholder="••••••••"
                        variant="light"
                    />

                    <InputError
                        :message="form.errors.password_confirmation"
                        class="mt-1.5"
                    />
                </div>
            </div>

            <div class="flex items-center gap-4 pt-2">
                <PrimaryButton :disabled="form.processing">
                    <span v-if="form.processing">Memperbarui...</span>
                    <span v-else>🔒 PERBARUI KATA SANDI</span>
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
                        ✓ Kata sandi berhasil diperbarui.
                    </span>
                </Transition>
            </div>
        </form>
    </section>
</template>
