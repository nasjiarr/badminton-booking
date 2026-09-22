<script setup>
defineProps({
    value: {
        type: String,
    },
    variant: {
        type: String,
        default: 'default',
    },
});
</script>

<template>
    <label
        :class="[
            'block text-[11px] font-display font-black tracking-wider uppercase mb-1.5',
            variant === 'light' ? 'text-courtSlate-700' : 'text-slate-300'
        ]"
    >
        <span v-if="value">{{ value }}</span>
        <span v-else><slot /></span>
    </label>
</template>
