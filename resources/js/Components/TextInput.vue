<script setup>
import { onMounted, ref } from 'vue';

const model = defineModel({
    type: String,
    required: true,
});

const input = ref(null);

onMounted(() => {
    if (input.value?.hasAttribute('autofocus')) {
        input.value.focus();
    }
});

defineExpose({ focus: () => input.value?.focus() });
</script>

<template>
    <input
        class="w-full rounded-xl bg-slate-950/80 border border-slate-700/80 text-white placeholder-slate-500 px-4 py-2.5 text-sm font-medium focus:border-volt focus:ring-2 focus:ring-volt/20 focus:outline-none transition duration-150"
        v-model="model"
        ref="input"
    />
</template>
