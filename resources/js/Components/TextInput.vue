<script setup>
import { onMounted, ref } from 'vue';

const model = defineModel({
    type: String,
    required: true,
});

const input = ref(null);

onMounted(() => {
    if (input.value.hasAttribute('autofocus')) {
        input.value.focus();
    }
});

defineExpose({ focus: () => input.value.focus() });
</script>

<template>
    <input
        ref="input"
        v-model="model"
        class="h-10 rounded-control border-border-strong bg-surface px-3 text-sm text-text placeholder:text-text-muted focus:border-primary-500 focus:ring-2 focus:ring-primary-600/20"
    />
</template>
