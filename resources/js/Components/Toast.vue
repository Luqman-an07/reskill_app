<script setup>
import { ref, watch, onMounted, onUnmounted } from 'vue';
import { usePage } from '@inertiajs/vue3';

const page = usePage();
const show = ref(false);
const message = ref('');
const type = ref('success'); // 'success' or 'error'

// Fungsi Tampilkan Toast Internal
const showToast = (msg, msgType = 'success') => {
    message.value = msg;
    type.value = msgType;
    show.value = true;

    // Sembunyikan otomatis
    setTimeout(() => {
        show.value = false;
        // Reset flash message Inertia agar tidak muncul ulang
        if (msgType === 'success' && page.props.flash.success === msg) page.props.flash.success = null;
        if (msgType === 'error' && page.props.flash.error === msg) page.props.flash.error = null;
    }, 3000);
};

// 1. Pantau Flash Message (Backend Inertia)
watch(() => page.props.flash.success, (msg) => { if (msg) showToast(msg, 'success'); });
watch(() => page.props.flash.error, (msg) => { if (msg) showToast(msg, 'error'); });

// 2. Event Listener untuk Real-time (Frontend Trigger)
const handleCustomToast = (event) => {
    const { message: msg, type: msgType } = event.detail;
    showToast(msg, msgType || 'success');
};

onMounted(() => {
    // Cek flash saat load awal
    if (page.props.flash.success) showToast(page.props.flash.success, 'success');
    if (page.props.flash.error) showToast(page.props.flash.error, 'error');

    // Pasang telinga untuk event manual
    window.addEventListener('trigger-toast', handleCustomToast);
});

onUnmounted(() => {
    window.removeEventListener('trigger-toast', handleCustomToast);
});
</script>

<template>
    <Transition
        enter-active-class="transition ease-out duration-300"
        enter-from-class="transform opacity-0 translate-y-2"
        enter-to-class="transform opacity-100 translate-y-0"
        leave-active-class="transition ease-in duration-200"
        leave-from-class="transform opacity-100 translate-y-0"
        leave-to-class="transform opacity-0 translate-y-2"
    >
        <div v-if="show" class="fixed top-5 right-5 z-[200] flex items-center w-full max-w-xs p-4 mb-4 text-gray-500 bg-white rounded-lg shadow-xl border-l-4"
             :class="type === 'success' ? 'border-green-500' : 'border-red-500'">
            
            <div v-if="type === 'success'" class="inline-flex items-center justify-center flex-shrink-0 w-8 h-8 text-green-500 bg-green-100 rounded-lg">
                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path d="M10 .5a9.5 9.5 0 1 0 9.5 9.5A9.51 9.51 0 0 0 10 .5Zm3.707 8.207-4 4a1 1 0 0 1-1.414 0l-2-2a1 1 0 0 1 1.414-1.414L9 10.586l3.293-3.293a1 1 0 0 1 1.414 1.414Z"/></svg>
            </div>

            <div v-else class="inline-flex items-center justify-center flex-shrink-0 w-8 h-8 text-red-500 bg-red-100 rounded-lg">
                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path d="M10 .5a9.5 9.5 0 1 0 9.5 9.5A9.51 9.51 0 0 0 10 .5Zm3.707 11.793a1 1 0 1 1-1.414 1.414L10 11.414l-2.293 2.293a1 1 0 0 1-1.414-1.414L8.586 10 6.293 7.707a1 1 0 0 1 1.414-1.414L10 8.586l2.293-2.293a1 1 0 0 1 1.414 1.414L11.414 10l2.293 2.293Z"/></svg>
            </div>

            <div class="ms-3 text-sm font-bold text-gray-800">{{ message }}</div>
            
            <button @click="show = false" type="button" class="ms-auto -mx-1.5 -my-1.5 bg-white text-gray-400 hover:text-gray-900 rounded-lg focus:ring-2 focus:ring-gray-300 p-1.5 hover:bg-gray-100 inline-flex items-center justify-center h-8 w-8">
                <svg class="w-3 h-3" fill="none" viewBox="0 0 14 14"><path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 1 6 6m0 0 6 6M7 7l6-6M7 7l-6 6"/></svg>
            </button>
        </div>
    </Transition>
</template>