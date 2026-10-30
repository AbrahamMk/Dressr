<script setup>
import { computed } from 'vue';
import AppIcon from '@/Components/UI/AppIcon.vue';

const props = defineProps({
    variant: {
        type: String,
        default: 'info',
    },
    title: {
        type: String,
        default: null,
    },
    dismissible: {
        type: Boolean,
        default: false,
    },
});

defineEmits(['close']);

const variants = {
    success: 'border-success-100 bg-success-50 text-success-700',
    warning: 'border-warning-100 bg-warning-50 text-warning-700',
    danger: 'border-danger-100 bg-danger-50 text-danger-700',
    info: 'border-info-100 bg-info-50 text-info-700',
};

const classes = computed(() => variants[props.variant] ?? variants.info);
</script>

<template>
    <div
        class="flex items-start gap-3 rounded-control border px-4 py-3 text-sm"
        :class="classes"
        role="status"
    >
        <AppIcon name="alert" class="mt-0.5 h-4 w-4 shrink-0" />

        <div class="min-w-0 flex-1">
            <p v-if="title" class="font-semibold">{{ title }}</p>
            <div :class="title ? 'mt-0.5' : ''">
                <slot />
            </div>
        </div>

        <button
            v-if="dismissible"
            type="button"
            class="rounded p-0.5 hover:bg-black/5"
            aria-label="Dismiss"
            @click="$emit('close')"
        >
            <AppIcon name="x" class="h-4 w-4" />
        </button>
    </div>
</template>
