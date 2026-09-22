<template>
    <div>
        <!-- Card Variant -->
        <div v-if="variant === 'card'" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
            <div 
                v-for="i in count" 
                :key="`card-${i}`" 
                class="bg-courtSlate-100 rounded-xl p-4 animate-pulse flex flex-col gap-4"
            >
                <div class="h-36 w-full rounded-lg bg-courtSlate-200"></div>
                <div class="flex flex-col gap-2">
                    <div class="h-4 w-3/4 rounded bg-courtSlate-200"></div>
                    <div class="h-3 w-1/2 rounded bg-courtSlate-200"></div>
                </div>
                <div class="mt-auto">
                    <div class="h-5 w-1/3 rounded bg-courtSlate-200"></div>
                </div>
            </div>
        </div>

        <!-- Table Row Variant -->
        <div v-else-if="variant === 'table-row'" class="flex flex-col gap-3">
            <div 
                v-for="i in count" 
                :key="`table-row-${i}`" 
                class="bg-courtSlate-100 rounded-xl p-4 animate-pulse flex items-center justify-between"
            >
                <div class="h-4 w-1/6 rounded bg-courtSlate-200"></div>
                <div class="h-4 w-1/4 rounded bg-courtSlate-200"></div>
                <div class="h-4 w-1/12 rounded bg-courtSlate-200"></div>
                <div class="h-4 w-1/5 rounded bg-courtSlate-200"></div>
                <div class="h-4 w-1/12 rounded bg-courtSlate-200"></div>
                <div class="h-8 w-1/12 rounded-lg bg-courtSlate-200"></div>
            </div>
        </div>

        <!-- Slot Grid Variant -->
        <div v-else-if="variant === 'slot-grid'" class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 xl:grid-cols-8 gap-2.5 sm:gap-3">
            <div 
                v-for="i in count" 
                :key="`slot-${i}`" 
                class="w-full aspect-square rounded-lg bg-courtSlate-200 animate-pulse"
            ></div>
        </div>
    </div>
</template>

<script setup>
defineProps({
    variant: {
        type: String,
        default: 'card',
        validator: (value) => ['card', 'table-row', 'slot-grid'].includes(value)
    },
    count: {
        type: Number,
        default: 3
    }
});
</script>
