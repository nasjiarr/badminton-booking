<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
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
                    class="mr-4 text-gray-400 hover:text-gray-600"
                >
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                </Link>
                <h2 class="text-xl font-semibold leading-tight text-gray-800">
                    Edit: {{ court.name }}
                </h2>
            </div>
        </template>

        <div class="py-6">
            <div class="px-4 sm:px-6 lg:px-8">
                <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                    <form @submit.prevent="submit" class="p-6 space-y-6">
                        <!-- Nama -->
                        <div>
                            <InputLabel for="name" value="Nama Lapangan" />
                            <TextInput
                                id="name"
                                type="text"
                                class="mt-1 block w-full"
                                v-model="form.name"
                                required
                                autofocus
                            />
                            <InputError class="mt-2" :message="form.errors.name" />
                        </div>

                        <!-- Deskripsi -->
                        <div>
                            <InputLabel for="description" value="Deskripsi" />
                            <textarea
                                id="description"
                                v-model="form.description"
                                rows="3"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            />
                            <InputError class="mt-2" :message="form.errors.description" />
                        </div>

                        <!-- Harga Per Jam -->
                        <div>
                            <InputLabel for="price_per_hour" value="Harga Per Jam (Rp)" />
                            <TextInput
                                id="price_per_hour"
                                type="number"
                                class="mt-1 block w-full"
                                v-model="form.price_per_hour"
                                required
                                min="1"
                                step="1000"
                            />
                            <InputError class="mt-2" :message="form.errors.price_per_hour" />
                        </div>

                        <!-- Upload Foto -->
                        <div>
                            <InputLabel for="image" value="Foto Lapangan (JPG/PNG, maks 2MB)" />
                            <input
                                id="image"
                                type="file"
                                class="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:rounded-md file:border-0 file:bg-indigo-50 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-indigo-700 hover:file:bg-indigo-100"
                                accept="image/jpeg,image/png,image/jpg"
                                @change="handleImageChange"
                            />
                            <InputError class="mt-2" :message="form.errors.image" />
                            <!-- Preview: show new upload or existing image -->
                            <div class="mt-3">
                                <img
                                    v-if="imagePreview"
                                    :src="imagePreview"
                                    class="h-32 w-32 rounded-lg object-cover"
                                    alt="Preview baru"
                                />
                                <div v-else-if="court.image_url" class="flex items-center gap-3">
                                    <img
                                        :src="court.image_url"
                                        class="h-32 w-32 rounded-lg object-cover"
                                        alt="Foto saat ini"
                                    />
                                    <span class="text-xs text-gray-500">Foto saat ini</span>
                                </div>
                                <p v-else class="text-sm text-gray-400">Belum ada foto</p>
                            </div>
                        </div>

                        <!-- Status Aktif -->
                        <div>
                            <label class="flex items-center">
                                <input
                                    type="checkbox"
                                    v-model="form.is_active"
                                    :true-value="true"
                                    :false-value="false"
                                    class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500"
                                />
                                <span class="ml-2 text-sm text-gray-600">Lapangan aktif (bisa dipesan)</span>
                            </label>
                        </div>

                        <!-- Actions -->
                        <div class="flex items-center justify-end gap-4">
                            <Link
                                :href="route('admin.courts.index')"
                                class="text-sm text-gray-600 hover:text-gray-900"
                            >
                                Batal
                            </Link>
                            <PrimaryButton :disabled="form.processing">
                                <span v-if="form.processing">Menyimpan...</span>
                                <span v-else>Perbarui</span>
                            </PrimaryButton>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>

