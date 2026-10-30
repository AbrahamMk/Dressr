<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import AppButton from '@/Components/UI/AppButton.vue';
import EmptyState from '@/Components/UI/EmptyState.vue';
import PageHeader from '@/Components/UI/PageHeader.vue';
import { Head } from '@inertiajs/vue3';
</script>

<template>
    <Head title="Wear Log" />

    <AppLayout>
        <div class="space-y-6">
            <PageHeader
                title="Wear Log"
                description="Track what you wore so recommendations stop repeating."
            />

            <EmptyState
                icon="calendar"
                title="Wear logging is not built yet"
                description="Logging arrives in a later phase, together with the variety penalty in outfit scoring."
            >
                <template #action>
                    <AppButton :href="route('dashboard')" variant="outline">
                        Back to dashboard
                    </AppButton>
                </template>
            </EmptyState>
        </div>
    </AppLayout>
</template>
