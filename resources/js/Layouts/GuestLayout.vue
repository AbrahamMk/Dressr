<script setup>
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import { Link } from '@inertiajs/vue3';
</script>

<template>
    <div class="flex min-h-screen flex-col bg-canvas">
        <header class="mx-auto w-full max-w-6xl px-4 py-6 lg:px-8">
            <Link
                href="/"
                class="inline-flex items-center gap-2.5 rounded-control text-text focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary-600"
            >
                <span
                    class="flex h-8 w-8 items-center justify-center rounded-control bg-primary-600 text-white"
                >
                    <ApplicationLogo class="h-5 w-5" />
                </span>
                <span class="text-sm font-semibold tracking-tight">Dressr</span>
            </Link>
        </header>

        <main
            class="flex flex-1 items-start justify-center px-4 pb-16 pt-6 sm:items-center sm:pt-0"
        >
            <div class="w-full sm:max-w-md">
                <div
                    class="rounded-card border border-border bg-surface p-6 shadow-card sm:p-8"
                >
                    <slot />
                </div>

                <p class="mt-6 text-center text-xs text-text-muted">
                    Your measurements and photos stay on your account.
                </p>
            </div>
        </main>
    </div>
</template>
