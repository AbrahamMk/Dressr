<script setup>
import AppIcon from '@/Components/UI/AppIcon.vue';

defineProps({
    icon: {
        type: String,
        default: null,
    },
    title: {
        type: String,
        required: true,
    },
    description: {
        type: String,
        default: null,
    },
});
</script>

<template>
    <div
        class="flex flex-col items-center justify-center rounded-card border border-dashed border-border-strong bg-surface px-6 py-12 text-center"
    >
        <span
            v-if="icon"
            class="mb-4 inline-flex h-11 w-11 items-center justify-center rounded-full bg-surface-muted text-text-muted"
        >
            <AppIcon :name="icon" class="h-5 w-5" />
        </span>

        <h3 class="text-base font-semibold text-text">{{ title }}</h3>

        <p v-if="description" class="mt-1 max-w-md text-sm text-text-muted">
            {{ description }}
        </p>

        <div v-if="$slots.action" class="mt-5">
            <slot name="action" />
        </div>
    </div>
</template>
