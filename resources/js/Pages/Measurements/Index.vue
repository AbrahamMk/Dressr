<script setup>
import { computed, ref } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import AppBadge from '@/Components/UI/AppBadge.vue';
import AppButton from '@/Components/UI/AppButton.vue';
import AppCard from '@/Components/UI/AppCard.vue';
import AppInput from '@/Components/UI/AppInput.vue';
import AppPagination from '@/Components/UI/AppPagination.vue';
import AppTable from '@/Components/UI/AppTable.vue';
import EmptyState from '@/Components/UI/EmptyState.vue';
import PageHeader from '@/Components/UI/PageHeader.vue';
import { Head, useForm } from '@inertiajs/vue3';

defineProps({
    measurements: {
        type: Object,
        required: true,
    },
    current: {
        type: Object,
        default: null,
    },
});

const fields = [
    { key: 'weight_kg', label: 'Weight (kg)' },
    { key: 'neck_cm', label: 'Neck (cm)' },
    { key: 'shoulder_width_cm', label: 'Shoulder width (cm)' },
    { key: 'chest_cm', label: 'Chest / bust (cm)', required: true },
    { key: 'waist_cm', label: 'Waist (cm)', required: true },
    { key: 'hips_cm', label: 'Hips (cm)', required: true },
    { key: 'inseam_cm', label: 'Inseam (cm)' },
    { key: 'arm_length_cm', label: 'Arm length (cm)' },
    { key: 'thigh_cm', label: 'Thigh (cm)' },
];

const form = useForm({
    measured_on: new Date().toISOString().slice(0, 10),
    height_cm: '',
    weight_kg: '',
    neck_cm: '',
    shoulder_width_cm: '',
    chest_cm: '',
    waist_cm: '',
    hips_cm: '',
    inseam_cm: '',
    arm_length_cm: '',
    thigh_cm: '',
});

const editingId = ref(null);
const formTitle = computed(() =>
    editingId.value ? 'Edit measurement' : 'Add measurement',
);

const startEdit = (measurement) => {
    editingId.value = measurement.id;
    form.clearErrors();
    form.measured_on = measurement.measured_on.slice(0, 10);
    form.height_cm = measurement.height_cm ?? '';

    fields.forEach((field) => {
        form[field.key] = measurement[field.key] ?? '';
    });
};

const cancelEdit = () => {
    editingId.value = null;
    form.reset();
    form.clearErrors();
};

const submit = () => {
    if (editingId.value) {
        form.patch(route('measurements.update', editingId.value), {
            preserveScroll: true,
            onSuccess: () => {
                editingId.value = null;
                form.reset();
            },
        });

        return;
    }

    form.post(route('measurements.store'), {
        preserveScroll: true,
        onSuccess: () => form.reset(),
    });
};
</script>

<template>
    <Head title="Measurements" />

    <AppLayout>
        <div class="space-y-6">
            <PageHeader
                title="Measurements"
                description="Height, chest, waist and hips are required. The rest sharpens fit advice."
            />

            <AppCard v-if="current" :padded="false">
                <template #header>
                    <h2 class="text-base font-semibold text-text">
                        Current measurements
                    </h2>
                    <AppBadge variant="neutral">
                        {{ current.measured_on?.slice(0, 10) }}
                    </AppBadge>
                </template>

                <div class="p-5">
                    <dl class="grid grid-cols-2 gap-4 sm:grid-cols-4">
                        <div>
                            <dt
                                class="text-xs uppercase tracking-wide text-text-muted"
                            >
                                Height
                            </dt>
                            <dd
                                class="mt-1 text-lg font-semibold tabular-nums text-text"
                            >
                                {{ current.height_cm }} cm
                            </dd>
                        </div>
                        <div>
                            <dt
                                class="text-xs uppercase tracking-wide text-text-muted"
                            >
                                Chest / bust
                            </dt>
                            <dd
                                class="mt-1 text-lg font-semibold tabular-nums text-text"
                            >
                                {{ current.chest_cm }} cm
                            </dd>
                        </div>
                        <div>
                            <dt
                                class="text-xs uppercase tracking-wide text-text-muted"
                            >
                                Waist
                            </dt>
                            <dd
                                class="mt-1 text-lg font-semibold tabular-nums text-text"
                            >
                                {{ current.waist_cm }} cm
                            </dd>
                        </div>
                        <div>
                            <dt
                                class="text-xs uppercase tracking-wide text-text-muted"
                            >
                                Hips
                            </dt>
                            <dd
                                class="mt-1 text-lg font-semibold tabular-nums text-text"
                            >
                                {{ current.hips_cm }} cm
                            </dd>
                        </div>
                    </dl>
                </div>
            </AppCard>

            <AppCard :padded="false">
                <template #header>
                    <h2 class="text-base font-semibold text-text">
                        {{ formTitle }}
                    </h2>
                </template>

                <form class="space-y-5 p-5" @submit.prevent="submit">
                    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                        <AppInput
                            id="measured_on"
                            v-model="form.measured_on"
                            label="Measured on"
                            type="date"
                            :error="form.errors.measured_on"
                        />

                        <AppInput
                            id="height_cm"
                            v-model="form.height_cm"
                            label="Height (cm)"
                            type="number"
                            step="0.1"
                            min="50"
                            max="250"
                            :error="form.errors.height_cm"
                        />

                        <AppInput
                            v-for="field in fields"
                            :id="field.key"
                            :key="field.key"
                            v-model="form[field.key]"
                            :label="
                                field.required
                                    ? `${field.label} *`
                                    : field.label
                            "
                            type="number"
                            step="0.1"
                            :error="form.errors[field.key]"
                        />
                    </div>

                    <div class="flex items-center gap-3">
                        <AppButton
                            type="submit"
                            :loading="form.processing"
                        >
                            {{ editingId ? 'Save changes' : 'Save measurement' }}
                        </AppButton>

                        <AppButton
                            v-if="editingId"
                            variant="outline"
                            @click="cancelEdit"
                        >
                            Cancel
                        </AppButton>
                    </div>
                </form>
            </AppCard>

            <AppCard :padded="false">
                <template #header>
                    <h2 class="text-base font-semibold text-text">History</h2>
                    <AppBadge>{{ measurements.data.length }} shown</AppBadge>
                </template>

                <div v-if="measurements.data.length === 0" class="p-5">
                    <EmptyState
                        icon="ruler"
                        title="No measurements recorded"
                        description="Saved measurements appear here, newest first."
                    />
                </div>

                <template v-else>
                    <AppTable
                        :headers="[
                            'Date',
                            'Height',
                            'Chest',
                            'Waist',
                            'Hips',
                            '',
                        ]"
                    >
                        <tr
                            v-for="measurement in measurements.data"
                            :key="measurement.id"
                        >
                            <td
                                class="whitespace-nowrap px-5 py-3 font-medium text-text"
                            >
                                {{ measurement.measured_on?.slice(0, 10) }}
                            </td>
                            <td class="px-5 py-3 tabular-nums text-text-secondary">
                                {{ measurement.height_cm }} cm
                            </td>
                            <td class="px-5 py-3 tabular-nums text-text-secondary">
                                {{ measurement.chest_cm }} cm
                            </td>
                            <td class="px-5 py-3 tabular-nums text-text-secondary">
                                {{ measurement.waist_cm }} cm
                            </td>
                            <td class="px-5 py-3 tabular-nums text-text-secondary">
                                {{ measurement.hips_cm }} cm
                            </td>
                            <td class="px-5 py-3 text-right">
                                <AppButton
                                    variant="ghost"
                                    size="sm"
                                    @click="startEdit(measurement)"
                                >
                                    Edit
                                </AppButton>
                            </td>
                        </tr>
                    </AppTable>

                    <div
                        v-if="measurements.links.length > 3"
                        class="border-t border-border px-5 py-4"
                    >
                        <AppPagination :links="measurements.links" />
                    </div>
                </template>
            </AppCard>
        </div>
    </AppLayout>
</template>
