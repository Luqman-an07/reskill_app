<script setup>
defineProps({
    show: {
        type: Boolean,
        default: false,
    },
    text: {
        type: String,
        default: 'Memproses data...',
    },
    isGlobal: { // Jika True = Fixed (Full Screen), Jika False = Absolute (Container)
        type: Boolean,
        default: true, 
    }
});
</script>

<template>
    <Transition
        enter-active-class="transition ease-out duration-300"
        enter-from-class="opacity-0"
        enter-to-class="opacity-100"
        leave-active-class="transition ease-in duration-200"
        leave-from-class="opacity-100"
        leave-to-class="opacity-0"
    >
        <div 
            v-if="show"
            :class="[
                'flex flex-col items-center justify-center z-[9999] bg-white/80 backdrop-blur-sm transition-all',
                isGlobal ? 'fixed inset-0' : 'absolute inset-0 rounded-xl'
            ]"
        >
            <div class="relative w-16 h-16">
                <div class="absolute inset-0 border-4 border-gray-200 rounded-full"></div>
                <div class="absolute inset-0 border-4 border-blue-900 rounded-full border-t-transparent animate-spin"></div>
                
                <div class="absolute inset-0 flex items-center justify-center">
                    <svg class="w-6 h-6 text-blue-900 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                </div>
            </div>

            <p class="mt-4 text-sm font-bold text-blue-900 tracking-wide animate-pulse">{{ text }}</p>
        </div>
    </Transition>
</template>