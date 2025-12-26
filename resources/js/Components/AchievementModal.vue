<script setup>
import { ref, watch, onMounted, onUnmounted } from 'vue';
import { usePage } from '@inertiajs/vue3';

const page = usePage();
const isOpen = ref(false);
const badge = ref(null);
let timer = null;
const duration = 5000;
const progressWidth = ref(100);
let progressInterval = null;

const closeModal = () => {
    isOpen.value = false;
    // Delay sedikit sebelum null-kan data agar animasi close mulus
    setTimeout(() => {
        badge.value = null;
    }, 300);
    clearTimer();
    
    // Reset flash Inertia jika ada
    if (page.props.flash.new_badge) page.props.flash.new_badge = null;
};

const clearTimer = () => {
    if (timer) clearTimeout(timer);
    if (progressInterval) clearInterval(progressInterval);
    progressWidth.value = 100;
};

const startTimer = () => {
    clearTimer();
    timer = setTimeout(() => closeModal(), duration);
    
    const step = 100 / (duration / 50);
    progressInterval = setInterval(() => {
        progressWidth.value -= step;
        if (progressWidth.value <= 0) progressWidth.value = 0;
    }, 50);
};

// 1. WATCHER (Untuk Aksi Synchronous / Server Flash)
watch(() => page.props.flash.new_badge, (newBadge) => {
    if (newBadge) {
        badge.value = newBadge;
        isOpen.value = true;
        startTimer();
    }
}, { deep: true });

// 2. EVENT LISTENER (Untuk Aksi Real-time / WebSocket)
const handleCustomTrigger = (event) => {
    const badgeData = event.detail;
    if (badgeData) {
        badge.value = badgeData;
        isOpen.value = true;
        startTimer();
    }
};

// Fitur Pause Timer
const pauseTimer = () => clearTimer();
const resumeTimer = () => { if (isOpen.value) startTimer(); };

onMounted(() => {
    window.addEventListener('trigger-achievement-modal', handleCustomTrigger);
});

onUnmounted(() => {
    window.removeEventListener('trigger-achievement-modal', handleCustomTrigger);
    clearTimer();
});
</script>

<template>
    <Teleport to="body">
        <Transition enter-active-class="transition duration-300 ease-out" enter-from-class="opacity-0" enter-to-class="opacity-100" leave-active-class="transition duration-200 ease-in" leave-from-class="opacity-100" leave-to-class="opacity-0">
            <div v-if="isOpen" class="fixed inset-0 z-[99999] flex items-center justify-center px-4 bg-gray-900/80 backdrop-blur-sm" @click="closeModal"></div>
        </Transition>

        <Transition enter-active-class="transition duration-300 cubic-bezier(0.34, 1.56, 0.64, 1)" enter-from-class="opacity-0 scale-75 translate-y-4" enter-to-class="opacity-100 scale-100 translate-y-0" leave-active-class="transition duration-200 ease-in" leave-from-class="opacity-100 scale-100 translate-y-0" leave-to-class="opacity-0 scale-95 translate-y-4">
            
            <div v-if="isOpen && badge" 
                 class="fixed z-[100000] bg-white w-full max-w-sm rounded-2xl shadow-2xl overflow-hidden text-center top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2"
                 @mouseenter="pauseTimer" 
                 @mouseleave="resumeTimer"
            >
                <button @click="closeModal" class="absolute top-3 right-3 text-gray-400 hover:text-gray-600 transition bg-gray-100 hover:bg-gray-200 rounded-full p-1 focus:outline-none z-10">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>

                <div class="bg-gradient-to-b from-yellow-50 to-white pt-8 pb-4 px-6 relative overflow-hidden">
                    <div class="absolute inset-0 animate-spin-slow opacity-30">
                        <svg class="w-full h-full text-yellow-200" fill="currentColor" viewBox="0 0 100 100"><path d="M50 0 L60 40 L100 50 L60 60 L50 100 L40 60 L0 50 L40 40 Z" /></svg>
                    </div>
                    <div class="relative z-10 w-24 h-24 mx-auto drop-shadow-xl animate-bounce-slow">
                        <img v-if="badge.icon_url" :src="badge.icon_url" class="w-full h-full object-contain">
                        <div v-else class="w-full h-full bg-yellow-400 rounded-full flex items-center justify-center text-4xl border-4 border-white shadow-inner">🏆</div>
                    </div>
                </div>

                <div class="px-6 pb-6 pt-2">
                    <h3 class="text-xs font-bold text-yellow-600 tracking-wider uppercase mb-1">Pencapaian Baru!</h3>
                    <h2 class="text-xl font-black text-gray-900 mb-2">{{ badge.badge_name }}</h2>
                    <p class="text-sm text-gray-600 leading-relaxed">{{ badge.description }}</p>
                    <div class="mt-5">
                        <button @click="closeModal" class="w-full py-2.5 bg-gray-900 text-white text-sm font-bold rounded-xl hover:bg-gray-800 transition active:scale-95 shadow-lg">Keren! Lanjut Belajar</button>
                    </div>
                </div>

                <div class="h-1.5 bg-gray-100 w-full mt-2">
                    <div class="h-full bg-yellow-400 transition-all duration-75 ease-linear" :style="{ width: progressWidth + '%' }"></div>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>

<style scoped>
.animate-spin-slow { animation: spin 10s linear infinite; }
@keyframes spin { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }
.animate-bounce-slow { animation: bounce 2s infinite; }
@keyframes bounce { 0%, 100% { transform: translateY(-5%); } 50% { transform: translateY(0); } }
</style>