<script setup>
import { ref, watch, onMounted } from 'vue';
import { usePage } from '@inertiajs/vue3';

const page = usePage();
const show = ref(false);
const message = ref('');
const type = ref('success');

const showToast = (msg, toastType = 'success') => {
    message.value = msg;
    type.value = toastType;
    show.value = true;

    setTimeout(() => {
        show.value = false;
    }, 3000);
};

// Watch for flash messages from Inertia shared data
watch(
    () => page.props.flash,
    (flash) => {
        if (flash?.success) {
            showToast(flash.success, 'success');
        }
        if (flash?.error) {
            showToast(flash.error, 'error');
        }
    },
    { immediate: true }
);
</script>

<template>
    <Transition
        enter-active-class="transform ease-out duration-300 transition"
        enter-from-class="translate-y-2 opacity-0 sm:translate-y-0 sm:translate-x-2"
        enter-to-class="translate-y-0 opacity-100 sm:translate-x-0"
        leave-active-class="transition ease-in duration-200"
        leave-from-class="opacity-100"
        leave-to-class="opacity-0"
    >
        <div
            v-if="show"
            class="fixed right-4 top-4 z-[100] max-w-sm rounded-lg p-4 shadow-lg"
            :class="{
                'bg-green-50 text-green-800 border border-green-200': type === 'success',
                'bg-red-50 text-red-800 border border-red-200': type === 'error',
            }"
        >
            <div class="flex items-center">
                <!-- Success icon -->
                <svg
                    v-if="type === 'success'"
                    class="mr-2 h-5 w-5 text-green-400"
                    fill="none"
                    viewBox="0 0 24 24"
                    stroke="currentColor"
                >
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <!-- Error icon -->
                <svg
                    v-if="type === 'error'"
                    class="mr-2 h-5 w-5 text-red-400"
                    fill="none"
                    viewBox="0 0 24 24"
                    stroke="currentColor"
                >
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <p class="text-sm font-medium">{{ message }}</p>
                <button
                    @click="show = false"
                    class="ml-4 inline-flex text-gray-400 hover:text-gray-600 focus:outline-none"
                >
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>
    </Transition>
</template>

