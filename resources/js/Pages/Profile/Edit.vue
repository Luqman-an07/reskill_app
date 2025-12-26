<script setup>
import AppLayout from '@/Layouts/AppLayout.vue'; // Pakai Layout Utama Kita
import DeleteUserForm from './Partials/DeleteUserForm.vue';
import UpdatePasswordForm from './Partials/UpdatePasswordForm.vue';
import UpdateProfileInformationForm from './Partials/UpdateProfileInformationForm.vue';
import { Head } from '@inertiajs/vue3';

defineProps({
    mustVerifyEmail: Boolean,
    status: String,
});
</script>

<template>
    <Head title="Pengaturan Profil" />

    <AppLayout>
        <div class="max-w-7xl mx-auto space-y-6">
            
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Pengaturan Profil</h1>
                <p class="text-sm text-gray-500">Kelola informasi profil dan pengaturan akun kamu di sini</p>
            </div>

            <div class="p-8 bg-white shadow-sm sm:rounded-xl border border-gray-200">
                <UpdateProfileInformationForm
                    :must-verify-email="mustVerifyEmail"
                    :status="status"
                    class="max-w-xl"
                />
            </div>

            <div class="p-8 bg-white shadow-sm sm:rounded-xl border border-gray-200">
                <UpdatePasswordForm class="max-w-xl" />
            </div>

            <div class="p-8 bg-white shadow-sm sm:rounded-xl border border-red-100">
                <DeleteUserForm class="max-w-xl" />
            </div>
            
        </div>
    </AppLayout>
</template>