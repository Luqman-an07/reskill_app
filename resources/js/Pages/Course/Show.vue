<script setup>
    import { Head, Link, router } from '@inertiajs/vue3';
    import AppLayout from '@/Layouts/AppLayout.vue';
    import { ref, computed } from 'vue';
    
    // --- PROPS DARI CONTROLLER ---
    const props = defineProps({
        course: Object,
        modules: Array,      // List Modul (Silabus)
        gamification: Object, // { total_points, earned_points, bonus_points }
        isEnrolled: Boolean,
        isCompleted: Boolean, // [BARU] Status Kelulusan
        progressPercent: Number,
        firstModuleId: Number,
        resumeModule: Object, // { id, type } - Modul terakhir/selanjutnya
    });
    
    // State Loading Tombol
    const isJoining = ref(false);
    
    // --- ACTION: JOIN COURSE ---
    const joinCourse = () => {
        isJoining.value = true;
        router.post(route('course.enroll', props.course.id), {}, {
            preserveScroll: true, 
            onFinish: () => {
                isJoining.value = false;
            },
            onError: () => {
                isJoining.value = false;
            }
        });
    };
    
    // --- ACTION: OPEN MODULE (Smart Routing) ---
    const openModule = (module) => {
        // 1. Cek Kunci
        if (module.status === 'locked') return; 
        
        // 2. Redirect sesuai tipe konten
        if (module.type === 'QUIZ') {
            router.visit(route('quiz.show', module.id));
        } else if (module.type === 'TASK') {
            router.visit(route('task.show', module.id));
        } else {
            router.visit(route('module.show', module.id));
        }
    };
    
    // --- LOGIKA LINK TOMBOL LANJUT (HEADER & STICKY BAR) ---
    const resumeLink = computed(() => {
        // Jika ada modul resume (terakhir akses), pakai itu
        if (props.resumeModule) {
            const { id, type } = props.resumeModule;
            if (type === 'QUIZ') return route('quiz.show', id);
            if (type === 'TASK') return route('task.show', id);
            return route('module.show', id); 
        }
        
        // Fallback: Jika baru enroll, cari modul pertama yang terbuka
        if (props.modules && props.modules.length > 0) {
            return route('module.show', props.modules[0].id);
        }
    
        return '#';
    });
    
    // Helper Format Durasi
    const formatDuration = (seconds) => {
        if (!seconds) return '-';
        if (seconds < 60) return `${seconds} dtk`;
        if (seconds < 3600) return `${Math.round(seconds / 60)} mnt`;
        return `${(seconds / 3600).toFixed(1)} jam`;
    };
    
    // HELPER: Ikon & Warna berdasarkan Tipe Konten
    const getTypeDetails = (type) => {
        switch(type) {
            case 'VIDEO': return {
                icon: 'M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z M21 12a9 9 0 11-18 0 9 9 0 0118 0z',
                class: 'bg-red-50 text-red-600 border-red-100',
                label: 'Video'
            };
            case 'QUIZ': return {
                icon: 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2',
                class: 'bg-purple-50 text-purple-600 border-purple-100',
                label: 'Kuis'
            };
            case 'TASK': return {
                icon: 'M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12',
                class: 'bg-orange-50 text-orange-600 border-orange-100',
                label: 'Tugas'
            };
            default: return { 
                icon: 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z',
                class: 'bg-blue-50 text-blue-600 border-blue-100',
                label: 'Bacaan'
            };
        }
    };
    </script>
    
    <template>
        <Head :title="course.title" />
    
        <AppLayout>
            <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-8 pb-24 md:pb-8"> <Link :href="route('my-courses')" class="inline-flex items-center text-sm font-medium text-gray-500 hover:text-blue-600 transition">
                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" /></svg>
                    Kembali ke Daftar Kursus
                </Link>
    
                <div class="bg-white rounded-3xl shadow-sm border border-gray-100 overflow-hidden relative">
                
                    <div class="absolute top-0 right-0 -mt-10 -mr-10 w-64 h-64 bg-blue-50 rounded-full blur-3xl opacity-50 pointer-events-none"></div>
                    <div class="absolute bottom-0 left-0 -mb-10 -ml-10 w-40 h-40 bg-yellow-50 rounded-full blur-2xl opacity-50 pointer-events-none"></div>
    
                    <div class="p-8 sm:p-10 relative z-10">
                        <div class="flex flex-col md:flex-row md:items-start md:justify-between gap-6">
                            
                            <div class="flex-1 min-w-0">
                                <div class="mb-4">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-blue-50 text-blue-700 border border-blue-100 mb-3 uppercase tracking-wide">
                                        {{ course.category || 'Umum' }}
                                    </span>
                                    
                                    <h1 class="text-3xl sm:text-4xl font-extrabold text-gray-900 leading-tight mb-3">
                                        {{ course.title }}
                                    </h1>
                                    
                                    <div class="flex items-center flex-wrap gap-4 text-sm text-gray-500 font-medium">
                                        <span class="flex items-center gap-1.5 bg-gray-50 px-3 py-1 rounded-full border border-gray-100">
                                            <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" /></svg>
                                            {{ course.mentor?.name || 'Tim HR' }}
                                        </span>
                                        <span class="flex items-center gap-1.5 bg-gray-50 px-3 py-1 rounded-full border border-gray-100">
                                            <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" /></svg>
                                            {{ modules?.length || 0 }} Materi
                                        </span>
                                    </div>
                                </div>
    
                                <p class="text-gray-600 text-base leading-relaxed max-w-3xl">
                                    {{ course.description || 'Deskripsi kursus belum tersedia.' }}
                                </p>
                            </div>
    
                            <div class="w-full md:w-80 flex-shrink-0 bg-gray-50 rounded-2xl p-5 border border-gray-100">
                                
                                <div v-if="isEnrolled">
                                    <div class="flex justify-between items-end mb-2">
                                        <span class="text-xs font-bold text-gray-400 uppercase tracking-wider">Progress</span>
                                        <span class="text-lg font-black" :class="isCompleted ? 'text-green-600' : 'text-blue-600'">{{ progressPercent }}%</span>
                                    </div>
                                    
                                    <div class="w-full bg-gray-200 rounded-full h-2.5 mb-5 overflow-hidden">
                                        <div class="h-full rounded-full transition-all duration-1000 ease-out" 
                                             :class="isCompleted ? 'bg-green-500' : 'bg-blue-600'"
                                             :style="{ width: progressPercent + '%' }"></div>
                                    </div>
    
                                    <div class="flex gap-4">
                                        <Link 
                                            :href="resumeLink" 
                                            class="flex-1 inline-flex items-center justify-center px-6 py-4 text-white font-bold rounded-xl transition shadow-lg transform active:scale-[0.98] group"
                                            :class="isCompleted 
                                                ? 'bg-green-600 hover:bg-green-700 shadow-green-200' 
                                                : 'bg-blue-600 hover:bg-blue-700 shadow-blue-200'"
                                        >
                                            <div class="flex flex-col items-start">
                                                <span class="text-xs font-normal opacity-90 uppercase tracking-wide">
                                                    {{ isCompleted ? 'Selesai' : 'Lanjut Training' }}
                                                </span>
                                            </div>
                                            <div class="ml-auto bg-white/20 p-1.5 rounded-lg group-hover:bg-white/30 transition">
                                                <svg v-if="isCompleted" class="w-5 h-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                                                <svg v-else class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" /></svg>
                                            </div>
                                        </Link>
                                    </div>
    
                                    <div class="mt-5 pt-4 border-t border-gray-200">
                                        <div class="flex items-center gap-3">
                                            <div class="flex-shrink-0 w-12 h-12 rounded-full flex items-center justify-center border-2 transition-colors duration-500"
                                                :class="isCompleted 
                                                    ? 'bg-yellow-100 border-yellow-200 text-yellow-600' 
                                                    : 'bg-white border-dashed border-gray-300 text-gray-400'"
                                            >
                                                <svg v-if="isCompleted" class="w-6 h-6 animate-bounce" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z" /></svg>
                                                <svg v-else class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v13m0-13V6a2 2 0 112 2h-2zm0 0V5.5A2.5 2.5 0 109.5 8H12zm-7 4h14M5 12a2 2 0 110-4h14a2 2 0 110 4M5 12v7a2 2 0 002 2h10a2 2 0 002-2v-7" /></svg>
                                            </div>

                                            <div class="flex-1">
                                                <p class="text-[10px] font-bold uppercase tracking-wider mb-0.5 text-gray-400">
                                                    Hadiah Penyelesaian
                                                </p>
                                                <div class="flex flex-col">
                                                    <span class="text-xs font-bold text-gray-700 leading-tight">Bonus Kursus</span>
                                                    <span class="text-sm font-black" :class="isCompleted ? 'text-yellow-600' : 'text-gray-500'">
                                                        +{{ gamification.bonus_points }} XP
                                                    </span>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="mt-3 text-[10px] bg-gray-50 py-2 px-3 rounded-lg border border-gray-100">
                                            <div class="flex justify-between items-center mb-1">
                                                <span class="text-gray-500">Poin Modul:</span>
                                                <span class="font-bold text-gray-700">
                                                    {{ gamification.earned_module_points }} XP
                                                </span>
                                            </div>
                                            
                                            <div class="flex justify-between items-center pt-1 border-t border-gray-200">
                                                <span class="text-gray-500">Total Akumulasi:</span>
                                                <span class="font-bold text-blue-600">
                                                    {{ gamification.earned_total_points }} / {{ gamification.max_total_points }}
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
    
                                <div v-else>
                                    <div class="text-center mb-4">
                                        <span class="block text-sm text-gray-500 mb-1">Status Anda</span>
                                        <span class="inline-block px-3 py-1 bg-gray-200 text-gray-600 text-xs font-bold rounded-full">Belum Terdaftar</span>
                                    </div>
    
                                    <button 
                                        @click="joinCourse" 
                                        :disabled="isJoining"
                                        class="w-full inline-flex items-center justify-center px-4 py-3 bg-gray-900 text-white font-bold rounded-xl hover:bg-black transition shadow-lg hover:shadow-xl active:scale-[0.98] disabled:opacity-70"
                                    >
                                        <span v-if="isJoining" class="flex items-center text-sm">
                                            <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                            Proses...
                                        </span>
                                        <span v-else class="flex items-center text-sm">
                                            Mulai Training
                                        </span>
                                    </button>
                                    <p class="text-[10px] text-gray-400 text-center mt-3">* Akses Penuh & Gratis</p>
                                </div>
                            </div>
    
                        </div>
                    </div>
                </div>
    
                <div class="max-w-4xl mx-auto">
                    <div class="flex items-center justify-between mb-6">
                        <h2 class="text-xl font-bold text-gray-900">Materi Training</h2>
                        <span class="px-3 py-1 bg-gray-100 text-gray-600 rounded-full text-xs font-bold">
                            {{ modules?.length || 0 }} Modul
                        </span>
                    </div>
    
                    <div class="space-y-3">
                        <div v-if="!modules || modules.length === 0" class="p-12 text-center text-gray-500 bg-gray-50 rounded-2xl border border-dashed border-gray-200">
                            <div class="mb-2 text-2xl">📭</div>
                            Belum ada materi yang diunggah.
                        </div>
    
                        <div 
                            v-else
                            v-for="(module, index) in modules" 
                            :key="module.id" 
                            @click="openModule(module)"
                            class="group relative bg-white border border-gray-100 rounded-xl p-4 transition-all duration-200"
                            :class="[
                                module.status !== 'locked'
                                    ? 'hover:border-blue-300 hover:shadow-md cursor-pointer' 
                                    : 'opacity-70 bg-gray-50/50 cursor-not-allowed',
                                module.status === 'completed' ? 'border-l-4 border-l-green-500' : ''
                            ]"
                        >
                            <div class="flex items-start gap-4">
                                <div class="flex-shrink-0 mt-1">
                                    <div v-if="module.status === 'completed'" class="w-10 h-10 rounded-full bg-green-100 text-green-600 flex items-center justify-center">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" /></svg>
                                    </div>
                                    <div v-else-if="module.status === 'locked'" class="w-10 h-10 rounded-full bg-gray-200 text-gray-400 flex items-center justify-center">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" /></svg>
                                    </div>
                                    <div v-else class="w-10 h-10 rounded-full bg-white border-2 border-blue-100 text-blue-600 flex items-center justify-center font-bold text-sm shadow-sm group-hover:border-blue-500 group-hover:bg-blue-600 group-hover:text-white transition-all">
                                        {{ index + 1 }}
                                    </div>
                                </div>
    
                                <div class="flex-1 min-w-0">
                                    <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-2">
                                        <div>
                                            <h3 class="text-base font-bold text-gray-900 group-hover:text-blue-700 transition-colors mb-2 leading-snug">
                                                {{ module.title }}
                                            </h3>
                                            <div class="flex flex-wrap items-center gap-3 text-xs text-gray-500">
                                                <span class="flex items-center gap-1.5 px-2.5 py-1 rounded-md border text-[10px] font-bold uppercase tracking-wide" :class="getTypeDetails(module.type).class">
                                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="getTypeDetails(module.type).icon"></path>
                                                    </svg>
                                                    {{ getTypeDetails(module.type).label }}
                                                </span>
                                                <span class="flex items-center gap-1 text-gray-400">
                                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                                    {{ formatDuration(module.duration) }}
                                                </span>
                                            </div>
                                        </div>
                                        <div class="flex-shrink-0 mt-2 sm:mt-0">
                                            <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-lg border text-xs font-bold transition-colors"
                                                :class="{
                                                    'bg-green-50 text-green-700 border-green-100': module.status === 'completed',
                                                    'bg-yellow-50 text-yellow-700 border-yellow-100': module.status !== 'completed' && module.status !== 'locked',
                                                    'bg-gray-50 text-gray-400 border-gray-100': module.status === 'locked'
                                                }"
                                            >
                                                <svg v-if="module.status === 'completed'" class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                                                <svg v-else class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" /></svg>
                                                <span>{{ module.points }} XP</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
    
                                <div v-if="module.status !== 'locked'" class="hidden sm:block text-gray-300 self-center group-hover:text-blue-500 group-hover:translate-x-1 transition-all">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" /></svg>
                                </div>
    
                            </div>
                        </div>
                    </div>
                </div>
    
                <div class="fixed bottom-0 left-0 w-full bg-white border-t border-gray-200 p-4 shadow-[0_-4px_6px_-1px_rgba(0,0,0,0.05)] z-50 md:hidden">
                    <div class="flex items-center justify-between gap-4">
                        <div>
                            <p class="text-[10px] text-gray-500 font-bold uppercase tracking-wider">Total Materi</p>
                            <p class="font-bold text-gray-900">{{ modules?.length || 0 }} Modul</p>
                        </div>
    
                        <div class="flex-1 max-w-[200px]">
                            <Link 
                                v-if="isEnrolled" 
                                :href="resumeLink"
                                class="flex items-center justify-center w-full px-4 py-2.5 text-white font-bold rounded-xl shadow-lg active:scale-95 transition"
                                :class="isCompleted ? 'bg-green-600 hover:bg-green-700' : 'bg-blue-600 hover:bg-blue-700'"
                            >
                                {{ isCompleted ? 'Selesai / Ulas' : 'Lanjut' }}
                            </Link>
    
                            <button 
                                v-else 
                                @click="joinCourse"
                                :disabled="isJoining"
                                class="flex items-center justify-center w-full px-4 py-2.5 bg-gray-900 text-white font-bold rounded-xl shadow-lg hover:bg-black active:scale-95 transition disabled:opacity-70"
                            >
                                <span v-if="isJoining">...</span>
                                <span v-else>Mulai</span>
                            </button>
                        </div>
                    </div>
                </div>
    
            </div>
        </AppLayout>
    </template>