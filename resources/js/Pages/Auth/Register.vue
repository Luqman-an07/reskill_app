<script setup>
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref, watch, computed } from 'vue';
import axios from 'axios';
import ApplicationLogo from '@/Components/ApplicationLogo.vue';

defineProps({
    departments: Array, 
});

const form = useForm({
    name: '',
    email: '',
    // department_id: '',
    password: '',
    password_confirmation: '',
});

// --- STATE LOGIKA ---
const showPassword = ref(false);
const emailAvailability = ref(null);
const passwordMatch = ref(null);

// State Baru: Validasi Format Email & Password Strength
const emailFormatError = ref(null);
const passwordScore = ref(0);
const passwordRequirements = ref({
    length: false,
    upper: false,
    lower: false,
    number: false,
    symbol: false
});

// 1. FUNGSI CEK FORMAT EMAIL
const validateEmailFormat = () => {
    emailAvailability.value = null; 
    const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    
    if (!emailPattern.test(form.email)) {
        emailFormatError.value = "Format email tidak valid (contoh: nama@domain.com)";
        return false;
    } else {
        emailFormatError.value = null;
        return true;
    }
};

// Integrasi Cek Email
let timeout = null;
const checkEmail = () => {
    clearTimeout(timeout);
    if (!validateEmailFormat()) return;

    timeout = setTimeout(async () => {
        try {
            const response = await axios.post('/check-email-exists', { email: form.email });
            emailAvailability.value = response.data.exists ? 'taken' : 'available';
        } catch (error) {
            console.error(error);
        }
    }, 500);
};

// 2. FUNGSI HITUNG KEKUATAN PASSWORD
const checkPasswordStrength = () => {
    const pass = form.password;
    let score = 0;

    passwordRequirements.value.length = pass.length >= 8;
    passwordRequirements.value.lower = /[a-z]/.test(pass);
    passwordRequirements.value.upper = /[A-Z]/.test(pass);
    passwordRequirements.value.number = /[0-9]/.test(pass);
    passwordRequirements.value.symbol = /[^A-Za-z0-9]/.test(pass);

    if (passwordRequirements.value.lower) score++;
    if (passwordRequirements.value.upper) score++;
    if (passwordRequirements.value.number) score++;
    if (passwordRequirements.value.symbol) score++;

    if (!passwordRequirements.value.length) score = 0;

    passwordScore.value = score;
};

// Watcher Password
watch(() => [form.password, form.password_confirmation], ([newPass, newConfirm]) => {
    checkPasswordStrength(); 
    if (newConfirm.length > 0) {
        passwordMatch.value = newPass === newConfirm;
    } else {
        passwordMatch.value = null;
    }
});

const submit = () => {
    if (emailFormatError.value) return; 
    form.post(route('register'), {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};

const strengthColor = computed(() => {
    if (passwordScore.value <= 1) return 'bg-red-500';
    if (passwordScore.value === 2) return 'bg-orange-500';
    if (passwordScore.value === 3) return 'bg-yellow-500';
    return 'bg-green-500'; 
});

const strengthLabel = computed(() => {
    if (passwordScore.value === 0) return 'Terlalu Lemah';
    if (passwordScore.value <= 2) return 'Sedang';
    if (passwordScore.value === 3) return 'Kuat';
    return 'Sangat Kuat';
});
</script>

<template>
    <div class="min-h-screen w-full flex bg-white">
        
        <div class="hidden lg:flex w-1/2 bg-gradient-to-br from-blue-900 via-blue-800 to-teal-600 p-12 flex-col justify-between relative overflow-hidden">
            <div class="text-white flex items-center gap-3 z-10">
                <div class="bg-white/10 p-2 rounded-lg backdrop-blur-sm">
                    <ApplicationLogo class="w-8 h-8 text-white" />
                </div>
                <span class="text-2xl font-bold tracking-wide">RE:SKILL</span>
            </div>
            <div class="text-white z-10 max-w-lg mb-10">
                <h1 class="text-5xl font-bold leading-tight mb-6">Mulai Perjalanan Karir Anda.</h1>
                <p class="text-blue-100 text-lg leading-relaxed opacity-90">Bergabunglah dengan profesional lainnya dan kembangkan potensi melalui pembelajaran terstruktur.</p>
            </div>
            <div class="flex gap-16 text-white z-10 border-t border-white/20 pt-8">
                <div><p class="text-4xl font-bold">50+</p><p class="text-blue-200 text-sm mt-1 font-medium">Peserta Aktif</p></div>
                <div><p class="text-4xl font-bold">110+</p><p class="text-blue-200 text-sm mt-1 font-medium">Kursus Tersedia</p></div>
            </div>
            <div class="absolute top-0 right-0 -mr-20 -mt-20 w-96 h-96 rounded-full bg-blue-500 opacity-20 blur-3xl"></div>
            <div class="absolute bottom-0 left-0 -ml-20 -mb-20 w-80 h-80 rounded-full bg-teal-500 opacity-20 blur-3xl"></div>
        </div>

        <div class="w-full lg:w-1/2 flex flex-col justify-center items-center p-6 sm:p-12 lg:p-24 overflow-y-auto h-screen">
            <div class="w-full max-w-md py-8">
                <Head title="Daftar" />
                
                <div class="lg:hidden flex justify-center mb-8">
                    <div class="flex items-center gap-2 text-blue-900">
                        <ApplicationLogo class="w-10 h-10" />
                        <span class="text-2xl font-bold tracking-wide">RE:SKILL</span>
                    </div>
                </div>

                <div class="mb-8 text-center lg:text-left">
                    <h2 class="text-3xl font-bold text-gray-900 tracking-tight">Buat Akun Baru</h2>
                    <p class="text-gray-500 mt-2 text-sm sm:text-base">Daftar sekarang untuk memulai akses Training.</p>
                </div>

                <form @submit.prevent="submit" class="space-y-5">
                    
                    <div>
                        <InputLabel for="name" value="Nama Lengkap" />
                        <TextInput id="name" type="text" class="mt-1 block w-full py-3 px-4 rounded-lg border-gray-300 focus:ring-blue-500 focus:border-blue-500" v-model="form.name" required autofocus autocomplete="name" placeholder="Masukkan Nama Lengkap" />
                        <InputError class="mt-2" :message="form.errors.name" />
                    </div>

                    <div>
                        <InputLabel for="email" value="Alamat Email" />
                        <div class="relative mt-1">
                            <input
                                id="email" type="email"
                                class="block w-full py-3 pl-4 pr-10 rounded-lg shadow-sm focus:ring-2 focus:ring-opacity-50 transition-colors duration-200"
                                :class="{
                                    'border-gray-300 focus:border-blue-500 focus:ring-blue-500': emailStatus === null,
                                    'border-green-500 focus:border-green-500 focus:ring-green-200 bg-green-50/30': emailStatus === 'valid',
                                    'border-red-500 focus:border-red-500 focus:ring-red-200 bg-red-50/30': emailStatus === 'invalid'
                                }"
                                v-model="form.email" @input="onEmailInput" required autocomplete="username" placeholder="nama@gmail.com"
                            />
                            <div v-if="emailStatus === 'valid'" class="absolute inset-y-0 right-0 pr-3 flex items-center text-green-500"><svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg></div>
                            <div v-if="emailStatus === 'invalid'" class="absolute inset-y-0 right-0 pr-3 flex items-center text-red-500"><svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/></svg></div>
                        </div>
                        <p v-if="emailFormatError" class="mt-1.5 text-xs text-red-500 font-medium">{{ emailFormatError }}</p>
                        <p v-if="emailAvailability === 'taken'" class="mt-1.5 text-xs text-red-500 font-medium">Email sudah terdaftar. Silakan login.</p>
                        <InputError class="mt-2" :message="form.errors.email" />
                    </div>

                    <div>
                        <InputLabel for="password" value="Kata Sandi" />
                        <div class="relative mt-1">
                            <input
                                id="password" :type="showPassword ? 'text' : 'password'"
                                class="block w-full py-3 pl-4 pr-10 rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500 transition-all"
                                :class="{'border-red-300 focus:border-red-500 focus:ring-red-200': passwordScore < 3 && form.password.length > 0}"
                                v-model="form.password" required autocomplete="new-password" placeholder="••••••••"
                            />
                            <button type="button" @click="showPassword = !showPassword" class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-gray-600">
                                <svg v-if="showPassword" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/></svg>
                                <svg v-else class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                            </button>
                        </div>

                        <div class="mt-2 mb-1 transition-all duration-300 ease-in-out" v-if="form.password.length > 0">
                            <div class="flex gap-1 h-1.5">
                                <div v-for="i in 4" :key="i" class="flex-1 rounded-full bg-gray-200 overflow-hidden">
                                    <div class="h-full transition-all duration-500 w-0" :class="passwordScore >= i ? strengthColor : ''" style="width: 100%"></div>
                                </div>
                            </div>
                            <p class="text-xs mt-1 text-right font-medium" :class="{
                                'text-red-500': passwordScore <= 1, 'text-orange-500': passwordScore === 2,
                                'text-yellow-600': passwordScore === 3, 'text-green-600': passwordScore === 4
                            }">{{ strengthLabel }}</p>
                            
                            <ul class="text-[10px] text-gray-400 mt-1 flex flex-wrap gap-x-3 gap-y-1">
                                <li :class="passwordRequirements.length ? 'text-green-500 font-bold' : ''">Min 8 Karakter</li>
                                <li :class="passwordRequirements.upper ? 'text-green-500 font-bold' : ''">Huruf Besar</li>
                                <li :class="passwordRequirements.lower ? 'text-green-500 font-bold' : ''">Huruf Kecil</li>
                                <li :class="passwordRequirements.number ? 'text-green-500 font-bold' : ''">Angka</li>
                                <li :class="passwordRequirements.symbol ? 'text-green-500 font-bold' : ''">Simbol</li>
                            </ul>
                        </div>
                        <InputError class="mt-2" :message="form.errors.password" />
                    </div>

                    <div>
                        <InputLabel for="password_confirmation" value="Konfirmasi Kata Sandi" />
                        <div class="relative mt-1">
                            <input
                                id="password_confirmation" :type="showPassword ? 'text' : 'password'"
                                class="block w-full py-3 pl-4 rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500 transition-all"
                                :class="{'border-green-500 focus:ring-green-200': passwordMatch === true, 'border-red-500 focus:ring-red-200': passwordMatch === false}"
                                v-model="form.password_confirmation" required autocomplete="new-password" placeholder="••••••••"
                            />
                        </div>
                        <p v-if="passwordMatch === false" class="mt-1 text-xs text-red-500 font-medium">Kata sandi tidak cocok.</p>
                        <InputError class="mt-2" :message="form.errors.password_confirmation" />
                    </div>

                    <div class="pt-2">
                        <PrimaryButton class="w-full justify-center py-3.5 bg-blue-900 hover:bg-blue-800 active:bg-blue-900 rounded-xl text-base font-bold shadow-lg shadow-blue-900/20 transition-all transform active:scale-[0.99]" :class="{ 'opacity-70 cursor-not-allowed': form.processing || passwordScore < 3 }" :disabled="form.processing || passwordScore < 3">
                            <svg v-if="form.processing" class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                            {{ form.processing ? 'Sedang Mendaftar...' : 'Daftar Akun ->' }}
                        </PrimaryButton>
                    </div>

                    <div class="mt-6 text-center text-sm text-gray-500">
                        Sudah memiliki akun? <Link :href="route('login')" class="text-blue-700 font-bold hover:text-blue-800 hover:underline transition">Masuk Disini</Link>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>