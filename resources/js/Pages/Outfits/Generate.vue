<script setup>
import { computed, reactive, ref } from 'vue';
import axios from 'axios';
import AppLayout from '@/Layouts/AppLayout.vue';
import AppBadge from '@/Components/UI/AppBadge.vue';
import AppButton from '@/Components/UI/AppButton.vue';
import AppCard from '@/Components/UI/AppCard.vue';
import AppIcon from '@/Components/UI/AppIcon.vue';
import AppInput from '@/Components/UI/AppInput.vue';
import AppSelect from '@/Components/UI/AppSelect.vue';
import EmptyState from '@/Components/UI/EmptyState.vue';
import PageHeader from '@/Components/UI/PageHeader.vue';
import { Head, router } from '@inertiajs/vue3';

const props = defineProps({
    options: {
        type: Object,
        required: true,
    },
    defaults: {
        type: Object,
        required: true,
    },
});

const form = reactive({
    occasion: props.defaults.occasion,
    season: props.defaults.season ?? '',
    temperature: props.defaults.temperature,
    count: props.defaults.count,
});

const loading = ref(false);
const error = ref(null);
const message = ref(null);
const results = ref([]);
const savingIndex = ref(null);

const label = (value) =>
    value
        ? value.replaceAll('_', ' ').replace(/\b\w/g, (letter) => letter.toUpperCase())
        : '';

const factorEntries = (factors) =>
    Object.entries(factors ?? {}).map(([key, value]) => ({
        key,
        value,
    }));

const canGenerate = computed(() => !!form.occasion && !!form.temperature);

const generate = async () => {
    loading.value = true;
    error.value = null;
    message.value = null;

    try {
        const { data } = await axios.post(route('outfits.generate.results'), {
            occasion: form.occasion,
            season: form.season || null,
            temperature: form.temperature,
            count: Number(form.count) || 5,
        });

        results.value = data.outfits ?? [];
        message.value = data.message;
    } catch (err) {
        results.value = [];
        error.value =
            err.response?.data?.message ||
            'Generation failed. Check your wardrobe and try again.';
    } finally {
        loading.value = false;
    }
};

const save = (outfit, index) => {
    savingIndex.value = index;

    router.post(
        route('outfits.store'),
        {
            name: `${label(form.occasion)} look ${index + 1}`,
            occasion: form.occasion,
            season: form.season || null,
            score: outfit.score,
            item_ids: outfit.item_ids,
            slots: outfit.slots,
        },
        {
            preserveScroll: true,
            onFinish: () => {
                savingIndex.value = null;
            },
        },
    );
};
</script>

<template>
    <Head title="Generate outfits" />

    <AppLayout>
        <div class="space-y-6">
            <PageHeader
                title="Generate outfits"
                description="Rule-based combinations from your wardrobe. No AI involved."
            >
                <template #actions>
                    <AppButton
                        :href="route('outfits.index')"
                        variant="outline"
                    >
                        Saved outfits
                    </AppButton>
                </template>
            </PageHeader>

            <AppCard>
                <form
                    class="grid gap-3 sm:grid-cols-2 lg:grid-cols-5"
                    @submit.prevent="generate"
                >
                    <AppSelect
                        id="occasion"
                        v-model="form.occasion"
                        label="Occasion"
                    >
                        <option
                            v-for="occasion in options.occasions"
                            :key="occasion.value"
                            :value="occasion.value"
                        >
                            {{ occasion.label }}
                        </option>
                    </AppSelect>

                    <AppSelect id="season" v-model="form.season" label="Season">
                        <option value="">Any season</option>
                        <option
                            v-for="season in options.seasons"
                            :key="season"
                            :value="season"
                        >
                            {{ label(season) }}
                        </option>
                    </AppSelect>

                    <AppSelect
                        id="temperature"
                        v-model="form.temperature"
                        label="Temperature"
                    >
                        <option
                            v-for="temperature in options.temperatures"
                            :key="temperature.value"
                            :value="temperature.value"
                        >
                            {{ temperature.label }}
                        </option>
                    </AppSelect>

                    <AppInput
                        id="count"
                        v-model="form.count"
                        label="How many"
                        type="number"
                        min="1"
                        :max="options.maxCount"
                    />

                    <div class="flex items-end">
                        <AppButton
                            type="submit"
                            :loading="loading"
                            :disabled="!canGenerate"
                            block
                        >
                            <AppIcon name="sparkles" class="h-4 w-4" />
                            Generate
                        </AppButton>
                    </div>
                </form>
            </AppCard>

            <AppCard v-if="error" class="border-danger-100 bg-danger-50">
                <p class="text-sm text-danger-700">{{ error }}</p>
            </AppCard>

            <EmptyState
                v-else-if="message && results.length === 0"
                icon="shirt"
                title="Not enough items"
                :description="message"
            >
                <template #action>
                    <AppButton :href="route('wardrobe.create')" variant="outline">
                        Add wardrobe items
                    </AppButton>
                </template>
            </EmptyState>

            <EmptyState
                v-else-if="!loading && results.length === 0 && !message"
                icon="sparkles"
                title="Ready when you are"
                description="Pick an occasion and season, then generate a few looks."
            />

            <div v-if="results.length" class="grid gap-4 lg:grid-cols-2">
                <AppCard
                    v-for="(outfit, index) in results"
                    :key="outfit.item_ids.join('-')"
                    :padded="false"
                    class="flex flex-col"
                >
                    <div class="flex items-center justify-between gap-3 border-b border-border px-5 py-3">
                        <div>
                            <h2 class="text-base font-semibold text-text">
                                Look {{ index + 1 }}
                            </h2>
                            <p class="text-sm text-text-muted">
                                Score
                                <span class="font-semibold tabular-nums text-text">
                                    {{ Number(outfit.score).toFixed(0) }}
                                </span>
                            </p>
                        </div>
                        <AppButton
                            size="sm"
                            :loading="savingIndex === index"
                            @click="save(outfit, index)"
                        >
                            Save
                        </AppButton>
                    </div>

                    <div class="grid grid-cols-2 gap-2 p-4 sm:grid-cols-3">
                        <div
                            v-for="item in outfit.items"
                            :key="item.id"
                            class="overflow-hidden rounded-control border border-border"
                        >
                            <div class="aspect-square bg-surface-muted">
                                <img
                                    v-if="item.thumb_url"
                                    :src="item.thumb_url"
                                    :alt="item.name"
                                    class="h-full w-full object-cover"
                                    loading="lazy"
                                />
                                <div
                                    v-else
                                    class="h-full w-full"
                                    :style="{
                                        backgroundColor: item.primary_color_hex,
                                    }"
                                />
                            </div>
                            <div class="space-y-1 p-2">
                                <p class="truncate text-xs font-medium text-text">
                                    {{ item.name }}
                                </p>
                                <AppBadge>{{ label(item.slot) }}</AppBadge>
                            </div>
                        </div>
                    </div>

                    <div class="border-t border-border px-5 py-3">
                        <p class="mb-2 text-xs font-medium uppercase tracking-wide text-text-muted">
                            Score breakdown
                        </p>
                        <dl class="grid grid-cols-2 gap-2 sm:grid-cols-3">
                            <div
                                v-for="factor in factorEntries(outfit.factors)"
                                :key="factor.key"
                            >
                                <dt class="text-xs text-text-muted">
                                    {{ label(factor.key) }}
                                </dt>
                                <dd class="text-sm font-semibold tabular-nums text-text">
                                    {{ Number(factor.value).toFixed(0) }}
                                </dd>
                            </div>
                        </dl>
                    </div>
                </AppCard>
            </div>
        </div>
    </AppLayout>
</template>
