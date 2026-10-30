<script setup>
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';

const props = defineProps({
    variant: {
        type: String,
        default: 'primary',
    },
    size: {
        type: String,
        default: 'md',
    },
    type: {
        type: String,
        default: 'button',
    },
    href: {
        type: String,
        default: null,
    },
    loading: {
        type: Boolean,
        default: false,
    },
    disabled: {
        type: Boolean,
        default: false,
    },
    block: {
        type: Boolean,
        default: false,
    },
});

const variants = {
    primary:
        'bg-primary-600 text-white hover:bg-primary-700 active:bg-primary-800',
    secondary:
        'border border-border bg-surface-muted text-text hover:bg-surface',
    outline:
        'border border-border-strong bg-surface text-text-secondary hover:bg-surface-muted',
    ghost: 'text-text-secondary hover:bg-surface-muted hover:text-text',
    'danger-ghost': 'text-danger-600 hover:bg-danger-50 hover:text-danger-700',
    danger:
        'bg-danger-600 text-white hover:bg-danger-700 active:bg-danger-700',
};

const sizes = {
    sm: 'h-8 px-3 text-xs',
    md: 'h-10 px-4 text-sm',
    lg: 'h-11 px-5 text-sm',
    icon: 'h-10 w-10 p-0',
};

const classes = computed(() => [
    'inline-flex shrink-0 items-center justify-center gap-2 rounded-control font-medium transition-colors',
    'focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary-600 focus-visible:ring-offset-2',
    'disabled:pointer-events-none disabled:opacity-50',
    variants[props.variant] ?? variants.primary,
    sizes[props.size] ?? sizes.md,
    props.block ? 'w-full' : '',
]);
</script>

<template>
    <component
        :is="href ? Link : 'button'"
        :href="href ?? undefined"
        :type="href ? undefined : type"
        :disabled="href ? undefined : disabled || loading"
        :class="classes"
    >
        <svg
            v-if="loading"
            class="h-4 w-4 animate-spin"
            viewBox="0 0 24 24"
            fill="none"
            aria-hidden="true"
        >
            <circle
                class="opacity-25"
                cx="12"
                cy="12"
                r="10"
                stroke="currentColor"
                stroke-width="4"
            />
            <path
                class="opacity-75"
                fill="currentColor"
                d="M4 12a8 8 0 0 1 8-8v4a4 4 0 0 0-4 4H4Z"
            />
        </svg>

        <slot />
    </component>
</template>
