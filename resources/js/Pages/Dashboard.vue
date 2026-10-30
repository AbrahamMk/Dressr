<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import AppBadge from '@/Components/UI/AppBadge.vue';
import AppButton from '@/Components/UI/AppButton.vue';
import AppCard from '@/Components/UI/AppCard.vue';
import AppIcon from '@/Components/UI/AppIcon.vue';
import EmptyState from '@/Components/UI/EmptyState.vue';
import PageHeader from '@/Components/UI/PageHeader.vue';
import { Head, Link } from '@inertiajs/vue3';

defineProps({
    currentMeasurement: {
        type: Object,
        default: null,
    },
    wardrobeItems: {
        type: Number,
        default: 0,
    },
});

const rows = (measurement) => [
    { label: 'Height', value: measurement.height_cm },
    { label: 'Chest / bust', value: measurement.chest_cm },
    { label: 'Waist', value: measurement.waist_cm },
    { label: 'Hips', value: measurement.hips_cm },
    { label: 'Shoulder', value: measurement.shoulder_width_cm },
    { label: 'Inseam', value: measurement.inseam_cm },
];

const shortcuts = [
    {
        label: 'Add a wardrobe item',
        description: 'Photos, colours and optional garment measurements.',
        route: 'wardrobe.create',
        icon: 'plus',
    },
    {
        label: 'Record measurements',
        description: 'Keep the reference your fit advice is based on current.',
        route: 'measurements.index',
        icon: 'ruler',
    },
    {
        label: 'Browse the wardrobe',
        description: 'Filter everything you own by category, season or colour.',
        route: 'wardrobe.index',
        icon: 'shirt',
    },
];
</script>

<template>
    <Head title="Dashboard" />

    <AppLayout>
        <div class="space-y-6">
            <PageHeader
                title="Dashboard"
                description="Your measurements and wardrobe at a glance."
            >
                <template #actions>
                    <AppButton :href="route('wardrobe.create')">
                        <AppIcon name="plus" class="h-4 w-4" />
                        Add item
                    </AppButton>
                </template>
            </PageHeader>

            <div class="grid gap-4 lg:grid-cols-3">
                <AppCard class="lg:col-span-2" :padded="false">
                    <template #header>
                        <div>
                            <h2 class="text-base font-semibold text-text">
                                Current measurements
                            </h2>
                            <p class="mt-0.5 text-sm text-text-muted">
                                The newest set is used for fit advice.
                            </p>
                        </div>

                        <AppBadge v-if="currentMeasurement" variant="neutral">
                            Recorded
                            {{ currentMeasurement.measured_on?.slice(0, 10) }}
                        </AppBadge>
                    </template>

                    <div v-if="currentMeasurement" class="p-5">
                        <dl class="grid grid-cols-2 gap-4 sm:grid-cols-3">
                            <div
                                v-for="row in rows(currentMeasurement)"
                                :key="row.label"
                            >
                                <dt
                                    class="text-xs uppercase tracking-wide text-text-muted"
                                >
                                    {{ row.label }}
                                </dt>
                                <dd
                                    class="mt-1 text-lg font-semibold tabular-nums text-text"
                                >
                                    {{ row.value ?? '—'
                                    }}<span
                                        v-if="row.value"
                                        class="ms-1 text-xs font-normal text-text-muted"
                                    >
                                        cm
                                    </span>
                                </dd>
                            </div>
                        </dl>
                    </div>

                    <div v-else class="p-5">
                        <EmptyState
                            icon="ruler"
                            title="No measurements yet"
                            description="Add height, chest, waist and hips to unlock fit feedback for every garment."
                        >
                            <template #action>
                                <AppButton :href="route('measurements.index')">
                                    Add measurements
                                </AppButton>
                            </template>
                        </EmptyState>
                    </div>

                    <template v-if="currentMeasurement" #footer>
                        <AppButton
                            :href="route('measurements.index')"
                            variant="ghost"
                            size="sm"
                        >
                            Manage measurements
                        </AppButton>
                    </template>
                </AppCard>

                <div class="space-y-4">
                    <AppCard>
                        <p
                            class="text-xs uppercase tracking-wide text-text-muted"
                        >
                            Wardrobe
                        </p>
                        <p
                            class="mt-2 text-3xl font-semibold tabular-nums text-text"
                        >
                            {{ wardrobeItems }}
                        </p>
                        <p class="mt-1 text-sm text-text-muted">
                            {{ wardrobeItems === 1 ? 'item' : 'items' }} saved
                        </p>

                        <AppButton
                            :href="route('wardrobe.index')"
                            variant="outline"
                            size="sm"
                            class="mt-4"
                            block
                        >
                            Open wardrobe
                        </AppButton>
                    </AppCard>

                    <AppCard :padded="false">
                        <template #header>
                            <h2 class="text-base font-semibold text-text">
                                Next steps
                            </h2>
                        </template>

                        <ul class="divide-y divide-border">
                            <li v-for="shortcut in shortcuts" :key="shortcut.route">
                                <Link
                                    :href="route(shortcut.route)"
                                    class="flex items-start gap-3 px-5 py-3 transition-colors hover:bg-surface-muted"
                                >
                                    <AppIcon
                                        :name="shortcut.icon"
                                        class="mt-0.5 h-4 w-4 shrink-0 text-text-muted"
                                    />
                                    <span class="min-w-0">
                                        <span
                                            class="block text-sm font-medium text-text"
                                        >
                                            {{ shortcut.label }}
                                        </span>
                                        <span
                                            class="mt-0.5 block text-xs text-text-muted"
                                        >
                                            {{ shortcut.description }}
                                        </span>
                                    </span>
                                </Link>
                            </li>
                        </ul>
                    </AppCard>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
