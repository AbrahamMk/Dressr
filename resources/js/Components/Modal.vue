<script setup>
import AppModal from '@/Components/UI/AppModal.vue';

defineProps({
    show: {
        type: Boolean,
        default: false,
    },
    maxWidth: {
        type: String,
        default: '2xl',
    },
    closeable: {
        type: Boolean,
        default: true,
    },
});

defineEmits(['close']);
</script>

<template>
    <AppModal
        :show="show"
        :max-width="maxWidth"
        :closeable="closeable"
        @close="$emit('close')"
    >
        <slot />
    </AppModal>
</template>
