<script setup>
defineOptions({ inheritAttrs: false });

const model = defineModel({
    type: String,
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
    rows: {
        type: [String, Number],
        default: 3,
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

        <textarea
            :id="id"
            v-model="model"
            :rows="rows"
            :aria-invalid="error ? 'true' : undefined"
            v-bind="$attrs"
            class="w-full rounded-control border bg-surface px-3 py-2 text-sm text-text placeholder:text-text-muted focus:border-primary-500 focus:ring-2 focus:ring-primary-600/20 focus:outline-none"
            :class="
                error ? 'border-danger-600' : 'border-border-strong'
            "
        />

        <p v-if="error" class="mt-1 text-xs text-danger-600">{{ error }}</p>
    </div>
</template>
