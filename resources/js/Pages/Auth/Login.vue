<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import AppAlert from '@/Components/UI/AppAlert.vue';
import AppButton from '@/Components/UI/AppButton.vue';
import AppCheckbox from '@/Components/UI/AppCheckbox.vue';
import AppInput from '@/Components/UI/AppInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

defineProps({
    canResetPassword: {
        type: Boolean,
    },
    status: {
        type: String,
    },
});

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const submit = () => {
    form.post(route('login'), {
        onFinish: () => form.reset('password'),
    });
};
</script>

<template>
    <GuestLayout>
        <Head title="Log in" />

        <div>
            <h1 class="text-lg font-semibold tracking-tight text-text">
                Welcome back
            </h1>
            <p class="mt-1 text-sm text-text-muted">
                Sign in to manage your wardrobe.
            </p>
        </div>

        <AppAlert v-if="status" variant="success" class="mt-4">
            {{ status }}
        </AppAlert>

        <form class="mt-6 space-y-4" @submit.prevent="submit">
            <AppInput
                id="email"
                v-model="form.email"
                label="Email"
                type="email"
                :error="form.errors.email"
                required
                autofocus
                autocomplete="username"
            />

            <AppInput
                id="password"
                v-model="form.password"
                label="Password"
                type="password"
                :error="form.errors.password"
                required
                autocomplete="current-password"
            />

            <div class="flex items-center justify-between gap-3">
                <AppCheckbox
                    id="remember"
                    v-model="form.remember"
                    label="Remember me"
                />

                <Link
                    v-if="canResetPassword"
                    :href="route('password.request')"
                    class="text-sm font-medium text-primary-600 hover:text-primary-700"
                >
                    Forgot password?
                </Link>
            </div>

            <AppButton type="submit" block :loading="form.processing">
                Log in
            </AppButton>
        </form>

        <p class="mt-6 text-center text-sm text-text-muted">
            No account?
            <Link
                :href="route('register')"
                class="font-medium text-primary-600 hover:text-primary-700"
            >
                Create one
            </Link>
        </p>
    </GuestLayout>
</template>
