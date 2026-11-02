<script setup>
import { computed, ref } from 'vue';
import axios from 'axios';
import AppLayout from '@/Layouts/AppLayout.vue';
import AppAlert from '@/Components/UI/AppAlert.vue';
import AppBadge from '@/Components/UI/AppBadge.vue';
import AppButton from '@/Components/UI/AppButton.vue';
import AppCard from '@/Components/UI/AppCard.vue';
import AppCheckbox from '@/Components/UI/AppCheckbox.vue';
import AppIcon from '@/Components/UI/AppIcon.vue';
import AppInput from '@/Components/UI/AppInput.vue';
import AppSelect from '@/Components/UI/AppSelect.vue';
import AppTextarea from '@/Components/UI/AppTextarea.vue';
import PageHeader from '@/Components/UI/PageHeader.vue';
import { Head, useForm } from '@inertiajs/vue3';

const props = defineProps({
    item: {
        type: Object,
        default: null,
    },
    options: {
        type: Object,
        required: true,
    },
    aiConfigured: {
        type: Boolean,
        default: false,
    },
});

const isEdit = computed(() => props.item !== null);

const form = useForm({
    name: props.item?.name ?? '',
    category: props.item?.category ?? 'top',
    subcategory: props.item?.subcategory ?? '',
    primary_color_hex: props.item?.primary_color_hex ?? '#1e3a8a',
    secondary_color_hex: props.item?.secondary_color_hex ?? '',
    pattern: props.item?.pattern ?? 'solid',
    material: props.item?.material ?? '',
    seasons: props.item?.seasons ?? [],
    formality: props.item?.formality ?? 3,
    fit_type: props.item?.fit_type ?? 'regular',
    stretch: props.item?.stretch ?? 'none',
    cut_tags: props.item?.cut_tags ?? [],
    size_label: props.item?.size_label ?? '',
    g_chest_width: props.item?.g_chest_width ?? '',
    g_waist_width: props.item?.g_waist_width ?? '',
    g_hip_width: props.item?.g_hip_width ?? '',
    g_length: props.item?.g_length ?? '',
    g_inseam: props.item?.g_inseam ?? '',
    g_shoulder_width: props.item?.g_shoulder_width ?? '',
    notes: props.item?.notes ?? '',
    is_active: props.item?.is_active ?? true,
    photo: null,
});

const garmentFields = [
    { key: 'g_chest_width', label: 'Chest width (cm)' },
    { key: 'g_waist_width', label: 'Waist width (cm)' },
    { key: 'g_hip_width', label: 'Hip width (cm)' },
    { key: 'g_length', label: 'Length (cm)' },
    { key: 'g_inseam', label: 'Inseam (cm)' },
    { key: 'g_shoulder_width', label: 'Shoulder width (cm)' },
];

const label = (value) =>
    value
        .replaceAll('_', ' ')
        .replace(/\b\w/g, (letter) => letter.toUpperCase());

const fitVariant = (fitLabel) =>
    ({
        good_fit: 'success',
        acceptable: 'info',
        tight: 'warning',
        loose: 'warning',
        unknown: 'neutral',
    })[fitLabel] ?? 'neutral';

const toggle = (list, value) =>
    list.includes(value)
        ? list.filter((entry) => entry !== value)
        : [...list, value];

const preview = ref(props.item?.photo_url ?? null);
const analyzing = ref(false);
const analyzeNote = ref(null);
const analyzeError = ref(null);
const galleryInput = ref(null);
const cameraInput = ref(null);

const setPhotoFile = (file) => {
    form.photo = file;
    analyzeNote.value = null;
    analyzeError.value = null;

    if (file) {
        preview.value = URL.createObjectURL(file);
    }
};

const onPhoto = (event) => {
    const file = event.target.files?.[0] ?? null;
    if (file) {
        setPhotoFile(file);
    }
};

const applySuggestions = (suggestions) => {
    if (!suggestions) {
        return;
    }

    const fields = [
        'name',
        'category',
        'subcategory',
        'primary_color_hex',
        'secondary_color_hex',
        'pattern',
        'material',
        'seasons',
        'formality',
        'fit_type',
        'stretch',
        'cut_tags',
        'size_label',
        'notes',
    ];

    fields.forEach((key) => {
        if (suggestions[key] !== undefined && suggestions[key] !== null) {
            form[key] = suggestions[key];
        }
    });

    if (!suggestions.secondary_color_hex) {
        form.secondary_color_hex = '';
    }
};

const analyzePhoto = async () => {
    if (!form.photo) {
        analyzeError.value = 'Choose or take a photo first.';
        return;
    }

    analyzing.value = true;
    analyzeError.value = null;
    analyzeNote.value = null;

    const body = new FormData();
    body.append('photo', form.photo);

    try {
        const { data } = await axios.post(route('wardrobe.analyze'), body, {
            headers: { 'Content-Type': 'multipart/form-data' },
        });

        applySuggestions(data.suggestions);
        analyzeNote.value = data.note || 'Suggestions applied. Review before saving.';
    } catch (err) {
        analyzeError.value =
            err.response?.data?.message ||
            err.response?.data?.errors?.photo?.[0] ||
            'Could not analyse the photo. Try again or fill the form manually.';
    } finally {
        analyzing.value = false;
    }
};

const submit = () => {
    if (isEdit.value) {
        form.patch(route('wardrobe.update', props.item.id), {
            forceFormData: true,
            preserveScroll: true,
        });

        return;
    }

    form.post(route('wardrobe.store'), {
        forceFormData: true,
        preserveScroll: true,
    });
};
</script>

<template>
    <Head :title="isEdit ? 'Edit item' : 'Add item'" />

    <AppLayout>
        <div class="space-y-6">
            <PageHeader
                :title="isEdit ? 'Edit item' : 'Add item'"
                description="The more detail you add, the better the fit and outfit advice gets."
            >
                <template #actions>
                    <AppButton
                        :href="route('wardrobe.index')"
                        variant="outline"
                    >
                        Back to wardrobe
                    </AppButton>
                </template>
            </PageHeader>

            <form class="space-y-4" @submit.prevent="submit">
                <AppCard :padded="false">
                    <template #header>
                        <div>
                            <h2 class="text-base font-semibold text-text">
                                Photo
                            </h2>
                            <p class="mt-0.5 text-sm text-text-muted">
                                Take or import a photo, then analyse it to
                                prefill colours and garment details.
                            </p>
                        </div>
                    </template>

                    <div class="space-y-4 p-5">
                        <div class="grid gap-5 sm:grid-cols-2">
                            <div class="space-y-3">
                                <input
                                    ref="galleryInput"
                                    type="file"
                                    accept="image/jpeg,image/png,image/webp"
                                    class="hidden"
                                    @change="onPhoto"
                                />
                                <input
                                    ref="cameraInput"
                                    type="file"
                                    accept="image/jpeg,image/png,image/webp"
                                    capture="environment"
                                    class="hidden"
                                    @change="onPhoto"
                                />

                                <div class="flex flex-wrap gap-2">
                                    <AppButton
                                        type="button"
                                        variant="outline"
                                        @click="cameraInput?.click()"
                                    >
                                        <AppIcon name="camera" class="h-4 w-4" />
                                        Take photo
                                    </AppButton>
                                    <AppButton
                                        type="button"
                                        variant="outline"
                                        @click="galleryInput?.click()"
                                    >
                                        <AppIcon name="plus" class="h-4 w-4" />
                                        Import image
                                    </AppButton>
                                    <AppButton
                                        type="button"
                                        :loading="analyzing"
                                        :disabled="!form.photo"
                                        @click="analyzePhoto"
                                    >
                                        <AppIcon
                                            name="sparkles"
                                            class="h-4 w-4"
                                        />
                                        Analyse photo
                                    </AppButton>
                                </div>

                                <p class="text-xs text-text-muted">
                                    JPG, PNG or WebP, up to 5 MB.
                                    <span v-if="aiConfigured">
                                        AI suggestions are enabled.
                                    </span>
                                    <span v-else>
                                        No AI key — colour sampling only. Set
                                        AI_PROVIDER + key in .env for fuller
                                        suggestions.
                                    </span>
                                </p>

                                <p
                                    v-if="form.errors.photo"
                                    class="text-xs text-danger-600"
                                >
                                    {{ form.errors.photo }}
                                </p>
                            </div>

                            <div class="flex items-start justify-center">
                                <img
                                    v-if="preview"
                                    :src="preview"
                                    alt="Photo preview"
                                    class="h-40 w-40 rounded-card border border-border object-cover"
                                />
                                <div
                                    v-else
                                    class="flex h-40 w-40 flex-col items-center justify-center gap-2 rounded-card border border-dashed border-border-strong text-text-muted"
                                >
                                    <AppIcon name="camera" class="h-6 w-6" />
                                    <span class="text-xs">No photo</span>
                                </div>
                            </div>
                        </div>

                        <AppAlert v-if="analyzeNote" variant="success">
                            {{ analyzeNote }}
                        </AppAlert>
                        <AppAlert v-if="analyzeError" variant="danger">
                            {{ analyzeError }}
                        </AppAlert>
                    </div>
                </AppCard>

                <AppCard :padded="false">
                    <template #header>
                        <h2 class="text-base font-semibold text-text">Basics</h2>
                    </template>

                    <div class="grid gap-4 p-5 sm:grid-cols-2">
                        <AppInput
                            id="name"
                            v-model="form.name"
                            label="Name"
                            :error="form.errors.name"
                            required
                        />

                        <AppInput
                            id="size_label"
                            v-model="form.size_label"
                            label="Size label"
                            placeholder="M"
                            :error="form.errors.size_label"
                        />

                        <AppSelect
                            id="category"
                            v-model="form.category"
                            label="Category"
                            :error="form.errors.category"
                        >
                            <option
                                v-for="category in options.categories"
                                :key="category"
                                :value="category"
                            >
                                {{ label(category) }}
                            </option>
                        </AppSelect>

                        <AppInput
                            id="subcategory"
                            v-model="form.subcategory"
                            label="Type"
                            placeholder="tshirt, jeans, jacket…"
                            :error="form.errors.subcategory"
                            required
                        />
                    </div>
                </AppCard>

                <AppCard :padded="false">
                    <template #header>
                        <h2 class="text-base font-semibold text-text">
                            Colour &amp; fabric
                        </h2>
                    </template>

                    <div class="grid gap-4 p-5 sm:grid-cols-2">
                        <div>
                            <p
                                class="mb-1 block text-sm font-medium text-text-secondary"
                            >
                                Primary colour
                            </p>
                            <div class="flex items-center gap-2">
                                <input
                                    v-model="form.primary_color_hex"
                                    type="color"
                                    aria-label="Pick primary colour"
                                    class="h-10 w-12 shrink-0 cursor-pointer rounded-control border border-border-strong bg-surface p-1"
                                />
                                <AppInput
                                    v-model="form.primary_color_hex"
                                    type="text"
                                    placeholder="#1e3a8a"
                                    :error="form.errors.primary_color_hex"
                                />
                            </div>
                        </div>

                        <div>
                            <p
                                class="mb-1 block text-sm font-medium text-text-secondary"
                            >
                                Secondary colour
                            </p>
                            <div class="flex items-center gap-2">
                                <input
                                    v-model="form.secondary_color_hex"
                                    type="color"
                                    aria-label="Pick secondary colour"
                                    class="h-10 w-12 shrink-0 cursor-pointer rounded-control border border-border-strong bg-surface p-1"
                                />
                                <AppInput
                                    v-model="form.secondary_color_hex"
                                    type="text"
                                    placeholder="#ffffff"
                                    :error="form.errors.secondary_color_hex"
                                />
                            </div>
                        </div>

                        <AppSelect
                            id="pattern"
                            v-model="form.pattern"
                            label="Pattern"
                            :error="form.errors.pattern"
                        >
                            <option
                                v-for="pattern in options.patterns"
                                :key="pattern"
                                :value="pattern"
                            >
                                {{ label(pattern) }}
                            </option>
                        </AppSelect>

                        <AppInput
                            id="material"
                            v-model="form.material"
                            label="Material"
                            placeholder="cotton, linen, wool…"
                            :error="form.errors.material"
                        />
                    </div>
                </AppCard>

                <AppCard :padded="false">
                    <template #header>
                        <h2 class="text-base font-semibold text-text">
                            Seasons &amp; formality
                        </h2>
                    </template>

                    <div class="space-y-5 p-5">
                        <div>
                            <p
                                class="mb-2 text-sm font-medium text-text-secondary"
                            >
                                Seasons
                            </p>
                            <div class="flex flex-wrap gap-2">
                                <button
                                    v-for="season in options.seasons"
                                    :key="season"
                                    type="button"
                                    class="h-8 rounded-full border px-3 text-xs font-medium transition-colors"
                                    :class="
                                        form.seasons.includes(season)
                                            ? 'border-primary-600 bg-primary-600 text-white'
                                            : 'border-border-strong bg-surface text-text-secondary hover:bg-surface-muted'
                                    "
                                    :aria-pressed="form.seasons.includes(season)"
                                    @click="
                                        form.seasons = toggle(
                                            form.seasons,
                                            season,
                                        )
                                    "
                                >
                                    {{ label(season) }}
                                </button>
                            </div>
                            <p
                                v-if="form.errors.seasons"
                                class="mt-1 text-xs text-danger-600"
                            >
                                {{ form.errors.seasons }}
                            </p>
                        </div>

                        <div class="grid gap-4 sm:grid-cols-3">
                            <AppInput
                                id="formality"
                                v-model="form.formality"
                                label="Formality (1 loungewear – 5 formal)"
                                type="number"
                                min="1"
                                max="5"
                                :error="form.errors.formality"
                            />

                            <AppSelect
                                id="fit_type"
                                v-model="form.fit_type"
                                label="Fit"
                                :error="form.errors.fit_type"
                            >
                                <option
                                    v-for="fit in options.fitTypes"
                                    :key="fit"
                                    :value="fit"
                                >
                                    {{ label(fit) }}
                                </option>
                            </AppSelect>

                            <AppSelect
                                id="stretch"
                                v-model="form.stretch"
                                label="Stretch"
                                :error="form.errors.stretch"
                            >
                                <option
                                    v-for="stretch in options.stretches"
                                    :key="stretch"
                                    :value="stretch"
                                >
                                    {{ label(stretch) }}
                                </option>
                            </AppSelect>
                        </div>
                    </div>
                </AppCard>

                <AppCard :padded="false">
                    <template #header>
                        <div>
                            <h2 class="text-base font-semibold text-text">
                                Cut tags
                            </h2>
                            <p class="mt-0.5 text-sm text-text-muted">
                                Optional. These feed the silhouette engine.
                            </p>
                        </div>
                    </template>

                    <div class="p-5">
                        <div class="flex flex-wrap gap-2">
                            <button
                                v-for="tag in options.cutTags"
                                :key="tag"
                                type="button"
                                class="h-8 rounded-full border px-3 text-xs font-medium transition-colors"
                                :class="
                                    form.cut_tags.includes(tag)
                                        ? 'border-primary-600 bg-primary-50 text-primary-700'
                                        : 'border-border-strong bg-surface text-text-secondary hover:bg-surface-muted'
                                "
                                :aria-pressed="form.cut_tags.includes(tag)"
                                @click="
                                    form.cut_tags = toggle(form.cut_tags, tag)
                                "
                            >
                                {{ label(tag) }}
                            </button>
                        </div>
                        <p
                            v-if="form.errors.cut_tags"
                            class="mt-1 text-xs text-danger-600"
                        >
                            {{ form.errors.cut_tags }}
                        </p>
                    </div>
                </AppCard>

                <AppCard :padded="false">
                    <template #header>
                        <div>
                            <h2 class="text-base font-semibold text-text">
                                Garment measurements
                            </h2>
                            <p class="mt-0.5 text-sm text-text-muted">
                                All optional. Lay the garment flat and measure
                                across.
                            </p>
                        </div>
                    </template>

                    <div class="grid gap-4 p-5 sm:grid-cols-3">
                        <AppInput
                            v-for="field in garmentFields"
                            :id="field.key"
                            :key="field.key"
                            v-model="form[field.key]"
                            :label="field.label"
                            type="number"
                            step="0.1"
                            min="0.5"
                            :error="form.errors[field.key]"
                        />
                    </div>
                </AppCard>

                <AppCard v-if="isEdit && item?.fit" :padded="false">
                    <template #header>
                        <div class="flex items-center justify-between gap-3">
                            <div>
                                <h2 class="text-base font-semibold text-text">
                                    Fit breakdown
                                </h2>
                                <p class="mt-0.5 text-sm text-text-muted">
                                    Compared against your current measurements.
                                </p>
                            </div>
                            <AppBadge :variant="fitVariant(item.fit.label)">
                                {{ label(item.fit.label) }}
                            </AppBadge>
                        </div>
                    </template>

                    <div class="space-y-3 p-5">
                        <p
                            v-if="item.fit.label === 'unknown'"
                            class="text-sm text-text-muted"
                        >
                            Add garment measurements for a precise fit.
                        </p>

                        <p
                            v-else-if="item.fit.score != null"
                            class="text-sm text-text-secondary"
                        >
                            Fit score
                            <span class="font-semibold tabular-nums text-text">
                                {{ item.fit.score }}
                            </span>
                        </p>

                        <ul
                            v-if="item.fit.eases?.length"
                            class="divide-y divide-border rounded-control border border-border"
                        >
                            <li
                                v-for="ease in item.fit.eases"
                                :key="ease.pair"
                                class="flex items-center justify-between gap-3 px-3 py-2 text-sm"
                            >
                                <span class="text-text-secondary">
                                    {{ label(ease.pair) }}
                                </span>
                                <span class="flex items-center gap-2">
                                    <span
                                        class="tabular-nums text-text-muted"
                                    >
                                        {{
                                            ease.ease == null
                                                ? '—'
                                                : `${ease.ease > 0 ? '+' : ''}${ease.ease} cm`
                                        }}
                                    </span>
                                    <AppBadge
                                        :variant="fitVariant(ease.label)"
                                    >
                                        {{ label(ease.label) }}
                                    </AppBadge>
                                </span>
                            </li>
                        </ul>
                    </div>
                </AppCard>

                <AppCard :padded="false">
                    <template #header>
                        <h2 class="text-base font-semibold text-text">
                            Notes &amp; availability
                        </h2>
                    </template>

                    <div class="space-y-4 p-5">
                        <AppTextarea
                            id="notes"
                            v-model="form.notes"
                            label="Notes"
                            :error="form.errors.notes"
                            :rows="3"
                        />

                        <AppCheckbox
                            id="is_active"
                            v-model="form.is_active"
                            label="Available for outfit generation"
                            :error="form.errors.is_active"
                        />
                    </div>
                </AppCard>

                <div class="flex items-center gap-3">
                    <AppButton type="submit" :loading="form.processing">
                        {{ isEdit ? 'Save changes' : 'Add item' }}
                    </AppButton>
                    <AppButton
                        :href="route('wardrobe.index')"
                        variant="ghost"
                    >
                        Cancel
                    </AppButton>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
