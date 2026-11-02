<script setup>
import { ref } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import AppBadge from '@/Components/UI/AppBadge.vue';
import AppButton from '@/Components/UI/AppButton.vue';
import AppCard from '@/Components/UI/AppCard.vue';
import AppIcon from '@/Components/UI/AppIcon.vue';
import AppPagination from '@/Components/UI/AppPagination.vue';
import ConfirmDialog from '@/Components/UI/ConfirmDialog.vue';
import EmptyState from '@/Components/UI/EmptyState.vue';
import PageHeader from '@/Components/UI/PageHeader.vue';
import { Head, router } from '@inertiajs/vue3';

const props = defineProps({
    outfits: {
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

const pendingDelete = ref(null);
const deleting = ref(false);

const label = (value) =>
    value
        ? value.replaceAll('_', ' ').replace(/\b\w/g, (letter) => letter.toUpperCase())
        : '';

const toggleFavorites = () => {
    router.get(
        route('outfits.index'),
        { favorites: props.filters.favorites ? 0 : 1 },
        { preserveState: true, replace: true },
    );
};

const toggleFavorite = (outfit) => {
    router.post(route('outfits.favorite', outfit.id), {}, { preserveScroll: true });
};

const woreToday = (outfit) => {
    router.post(
        route('wear-log.store'),
        { outfit_id: outfit.id },
        { preserveScroll: true },
    );
};

const confirmDelete = () => {
    if (!pendingDelete.value) {
        return;
    }

    router.delete(route('outfits.destroy', pendingDelete.value.id), {
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
    <Head title="Outfits" />

    <AppLayout>
        <div class="space-y-6">
            <PageHeader
                title="Outfits"
                description="Saved combinations from the rule-based generator."
            >
                <template #actions>
                    <AppButton
                        variant="outline"
                        @click="toggleFavorites"
                    >
                        <AppIcon
                            :name="filters.favorites ? 'sparkles' : 'sparkles'"
                            class="h-4 w-4"
                        />
                        {{ filters.favorites ? 'Show all' : 'Favorites only' }}
                    </AppButton>
                    <AppButton :href="route('outfits.generate')">
                        <AppIcon name="plus" class="h-4 w-4" />
                        Generate
                    </AppButton>
                </template>
            </PageHeader>

            <EmptyState
                v-if="outfits.data.length === 0"
                icon="sparkles"
                :title="
                    filters.favorites
                        ? 'No favorites yet'
                        : 'No saved outfits yet'
                "
                description="Generate a few combinations and save the ones you like."
            >
                <template #action>
                    <AppButton :href="route('outfits.generate')">
                        Generate outfits
                    </AppButton>
                </template>
            </EmptyState>

            <div v-else class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                <AppCard
                    v-for="outfit in outfits.data"
                    :key="outfit.id"
                    :padded="false"
                    class="flex flex-col"
                >
                    <div class="grid grid-cols-3 gap-1 bg-surface-muted p-1">
                        <div
                            v-for="item in outfit.items.slice(0, 3)"
                            :key="`${outfit.id}-${item.id}`"
                            class="aspect-square overflow-hidden rounded-control bg-surface"
                        >
                            <img
                                v-if="item.thumb_url"
                                :src="item.thumb_url"
                                :alt="item.name"
                                class="h-full w-full object-cover"
                                loading="lazy"
                            />
                            <div
                                v-else
                                class="flex h-full items-center justify-center"
                                :style="{
                                    backgroundColor:
                                        item.primary_color_hex || '#e2e8f0',
                                }"
                            />
                        </div>
                    </div>

                    <div class="flex flex-1 flex-col gap-2 p-4">
                        <div class="flex items-start justify-between gap-2">
                            <h2 class="text-sm font-semibold text-text">
                                {{ outfit.name }}
                            </h2>
                            <button
                                type="button"
                                class="text-text-muted hover:text-warning-600"
                                :aria-label="
                                    outfit.is_favorite
                                        ? 'Remove favorite'
                                        : 'Favorite'
                                "
                                @click="toggleFavorite(outfit)"
                            >
                                <AppIcon
                                    name="sparkles"
                                    class="h-4 w-4"
                                    :class="
                                        outfit.is_favorite
                                            ? 'text-warning-600'
                                            : ''
                                    "
                                />
                            </button>
                        </div>

                        <div class="flex flex-wrap gap-1">
                            <AppBadge variant="primary">
                                {{ label(outfit.occasion) }}
                            </AppBadge>
                            <AppBadge v-if="outfit.season">
                                {{ label(outfit.season) }}
                            </AppBadge>
                            <AppBadge v-if="outfit.score != null" variant="info">
                                {{ Number(outfit.score).toFixed(0) }}
                            </AppBadge>
                        </div>

                        <p class="text-xs text-text-muted">
                            {{ outfit.items.map((item) => item.name).join(' · ') }}
                        </p>

                        <div class="mt-auto flex flex-wrap items-center justify-end gap-2 pt-2">
                            <AppButton
                                variant="outline"
                                size="sm"
                                @click="woreToday(outfit)"
                            >
                                Wore this today
                            </AppButton>
                            <AppButton
                                variant="danger-ghost"
                                size="sm"
                                @click="pendingDelete = outfit"
                            >
                                Delete
                            </AppButton>
                        </div>
                    </div>
                </AppCard>
            </div>

            <AppPagination
                v-if="outfits.links.length > 3"
                :links="outfits.links"
            />
        </div>

        <ConfirmDialog
            :show="pendingDelete !== null"
            title="Delete outfit?"
            :description="
                pendingDelete
                    ? `“${pendingDelete.name}” will be removed. This cannot be undone.`
                    : null
            "
            confirm-label="Delete outfit"
            :processing="deleting"
            @close="pendingDelete = null"
            @confirm="confirmDelete"
        />
    </AppLayout>
</template>
