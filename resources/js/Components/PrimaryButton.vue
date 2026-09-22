<template>
    <button
        class="inline-flex items-center justify-center rounded-xl bg-volt px-5 py-2.5 font-display text-sm font-black uppercase tracking-wider text-arena-base shadow-lg shadow-volt/20 transition-all duration-150 hover:bg-volt-hover hover:shadow-volt-glow-sm focus:outline-none focus:ring-2 focus:ring-volt focus:ring-offset-2 focus:ring-offset-arena-base active:scale-[0.98] disabled:opacity-40 disabled:cursor-not-allowed -skew-x-3"
    >
        <span class="inline-flex items-center gap-1.5 transform skew-x-3">
            <slot />
        </span>
    </button>
</template>
