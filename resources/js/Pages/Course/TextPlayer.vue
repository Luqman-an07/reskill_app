<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, onMounted, onUnmounted, computed } from 'vue';
import axios from 'axios';

const props = defineProps({
    module: Object,          // Data Modul + Attachments
    course: Object,          // Data Kursus (untuk tombol Back)
    currentProgress: Object, // Data Progress User (time_spent, last_position)
});

// --- STATE MANAGEMENT ---
const timeSpent = ref(props.currentProgress?.time_spent || 0); // Detik berjalan
const isSaving = ref(false);
let timerInterval = null;
let saveInterval = null;

// Helper: Cek apakah string adalah URL
const isUrl = (string) => {
    if (!string) return false;
    try {
        new URL(string);
        return true;
    } catch (_) {
        return false;
    }
};

// --- COMPUTED LOGIC ---
// Hitung sisa waktu yang wajib dibaca/ditonton
const remainingTime = computed(() => {
    const required = props.module.required_time || 0;
    return Math.max(0, required - timeSpent.value);
});

// Tombol selesai hanya aktif jika waktu habis
const canComplete = computed(() => remainingTime.value === 0);

// --- HELPER: VIDEO URL PARSER ---
const getYoutubeEmbedUrl = (url) => {
    if (!url) return '';
    let videoId = '';
    
    // Pola 1: youtube.com/watch?v=ID
    if (url.includes('youtube.com/watch?v=')) {
        videoId = url.split('v=')[1];
        const ampersandPosition = videoId.indexOf('&');
        if (ampersandPosition !== -1) {
            videoId = videoId.substring(0, ampersandPosition);
        }
    } 
    // Pola 2: youtu.be/ID
    else if (url.includes('youtu.be/')) {
        videoId = url.split('youtu.be/')[1];
    }
    
    return `https://www.youtube.com/embed/${videoId}`;
};

// --- BACKEND SYNC (HEARTBEAT) ---
const syncProgress = async () => {
    isSaving.value = true;
    try {
        await axios.post(route('module.progress', props.module.id), {
            time_spent: timeSpent.value,
            last_position: window.scrollY,
        });
    } catch (e) {
        console.error("Gagal menyimpan progress:", e);
    } finally {
        isSaving.value = false;
    }
};

// --- LIFECYCLE HOOKS ---
onMounted(() => {
    if (props.currentProgress?.last_position > 0) {
        setTimeout(() => {
            window.scrollTo({
                top: props.currentProgress.last_position,
                behavior: 'smooth'
            });
        }, 500);
    }

    timerInterval = setInterval(() => {
        timeSpent.value++;
    }, 1000);

    saveInterval = setInterval(syncProgress, 10000);
});

onUnmounted(() => {
    clearInterval(timerInterval);
    clearInterval(saveInterval);
    syncProgress(); 
});

// --- ACTION: MARK COMPLETE ---
const markAsComplete = () => {
    if (!canComplete.value) return;

    router.post(route('module.complete', props.module.id), {}, {
        onSuccess: () => {
            router.visit(route('course.show', props.course.id));
        }
    });
};
</script>

<template>
    <Head :title="module.module_title" />

    <div class="min-h-screen bg-gray-100 font-sans py-4 px-4 sm:py-8 sm:px-6 lg:px-8 pb-40">
        
        <div class="max-w-5xl mx-auto mb-6 flex items-center justify-between sticky top-0 z-50 bg-gray-100/95 backdrop-blur py-3 border-b border-gray-200/50 sm:border-none">
            <Link :href="route('course.show', course.id)" class="text-gray-600 hover:text-blue-600 font-medium flex items-center gap-1 sm:gap-2 transition group text-sm sm:text-base">
                <span class="group-hover:-translate-x-1 transition-transform">←</span> 
                <span class="hidden sm:inline">Kembali ke Kursus</span>
                <span class="sm:hidden">Kembali</span>
            </Link>
            
            <div class="flex items-center gap-2 text-xs text-gray-400 bg-white/50 px-2 py-1 rounded-full sm:bg-transparent sm:p-0">
                <span v-if="isSaving" class="flex items-center gap-1">
                    <svg class="animate-spin h-3 w-3 text-blue-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                    <span class="hidden sm:inline">Menyimpan...</span>
                </span>
                <span v-else class="flex items-center gap-1 text-green-600">
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                    <span class="hidden sm:inline">Tersimpan</span>
                </span>
            </div>
        </div>

        <div class="max-w-5xl mx-auto bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
            
            <div class="p-5 md:p-10">
                
                <div class="mb-6 md:mb-8 pb-6 border-b border-gray-100">
                    <h1 class="text-xl md:text-3xl font-bold text-gray-900 mb-3 leading-tight">
                        {{ module.module_title }}
                    </h1>
                    
                    <div class="flex flex-wrap items-center gap-3 md:gap-4 text-xs md:text-sm text-gray-500">
                        <span class="flex items-center gap-1 bg-blue-50 text-blue-700 px-2 py-1 rounded font-bold uppercase tracking-wide">
                            {{ module.content_type }}
                        </span>
                        <span class="flex items-center gap-1 whitespace-nowrap">
                            ⏱️ {{ Math.ceil(module.required_time / 60) }} Mnt
                        </span>
                        <span class="flex items-center gap-1 text-yellow-600 font-bold whitespace-nowrap">
                            🏆 {{ module.completion_points }} XP
                        </span>
                    </div>
                </div>

                <div class="w-full min-h-[300px]">
                    
                    <div v-if="module.content_type === 'PDF' || (module.content_url && module.content_url.endsWith('.pdf'))" class="w-full flex flex-col gap-4">
                        <div class="flex justify-end">
                            <a :href="module.content_url" target="_blank" class="text-sm text-blue-600 hover:text-blue-800 flex items-center gap-1 font-medium transition p-2 bg-blue-50 rounded-lg">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                                Unduh PDF
                            </a>
                        </div>
                        <iframe 
                            :src="module.content_url" 
                            class="w-full h-[60vh] md:h-[80vh] rounded-lg border border-gray-200 bg-gray-50"
                            frameborder="0"
                        ></iframe>
                    </div>

                    <div v-else-if="module.content_type === 'VIDEO'" class="w-full">
                        <div v-if="module.content_url.includes('youtube.com') || module.content_url.includes('youtu.be')" class="aspect-video bg-black rounded-xl overflow-hidden shadow-lg w-full">
                            <iframe 
                                :src="getYoutubeEmbedUrl(module.content_url)" 
                                class="w-full h-full" 
                                frameborder="0" 
                                allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" 
                                allowfullscreen>
                            </iframe>
                        </div>
                        <div v-else class="aspect-video bg-black rounded-xl overflow-hidden shadow-lg flex justify-center items-center bg-gray-900">
                            <video controls class="w-full h-full max-h-[80vh]" controlsList="nodownload">
                                <source :src="module.content_url" type="video/mp4">
                                Browser Anda tidak mendukung tag video.
                            </video>
                        </div>
                    </div>

                    <div v-else-if="module.content_type === 'PPT'" class="bg-gray-50 p-8 md:p-12 rounded-xl border-2 border-dashed border-gray-300 text-center">
                        <div class="mb-4">
                            <span class="text-5xl md:text-6xl">📊</span>
                        </div>
                        <h3 class="text-lg font-bold text-gray-900 mb-2">Materi Presentasi</h3>
                        <p class="text-gray-500 text-sm mb-6 px-4">Materi ini tersedia untuk diunduh. Silakan pelajari slide secara mandiri.</p>
                        
                        <a :href="module.content_url" class="inline-flex items-center gap-2 px-6 py-3 bg-orange-600 text-white font-bold rounded-lg hover:bg-orange-700 transition shadow-md hover:shadow-lg transform hover:-translate-y-0.5 text-sm md:text-base">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                            Unduh Presentasi
                        </a>
                    </div>

                    <div v-else>
                        <div v-if="isUrl(module.content_url)" class="bg-white border border-gray-200 rounded-xl p-6 md:p-8 text-center shadow-sm">
                            <div class="w-16 h-16 bg-blue-50 text-blue-600 rounded-full flex items-center justify-center mx-auto mb-4 text-3xl">🔗</div>
                            <h3 class="text-xl font-bold text-gray-900 mb-2">Sumber Eksternal</h3>
                            <p class="text-gray-500 mb-6 text-sm md:text-base">
                                Materi ini tersedia di situs luar. Baca materi tersebut, lalu kembali ke sini untuk menyelesaikan modul.
                            </p>
                            <a :href="module.content_url" target="_blank" class="inline-flex items-center gap-2 px-6 py-3 bg-blue-600 text-white font-bold rounded-lg hover:bg-blue-700 transition shadow-md">
                                <span>Buka Materi</span>
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" /></svg>
                            </a>
                            <div class="mt-4 text-xs text-gray-400 truncate max-w-[250px] md:max-w-md mx-auto bg-gray-50 p-1 rounded">Sumber: {{ module.content_url }}</div>
                        </div>

                        <div v-else class="prose prose-sm sm:prose-base md:prose-lg prose-blue max-w-none text-gray-700 leading-relaxed overflow-hidden">
                            <div v-html="module.content_url"></div>
                        </div>
                    </div>

                </div>

                <div v-if="module.attachments && module.attachments.length > 0" class="mt-10 pt-8 border-t border-gray-100">
                    <h3 class="text-lg font-bold text-gray-900 mb-4 flex items-center gap-2">
                        <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13" /></svg>
                        Lampiran Tambahan
                    </h3>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <a v-for="file in module.attachments" :key="file.id" :href="file.file_path" target="_blank"
                            class="flex items-center gap-4 p-3 md:p-4 border border-gray-200 rounded-xl hover:border-blue-400 hover:bg-blue-50/30 hover:shadow-sm transition bg-white group">
                            <div class="w-10 h-10 md:w-12 md:h-12 rounded-lg bg-gray-100 flex items-center justify-center text-xl md:text-2xl group-hover:bg-blue-100 transition shrink-0">
                                <span v-if="file.file_type === 'pdf'">📕</span>
                                <span v-else-if="['ppt','pptx'].includes(file.file_type)">📊</span>
                                <span v-else-if="['doc','docx'].includes(file.file_type)">📝</span>
                                <span v-else>📄</span>
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-bold text-gray-900 truncate group-hover:text-blue-700 transition">{{ file.file_name }}</p>
                                <p class="text-xs text-gray-500 uppercase mt-0.5 font-semibold">{{ file.file_type }} File</p>
                            </div>
                            <svg class="w-5 h-5 text-gray-400 group-hover:text-blue-600 transition" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                        </a>
                    </div>
                </div>

            </div>

            <div class="bg-white p-4 border-t border-gray-200 flex flex-col md:flex-row items-center justify-between gap-4 sticky bottom-0 z-40 shadow-[0_-4px_10px_-2px_rgba(0,0,0,0.05)] mt-auto rounded-b-2xl">
                
                <div class="text-sm text-gray-600 w-full md:w-auto bg-gray-50 px-4 py-3 rounded-xl border border-gray-200 flex justify-center md:justify-start order-2 md:order-1">
                    <div v-if="canComplete" class="flex items-center gap-2 text-green-600 font-bold">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                        <span>Target waktu tercapai!</span>
                    </div>
                    <div v-else class="flex items-center gap-2">
                        <svg class="w-5 h-5 text-blue-500 animate-pulse" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                        <span>Baca minimal <span class="font-black text-gray-900">{{ remainingTime }} detik</span> lagi.</span>
                    </div>
                </div>

                <button 
                    @click="markAsComplete"
                    :disabled="!canComplete"
                    class="w-full md:w-auto px-8 py-3.5 rounded-xl font-bold text-white shadow-md transition-all transform active:scale-95 flex items-center justify-center gap-2 text-sm tracking-wide uppercase order-1 md:order-2"
                    :class="canComplete 
                        ? 'bg-blue-900 hover:bg-blue-800 hover:shadow-lg cursor-pointer' 
                        : 'bg-gray-300 text-gray-500 cursor-not-allowed'"
                >
                    <span>Tandai Selesai</span>
                    <svg v-if="canComplete" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6" /></svg>
                </button>
            </div>

        </div>
    </div>
</template>