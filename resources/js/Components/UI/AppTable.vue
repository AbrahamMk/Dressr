<script setup>
defineProps({
    headers: {
        type: Array,
        default: () => [],
    },
});
</script>

<template>
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-border text-sm">
            <thead
                class="bg-surface-muted text-left text-xs font-medium uppercase tracking-wide text-text-muted"
            >
                <tr>
                    <th
                        v-for="header in headers"
                        :key="header"
                        scope="col"
                        class="whitespace-nowrap px-5 py-3"
                    >
                        {{ header }}
                    </th>
                </tr>
            </thead>
            <tbody class="divide-y divide-border bg-surface">
                <slot />
            </tbody>
        </table>
    </div>
</template>
