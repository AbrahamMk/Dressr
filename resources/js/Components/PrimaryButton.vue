<script setup>
import AppButton from '@/Components/UI/AppButton.vue';
</script>

<template>
    <AppButton v-bind="$attrs">
        <slot />
    </AppButton>
</template>
