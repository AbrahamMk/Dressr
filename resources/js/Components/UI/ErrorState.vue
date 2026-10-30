<script setup>
defineProps({
    title: {
        type: String,
        default: 'Something went wrong',
    },
    description: {
        type: String,
        default: 'Try again. If the problem keeps happening, contact support.',
    },
});
</script>

<template>
    <div
        class="rounded-card border border-danger-100 bg-danger-50 px-6 py-8 text-center"
    >
        <h3 class="text-base font-semibold text-danger-700">{{ title }}</h3>
        <p class="mt-1 text-sm text-danger-700/80">{{ description }}</p>

        <div v-if="$slots.action" class="mt-4">
            <slot name="action" />
        </div>
    </div>
</template>
