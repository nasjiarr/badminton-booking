<script setup>
defineProps({
    type: {
        type: String,
        default: 'button',
    },
    variant: {
        type: String,
        default: 'default',
    },
});
</script>

<template>
    <button
        :type="type"
        :class="[
            'inline-flex items-center justify-center rounded-xl px-4 py-2.5 font-display text-xs font-bold uppercase tracking-wider transition-all duration-150 active:scale-[0.98] disabled:opacity-40 cursor-pointer',
            variant === 'light'
                ? 'border-2 border-courtSlate-200 bg-white text-courtSlate-700 hover:bg-courtSlate-100 hover:text-courtSlate-900 focus:outline-none focus:ring-2 focus:ring-courtSlate-400'
                : 'border border-slate-700 bg-slate-800 text-slate-200 hover:bg-slate-700 hover:text-white focus:outline-none focus:ring-2 focus:ring-slate-500 focus:ring-offset-2 focus:ring-offset-arena-base'
        ]"
    >
        <slot />
    </button>
</template>
