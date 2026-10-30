<script setup>
defineProps({
    padded: {
        type: Boolean,
        default: true,
    },
});
</script>

<template>
    <section
        class="overflow-hidden rounded-card border border-border bg-surface shadow-card"
    >
        <header
            v-if="$slots.header"
            class="flex flex-wrap items-center justify-between gap-3 border-b border-border px-5 py-4"
        >
            <slot name="header" />
        </header>

        <div :class="padded ? 'p-5' : ''">
            <slot />
        </div>

        <footer
            v-if="$slots.footer"
            class="border-t border-border bg-surface-muted px-5 py-3"
        >
            <slot name="footer" />
        </footer>
    </section>
</template>
