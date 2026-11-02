<script setup>
import { onMounted, ref } from 'vue';
import AppButton from '@/Components/UI/AppButton.vue';

const STORAGE_KEY = 'dressr-pwa-install-dismissed';

const visible = ref(false);
let deferredPrompt = null;

const dismiss = () => {
    visible.value = false;
    try {
        localStorage.setItem(STORAGE_KEY, '1');
    } catch {
        // ignore
    }
};

const install = async () => {
    if (!deferredPrompt) {
        dismiss();
        return;
    }

    deferredPrompt.prompt();
    await deferredPrompt.userChoice;
    deferredPrompt = null;
    dismiss();
};

onMounted(() => {
    try {
        if (localStorage.getItem(STORAGE_KEY) === '1') {
            return;
        }
    } catch {
        // ignore
    }

    if (window.matchMedia('(display-mode: standalone)').matches) {
        return;
    }

    window.addEventListener('beforeinstallprompt', (event) => {
        event.preventDefault();
        deferredPrompt = event;
        visible.value = true;
    });
});
</script>

<template>
    <div
        v-if="visible"
        class="fixed inset-x-0 bottom-0 z-40 border-t border-border bg-surface p-4 shadow-overlay sm:inset-x-auto sm:bottom-4 sm:end-4 sm:w-96 sm:rounded-card sm:border"
        role="dialog"
        aria-label="Install Dressr"
    >
        <p class="text-sm font-semibold text-text">Install Dressr</p>
        <p class="mt-1 text-sm text-text-muted">
            Add it to your home screen for quick access. We will not ask again.
        </p>
        <div class="mt-3 flex items-center gap-2">
            <AppButton size="sm" @click="install">Install</AppButton>
            <AppButton size="sm" variant="ghost" @click="dismiss">
                Not now
            </AppButton>
        </div>
    </div>
</template>
