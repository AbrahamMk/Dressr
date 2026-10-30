<script setup>
import { Link } from '@inertiajs/vue3';

defineProps({
    links: {
        type: Array,
        default: () => [],
    },
});
</script>

<template>
    <nav
        v-if="links.length > 3"
        class="flex flex-wrap items-center gap-1"
        aria-label="Pagination"
    >
        <template v-for="link in links" :key="link.label">
            <Link
                v-if="link.url"
                :href="link.url"
                preserve-scroll
                class="inline-flex h-9 min-w-9 items-center justify-center rounded-control border px-3 text-sm"
                :class="
                    link.active
                        ? 'border-primary-600 bg-primary-600 text-white'
                        : 'border-border bg-surface text-text-secondary hover:bg-surface-muted'
                "
                v-html="link.label"
            />
            <span
                v-else
                class="inline-flex h-9 min-w-9 items-center justify-center rounded-control border border-border bg-surface px-3 text-sm text-text-muted opacity-60"
                v-html="link.label"
            />
        </template>
    </nav>
</template>
