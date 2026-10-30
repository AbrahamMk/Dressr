<script setup>
import AppButton from '@/Components/UI/AppButton.vue';

defineProps({
    type: {
        type: String,
        default: 'button',
    },
});
</script>

<template>
    <AppButton variant="outline" :type="type" v-bind="$attrs">
        <slot />
    </AppButton>
</template>
