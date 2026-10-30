<script setup>
defineOptions({ inheritAttrs: false });

const model = defineModel({
    type: [String, Number],
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

        <input
            :id="id"
            v-model="model"
            :aria-invalid="error ? 'true' : undefined"
            v-bind="$attrs"
            class="h-10 w-full rounded-control border bg-surface px-3 text-sm text-text placeholder:text-text-muted focus:border-primary-500 focus:ring-2 focus:ring-primary-600/20 focus:outline-none disabled:bg-surface-muted disabled:text-text-muted"
            :class="
                error
                    ? 'border-danger-600 focus:border-danger-600 focus:ring-danger-600/20'
                    : 'border-border-strong'
            "
        />

        <p v-if="error" class="mt-1 text-xs text-danger-600">{{ error }}</p>
    </div>
</template>
