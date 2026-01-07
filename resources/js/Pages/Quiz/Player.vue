<script setup>
    import { Head, Link, useForm } from '@inertiajs/vue3';
    import { ref, onMounted, onUnmounted, computed } from 'vue';
    
    const props = defineProps({
        quiz: Object,
        previousAnswers: Object,
        remaining_seconds: Number,
    });
    
    // --- STATE ---
    const currentQuestionIndex = ref(0);
    const answers = ref(props.previousAnswers || {});
    // [FIX] Gunakan remaining_seconds dari backend
    const timeLeft = ref(props.remaining_seconds);
    let timerInterval = null;
    
    // --- COMPUTED ---
    const currentQuestion = computed(() => props.quiz.questions[currentQuestionIndex.value]);
    const isLastQuestion = computed(() => currentQuestionIndex.value === props.quiz.questions.length - 1);
    const progressPercent = computed(() => {
        const answeredCount = Object.keys(answers.value).length;
        return Math.round((answeredCount / props.quiz.questions.length) * 100);
    });
    
    // Format Timer (MM:SS)
    const formattedTime = computed(() => {
        const m = Math.floor(timeLeft.value / 60).toString().padStart(2, '0');
        const s = (timeLeft.value % 60).toString().padStart(2, '0');
        return `${m}:${s}`;
    });
    
    // --- ACTIONS ---
    const selectOption = (key) => {
        answers.value[currentQuestion.value.id] = key;
    };
    
    const jumpToQuestion = (index) => {
        currentQuestionIndex.value = index;
    };
    
    const nextQuestion = () => {
        if (!isLastQuestion.value) currentQuestionIndex.value++;
    };
    
    const prevQuestion = () => {
        if (currentQuestionIndex.value > 0) currentQuestionIndex.value--;
    };
    
    // Cek apakah soal sudah dijawab (untuk navigasi)
    const isAnswered = (questionId) => {
        return answers.value[questionId] !== undefined;
    };
    
    // Submit Form
    const form = useForm({
        answers: {},
        attempt_id: props.quiz.attempt_id
    });
    
    const submitQuiz = () => {
        clearInterval(timerInterval); // Stop timer
        form.answers = answers.value;
        form.post(route('quiz.submit', props.quiz.id), {
            preserveScroll: true,
        });
    };
    
    // --- LIFECYCLE ---
    onMounted(() => {
        // Timer Countdown
        if (timeLeft.value > 0) {
            timerInterval = setInterval(() => {
                if (timeLeft.value > 0) {
                    timeLeft.value--;
                } else {
                    // Waktu Habis -> Auto Submit
                    submitQuiz();
                }
            }, 1000);
        } else {
            // Jika waktu 0 dari awal (mungkin error atau reload telat), auto submit
            submitQuiz();
        }
    });
    
    onUnmounted(() => clearInterval(timerInterval));
    </script>
    
    <template>
        <Head :title="quiz.title" />
    
        <div class="min-h-screen bg-gray-50 font-sans pb-12">
            
            <header class="bg-white border-b border-gray-200 sticky top-0 z-30 shadow-sm">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between">
                    
                    <div class="flex items-center gap-4">
                        <Link :href="route('course.show', quiz.course_id)" class="text-gray-400 hover:text-gray-600 transition p-2 rounded-full hover:bg-gray-100">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                        </Link>
                        <div>
                            <h1 class="text-base font-bold text-gray-900 leading-tight line-clamp-1">{{ quiz.title }}</h1>
                            <div class="text-xs text-gray-500 hidden sm:block">
                                Soal {{ currentQuestionIndex + 1 }} dari {{ quiz.questions.length }}
                            </div>
                        </div>
                    </div>
    
                    <div class="flex items-center gap-3">
                        <div class="flex items-center gap-2 px-3 py-1.5 rounded-lg border font-mono font-bold text-lg shadow-sm transition-colors duration-500"
                            :class="timeLeft < 60 ? 'bg-red-50 border-red-200 text-red-600 animate-pulse' : 'bg-gray-50 border-gray-200 text-gray-700'">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                            {{ formattedTime }}
                        </div>
                        
                        <button 
                            @click="submitQuiz" 
                            class="hidden sm:inline-flex items-center justify-center px-4 py-2 bg-blue-600 text-white text-sm font-bold rounded-lg hover:bg-blue-700 transition shadow-sm active:scale-95"
                        >
                            Selesai
                        </button>
                    </div>
                </div>
                
                <div class="h-1 bg-gray-100 w-full">
                    <div class="h-full bg-blue-600 transition-all duration-500 ease-out" :style="{ width: progressPercent + '%' }"></div>
                </div>
            </header>
    
            <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
                <div class="flex flex-col lg:flex-row gap-8 items-start">
                    
                    <div class="flex-1 w-full lg:w-auto">
                        <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden min-h-[400px] flex flex-col">
                            
                            <div class="p-6 md:p-10 flex-1">
                                <span class="inline-block px-3 py-1 bg-gray-100 text-gray-500 text-xs font-bold rounded-full mb-4">
                                    Pertanyaan #{{ currentQuestionIndex + 1 }}
                                </span>
    
                                <h2 class="text-xl md:text-2xl text-gray-900 font-bold mb-8 leading-relaxed">
                                    {{ currentQuestion.text }}
                                </h2>
    
                                <div class="space-y-3">
                                    <div 
                                        v-for="option in currentQuestion.options" 
                                        :key="option.key"
                                        @click="selectOption(option.key)"
                                        class="group relative flex items-center p-4 rounded-xl border-2 cursor-pointer transition-all duration-200"
                                        :class="answers[currentQuestion.id] === option.key 
                                            ? 'border-blue-600 bg-blue-50/50 ring-1 ring-blue-600' 
                                            : 'border-gray-200 hover:border-blue-300 hover:bg-gray-50'"
                                    >
                                        <div class="flex-shrink-0 mr-4">
                                            <div class="w-6 h-6 rounded-full border-2 flex items-center justify-center transition-colors duration-200"
                                                 :class="answers[currentQuestion.id] === option.key ? 'border-blue-600 bg-blue-600' : 'border-gray-300 group-hover:border-blue-400'">
                                                <div v-if="answers[currentQuestion.id] === option.key" class="w-2.5 h-2.5 bg-white rounded-full"></div>
                                            </div>
                                        </div>
                                        
                                        <span class="text-base font-medium transition-colors duration-200"
                                            :class="answers[currentQuestion.id] === option.key ? 'text-blue-900' : 'text-gray-700'">
                                            {{ option.label }}
                                        </span>
                                    </div>
                                </div>
                            </div>
    
                            <div class="bg-gray-50 px-6 py-4 border-t border-gray-200 flex justify-between items-center">
                                <button 
                                    @click="prevQuestion" 
                                    :disabled="currentQuestionIndex === 0"
                                    class="px-5 py-2.5 rounded-lg text-sm font-bold text-gray-600 bg-white border border-gray-300 hover:bg-gray-100 disabled:opacity-50 disabled:cursor-not-allowed transition shadow-sm"
                                >
                                    ← Sebelumnya
                                </button>
    
                                <button 
                                    v-if="!isLastQuestion"
                                    @click="nextQuestion" 
                                    class="px-6 py-2.5 rounded-lg text-sm font-bold text-white bg-gray-900 hover:bg-black transition shadow-md active:scale-95"
                                >
                                    Lanjut →
                                </button>
    
                                <button 
                                    v-else
                                    @click="submitQuiz" 
                                    class="px-8 py-2.5 rounded-lg text-sm font-bold text-white bg-blue-600 hover:bg-blue-700 shadow-md transition transform active:scale-95"
                                >
                                    Kirim Jawaban
                                </button>
                            </div>
                        </div>
                    </div>
    
                    <div class="w-full lg:w-80 flex-shrink-0">
                        <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-5 sticky top-24">
                            <div class="flex justify-between items-center mb-4">
                                <h3 class="font-bold text-gray-900">Navigasi Soal</h3>
                                <span class="text-xs text-gray-500 font-medium bg-gray-100 px-2 py-1 rounded">
                                    {{ Object.keys(answers).length }} / {{ quiz.questions.length }} Terjawab
                                </span>
                            </div>
    
                            <div class="grid grid-cols-5 gap-2">
                                <button 
                                    v-for="(q, index) in quiz.questions" 
                                    :key="q.id"
                                    @click="jumpToQuestion(index)"
                                    class="w-10 h-10 rounded-lg flex items-center justify-center text-sm font-bold transition-all duration-200 border"
                                    :class="[
                                        currentQuestionIndex === index 
                                            ? 'ring-2 ring-blue-600 ring-offset-2 border-blue-600 bg-blue-600 text-white' 
                                            : (isAnswered(q.id) 
                                                ? 'bg-green-100 text-green-700 border-green-200 hover:bg-green-200' 
                                                : 'bg-white text-gray-600 border-gray-200 hover:bg-gray-100')
                                    ]"
                                >
                                    {{ index + 1 }}
                                </button>
                            </div>
    
                            <div class="mt-6 pt-6 border-t border-gray-100">
                                <div class="flex flex-col gap-2 text-xs text-gray-500">
                                    <div class="flex items-center gap-2">
                                        <div class="w-3 h-3 bg-blue-600 rounded"></div> Sedang Dikerjakan
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <div class="w-3 h-3 bg-green-100 border border-green-200 rounded"></div> Sudah Dijawab
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <div class="w-3 h-3 bg-white border border-gray-200 rounded"></div> Belum Dijawab
                                    </div>
                                </div>
                            </div>
    
                            <button 
                                @click="submitQuiz" 
                                class="mt-6 w-full sm:hidden flex items-center justify-center px-4 py-3 bg-blue-600 text-white text-sm font-bold rounded-xl hover:bg-blue-700 transition shadow-lg active:scale-95"
                            >
                                Selesai & Kirim
                            </button>
                        </div>
                    </div>
    
                </div>
            </main>
        </div>
    </template>