<script setup>
import { reactive, ref } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import AppBadge from '@/Components/UI/AppBadge.vue';
import AppButton from '@/Components/UI/AppButton.vue';
import AppCard from '@/Components/UI/AppCard.vue';
import AppIcon from '@/Components/UI/AppIcon.vue';
import AppInput from '@/Components/UI/AppInput.vue';
import AppPagination from '@/Components/UI/AppPagination.vue';
import AppSelect from '@/Components/UI/AppSelect.vue';
import ConfirmDialog from '@/Components/UI/ConfirmDialog.vue';
import EmptyState from '@/Components/UI/EmptyState.vue';
import PageHeader from '@/Components/UI/PageHeader.vue';
import { Head, Link, router } from '@inertiajs/vue3';

const props = defineProps({
    items: {
        type: Object,
        required: true,
    },
    filters: {
        type: Object,
        required: true,
    },
    options: {
        type: Object,
        required: true,
    },
});

const filters = reactive({
    category: props.filters.category ?? '',
    season: props.filters.season ?? '',
    color_family: props.filters.color_family ?? '',
    search: props.filters.search ?? '',
});

const pendingDelete = ref(null);
const deleting = ref(false);

const label = (value) =>
    value
        .replaceAll('_', ' ')
        .replace(/\b\w/g, (letter) => letter.toUpperCase());

const apply = () => {
    const params = Object.fromEntries(
        Object.entries(filters).filter(([, value]) => value !== ''),
    );

    router.get(route('wardrobe.index'), params, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
};

const reset = () => {
    filters.category = '';
    filters.season = '';
    filters.color_family = '';
    filters.search = '';
    apply();
};

const confirmDelete = () => {
    if (!pendingDelete.value) {
        return;
    }

    router.delete(route('wardrobe.destroy', pendingDelete.value.id), {
        preserveScroll: true,
        onStart: () => (deleting.value = true),
        onFinish: () => {
            deleting.value = false;
            pendingDelete.value = null;
        },
    });
};
</script>

<template>
    <Head title="Wardrobe" />

    <AppLayout>
        <div class="space-y-6">
            <PageHeader
                title="Wardrobe"
                description="Everything you own, with the details that drive fit and outfit advice."
            >
                <template #actions>
                    <AppButton :href="route('wardrobe.create')">
                        <AppIcon name="plus" class="h-4 w-4" />
                        Add item
                    </AppButton>
                </template>
            </PageHeader>

            <AppCard>
                <form
                    class="grid gap-3 sm:grid-cols-2 lg:grid-cols-5"
                    @submit.prevent="apply"
                >
                    <div class="lg:col-span-2">
                        <AppInput
                            id="search"
                            v-model="filters.search"
                            label="Search"
                            type="search"
                            placeholder="Name, type or material"
                        />
                    </div>

                    <AppSelect
                        id="category"
                        v-model="filters.category"
                        label="Category"
                        @change="apply"
                    >
                        <option value="">All categories</option>
                        <option
                            v-for="category in options.categories"
                            :key="category"
                            :value="category"
                        >
                            {{ label(category) }}
                        </option>
                    </AppSelect>

                    <AppSelect
                        id="season"
                        v-model="filters.season"
                        label="Season"
                        @change="apply"
                    >
                        <option value="">All seasons</option>
                        <option
                            v-for="season in options.seasons"
                            :key="season"
                            :value="season"
                        >
                            {{ label(season) }}
                        </option>
                    </AppSelect>

                    <AppSelect
                        id="color_family"
                        v-model="filters.color_family"
                        label="Colour family"
                        @change="apply"
                    >
                        <option value="">All colours</option>
                        <option
                            v-for="family in options.colorFamilies"
                            :key="family"
                            :value="family"
                        >
                            {{ label(family) }}
                        </option>
                    </AppSelect>

                    <div class="flex items-end gap-2 lg:col-span-5">
                        <AppButton type="submit" variant="secondary">
                            <AppIcon name="search" class="h-4 w-4" />
                            Apply filters
                        </AppButton>
                        <AppButton variant="ghost" @click="reset">
                            Reset
                        </AppButton>
                    </div>
                </form>
            </AppCard>

            <EmptyState
                v-if="items.data.length === 0"
                icon="shirt"
                title="No items match"
                description="Add your first piece, or clear the filters to see everything you own."
            >
                <template #action>
                    <AppButton :href="route('wardrobe.create')">
                        Add an item
                    </AppButton>
                </template>
            </EmptyState>

            <div
                v-else
                class="grid gap-4 sm:grid-cols-2 md:grid-cols-3 xl:grid-cols-4"
            >
                <AppCard
                    v-for="item in items.data"
                    :key="item.id"
                    :padded="false"
                    class="flex flex-col"
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
                            class="flex h-full items-center justify-center text-text-muted"
                        >
                            <AppIcon name="camera" class="h-8 w-8" />
                        </div>
                    </div>

                    <div class="flex flex-1 flex-col gap-2 p-4">
                        <div class="flex items-start justify-between gap-2">
                            <h2
                                class="min-w-0 truncate text-sm font-semibold text-text"
                            >
                                {{ item.name }}
                            </h2>
                            <span
                                class="mt-0.5 h-4 w-4 shrink-0 rounded-full border border-border"
                                :style="{
                                    backgroundColor: item.primary_color_hex,
                                }"
                                :title="item.primary_color_hex"
                            />
                        </div>

                        <p class="text-xs text-text-muted">
                            {{ label(item.category) }} · {{ item.subcategory }}
                            <span v-if="item.size_label">
                                · {{ item.size_label }}
                            </span>
                        </p>

                        <p class="text-xs text-text-muted">
                            {{ label(item.fit_type) }} fit · formality
                            {{ item.formality }}/5
                        </p>

                        <div class="flex flex-wrap gap-1">
                            <AppBadge
                                v-for="season in item.seasons"
                                :key="season"
                            >
                                {{ label(season) }}
                            </AppBadge>
                        </div>

                        <div
                            class="mt-auto flex items-center justify-between pt-2"
                        >
                            <Link
                                :href="route('wardrobe.edit', item.id)"
                                class="text-sm font-medium text-primary-600 hover:text-primary-700"
                            >
                                Edit
                            </Link>
                            <AppButton
                                variant="danger-ghost"
                                size="sm"
                                @click="pendingDelete = item"
                            >
                                Delete
                            </AppButton>
                        </div>
                    </div>
                </AppCard>
            </div>

            <AppPagination v-if="items.links.length > 3" :links="items.links" />
        </div>

        <ConfirmDialog
            :show="pendingDelete !== null"
            title="Remove item?"
            :description="
                pendingDelete
                    ? `“${pendingDelete.name}” and its photos will be deleted. This cannot be undone.`
                    : null
            "
            confirm-label="Delete item"
            :processing="deleting"
            @close="pendingDelete = null"
            @confirm="confirmDelete"
        />
    </AppLayout>
</template>
