<script setup>
defineProps({
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
        class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between"
    >
        <div class="min-w-0">
            <slot name="breadcrumbs" />

            <h1 class="text-xl font-semibold tracking-tight text-text">
                {{ title }}
            </h1>

            <p v-if="description" class="mt-1 max-w-2xl text-sm text-text-muted">
                {{ description }}
            </p>
        </div>

        <div v-if="$slots.actions" class="flex shrink-0 items-center gap-2">
            <slot name="actions" />
        </div>
    </div>
</template>
