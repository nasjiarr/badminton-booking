<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import Modal from '@/Components/Modal.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    courts: Array,
});

const confirmingDeletion = ref(false);
const courtToDelete = ref(null);

const confirmDelete = (court) => {
    courtToDelete.value = court;
    confirmingDeletion.value = true;
};

const deleteCourt = () => {
    router.delete(route('admin.courts.destroy', courtToDelete.value.id), {
        onSuccess: () => {
            confirmingDeletion.value = false;
            courtToDelete.value = null;
        },
    });
};

const closeModal = () => {
    confirmingDeletion.value = false;
    courtToDelete.value = null;
};

const formatPrice = (price) => {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0,
    }).format(price);
};
</script>

<template>
    <Head title="Manajemen Lapangan" />

    <AdminLayout>
        <template #header>
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h2 class="text-2xl sm:text-3xl font-display font-black tracking-tight text-courtSlate-900 uppercase">
                        Manajemen Lapangan
                    </h2>
                    <p class="mt-0.5 text-sm font-medium text-courtSlate-500">
                        Kelola data 4 lapangan, tarif per jam, dan ketersediaan operasional.
                    </p>
                </div>
                <Link
                    :href="route('admin.courts.create')"
                    class="inline-flex items-center justify-center gap-2 rounded-lg bg-volt px-4 py-2.5 text-sm font-display font-black uppercase tracking-wider text-volt-contrast shadow-sm hover:bg-volt-hover hover:shadow-volt-glow-sm transition active:scale-[0.98]"
                >
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                    </svg>
                    Tambah Lapangan
                </Link>
            </div>
        </template>

        <div class="py-6">
            <div class="px-4 sm:px-6 lg:px-8">
                <div class="overflow-hidden rounded-xl border-2 border-courtSlate-200 bg-white shadow-card-elevated">
                    <table class="min-w-full divide-y-2 divide-courtSlate-200">
                        <thead class="bg-arena-base text-courtSlate-200">
                            <tr>
                                <th class="px-6 py-3.5 text-left font-display font-extrabold text-xs uppercase tracking-wider text-courtSlate-300">
                                    Foto
                                </th>
                                <th class="px-6 py-3.5 text-left font-display font-extrabold text-xs uppercase tracking-wider text-courtSlate-300">
                                    Nama & Deskripsi
                                </th>
                                <th class="px-6 py-3.5 text-left font-display font-extrabold text-xs uppercase tracking-wider text-courtSlate-300">
                                    Harga / Jam
                                </th>
                                <th class="px-6 py-3.5 text-left font-display font-extrabold text-xs uppercase tracking-wider text-courtSlate-300">
                                    Status
                                </th>
                                <th class="px-6 py-3.5 text-right font-display font-extrabold text-xs uppercase tracking-wider text-courtSlate-300">
                                    Aksi
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-courtSlate-200 bg-white">
                            <tr v-for="court in courts" :key="court.id" class="hover:bg-courtSlate-50/80 transition-colors">
                                <td class="whitespace-nowrap px-6 py-4">
                                    <img
                                        v-if="court.image_url"
                                        :src="court.image_url"
                                        :alt="court.name"
                                        class="h-14 w-20 rounded-lg object-cover border border-courtSlate-200 shadow-xs"
                                    />
                                    <div
                                        v-else
                                        class="flex h-14 w-20 items-center justify-center rounded-lg bg-courtSlate-100 border border-courtSlate-200 text-courtSlate-400"
                                    >
                                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                        </svg>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="font-display font-black text-lg text-courtSlate-900 uppercase tracking-tight">
                                        {{ court.name }}
                                    </div>
                                    <div v-if="court.description" class="text-xs font-normal text-courtSlate-500 line-clamp-1 max-w-sm mt-0.5">
                                        {{ court.description }}
                                    </div>
                                </td>
                                <td class="whitespace-nowrap px-6 py-4">
                                    <div class="flex items-baseline gap-1">
                                        <span class="athletic-number text-xl font-black text-courtSlate-900">
                                            {{ formatPrice(court.price_per_hour) }}
                                        </span>
                                        <span class="text-xs font-semibold text-courtSlate-400">/ jam</span>
                                    </div>
                                </td>
                                <td class="whitespace-nowrap px-6 py-4">
                                    <span
                                        v-if="court.is_active"
                                        class="court-badge-volt text-xs"
                                    >
                                        <span>AKTIF</span>
                                    </span>
                                    <span
                                        v-else
                                        class="court-badge-orange text-xs"
                                    >
                                        <span>NONAKTIF</span>
                                    </span>
                                </td>
                                <td class="whitespace-nowrap px-6 py-4 text-right">
                                    <div class="inline-flex items-center gap-2">
                                        <Link
                                            :href="route('admin.courts.edit', court.id)"
                                            class="inline-flex items-center rounded-lg bg-courtSlate-100 px-3 py-1.5 font-display font-extrabold text-xs uppercase tracking-wider text-courtSlate-800 hover:bg-arena-base hover:text-volt transition"
                                        >
                                            Edit
                                        </Link>
                                        <button
                                            @click="confirmDelete(court)"
                                            class="inline-flex items-center rounded-lg bg-courtOrange-light px-3 py-1.5 font-display font-extrabold text-xs uppercase tracking-wider text-courtOrange hover:bg-courtOrange hover:text-white transition"
                                        >
                                            Hapus
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="courts.length === 0">
                                <td colspan="5" class="px-6 py-12 text-center">
                                    <p class="font-display font-extrabold text-lg text-courtSlate-700 uppercase tracking-tight">
                                        Belum ada lapangan terdaftar
                                    </p>
                                    <p class="text-sm text-courtSlate-500 mt-1">
                                        Klik "Tambah Lapangan" untuk mendaftarkan lapangan baru ke sistem.
                                    </p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Delete Confirmation Modal -->
        <Modal :show="confirmingDeletion" @close="closeModal">
            <div class="p-6">
                <div class="flex items-center gap-3">
                    <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-courtOrange-light text-courtOrange">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                    </div>
                    <h2 class="font-display font-black text-xl uppercase tracking-tight text-courtSlate-900">
                        Hapus Lapangan
                    </h2>
                </div>
                <p class="mt-3 text-sm text-courtSlate-600 leading-relaxed">
                    Apakah Anda yakin ingin menghapus <strong class="text-courtSlate-900">{{ courtToDelete?.name }}</strong>?
                    Semua data booking terkait juga akan terhapus. Tindakan ini tidak dapat dibatalkan.
                </p>
                <div class="mt-6 flex justify-end gap-3">
                    <button
                        type="button"
                        @click="closeModal"
                        class="rounded-lg border-2 border-courtSlate-200 px-4 py-2 font-display font-bold text-xs uppercase tracking-wider text-courtSlate-700 hover:bg-courtSlate-100 transition"
                    >
                        Batal
                    </button>
                    <button
                        type="button"
                        @click="deleteCourt"
                        class="rounded-lg bg-courtOrange px-4 py-2 font-display font-black text-xs uppercase tracking-wider text-white hover:bg-courtOrange-hover shadow-sm transition"
                    >
                        Ya, Hapus
                    </button>
                </div>
            </div>
        </Modal>
    </AdminLayout>
</template>

