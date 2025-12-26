<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { onMounted, ref } from 'vue';

const props = defineProps({
    quiz: Object,
    attempt: Object,
    stats: Object,
});

// Logika Tampilan
const isPassed = props.attempt.is_passed;
const percentage = Math.round(props.attempt.score); // Asumsi score sudah 0-100

// State Animasi
const showConfetti = ref(false);

onMounted(() => {
    if (isPassed) {
        // Efek delay sederhana untuk animasi masuk
        setTimeout(() => showConfetti.value = true, 300);
    }
});
</script>

<template>
    <Head title="Hasil Kuis" />

    <AppLayout>
        <div class="min-h-[calc(100vh-4rem)] flex items-center justify-center p-4 bg-gradient-to-br from-gray-50 to-blue-50">
            
            <div class="w-full max-w-2xl bg-white rounded-3xl shadow-xl border border-gray-100 overflow-hidden relative animate-fade-in-up">
                
                <div class="absolute top-0 left-0 w-full h-32 z-0"
                     :class="isPassed ? 'bg-gradient-to-r from-green-400 to-emerald-500' : 'bg-gradient-to-r from-red-400 to-rose-500'">
                </div>

                <div class="relative z-10 pt-12 pb-8 px-8 text-center">
                    
                    <div class="mx-auto w-20 h-20 bg-white rounded-full p-1.5 shadow-lg mb-6 flex items-center justify-center transform transition-transform hover:scale-105 duration-300">
                        <div class="w-full h-full rounded-full flex items-center justify-center border-4"
                             :class="isPassed ? 'border-green-100 bg-green-50 text-green-500' : 'border-red-100 bg-red-50 text-red-500'">
                            
                            <svg v-if="isPassed" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-10 h-10 animate-bounce-slow">
                                <path fill-rule="evenodd" d="M5.166 2.621v.858c-1.035.148-2.059.33-3.071.543a.75.75 0 00-.584.859 6.753 6.753 0 006.138 5.6 6.73 6.73 0 002.743 1.346A6.707 6.707 0 019.279 15H8.54c-1.036 0-1.875.84-1.875 1.875V19.5h-.75a2.25 2.25 0 00-2.25 2.25c0 .414.336.75.75.75h15a.75.75 0 00.75-.75 2.25 2.25 0 00-2.25-2.25h-.75v-2.625c0-1.036-.84-1.875-1.875-1.875h-.739a6.706 6.706 0 01-1.112-3.173 6.73 6.73 0 002.743-1.347 6.753 6.753 0 006.139-5.6.75.75 0 00-.585-.858 47.077 47.077 0 00-3.07-.543V2.62a.75.75 0 00-.658-.744 49.22 49.22 0 00-6.093-.377c-2.063 0-4.096.128-6.093.377a.75.75 0 00-.657.744zm0 2.629c0 1.196.312 2.32.857 3.294A5.266 5.266 0 013.16 5.337a45.6 45.6 0 012.006-.348zm13.668 8.031a5.255 5.255 0 01-5.502-6.445 6.72 6.72 0 003.213-1.68 6.74 6.74 0 002.289-5.347zm-4.632-10.73a47.77 47.77 0 00-2.302-.225 48.503 48.503 0 00-4.795 0c-.77.05-1.532.117-2.288.2.075 3.143 2.392 5.713 5.53 6.734 2.76-1.064 4.813-3.627 4.855-6.709z" clip-rule="evenodd" />
                            </svg>

                            <svg v-else xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-10 h-10">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" />
                            </svg>
                        </div>
                    </div>

                    <h1 class="text-3xl font-bold text-gray-900 mb-2">{{ isPassed ? 'Kuis Selesai!' : 'Coba Lagi, yaa!' }}</h1>
                    <p class="text-gray-500 text-lg max-w-md mx-auto">
                        {{ isPassed 
                            ? 'Bagus sekali! Kamu telah menunjukkan pemahaman yang sangat baik tentang materi tersebut.' 
                            : 'Jangan khawatir. Periksa kembali materi dan coba lagi untuk mendapatkan poin Kamu.' 
                        }}
                    </p>

                    <div class="grid grid-cols-3 gap-4 mt-10 mb-8">
                        
                        <div class="bg-gray-50 rounded-2xl p-4 border border-gray-100 flex flex-col items-center justify-center">
                            <span class="text-xs uppercase font-bold text-gray-400 tracking-wider mb-1">Skor</span>
                            <div class="text-2xl font-black" :class="isPassed ? 'text-gray-900' : 'text-red-500'">
                                {{ percentage }}%
                            </div>
                            <span class="text-[10px] text-gray-400 mt-1">Min: {{ quiz.passing_score }}%</span>
                        </div>

                        <div class="bg-gray-50 rounded-2xl p-4 border border-gray-100 flex flex-col items-center justify-center">
                            <span class="text-xs uppercase font-bold text-gray-400 tracking-wider mb-1">Pertanyaan</span>
                            <div class="text-2xl font-black text-gray-900">
                                {{ stats.total_questions }}
                            </div>
                            <span class="text-[10px] text-gray-400 mt-1">Total Pertanyaan</span>
                        </div>

                        <div class="bg-gray-50 rounded-2xl p-4 border border-gray-100 flex flex-col items-center justify-center relative overflow-hidden"
                             :class="{'ring-2 ring-yellow-400 ring-offset-2': isPassed}">
                            <div v-if="isPassed" class="absolute top-0 right-0 -mt-1 -mr-1 w-3 h-3 bg-yellow-400 rounded-full animate-ping"></div>
                            <span class="text-xs uppercase font-bold text-gray-400 tracking-wider mb-1">Hadiah</span>
                            <div class="text-2xl font-black text-yellow-500">
                                +{{ isPassed ? quiz.points_reward : 0 }}
                            </div>
                            <span class="text-[10px] text-yellow-600 font-bold mt-1">XP Diperoleh</span>
                        </div>
                    </div>

                    <div class="flex flex-col sm:flex-row justify-center gap-4">
                        <Link v-if="!isPassed" 
                              :href="route('quiz.show', quiz.module_id)" 
                              class="px-8 py-3 rounded-xl font-bold text-gray-700 bg-white border border-gray-300 hover:bg-gray-50 hover:text-gray-900 transition shadow-sm"
                        >
                            Coba Lagi
                        </Link>

                        <Link :href="route('course.show', quiz.course_id)" 
                              class="px-10 py-3 rounded-xl font-bold text-white shadow-lg shadow-blue-200 transition transform hover:-translate-y-1 active:scale-95 flex items-center justify-center gap-2"
                              :class="isPassed ? 'bg-blue-900 hover:bg-blue-800' : 'bg-gray-800 hover:bg-gray-900'">
                            <span>Kembali Ke kursus</span>
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6" /></svg>
                        </Link>
                    </div>

                </div>
            </div>

        </div>
    </AppLayout>
</template>

<style scoped>
/* Animasi Masuk */
.animate-fade-in-up {
    animation: fadeInUp 0.6s ease-out forwards;
}

@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* Animasi Bounce Lambat untuk Piala */
.animate-bounce-slow {
    animation: bounce 3s infinite;
}
</style>