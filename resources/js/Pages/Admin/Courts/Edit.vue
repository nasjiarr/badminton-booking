<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import InputError from '@/Components/InputError.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    court: Object,
});

const form = useForm({
    _method: 'PUT',
    name: props.court.name,
    description: props.court.description || '',
    price_per_hour: props.court.price_per_hour,
    image: null,
    is_active: props.court.is_active,
});

const imagePreview = ref(null);

const handleImageChange = (event) => {
    const file = event.target.files[0];
    form.image = file;

    if (file) {
        const reader = new FileReader();
        reader.onload = (e) => {
            imagePreview.value = e.target.result;
        };
        reader.readAsDataURL(file);
    } else {
        imagePreview.value = null;
    }
};

const submit = () => {
    form.post(route('admin.courts.update', props.court.id), {
        forceFormData: true,
    });
};
</script>

<template>
    <Head :title="`Edit ${court.name}`" />

    <AdminLayout>
        <template #header>
            <div class="flex items-center">
                <Link
                    :href="route('admin.courts.index')"
                    class="mr-4 inline-flex h-10 w-10 items-center justify-center rounded-lg border-2 border-courtSlate-200 bg-white text-courtSlate-700 shadow-xs transition hover:border-arena-base hover:bg-arena-base hover:text-volt"
                    title="Kembali ke daftar lapangan"
                >
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                </Link>
                <div>
                    <h2 class="text-2xl sm:text-3xl font-display font-black tracking-tight text-courtSlate-900 uppercase">
                        Edit: {{ court.name }}
                    </h2>
                    <p class="text-xs sm:text-sm font-medium text-courtSlate-500">
                        Perbarui tarif per jam, deskripsi fasilitas, atau status operasional lapangan.
                    </p>
                </div>
            </div>
        </template>

        <div class="py-6">
            <div class="px-4 sm:px-6 lg:px-8">
                <div class="max-w-3xl overflow-hidden rounded-xl border-2 border-courtSlate-200 bg-white shadow-card-elevated">
                    <form @submit.prevent="submit" class="p-6 sm:p-8 space-y-6">
                        <!-- Nama -->
                        <div>
                            <label for="name" class="block font-display font-extrabold text-xs uppercase tracking-wider text-courtSlate-700 mb-1.5">
                                Nama Lapangan <span class="text-courtOrange">*</span>
                            </label>
                            <input
                                id="name"
                                type="text"
                                class="w-full rounded-lg border-2 border-courtSlate-200 px-4 py-2.5 text-sm font-semibold text-courtSlate-900 placeholder-courtSlate-400 transition focus:border-arena-base focus:ring-2 focus:ring-volt"
                                v-model="form.name"
                                required
                                autofocus
                            />
                            <InputError class="mt-2" :message="form.errors.name" />
                        </div>

                        <!-- Deskripsi -->
                        <div>
                            <label for="description" class="block font-display font-extrabold text-xs uppercase tracking-wider text-courtSlate-700 mb-1.5">
                                Deskripsi & Fasilitas
                            </label>
                            <textarea
                                id="description"
                                v-model="form.description"
                                rows="3"
                                class="w-full rounded-lg border-2 border-courtSlate-200 p-3.5 text-sm text-courtSlate-900 placeholder-courtSlate-400 transition focus:border-arena-base focus:ring-2 focus:ring-volt"
                            />
                            <InputError class="mt-2" :message="form.errors.description" />
                        </div>

                        <!-- Harga Per Jam -->
                        <div>
                            <label for="price_per_hour" class="block font-display font-extrabold text-xs uppercase tracking-wider text-courtSlate-700 mb-1.5">
                                Harga Sewa Per Jam <span class="text-courtOrange">*</span>
                            </label>
                            <div class="relative flex rounded-lg shadow-xs">
                                <span class="inline-flex items-center rounded-l-lg border-2 border-r-0 border-courtSlate-200 bg-courtSlate-100 px-4 font-display font-extrabold text-sm text-courtSlate-700">
                                    Rp
                                </span>
                                <input
                                    id="price_per_hour"
                                    type="number"
                                    class="block w-full rounded-none rounded-r-lg border-2 border-courtSlate-200 px-4 py-2.5 font-display font-black text-lg text-courtSlate-900 placeholder-courtSlate-400 transition focus:border-arena-base focus:ring-2 focus:ring-volt"
                                    v-model="form.price_per_hour"
                                    required
                                    min="1"
                                    step="1000"
                                />
                            </div>
                            <InputError class="mt-2" :message="form.errors.price_per_hour" />
                        </div>

                        <!-- Upload Foto -->
                        <div>
                            <label for="image" class="block font-display font-extrabold text-xs uppercase tracking-wider text-courtSlate-700 mb-1.5">
                                Ganti Foto Lapangan (JPG / PNG, Maks 2MB)
                            </label>
                            <input
                                id="image"
                                type="file"
                                class="block w-full text-sm text-courtSlate-600 rounded-lg border-2 border-dashed border-courtSlate-200 bg-courtSlate-50 p-3 file:mr-4 file:cursor-pointer file:rounded-lg file:border-0 file:bg-arena-base file:px-4 file:py-2 file:font-display file:font-extrabold file:text-xs file:uppercase file:tracking-wider file:text-volt hover:file:bg-arena-surface transition"
                                accept="image/jpeg,image/png,image/jpg"
                                @change="handleImageChange"
                            />
                            <InputError class="mt-2" :message="form.errors.image" />
                            <!-- Preview: show new upload or existing image -->
                            <div class="mt-4">
                                <div v-if="imagePreview" class="flex items-center gap-4">
                                    <img
                                        :src="imagePreview"
                                        class="h-28 w-40 rounded-lg border-2 border-volt object-cover shadow-md"
                                        alt="Preview baru"
                                    />
                                    <div>
                                        <span class="court-badge-volt text-xs"><span>FOTO BARU (BELUM DISIMPAN)</span></span>
                                        <p class="text-xs text-courtSlate-500 mt-1">Foto ini akan menggantikan foto lama saat Anda menekan Simpan.</p>
                                    </div>
                                </div>
                                <div v-else-if="court.image_url" class="flex items-center gap-4">
                                    <img
                                        :src="court.image_url"
                                        class="h-28 w-40 rounded-lg border-2 border-courtSlate-200 object-cover shadow-xs"
                                        alt="Foto saat ini"
                                    />
                                    <div>
                                        <span class="inline-flex items-center rounded bg-courtSlate-100 px-2 py-0.5 font-display font-bold text-xs uppercase tracking-wider text-courtSlate-600">
                                            Foto Saat Ini
                                        </span>
                                        <p class="text-xs text-courtSlate-500 mt-1">Pilih file baru di atas jika ingin mengganti gambar lapangan ini.</p>
                                    </div>
                                </div>
                                <p v-else class="text-sm text-courtSlate-400 italic">Belum ada foto yang diunggah untuk lapangan ini.</p>
                            </div>
                        </div>

                        <!-- Status Aktif -->
                        <div class="rounded-lg border-2 border-courtSlate-200 bg-courtSlate-50/70 p-4">
                            <label class="flex items-start gap-3 cursor-pointer">
                                <input
                                    type="checkbox"
                                    v-model="form.is_active"
                                    :true-value="true"
                                    :false-value="false"
                                    class="mt-0.5 h-5 w-5 rounded border-2 border-courtSlate-300 text-arena-base focus:ring-volt focus:ring-offset-0"
                                />
                                <div>
                                    <span class="block font-display font-extrabold text-sm uppercase tracking-wide text-courtSlate-900">
                                        Status Lapangan Aktif
                                    </span>
                                    <span class="text-xs text-courtSlate-500">
                                        Jika aktif, lapangan ini akan langsung muncul di jadwal dan siap dipesan oleh pelanggan.
                                    </span>
                                </div>
                            </label>
                        </div>

                        <!-- Actions -->
                        <div class="flex items-center justify-end gap-3 pt-4 border-t border-courtSlate-200">
                            <Link
                                :href="route('admin.courts.index')"
                                class="rounded-lg border-2 border-courtSlate-200 px-4 py-2.5 font-display font-bold text-xs uppercase tracking-wider text-courtSlate-700 hover:bg-courtSlate-100 transition"
                            >
                                Batal
                            </Link>
                            <button
                                type="submit"
                                :disabled="form.processing"
                                class="inline-flex items-center justify-center rounded-lg bg-volt px-6 py-2.5 font-display font-black text-sm uppercase tracking-wider text-volt-contrast shadow-sm hover:bg-volt-hover hover:shadow-volt-glow-sm transition active:scale-[0.98] disabled:opacity-50 disabled:cursor-not-allowed"
                            >
                                <span v-if="form.processing">Menyimpan...</span>
                                <span v-else>Perbarui Lapangan</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>

