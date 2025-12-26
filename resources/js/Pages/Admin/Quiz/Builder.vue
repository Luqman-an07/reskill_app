<script setup>
    import { ref } from 'vue';
    import { Head, Link, useForm, router } from '@inertiajs/vue3';
    import AppLayout from '@/Layouts/AppLayout.vue';
    
    const props = defineProps({
        quiz: Object,
        questions: Array,
        courseId: Number,
        backRoute: String,
    });
    
    // State
    const activeQuestionId = ref('new'); // 'new' atau ID question
    
    // Form Inertia
    const form = useForm({
        id: null,
        question_text: '',
        option_a: '',
        option_b: '',
        option_c: '',
        option_d: '',
        correct_answer: 'a',
        score_per_question: 10,
    });
    
    // --- ACTIONS ---
    
    const selectQuestion = (question) => {
        activeQuestionId.value = question.id;
        // Isi form
        form.id = question.id;
        form.question_text = question.question_text;
        form.option_a = question.option_a;
        form.option_b = question.option_b;
        form.option_c = question.option_c;
        form.option_d = question.option_d;
        form.correct_answer = question.correct_answer;
        form.score_per_question = question.score_per_question;
        form.clearErrors();
    };
    
    const selectNew = () => {
        activeQuestionId.value = 'new';
        form.reset();
        form.id = null;
        form.clearErrors();
    };
    
    const submit = () => {
        if (activeQuestionId.value === 'new') {
            // Create
            form.post(route('admin.quizzes.questions.store', props.quiz.id), {
                onSuccess: () => selectNew(),
            });
        } else {
            // Update
            form.put(route('admin.questions.update', activeQuestionId.value), {
                preserveScroll: true,
            });
        }
    };
    
    const deleteQuestion = (id) => {
        if(confirm("Apakah Anda yakin ingin menghapus pertanyaan ini?")) {
            router.delete(route('admin.questions.destroy', id), {
                onSuccess: () => {
                    if(activeQuestionId.value === id) selectNew();
                }
            });
        }
    };
    </script>
    
    <template>
        <Head :title="'Pembuatan Kuis: ' + quiz.quiz_title" />
    
        <AppLayout>
            <div class="max-w-6xl mx-auto h-auto md:h-[calc(100vh-100px)] flex flex-col p-2 md:p-0">
                
                <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-4 sm:mb-6 shrink-0 gap-2">
                    <div>
                        <Link :href="route(backRoute, courseId)" class="text-sm text-gray-500 hover:text-gray-700 flex items-center gap-1 mb-1">
                            <span>←</span> Kembali ke Kursus
                        </Link>
                        <h1 class="text-xl sm:text-2xl font-bold text-gray-900 line-clamp-1">Kuis: <span class="text-blue-900">{{ quiz.quiz_title }}</span></h1>
                    </div>
                    <div class="text-left sm:text-right flex items-center sm:block gap-2">
                        <p class="text-xs sm:text-sm text-gray-500">Total Soal</p>
                        <p class="text-xl sm:text-2xl font-bold text-gray-900">{{ questions.length }}</p>
                    </div>
                </div>
    
                <div class="flex flex-col md:flex-row gap-4 md:gap-6 flex-1 min-h-0">
                    
                    <div class="w-full md:w-1/3 bg-white rounded-xl border border-gray-200 flex flex-col shadow-sm overflow-hidden h-64 md:h-auto shrink-0 order-2 md:order-1">
                        <div class="p-3 sm:p-4 border-b border-gray-100 bg-gray-50 flex justify-between items-center sticky top-0 z-10">
                            <h3 class="font-bold text-gray-700 text-sm sm:text-base">Daftar Soal</h3>
                            <button @click="selectNew" class="text-xs bg-blue-600 text-white px-2 py-1 rounded hover:bg-blue-700 transition shadow-sm">+ Buat Baru</button>
                        </div>
                        <div class="flex-1 overflow-y-auto p-2 space-y-2 bg-gray-50/50">
                            <div v-for="(q, idx) in questions" :key="q.id" 
                                 @click="selectQuestion(q)"
                                 class="p-3 rounded-lg cursor-pointer border transition relative group"
                                 :class="activeQuestionId === q.id ? 'bg-blue-50 border-blue-500 ring-1 ring-blue-500 shadow-sm' : 'bg-white border-gray-200 hover:border-gray-300'"
                            >
                                <div class="flex justify-between items-start gap-2">
                                    <span class="text-xs font-bold text-gray-500 min-w-[20px]">#{{ idx + 1 }}</span>
                                    <p class="text-xs sm:text-sm text-gray-800 line-clamp-2 flex-1 font-medium">{{ q.question_text }}</p>
                                </div>
                                <div class="mt-2 flex justify-between items-center">
                                    <span class="text-[10px] bg-gray-100 text-gray-600 px-1.5 py-0.5 rounded border border-gray-200 font-mono">Skor: {{ q.score_per_question }}</span>
                                    <button @click.stop="deleteQuestion(q.id)" class="text-gray-300 hover:text-red-500 p-1 transition">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                    </button>
                                </div>
                            </div>
                            
                            <button v-if="activeQuestionId === 'new'" class="w-full p-3 rounded-lg border-2 border-dashed border-blue-300 bg-blue-50 text-blue-600 text-xs sm:text-sm font-bold text-center">
                                Sedang Membuat...
                            </button>
                        </div>
                    </div>
    
                    <div class="w-full md:w-2/3 bg-white rounded-xl border border-gray-200 shadow-sm flex flex-col overflow-hidden order-1 md:order-2 h-auto md:h-full">
                        <div class="p-4 sm:p-6 border-b border-gray-100 bg-white sticky top-0 z-20">
                            <h3 class="text-lg font-bold text-gray-900 flex items-center gap-2">
                                <span class="w-2 h-6 bg-blue-600 rounded-full"></span>
                                {{ activeQuestionId === 'new' ? 'Tambah Pertanyaan Baru' : 'Ubah Pertanyaan' }}
                            </h3>
                        </div>
                        
                        <div class="flex-1 overflow-y-auto p-4 sm:p-8 bg-white">
                            <form @submit.prevent="submit" class="max-w-2xl mx-auto space-y-6">
                                
                                <div>
                                    <label class="block text-sm font-bold text-gray-700 mb-2">Pertanyaan</label>
                                    <textarea v-model="form.question_text" rows="3" class="w-full rounded-lg border-gray-300 focus:ring-blue-500 focus:border-blue-500 text-sm" placeholder="Ketik pertanyaan disini..." required></textarea>
                                    <p v-if="form.errors.question_text" class="text-red-500 text-xs mt-1">{{ form.errors.question_text }}</p>
                                </div>
    
                                <div class="bg-gray-50 p-4 sm:p-6 rounded-xl border border-gray-200 space-y-4">
                                    <label class="block text-sm font-bold text-gray-700 mb-2">Pilihan Jawaban</label>
                                    
                                    <div v-for="opt in ['a', 'b', 'c', 'd']" :key="opt" class="flex items-center gap-3">
                                        <div class="flex items-center pt-1 self-start">
                                            <input type="radio" :id="'correct_'+opt" :value="opt" v-model="form.correct_answer" class="w-4 h-4 sm:w-5 sm:h-5 text-green-600 focus:ring-green-500 cursor-pointer border-gray-300">
                                        </div>
                                        
                                        <div class="flex-1 relative">
                                            <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-500 font-bold uppercase text-xs">{{ opt }}</span>
                                            <input v-model="form['option_'+opt]" type="text" class="w-full pl-8 rounded-lg border-gray-300 focus:ring-blue-500 focus:border-blue-500 text-sm" :placeholder="'Pilihan ' + opt.toUpperCase()" required>
                                        </div>
                                    </div>
                                    <p class="text-xs text-gray-500 mt-2 flex items-center gap-1">
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                        Pilih radio button untuk kunci jawaban yang benar.
                                    </p>
                                </div>
    
                                <div>
                                    <label class="block text-sm font-bold text-gray-700 mb-2">Bobot Poin</label>
                                    <input v-model="form.score_per_question" type="number" min="1" class="w-full sm:w-32 rounded-lg border-gray-300 focus:ring-blue-500 focus:border-blue-500 text-sm">
                                </div>
    
                            </form>
                        </div>
    
                        <div class="p-4 sm:p-6 border-t border-gray-200 bg-gray-50 flex justify-end gap-3 sticky bottom-0 z-20">
                            <button v-if="activeQuestionId !== 'new'" @click="selectNew" type="button" class="px-4 sm:px-6 py-2.5 border border-gray-300 text-gray-700 font-medium rounded-lg hover:bg-white transition text-sm">Batal</button>
                            <button @click="submit" type="button" class="px-6 sm:px-8 py-2.5 bg-blue-900 text-white font-bold rounded-lg hover:bg-blue-800 shadow-md flex items-center gap-2 text-sm disabled:opacity-50" :disabled="form.processing">
                                <svg v-if="form.processing" class="animate-spin h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                <span>{{ form.processing ? 'Menyimpan...' : (activeQuestionId === 'new' ? 'Simpan Soal' : 'Perbaharui Soal') }}</span>
                            </button>
                        </div>
                    </div>
    
                </div>
            </div>
        </AppLayout>
    </template>