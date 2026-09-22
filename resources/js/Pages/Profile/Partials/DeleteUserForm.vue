<script setup>
import DangerButton from '@/Components/DangerButton.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import Modal from '@/Components/Modal.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { useForm } from '@inertiajs/vue3';
import { nextTick, ref } from 'vue';

const confirmingUserDeletion = ref(false);
const passwordInput = ref(null);

const form = useForm({
    password: '',
});

const confirmUserDeletion = () => {
    confirmingUserDeletion.value = true;

    nextTick(() => passwordInput.value?.focus());
};

const deleteUser = () => {
    form.delete(route('profile.destroy'), {
        preserveScroll: true,
        onSuccess: () => closeModal(),
        onError: () => passwordInput.value?.focus(),
        onFinish: () => form.reset(),
    });
};

const closeModal = () => {
    confirmingUserDeletion.value = false;

    form.clearErrors();
    form.reset();
};
</script>

<template>
    <section class="space-y-5">
        <header>
            <div class="flex items-center gap-2 mb-2">
                <span class="court-badge-orange text-[10px] py-0.5">
                    <span>ZONA KRITIS</span>
                </span>
                <span class="text-xs text-courtSlate-400 font-medium">Penghapusan Akun</span>
            </div>
            <h2 class="text-xl sm:text-2xl font-display font-black tracking-tight text-courtOrange uppercase">
                Hapus Akun Member
            </h2>

            <p class="mt-1 text-xs sm:text-sm text-courtSlate-500 font-medium leading-relaxed">
                Setelah akun Anda dihapus, seluruh riwayat booking, akumulasi poin loyalty, dan data profil akan dihapus secara permanen dari sistem Smash Arena.
            </p>
        </header>

        <div>
            <DangerButton @click="confirmUserDeletion">
                ⚠️ HAPUS AKUN SAYA
            </DangerButton>
        </div>

        <Modal :show="confirmingUserDeletion" @close="closeModal">
            <div class="p-6 sm:p-8 bg-white text-courtSlate-900 rounded-2xl border-2 border-courtSlate-200">
                <div class="flex items-center gap-3 text-courtOrange mb-3">
                    <div class="w-10 h-10 rounded-xl bg-courtOrange/10 border-2 border-courtOrange/20 flex items-center justify-center shrink-0">
                        <svg class="w-5 h-5 text-courtOrange" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-lg font-display font-black uppercase tracking-tight text-courtSlate-900">
                            Konfirmasi Penghapusan Akun
                        </h3>
                        <span class="text-xs text-courtSlate-500">Tindakan ini permanen dan tidak dapat dibatalkan.</span>
                    </div>
                </div>

                <p class="text-xs sm:text-sm text-courtSlate-600 leading-relaxed mb-4">
                    Apakah Anda yakin ingin menghapus akun Anda secara permanen? Silakan masukkan kata sandi Anda untuk mengonfirmasi tindakan ini.
                </p>

                <div class="p-3.5 rounded-xl bg-courtOrange-light border border-courtOrange/20 text-xs text-courtOrange leading-relaxed mb-4">
                    ⚠️ <strong>Peringatan:</strong> Seluruh data booking aktif dan sisa saldo poin loyalty Anda akan segera hangus dan tidak dapat dipulihkan kembali.
                </div>

                <div class="mt-4">
                    <InputLabel
                        for="password"
                        value="Kata Sandi Konfirmasi"
                        variant="light"
                    />

                    <TextInput
                        id="password"
                        ref="passwordInput"
                        v-model="form.password"
                        type="password"
                        class="mt-1 block w-full"
                        placeholder="Masukkan kata sandi Anda"
                        variant="light"
                        @keyup.enter="deleteUser"
                    />

                    <InputError :message="form.errors.password" class="mt-1.5" />
                </div>

                <div class="mt-6 flex justify-end gap-3">
                    <SecondaryButton @click="closeModal" variant="light">
                        Batal
                    </SecondaryButton>

                    <DangerButton
                        :class="{ 'opacity-50 cursor-not-allowed': form.processing }"
                        :disabled="form.processing"
                        @click="deleteUser"
                    >
                        <span v-if="form.processing">Menghapus...</span>
                        <span v-else>Ya, Hapus Akun</span>
                    </DangerButton>
                </div>
            </div>
        </Modal>
    </section>
</template>
