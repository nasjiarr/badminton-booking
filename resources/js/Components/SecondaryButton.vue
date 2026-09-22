<script setup>
defineProps({
    type: {
        type: String,
        default: 'button',
    },
});
</script>

<template>
    <button
        :type="type"
        class="inline-flex items-center justify-center rounded-xl border border-slate-700 bg-slate-800 px-4 py-2.5 font-display text-sm font-bold uppercase tracking-wider text-slate-200 transition-all duration-150 hover:bg-slate-700 hover:text-white focus:outline-none focus:ring-2 focus:ring-slate-500 focus:ring-offset-2 focus:ring-offset-arena-base active:scale-[0.98] disabled:opacity-40"
    >
        <slot />
    </button>
</template>
