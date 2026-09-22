<script setup>
import { Link } from '@inertiajs/vue3';

const props = defineProps({
    href: {
        type: String,
        required: true,
    },
    active: {
        type: Boolean,
        default: false,
    },
    name: {
        type: String,
        required: true,
    },
    icon: {
        type: String,
        default: null,
    },
    badge: {
        type: String,
        default: null,
    },
    disabled: {
        type: Boolean,
        default: false,
    },
});

const emit = defineEmits(['navigate']);

const handleClick = () => {
    if (!props.disabled) {
        emit('navigate');
    }
};
</script>

<template>
    <Link
        v-if="!disabled"
        :href="href"
        @click="handleClick"
        :class="[
            active
                ? 'bg-arena-surface text-volt border-l-4 border-volt font-bold shadow-xs'
                : 'text-courtSlate-300 hover:bg-arena-card hover:text-white',
            'group flex items-center rounded-r-xl px-3 py-2.5 text-sm font-medium transition-all duration-150',
        ]"
    >
        <slot name="icon">
            <svg
                v-if="icon"
                class="mr-3 h-5 w-5 flex-shrink-0 transition-transform duration-150 group-hover:scale-105"
                :class="active ? 'text-volt' : 'text-courtSlate-400 group-hover:text-white'"
                fill="none"
                viewBox="0 0 24 24"
                stroke="currentColor"
                stroke-width="1.8"
            >
                <path stroke-linecap="round" stroke-linejoin="round" :d="icon" />
            </svg>
        </slot>
        <span class="truncate">{{ name }}</span>
        <span
            v-if="badge"
            class="ml-auto text-[10px] font-display font-black uppercase tracking-wider px-2 py-0.5 rounded -skew-x-6"
            :class="active ? 'bg-volt text-arena-base' : 'bg-arena-surface text-courtSlate-300 border border-arena-border'"
        >
            <span class="skew-x-6">{{ badge }}</span>
        </span>
    </Link>

    <span
        v-else
        class="group flex cursor-not-allowed items-center rounded-r-xl px-3 py-2.5 text-sm font-medium text-courtSlate-500 opacity-60"
    >
        <slot name="icon">
            <svg
                v-if="icon"
                class="mr-3 h-5 w-5 flex-shrink-0 text-courtSlate-600"
                fill="none"
                viewBox="0 0 24 24"
                stroke="currentColor"
                stroke-width="1.8"
            >
                <path stroke-linecap="round" stroke-linejoin="round" :d="icon" />
            </svg>
        </slot>
        <span class="truncate">{{ name }}</span>
        <span
            v-if="badge"
            class="ml-auto text-[10px] font-display font-bold uppercase tracking-wider bg-arena-card px-1.5 py-0.5 rounded text-courtSlate-500"
        >
            {{ badge }}
        </span>
    </span>
</template>

