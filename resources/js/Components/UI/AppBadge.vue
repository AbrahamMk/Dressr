<script setup>
import { computed } from 'vue';

const props = defineProps({
    variant: {
        type: String,
        default: 'neutral',
    },
});

const variants = {
    neutral: 'border-border bg-surface-muted text-text-secondary',
    primary: 'border-primary-100 bg-primary-50 text-primary-700',
    success: 'border-success-100 bg-success-50 text-success-700',
    warning: 'border-warning-100 bg-warning-50 text-warning-700',
    danger: 'border-danger-100 bg-danger-50 text-danger-700',
    info: 'border-info-100 bg-info-50 text-info-700',
};

const classes = computed(() => variants[props.variant] ?? variants.neutral);
</script>

<template>
    <span
        class="inline-flex items-center gap-1 rounded-full border px-2.5 py-0.5 text-xs font-medium"
        :class="classes"
    >
        <slot />
    </span>
</template>
