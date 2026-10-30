<script setup>
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import AppBadge from '@/Components/UI/AppBadge.vue';
import AppButton from '@/Components/UI/AppButton.vue';
import AppCard from '@/Components/UI/AppCard.vue';
import AppIcon from '@/Components/UI/AppIcon.vue';
import { Head, Link, usePage } from '@inertiajs/vue3';

defineProps({
    canLogin: {
        type: Boolean,
    },
    canRegister: {
        type: Boolean,
    },
});

const page = usePage();

const features = [
    {
        icon: 'ruler',
        title: 'Measurements that stay current',
        description:
            'Keep a history of body measurements, with the newest set driving every fit judgement.',
    },
    {
        icon: 'shirt',
        title: 'A wardrobe with real detail',
        description:
            'Colours, pattern, formality, stretch and optional garment measurements — not just photos.',
    },
    {
        icon: 'sparkles',
        title: 'Outfits built on rules',
        description:
            'A rule-based generator, with an optional AI mode. Both arrive in later phases.',
    },
];
</script>

<template>
    <Head title="Welcome" />

    <div class="min-h-screen bg-canvas">
        <header
            class="mx-auto flex w-full max-w-6xl items-center justify-between gap-4 px-4 py-6 lg:px-8"
        >
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

            <nav class="flex items-center gap-2">
                <template v-if="page.props.auth?.user">
                    <AppButton :href="route('dashboard')" size="sm">
                        Dashboard
                    </AppButton>
                </template>
                <template v-else>
                    <AppButton
                        v-if="canLogin"
                        :href="route('login')"
                        variant="ghost"
                        size="sm"
                    >
                        Log in
                    </AppButton>
                    <AppButton
                        v-if="canRegister"
                        :href="route('register')"
                        size="sm"
                    >
                        Create account
                    </AppButton>
                </template>
            </nav>
        </header>

        <main
            class="mx-auto grid w-full max-w-6xl gap-10 px-4 pb-20 pt-8 lg:grid-cols-2 lg:items-start lg:gap-16 lg:px-8 lg:pt-16"
        >
            <div>
                <AppBadge variant="primary">Wardrobe · Fit · Outfits</AppBadge>

                <h1
                    class="mt-4 text-3xl font-semibold tracking-tight text-text sm:text-4xl"
                >
                    Dress from the wardrobe you already own.
                </h1>

                <p class="mt-4 max-w-xl text-base leading-relaxed text-text-muted">
                    Dressr records your measurements, keeps every garment's fit
                    details in one place, and recommends combinations that suit
                    your body — without selling you anything.
                </p>

                <div class="mt-8 flex flex-wrap gap-3">
                    <AppButton
                        v-if="page.props.auth?.user"
                        :href="route('dashboard')"
                    >
                        Go to dashboard
                    </AppButton>
                    <template v-else>
                        <AppButton
                            v-if="canRegister"
                            :href="route('register')"
                        >
                            Create account
                        </AppButton>
                        <AppButton
                            v-if="canLogin"
                            :href="route('login')"
                            variant="outline"
                        >
                            Log in
                        </AppButton>
                    </template>
                </div>

                <p class="mt-4 text-xs text-text-muted">
                    No social login, no tracking pixels, no AI key required.
                </p>
            </div>

            <div class="grid gap-4">
                <AppCard
                    v-for="feature in features"
                    :key="feature.title"
                    :padded="false"
                >
                    <div class="flex items-start gap-4 p-5">
                        <span
                            class="flex h-9 w-9 shrink-0 items-center justify-center rounded-control bg-primary-50 text-primary-700"
                        >
                            <AppIcon :name="feature.icon" class="h-5 w-5" />
                        </span>
                        <div>
                            <h2 class="text-sm font-semibold text-text">
                                {{ feature.title }}
                            </h2>
                            <p class="mt-1 text-sm text-text-muted">
                                {{ feature.description }}
                            </p>
                        </div>
                    </div>
                </AppCard>
            </div>
        </main>
    </div>
</template>
