<script setup>
import { computed } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import AppBadge from '@/Components/UI/AppBadge.vue';
import AppButton from '@/Components/UI/AppButton.vue';
import AppCard from '@/Components/UI/AppCard.vue';
import EmptyState from '@/Components/UI/EmptyState.vue';
import PageHeader from '@/Components/UI/PageHeader.vue';
import { Head, Link, router } from '@inertiajs/vue3';

const props = defineProps({
    month: { type: String, required: true },
    days: { type: Array, required: true },
    selectedDate: { type: String, required: true },
    selectedLogs: { type: Array, required: true },
    prevMonth: { type: String, required: true },
    nextMonth: { type: String, required: true },
});

const monthLabel = computed(() => {
    const [year, month] = props.month.split('-').map(Number);
    return new Date(year, month - 1, 1).toLocaleString(undefined, {
        month: 'long',
        year: 'numeric',
    });
});

const leadingBlanks = computed(() => {
    const first = props.days[0];
    return first ? first.weekday : 0;
});

const goMonth = (month) => {
    router.get(
        route('wear-log.index'),
        { month, date: props.selectedDate },
        { preserveState: true, replace: true },
    );
};

const selectDay = (date) => {
    router.get(
        route('wear-log.index'),
        { month: props.month, date },
        { preserveState: true, replace: true },
    );
};
</script>

<template>
    <Head title="Wear Log" />

    <AppLayout>
        <div class="space-y-6">
            <PageHeader
                title="Wear Log"
                description="Track what you wore so recommendations stop repeating."
            >
                <template #actions>
                    <AppButton :href="route('outfits.index')" variant="outline">
                        Saved outfits
                    </AppButton>
                </template>
            </PageHeader>

            <div class="grid gap-4 lg:grid-cols-5">
                <AppCard class="lg:col-span-3" :padded="false">
                    <template #header>
                        <div class="flex w-full items-center justify-between gap-3">
                            <AppButton
                                variant="ghost"
                                size="sm"
                                @click="goMonth(prevMonth)"
                            >
                                Previous
                            </AppButton>
                            <h2 class="text-base font-semibold text-text">
                                {{ monthLabel }}
                            </h2>
                            <AppButton
                                variant="ghost"
                                size="sm"
                                @click="goMonth(nextMonth)"
                            >
                                Next
                            </AppButton>
                        </div>
                    </template>

                    <div class="p-4">
                        <div
                            class="mb-2 grid grid-cols-7 gap-1 text-center text-xs font-medium text-text-muted"
                        >
                            <span>Sun</span>
                            <span>Mon</span>
                            <span>Tue</span>
                            <span>Wed</span>
                            <span>Thu</span>
                            <span>Fri</span>
                            <span>Sat</span>
                        </div>

                        <div class="grid grid-cols-7 gap-1">
                            <div
                                v-for="n in leadingBlanks"
                                :key="`blank-${n}`"
                                class="aspect-square"
                            />

                            <button
                                v-for="day in days"
                                :key="day.date"
                                type="button"
                                class="aspect-square rounded-control border p-1 text-left transition-colors"
                                :class="
                                    day.date === selectedDate
                                        ? 'border-primary-600 bg-primary-50'
                                        : day.count
                                          ? 'border-border-strong bg-surface hover:bg-surface-muted'
                                          : 'border-transparent hover:bg-surface-muted'
                                "
                                @click="selectDay(day.date)"
                            >
                                <span
                                    class="block text-xs font-semibold tabular-nums text-text"
                                >
                                    {{ day.day }}
                                </span>
                                <span
                                    v-if="day.count"
                                    class="mt-1 block text-[10px] text-primary-700"
                                >
                                    {{ day.count }}
                                    {{ day.count === 1 ? 'look' : 'looks' }}
                                </span>
                            </button>
                        </div>
                    </div>
                </AppCard>

                <AppCard class="lg:col-span-2" :padded="false">
                    <template #header>
                        <div>
                            <h2 class="text-base font-semibold text-text">
                                {{ selectedDate }}
                            </h2>
                            <p class="mt-0.5 text-sm text-text-muted">
                                What you logged for this day.
                            </p>
                        </div>
                    </template>

                    <div v-if="selectedLogs.length" class="divide-y divide-border">
                        <div
                            v-for="log in selectedLogs"
                            :key="log.id"
                            class="space-y-2 p-4"
                        >
                            <div class="flex items-center justify-between gap-2">
                                <p class="text-sm font-medium text-text">
                                    {{ log.outfit_name || 'Custom set' }}
                                </p>
                                <AppBadge>{{ log.items.length }} items</AppBadge>
                            </div>

                            <div class="flex flex-wrap gap-2">
                                <div
                                    v-for="item in log.items"
                                    :key="`${log.id}-${item.id}`"
                                    class="h-12 w-12 overflow-hidden rounded-control border border-border"
                                    :title="item.name"
                                >
                                    <img
                                        v-if="item.thumb_url"
                                        :src="item.thumb_url"
                                        :alt="item.name"
                                        class="h-full w-full object-cover"
                                    />
                                    <div
                                        v-else
                                        class="h-full w-full"
                                        :style="{
                                            backgroundColor:
                                                item.primary_color_hex ||
                                                '#e2e8f0',
                                        }"
                                    />
                                </div>
                            </div>
                        </div>
                    </div>

                    <div v-else class="p-5">
                        <EmptyState
                            icon="calendar"
                            title="Nothing logged"
                            description="Open a saved outfit and tap “Wore this today”."
                        >
                            <template #action>
                                <AppButton
                                    :href="route('outfits.index')"
                                    variant="outline"
                                    size="sm"
                                >
                                    Browse outfits
                                </AppButton>
                            </template>
                        </EmptyState>
                    </div>
                </AppCard>
            </div>

            <p class="text-sm text-text-muted">
                Tip: logging changes the next outfit generation — recently worn
                pieces score lower on variety.
                <Link
                    :href="route('outfits.generate')"
                    class="font-medium text-primary-600 hover:text-primary-700"
                >
                    Generate again
                </Link>
            </p>
        </div>
    </AppLayout>
</template>
