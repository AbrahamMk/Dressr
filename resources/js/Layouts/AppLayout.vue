<script setup>
import { computed, ref, watch } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import Dropdown from '@/Components/Dropdown.vue';
import DropdownLink from '@/Components/DropdownLink.vue';
import AppIcon from '@/Components/UI/AppIcon.vue';
import ToastContainer from '@/Components/UI/ToastContainer.vue';
import { navigation } from '@/Config/navigation.js';
import { useToast } from '@/Composables/useToast.js';

const page = usePage();
const { push } = useToast();

const mobileOpen = ref(false);
const collapsed = ref(false);

if (typeof window !== 'undefined') {
    collapsed.value =
        window.localStorage.getItem('dressr.sidebar') === 'collapsed';
}

watch(collapsed, (value) => {
    if (typeof window !== 'undefined') {
        window.localStorage.setItem(
            'dressr.sidebar',
            value ? 'collapsed' : 'expanded',
        );
    }
});

const items = computed(() =>
    navigation.filter((item) => {
        try {
            return route().has(item.route);
        } catch {
            return false;
        }
    }),
);

const isActive = (item) => {
    try {
        return route().current(item.match);
    } catch {
        return false;
    }
};

const user = computed(() => page.props.auth?.user ?? null);

const initials = computed(() => {
    const parts = (user.value?.name ?? '')
        .split(' ')
        .filter(Boolean)
        .map((part) => part[0]);

    return parts.slice(0, 2).join('').toUpperCase() || '?';
});

watch(
    () => page.props.flash,
    (flash) => {
        if (flash?.success) {
            push(flash.success, 'success');
        }

        if (flash?.error) {
            push(flash.error, 'danger');
        }
    },
    { immediate: true },
);
</script>

<template>
    <div class="min-h-screen bg-canvas">
        <div
            v-if="mobileOpen"
            class="fixed inset-0 z-30 bg-slate-900/40 lg:hidden"
            @click="mobileOpen = false"
        />

        <aside
            class="fixed inset-y-0 start-0 z-40 flex flex-col border-e border-border bg-surface transition-[width,transform] duration-200"
            :class="[
                collapsed
                    ? 'w-[var(--sidebar-collapsed-width)]'
                    : 'w-[var(--sidebar-width)]',
                mobileOpen
                    ? 'translate-x-0'
                    : '-translate-x-full lg:translate-x-0',
            ]"
        >
            <div
                class="flex h-[var(--header-height)] shrink-0 items-center gap-3 border-b border-border px-4"
            >
                <Link
                    :href="route('dashboard')"
                    class="flex min-w-0 items-center gap-2.5 rounded-control focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary-600"
                >
                    <span
                        class="flex h-8 w-8 shrink-0 items-center justify-center rounded-control bg-primary-600 text-white"
                    >
                        <ApplicationLogo class="h-5 w-5" />
                    </span>
                    <span
                        v-if="!collapsed"
                        class="truncate text-sm font-semibold tracking-tight text-text"
                    >
                        Dressr
                    </span>
                </Link>
            </div>

            <nav class="flex-1 space-y-1 overflow-y-auto p-3">
                <p
                    v-if="!collapsed"
                    class="px-3 pb-2 pt-1 text-xs font-medium uppercase tracking-wide text-text-muted"
                >
                    Workspace
                </p>

                <Link
                    v-for="item in items"
                    :key="item.route"
                    :href="route(item.route)"
                    :title="collapsed ? item.label : undefined"
                    class="flex items-center gap-3 rounded-control px-3 py-2 text-sm font-medium transition-colors"
                    :class="
                        isActive(item)
                            ? 'bg-primary-50 text-primary-700'
                            : 'text-text-secondary hover:bg-surface-muted hover:text-text'
                    "
                    :aria-current="isActive(item) ? 'page' : undefined"
                    @click="mobileOpen = false"
                >
                    <AppIcon :name="item.icon" class="h-5 w-5 shrink-0" />
                    <span v-if="!collapsed" class="truncate">
                        {{ item.label }}
                    </span>
                </Link>
            </nav>

            <div class="hidden border-t border-border p-3 lg:block">
                <button
                    type="button"
                    class="flex w-full items-center gap-3 rounded-control px-3 py-2 text-sm font-medium text-text-muted transition-colors hover:bg-surface-muted hover:text-text"
                    :aria-label="collapsed ? 'Expand sidebar' : 'Collapse sidebar'"
                    @click="collapsed = !collapsed"
                >
                    <AppIcon name="panel-left" class="h-5 w-5 shrink-0" />
                    <span v-if="!collapsed">Collapse</span>
                </button>
            </div>
        </aside>

        <div
            class="min-h-screen transition-[padding] duration-200"
            :class="
                collapsed
                    ? 'lg:ps-[var(--sidebar-collapsed-width)]'
                    : 'lg:ps-[var(--sidebar-width)]'
            "
        >
            <header
                class="sticky top-0 z-20 flex h-[var(--header-height)] items-center justify-between gap-3 border-b border-border bg-surface px-4 lg:px-8"
            >
                <div class="flex items-center gap-2">
                    <button
                        type="button"
                        class="inline-flex h-10 w-10 items-center justify-center rounded-control text-text-secondary hover:bg-surface-muted lg:hidden"
                        aria-label="Open navigation"
                        @click="mobileOpen = true"
                    >
                        <AppIcon name="menu" class="h-5 w-5" />
                    </button>

                    <p class="text-sm text-text-muted lg:hidden">Dressr</p>
                </div>

                <div v-if="user" class="relative">
                    <Dropdown align="right" width="48">
                        <template #trigger>
                            <button
                                type="button"
                                class="inline-flex items-center gap-2 rounded-control px-1.5 py-1 text-sm text-text-secondary transition-colors hover:bg-surface-muted focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary-600"
                            >
                                <span
                                    class="flex h-8 w-8 items-center justify-center rounded-full bg-primary-100 text-xs font-semibold text-primary-700"
                                >
                                    {{ initials }}
                                </span>
                                <span class="hidden max-w-40 truncate sm:block">
                                    {{ user.name }}
                                </span>
                                <AppIcon
                                    name="chevron-down"
                                    class="hidden h-4 w-4 sm:block"
                                />
                            </button>
                        </template>

                        <template #content>
                            <div class="border-b border-border px-4 py-3">
                                <p class="truncate text-sm font-medium text-text">
                                    {{ user.name }}
                                </p>
                                <p class="truncate text-xs text-text-muted">
                                    {{ user.email }}
                                </p>
                            </div>
                            <DropdownLink :href="route('profile.edit')">
                                Profile
                            </DropdownLink>
                            <DropdownLink
                                :href="route('logout')"
                                method="post"
                                as="button"
                            >
                                Log out
                            </DropdownLink>
                        </template>
                    </Dropdown>
                </div>
            </header>

            <main class="mx-auto w-full max-w-7xl px-4 py-6 lg:px-8 lg:py-8">
                <slot />
            </main>
        </div>

        <ToastContainer />
    </div>
</template>
