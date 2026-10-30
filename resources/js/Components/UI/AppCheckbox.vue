<script setup>
defineOptions({ inheritAttrs: false });

const model = defineModel({
    type: Boolean,
    default: false,
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
        <label class="flex items-center gap-2">
            <input
                :id="id"
                v-model="model"
                type="checkbox"
                v-bind="$attrs"
                class="h-4 w-4 rounded border-border-strong text-primary-600 focus:ring-primary-600"
            />
            <span v-if="label" class="text-sm text-text-secondary">
                {{ label }}
            </span>
        </label>

        <p v-if="error" class="mt-1 text-xs text-danger-600">{{ error }}</p>
    </div>
</template>
