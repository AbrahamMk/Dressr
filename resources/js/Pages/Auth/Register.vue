<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import AppButton from '@/Components/UI/AppButton.vue';
import AppInput from '@/Components/UI/AppInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const form = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
});

const submit = () => {
    form.post(route('register'), {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};
</script>

<template>
    <GuestLayout>
        <Head title="Create account" />

        <div>
            <h1 class="text-lg font-semibold tracking-tight text-text">
                Create your account
            </h1>
            <p class="mt-1 text-sm text-text-muted">
                Start recording measurements and building your wardrobe.
            </p>
        </div>

        <form class="mt-6 space-y-4" @submit.prevent="submit">
            <AppInput
                id="name"
                v-model="form.name"
                label="Name"
                :error="form.errors.name"
                required
                autofocus
                autocomplete="name"
            />

            <AppInput
                id="email"
                v-model="form.email"
                label="Email"
                type="email"
                :error="form.errors.email"
                required
                autocomplete="username"
            />

            <AppInput
                id="password"
                v-model="form.password"
                label="Password"
                type="password"
                :error="form.errors.password"
                required
                autocomplete="new-password"
            />

            <AppInput
                id="password_confirmation"
                v-model="form.password_confirmation"
                label="Confirm password"
                type="password"
                :error="form.errors.password_confirmation"
                required
                autocomplete="new-password"
            />

            <AppButton type="submit" block :loading="form.processing">
                Create account
            </AppButton>
        </form>

        <p class="mt-6 text-center text-sm text-text-muted">
            Already registered?
            <Link
                :href="route('login')"
                class="font-medium text-primary-600 hover:text-primary-700"
            >
                Log in
            </Link>
        </p>
    </GuestLayout>
</template>
