<template>
    <button
        class="inline-flex items-center justify-center rounded-xl bg-courtOrange px-5 py-2.5 font-display text-sm font-black uppercase tracking-wider text-white shadow-lg shadow-courtOrange/25 transition-all duration-150 hover:bg-courtOrange-hover hover:shadow-orange-glow focus:outline-none focus:ring-2 focus:ring-courtOrange focus:ring-offset-2 focus:ring-offset-arena-base active:scale-[0.98] disabled:opacity-40 -skew-x-3"
    >
        <span class="inline-flex items-center gap-1.5 transform skew-x-3">
            <slot />
        </span>
    </button>
</template>
