<template>
    <div class="bg-courtSlate-50 rounded-2xl border-2 border-dashed border-courtSlate-300 p-10 text-center flex flex-col items-center justify-center">
        <div class="w-16 h-16 mx-auto rounded-2xl bg-courtSlate-200 border border-courtSlate-300 flex items-center justify-center text-3xl text-courtSlate-400 mb-4">
            {{ icon }}
        </div>
        
        <h3 class="font-display font-black text-lg text-courtSlate-900 uppercase tracking-tight mb-2">
            {{ title }}
        </h3>
        
        <p v-if="description" class="text-sm text-courtSlate-500 max-w-md mx-auto mb-6">
            {{ description }}
        </p>
        
        <slot></slot>
        
        <Link 
            v-if="actionLabel && actionUrl" 
            :href="actionUrl" 
            class="mt-4 px-6 py-2.5 bg-arena-base text-volt font-display font-black text-xs uppercase tracking-wider rounded-xl shadow-sm hover:bg-arena-surface hover:shadow-volt-glow-sm -skew-x-3 transition-all duration-200 inline-flex items-center justify-center"
        >
            <span class="skew-x-3">{{ actionLabel }}</span>
        </Link>
    </div>
</template>

<script setup>
import { Link } from '@inertiajs/vue3';

defineProps({
    icon: {
        type: String,
        default: '📋'
    },
    title: {
        type: String,
        required: true
    },
    description: {
        type: String,
        default: null
    },
    actionLabel: {
        type: String,
        default: null
    },
    actionUrl: {
        type: String,
        default: null
    }
});
</script>
