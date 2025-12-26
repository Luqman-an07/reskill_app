<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref, onMounted, onUnmounted, computed } from 'vue';

const props = defineProps({
    quiz: Object,
    previousAnswers: Object,
});

// --- STATE ---
const currentQuestionIndex = ref(0);
const answers = ref(props.previousAnswers || {});
const timeLeft = ref(props.quiz.duration); // Detik
let timerInterval = null;

// --- COMPUTED ---
const currentQuestion = computed(() => props.quiz.questions[currentQuestionIndex.value]);
const isLastQuestion = computed(() => currentQuestionIndex.value === props.quiz.questions.length - 1);
const progressPercent = computed(() => ((currentQuestionIndex.value + 1) / props.quiz.questions.length) * 100);

// Format Timer (MM:SS)
const formattedTime = computed(() => {
    const m = Math.floor(timeLeft.value / 60).toString().padStart(2, '0');
    const s = (timeLeft.value % 60).toString().padStart(2, '0');
    return `${m} : ${s}`;
});

// --- ACTIONS ---
const selectOption = (key) => {
    answers.value[currentQuestion.value.id] = key;
};

const nextQuestion = () => {
    if (!isLastQuestion.value) currentQuestionIndex.value++;
};

const prevQuestion = () => {
    if (currentQuestionIndex.value > 0) currentQuestionIndex.value--;
};

// Submit Form
const form = useForm({
    answers: {},
    attempt_id: props.quiz.attempt_id
});

const submitQuiz = () => {
    clearInterval(timerInterval); // Stop timer
    form.answers = answers.value;
    form.post(route('quiz.submit', props.quiz.id));
};

// --- LIFECYCLE ---
onMounted(() => {
    // Timer Countdown
    timerInterval = setInterval(() => {
        if (timeLeft.value > 0) {
            timeLeft.value--;
        } else {
            // Waktu Habis -> Auto Submit
            submitQuiz();
        }
    }, 1000);
});

onUnmounted(() => clearInterval(timerInterval));
</script>

<template>
    <Head :title="quiz.title" />

    <div class="min-h-screen bg-gray-100 font-sans py-8 px-4">
        
        <div class="max-w-4xl mx-auto">
            
            <div class="flex items-center justify-between mb-6">
                <Link :href="route('course.show', quiz.course_id)" class="text-gray-600 hover:text-blue-600 font-medium flex items-center gap-1 sm:gap-2 transition group text-sm sm:text-base">
                    <span class="group-hover:-translate-x-1 transition-transform">←</span> 
                    <span class="hidden sm:inline">Kembali ke Kursus</span>
                    <span class="sm:hidden">Kembali</span>
                </Link>
                
                <div class="bg-gray-800 text-white px-4 py-2 rounded-lg font-mono font-bold flex items-center gap-2 shadow-sm">
                    <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                    {{ formattedTime }}
                </div>
            </div>

            <div class="bg-white rounded-2xl shadow-lg border border-gray-200 overflow-hidden relative min-h-[500px] flex flex-col">
                
                <div class="h-2 bg-gray-100 w-full">
                    <div class="h-full bg-blue-600 transition-all duration-300" :style="{ width: progressPercent + '%' }"></div>
                </div>

                <div class="p-8 md:p-12 flex-1 flex flex-col">
                    
                    <div class="flex justify-between items-start mb-6">
                        <h1 class="text-2xl font-bold text-gray-900">{{ quiz.title }}</h1>
                        <span class="text-xs font-bold text-gray-400 uppercase tracking-wide">
                            Pertanyaan {{ currentQuestionIndex + 1 }} dari {{ quiz.questions.length }}
                        </span>
                    </div>

                    <h2 class="text-lg md:text-xl text-gray-800 font-medium mb-8 leading-relaxed">
                        {{ currentQuestion.text }}
                    </h2>

                    <div class="space-y-4 mb-8">
                        <div 
                            v-for="option in currentQuestion.options" 
                            :key="option.key"
                            @click="selectOption(option.key)"
                            class="group flex items-center gap-4 p-4 rounded-xl border-2 cursor-pointer transition-all duration-200"
                            :class="answers[currentQuestion.id] === option.key 
                                ? 'border-blue-600 bg-blue-50' 
                                : 'border-gray-200 bg-gray-50 hover:border-gray-300 hover:bg-gray-100'"
                        >
                            <div class="w-6 h-6 rounded-full border-2 flex items-center justify-center flex-shrink-0"
                                 :class="answers[currentQuestion.id] === option.key ? 'border-blue-600' : 'border-gray-400'">
                                <div v-if="answers[currentQuestion.id] === option.key" class="w-3 h-3 bg-blue-600 rounded-full"></div>
                            </div>
                            
                            <span class="text-gray-700 font-medium group-hover:text-gray-900">
                                {{ option.label }}
                            </span>
                        </div>
                    </div>

                </div>

                <div class="bg-gray-50 p-6 border-t border-gray-200 flex justify-between items-center">
                    
                    <button 
                        @click="prevQuestion" 
                        :disabled="currentQuestionIndex === 0"
                        class="px-6 py-2.5 rounded-lg font-medium text-gray-600 bg-white border border-gray-300 hover:bg-gray-100 disabled:opacity-50 disabled:cursor-not-allowed transition"
                    >
                        Sebelumnya
                    </button>

                    <button 
                        v-if="!isLastQuestion"
                        @click="nextQuestion" 
                        class="px-6 py-2.5 rounded-lg font-medium text-white bg-gray-800 hover:bg-gray-900 transition"
                    >
                        Lanjut
                    </button>

                    <button 
                        v-else
                        @click="submitQuiz" 
                        class="px-8 py-2.5 rounded-lg font-bold text-white bg-blue-600 hover:bg-blue-700 shadow-md transition transform active:scale-95"
                    >
                        Kuis Selesai
                    </button>

                </div>

            </div>

        </div>
    </div>
</template>