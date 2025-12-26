<script setup>
import { Head, Link } from '@inertiajs/vue3';

const props = defineProps({
    quiz: Object,
    attempt: Object, // Berisi 'answers' (jawaban user)
});

// Helper untuk cek status jawaban
const getOptionClass = (questionId, optionKey, correctAnswer) => {
    const userAnswers = props.attempt.answers || {};
    const userAnswer = userAnswers[questionId];
        
    // 1. Jika ini jawaban BENAR (Kunci)
    if (optionKey === correctAnswer) {
        return 'bg-green-100 border-green-500 text-green-800';
    }
    
    // 2. Jika ini jawaban USER dan SALAH
    if (userAnswer === optionKey && userAnswer !== correctAnswer) {
        return 'bg-red-100 border-red-500 text-red-800';
    }

    // 3. Sisanya (netral)
    return 'bg-gray-50 border-gray-200 text-gray-500';
};
</script>

<template>
    <Head :title="'Ulasan: ' + quiz.title" />

    <div class="min-h-screen bg-gray-100 font-sans py-8 px-4">
        <div class="max-w-4xl mx-auto">
            
            <div class="flex items-center justify-between mb-6">
                <Link :href="route('course.show', quiz.course_id)" class="text-gray-600 hover:text-blue-600 font-medium flex items-center gap-1 sm:gap-2 transition group text-sm sm:text-base">
                    <span class="group-hover:-translate-x-1 transition-transform">←</span> 
                    <span class="hidden sm:inline">Kembali ke Kursus</span>
                    <span class="sm:hidden">Kembali</span>
                </Link>
                <div class="bg-white px-4 py-2 rounded-lg shadow-sm text-sm font-bold">
                    Skor: <span :class="attempt.is_passed ? 'text-green-600' : 'text-red-600'">{{ attempt.final_score }}</span>
                </div>
            </div>

            <div class="bg-white rounded-2xl shadow-lg border border-gray-200 overflow-hidden p-8">
                <h1 class="text-2xl font-bold text-gray-900 mb-2">Ulasan: {{ quiz.title }}</h1>
                <p class="text-gray-500 mb-8">Periksa jawaban Kamu dan pelajari dari kesalahan.</p>

                <div class="space-y-10">
                    <div v-for="(q, index) in quiz.questions" :key="q.id" class="border-b border-gray-100 pb-8 last:border-0">
                        
                        <div class="flex gap-4 mb-4">
                            <span class="flex-shrink-0 w-8 h-8 bg-blue-100 text-blue-700 rounded-full flex items-center justify-center font-bold text-sm">
                                {{ index + 1 }}
                            </span>
                            <h3 class="text-lg font-medium text-gray-800">{{ q.text }}</h3>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-3 ml-12">
                            <div v-for="opt in q.options" :key="opt.key" 
                                 class="p-3 rounded-lg border-2 text-sm font-medium transition-colors flex justify-between items-center"
                                 :class="getOptionClass(q.id, opt.key, q.correct_answer)"
                            >
                                <span>{{ opt.key.toUpperCase() }}. {{ opt.label }}</span>
                                
                                <span v-if="opt.key === q.correct_answer">✅</span>
                                <span v-else-if="attempt.answers[q.id] === opt.key">❌</span>
                            </div>
                        </div>

                        <div v-if="attempt.answers[q.id] !== q.correct_answer" class="ml-12 mt-3 text-sm text-red-500">
                            Anda menjawab <strong>{{ attempt.answers[q.id]?.toUpperCase() || '-' }}</strong>, jawaban yang benar adalah <strong>{{ q.correct_answer.toUpperCase() }}</strong>.
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
</template>