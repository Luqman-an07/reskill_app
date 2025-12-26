<script setup>
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Link, useForm, usePage } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    mustVerifyEmail: Boolean,
    status: String,
});

const user = usePage().props.auth.user;
const photoInput = ref(null);
const photoPreview = ref(null);

const form = useForm({
    name: user.name,
    email: user.email,
    photo: null, // Field baru untuk foto
});

// Helper: Inisial Nama
const getInitials = (name) => name.split(' ').map(n => n[0]).join('').substring(0, 2).toUpperCase();

// Handle File Select
const selectNewPhoto = () => {
    photoInput.value.click();
};

const updatePhotoPreview = () => {
    const photo = photoInput.value.files[0];

    if (! photo) return;

    const reader = new FileReader();

    reader.onload = (e) => {
        photoPreview.value = e.target.result;
    };

    reader.readAsDataURL(photo);
    
    // Masukkan ke form inertia
    form.photo = photo;
};

const submit = () => {
    // Gunakan POST dengan _method: patch karena upload file
    // Inertia v1.0+ otomatis menangani multipart form data
    form.transform((data) => ({
        ...data,
        _method: 'PATCH',
    })).post(route('profile.update'), {
        onSuccess: () => {
             // Reset file input agar bersih setelah sukses
             if (photoInput.value) photoInput.value.value = null;
             // Reset preview
             // photoPreview.value = null; (Opsional: biarkan agar user melihat hasil barunya)
        },
    });
};
</script>

<template>
    <section>
        <header>
            <h2 class="text-lg font-medium text-gray-900">Informasi Profil</h2>
            <p class="mt-1 text-sm text-gray-600">
                Perbarui informasi profil dan alamat email akun Anda.
            </p>
        </header>

        <form @submit.prevent="submit" class="mt-6 space-y-6">
            
            <div class="col-span-6 sm:col-span-4">
                <input
                    ref="photoInput"
                    type="file"
                    class="hidden"
                    @change="updatePhotoPreview"
                >

                <InputLabel for="photo" value="Foto" />

                <div class="mt-2 flex items-center gap-4">
                    <div class="relative h-20 w-20 rounded-full overflow-hidden border border-gray-200 shadow-sm bg-gray-100 flex items-center justify-center">
                        <img v-if="photoPreview" :src="photoPreview" class="h-full w-full object-cover" />
                        
                        <img v-else-if="user.profile_picture" :src="`/storage/${user.profile_picture}`" class="h-full w-full object-cover" />
                        
                        <span v-else class="text-2xl font-bold text-gray-400">
                            {{ getInitials(user.name) }}
                        </span>
                    </div>

                    <button 
                        type="button" 
                        class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 disabled:opacity-25 transition ease-in-out duration-150"
                        @click="selectNewPhoto"
                    >
                        Pilih Foto Baru
                    </button>
                </div>

                <InputError :message="form.errors.photo" class="mt-2" />
            </div>

            <div>
                <InputLabel for="name" value="Nama" />
                <TextInput
                    id="name"
                    type="text"
                    class="mt-1 block w-full"
                    v-model="form.name"
                    required
                    autofocus
                    autocomplete="name"
                />
                <InputError class="mt-2" :message="form.errors.name" />
            </div>

            <div>
                <InputLabel for="email" value="Email" />
                <TextInput
                    id="email"
                    type="email"
                    class="mt-1 block w-full"
                    v-model="form.email"
                    required
                    autocomplete="username"
                />
                <InputError class="mt-2" :message="form.errors.email" />
            </div>

            <div v-if="mustVerifyEmail && user.email_verified_at === null">
                <p class="text-sm mt-2 text-gray-800">
                    Alamat email Anda belum diverifikasi.
                    <Link
                        :href="route('verification.send')"
                        method="post"
                        as="button"
                        class="underline text-sm text-gray-600 hover:text-gray-900 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                    >
                    Klik di sini untuk mengirim ulang email verifikasi.
                    </Link>
                </p>
                <div
                    v-show="status === 'verification-link-sent'"
                    class="mt-2 font-medium text-sm text-green-600"
                >
                    Tautan verifikasi baru telah dikirimkan ke alamat email Anda.
                </div>
            </div>

            <div class="flex items-center gap-4">
                <PrimaryButton :disabled="form.processing">Simpan</PrimaryButton>

                <Transition
                    enter-active-class="transition ease-in-out"
                    enter-from-class="opacity-0"
                    leave-active-class="transition ease-in-out"
                    leave-to-class="opacity-0"
                >
                    <p v-if="form.recentlySuccessful" class="text-sm text-gray-600">Simpan</p>
                </Transition>
            </div>
        </form>
    </section>
</template>