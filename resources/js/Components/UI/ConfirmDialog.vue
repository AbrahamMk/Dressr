<script setup>
import AppButton from '@/Components/UI/AppButton.vue';
import AppModal from '@/Components/UI/AppModal.vue';

defineProps({
    show: {
        type: Boolean,
        default: false,
    },
    title: {
        type: String,
        default: 'Are you sure?',
    },
    description: {
        type: String,
        default: null,
    },
    confirmLabel: {
        type: String,
        default: 'Confirm',
    },
    processing: {
        type: Boolean,
        default: false,
    },
});

defineEmits(['confirm', 'close']);
</script>

<template>
    <AppModal :show="show" max-width="md" @close="$emit('close')">
        <div class="p-5">
            <h2 class="text-base font-semibold text-text">{{ title }}</h2>

            <p v-if="description" class="mt-1 text-sm text-text-muted">
                {{ description }}
            </p>

            <div class="mt-6 flex justify-end gap-2">
                <AppButton variant="outline" @click="$emit('close')">
                    Cancel
                </AppButton>
                <AppButton
                    variant="danger"
                    :loading="processing"
                    @click="$emit('confirm')"
                >
                    {{ confirmLabel }}
                </AppButton>
            </div>
        </div>
    </AppModal>
</template>
