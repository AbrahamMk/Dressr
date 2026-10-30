<script setup>
defineOptions({ inheritAttrs: false });

const model = defineModel({
    default: '',
});

defineProps({
    id: {
        type: String,
        default: null,
    },
    label: {
        type: String,
        default: null,
    },
    error: {
        type: String,
        default: null,
    },
});
</script>

<template>
    <div>
        <label
            v-if="label"
            :for="id"
            class="mb-1 block text-sm font-medium text-text-secondary"
        >
            {{ label }}
        </label>

        <select
            :id="id"
            v-model="model"
            :aria-invalid="error ? 'true' : undefined"
            v-bind="$attrs"
            class="h-10 w-full rounded-control border bg-surface px-3 text-sm text-text focus:border-primary-500 focus:ring-2 focus:ring-primary-600/20 focus:outline-none disabled:bg-surface-muted disabled:text-text-muted"
            :class="
                error
                    ? 'border-danger-600'
                    : 'border-border-strong'
            "
        >
            <slot />
        </select>

        <p v-if="error" class="mt-1 text-xs text-danger-600">{{ error }}</p>
    </div>
</template>
