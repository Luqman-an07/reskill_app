<script setup>
import Checkbox from '@/Components/Checkbox.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import axios from 'axios';
import ApplicationLogo from '@/Components/ApplicationLogo.vue';

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

// --- LOGIKA INTERAKTIF ---
const emailStatus = ref(null); // null, 'valid', 'invalid'
const showPassword = ref(false); // toggle hide/show

// 1. Fungsi Cek Email Real-time
const checkEmail = async () => {
    if (form.email.length < 5 || !form.email.includes('@')) {
        emailStatus.value = null;
        return;
    }

    try {
        const response = await axios.post('/check-email-exists', { email: form.email });
        if (response.data.exists) {
            emailStatus.value = 'valid';
        } else {
            emailStatus.value = 'invalid';
        }
    } catch (error) {
        console.error('Error checking email', error);
    }
};

// Debounce agar tidak spam request
let timeout = null;
const onEmailInput = () => {
    clearTimeout(timeout);
    emailStatus.value = null; 
    timeout = setTimeout(() => {
        checkEmail();
    }, 500); 
};

const submit = () => {
    form.post(route('login'), {
        onFinish: () => {
            form.reset('password'); 
        },
    });
};
</script>

<template>
    <Head title="Masuk" />

    <div class="min-h-screen w-full flex bg-white">
        
        <div class="hidden lg:flex w-1/2 bg-gradient-to-br from-blue-900 via-blue-800 to-teal-600 p-12 flex-col justify-between relative overflow-hidden">
            <div class="text-white flex items-center gap-3 z-10">
                <div class="bg-white/10 p-2 rounded-lg backdrop-blur-sm">
                    <ApplicationLogo class="w-8 h-8 text-white" />
                </div>
                <span class="text-2xl font-bold tracking-wide">RE:SKILL</span>
            </div>
            
            <div class="text-white z-10 max-w-lg mb-10">
                <h1 class="text-5xl font-bold leading-tight mb-6">Tingkatkan Potensi Tim Anda.</h1>
                <p class="text-blue-100 text-lg leading-relaxed opacity-90">
                    Platform pembelajaran korporat yang menggabungkan kurikulum terstruktur dengan gamifikasi yang menyenangkan.
                </p>
            </div>
            
            <div class="flex gap-16 text-white z-10 border-t border-white/20 pt-8">
                <div>
                    <p class="text-4xl font-bold">50+</p>
                    <p class="text-blue-200 text-sm mt-1 font-medium">Peserta Aktif</p>
                </div>
                <div>
                    <p class="text-4xl font-bold">95%</p>
                    <p class="text-blue-200 text-sm mt-1 font-medium">Tingkat Penyelesaian</p>
                </div>
            </div>
            
            <div class="absolute top-0 right-0 -mr-20 -mt-20 w-96 h-96 rounded-full bg-blue-500 opacity-20 blur-3xl"></div>
            <div class="absolute bottom-0 left-0 -ml-20 -mb-20 w-80 h-80 rounded-full bg-teal-500 opacity-20 blur-3xl"></div>
        </div>

        <div class="w-full lg:w-1/2 flex flex-col justify-center items-center p-6 sm:p-12 lg:p-24 overflow-y-auto">
            <div class="w-full max-w-md space-y-8">
                
                <div class="lg:hidden flex justify-center mb-6">
                    <div class="flex items-center gap-2 text-blue-900">
                        <ApplicationLogo class="w-10 h-10" />
                        <span class="text-2xl font-bold tracking-wide">RE:SKILL</span>
                    </div>
                </div>

                <div class="text-center lg:text-left">
                    <h2 class="text-3xl font-bold text-gray-900 tracking-tight">Selamat Datang!</h2>
                    <p class="text-gray-500 mt-2 text-sm sm:text-base">Masuk untuk melanjutkan progres Training Kamu.</p>
                </div>

                <div v-if="status" class="mb-4 font-medium text-sm text-green-600 bg-green-50 p-3 rounded-lg text-center">
                    {{ status }}
                </div>

                <form @submit.prevent="submit" class="space-y-6">
                    
                    <div>
                        <InputLabel for="email" value="Alamat Email" class="text-gray-700 font-medium" />
                        <div class="relative mt-1.5">
                            <input
                                id="email"
                                type="email"
                                class="block w-full rounded-lg shadow-sm focus:ring-2 focus:ring-opacity-50 transition-all duration-200 py-3 pl-4 pr-10 sm:text-sm"
                                :class="{
                                    'border-gray-300 focus:border-blue-500 focus:ring-blue-500': emailStatus === null,
                                    'border-green-500 focus:border-green-500 focus:ring-green-200 bg-green-50/30': emailStatus === 'valid',
                                    'border-red-500 focus:border-red-500 focus:ring-red-200 bg-red-50/30': emailStatus === 'invalid'
                                }"
                                v-model="form.email"
                                @input="onEmailInput"
                                required
                                autofocus
                                autocomplete="username"
                                placeholder="nama@gmail.com"
                            />
                            
                            <div v-if="emailStatus === 'valid'" class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none text-green-500">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-5 h-5"><path fill-rule="evenodd" d="M2.25 12c0-5.385 4.365-9.75 9.75-9.75s9.75 4.365 9.75 9.75-4.365 9.75-9.75 9.75S2.25 17.385 2.25 12zm13.36-1.814a.75.75 0 10-1.22-.872l-3.236 4.53L9.53 12.22a.75.75 0 00-1.06 1.06l2.25 2.25a.75.75 0 001.14-.094l3.75-5.25z" clip-rule="evenodd" /></svg>
                            </div>
                            <div v-if="emailStatus === 'invalid'" class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none text-red-500">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-5 h-5"><path fill-rule="evenodd" d="M12 2.25c-5.385 0-9.75 4.365-9.75 9.75s4.365 9.75 9.75 9.75 9.75-4.365 9.75-9.75S17.385 2.25 12 2.25zm-1.72 6.97a.75.75 0 10-1.06 1.06L10.94 12l-1.72 1.72a.75.75 0 101.06 1.06L12 13.06l1.72 1.72a.75.75 0 101.06-1.06L13.06 12l1.72-1.72a.75.75 0 10-1.06-1.06L12 10.94l-1.72-1.72z" clip-rule="evenodd" /></svg>
                            </div>
                        </div>
                        
                        <p v-if="emailStatus === 'invalid'" class="mt-1.5 text-xs text-red-500 font-medium animate-pulse">
                            Email tidak ditemukan dalam sistem kami.
                        </p>
                        <InputError class="mt-2" :message="form.errors.email" />
                    </div>

                    <div>
                        <div class="flex justify-between items-center">
                            <InputLabel for="password" value="Kata Sandi" class="text-gray-700 font-medium" />
                            <Link v-if="canResetPassword" :href="route('password.request')" class="text-sm text-blue-600 hover:text-blue-800 font-semibold hover:underline transition">
                                Lupa Kata Sandi?
                            </Link>
                        </div>
                        <div class="relative mt-1.5">
                            <input
                                id="password"
                                :type="showPassword ? 'text' : 'password'"
                                class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 py-3 pl-4 pr-10 sm:text-sm transition-colors"
                                v-model="form.password"
                                required
                                autocomplete="current-password"
                                placeholder="••••••••"
                            />
                            <button 
                                type="button" 
                                @click="showPassword = !showPassword"
                                class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-gray-600 focus:outline-none transition"
                            >
                                <svg v-if="showPassword" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5"><path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.454 10.454 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.242 4.242L9.88 9.88" /></svg>
                                <svg v-else xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                            </button>
                        </div>
                        <InputError class="mt-2" :message="form.errors.password" />
                    </div>

                    <div class="block">
                        <label class="flex items-center cursor-pointer">
                            <Checkbox name="remember" v-model:checked="form.remember" class="text-blue-600 focus:ring-blue-500 rounded" />
                            <span class="ms-2 text-sm text-gray-600 select-none">Ingat saya selama 30 hari</span>
                        </label>
                    </div>

                    <div>
                        <PrimaryButton
                            class="w-full justify-center py-3.5 bg-blue-900 hover:bg-blue-800 active:bg-blue-900 rounded-xl text-base font-bold shadow-lg shadow-blue-900/20 transition-all transform active:scale-[0.99]"
                            :class="{ 'opacity-70 cursor-not-allowed': form.processing }"
                            :disabled="form.processing"
                        >
                            <svg v-if="form.processing" class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                            {{ form.processing ? 'Sedang Masuk...' : 'Masuk Sekarang' }}
                        </PrimaryButton>
                    </div>

                    <div class="mt-6 text-center text-sm text-gray-500">
                        Belum punya akun? 
                        <Link :href="route('register')" class="text-blue-700 font-bold hover:text-blue-800 hover:underline transition">
                            Daftar di sini
                        </Link>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>