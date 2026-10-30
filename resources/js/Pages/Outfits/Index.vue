<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import AppButton from '@/Components/UI/AppButton.vue';
import EmptyState from '@/Components/UI/EmptyState.vue';
import PageHeader from '@/Components/UI/PageHeader.vue';
import { Head } from '@inertiajs/vue3';
</script>

<template>
    <Head title="Outfits" />

    <AppLayout>
        <div class="space-y-6">
            <PageHeader
                title="Outfits"
                description="Generated and saved combinations will live here."
            />

            <EmptyState
                icon="sparkles"
                title="Outfit generation is not built yet"
                description="The rule-based generator arrives in a later phase. Your wardrobe and measurements are already in place for it."
            >
                <template #action>
                    <AppButton :href="route('wardrobe.index')" variant="outline">
                        Review the wardrobe
                    </AppButton>
                </template>
            </EmptyState>
        </div>
    </AppLayout>
</template>
