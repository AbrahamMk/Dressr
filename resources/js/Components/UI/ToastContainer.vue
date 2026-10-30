<script setup>
import AppIcon from '@/Components/UI/AppIcon.vue';
import { useToast } from '@/Composables/useToast.js';

const { toasts, dismiss } = useToast();

const variants = {
    success: 'border-success-100 bg-success-50 text-success-700',
    warning: 'border-warning-100 bg-warning-50 text-warning-700',
    danger: 'border-danger-100 bg-danger-50 text-danger-700',
    info: 'border-info-100 bg-info-50 text-info-700',
};
</script>

<template>
    <div
        class="pointer-events-none fixed inset-x-0 bottom-0 z-50 flex flex-col items-center gap-2 p-4 sm:items-end"
        aria-live="polite"
    >
        <TransitionGroup
            enter-active-class="transition duration-200 ease-out"
            enter-from-class="translate-y-2 opacity-0"
            leave-active-class="transition duration-150 ease-in"
            leave-to-class="opacity-0"
        >
            <div
                v-for="toast in toasts"
                :key="toast.id"
                class="pointer-events-auto flex w-full max-w-sm items-start gap-3 rounded-control border px-4 py-3 shadow-card"
                :class="variants[toast.variant] ?? variants.info"
            >
                <AppIcon name="check" class="mt-0.5 h-4 w-4 shrink-0" />

                <p class="min-w-0 flex-1 text-sm">{{ toast.message }}</p>

                <button
                    type="button"
                    class="rounded p-0.5 hover:bg-black/5"
                    aria-label="Dismiss notification"
                    @click="dismiss(toast.id)"
                >
                    <AppIcon name="x" class="h-4 w-4" />
                </button>
            </div>
        </TransitionGroup>
    </div>
</template>
