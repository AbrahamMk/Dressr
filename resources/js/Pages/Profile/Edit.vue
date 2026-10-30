<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import AppCard from '@/Components/UI/AppCard.vue';
import PageHeader from '@/Components/UI/PageHeader.vue';
import DeleteUserForm from './Partials/DeleteUserForm.vue';
import UpdatePasswordForm from './Partials/UpdatePasswordForm.vue';
import UpdateProfileInformationForm from './Partials/UpdateProfileInformationForm.vue';
import { Head } from '@inertiajs/vue3';

defineProps({
    mustVerifyEmail: {
        type: Boolean,
    },
    status: {
        type: String,
    },
});
</script>

<template>
    <Head title="Profile" />

    <AppLayout>
        <div class="space-y-6">
            <PageHeader
                title="Profile"
                description="Manage your account details and password."
            />

            <AppCard>
                <UpdateProfileInformationForm
                    :must-verify-email="mustVerifyEmail"
                    :status="status"
                    class="max-w-xl"
                />
            </AppCard>

            <AppCard>
                <UpdatePasswordForm class="max-w-xl" />
            </AppCard>

            <AppCard>
                <DeleteUserForm class="max-w-xl" />
            </AppCard>
        </div>
    </AppLayout>
</template>
