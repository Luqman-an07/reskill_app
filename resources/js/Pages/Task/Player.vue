<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref, computed } from 'vue'; // Import computed

const props = defineProps({
    task: Object,       // Harus ada field 'deadline'
    submission: Object, // Harus ada field 'is_late', 'status', 'submitted_at'
});

// --- STATE UPLOAD ---
const form = useForm({
    file: null,
    notes: '',
});

const isDragging = ref(false);

// --- LOGIKA DEADLINE ---

// 1. Helper Format Tanggal
const formatDate = (dateString) => {
    if (!dateString) return 'Tanpa Batas Waktu';
    return new Date(dateString).toLocaleDateString('id-ID', {
        weekday: 'long', year: 'numeric', month: 'short', day: 'numeric',
        hour: '2-digit', minute: '2-digit'
    });
};

// 2. Cek Realtime: Apakah user telat (sebelum upload)?
const isOverdue = computed(() => {
    // Jika tidak ada deadline atau sudah submit, abaikan warning realtime
    if (!props.task.deadline || props.submission) return false;
    
    // Cek: Sekarang > Deadline?
    return new Date() > new Date(props.task.deadline);
});

// Handle File Drop/Select
const handleFile = (event) => {
    const file = event.target.files ? event.target.files[0] : event.dataTransfer.files[0];
    if (file) {
        form.file = file;
    }
};

const submitTask = () => {
    form.post(route('task.submit', props.task.id), {
        preserveScroll: true,
        onSuccess: () => {
            // Reset handled by Inertia reload
        }
    });
};
</script>

<template>
    <Head :title="task.title" />

    <div class="min-h-screen bg-gray-50 font-sans py-8 px-4">
        
        <div class="max-w-3xl mx-auto">
            
            <div class="flex items-center justify-between mb-6">
                <Link :href="route('course.show', task.course_id)" class="text-gray-600 hover:text-blue-600 font-medium flex items-center gap-1 sm:gap-2 transition group text-sm sm:text-base">
                    <span class="group-hover:-translate-x-1 transition-transform">←</span> 
                    <span class="hidden sm:inline">Kembali ke Kursus</span>
                    <span class="sm:hidden">Kembali</span>
                </Link>
            </div>

            <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
                
                <div class="p-8 border-b border-gray-100 flex justify-between items-start">
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900 mb-1">{{ task.title }}</h1>
                        <p class="text-sm text-gray-500">{{ task.module_title }}</p>
                    </div>
                    <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full bg-gray-100 text-gray-600 text-xs font-bold border border-gray-200">
                        🏆 {{ task.points }} poin
                    </span>
                </div>

                <div class="p-8">

                    <div v-if="!submission">
                        
                        <div class="flex flex-wrap items-center gap-3 mb-6">
                            <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-lg border text-sm font-medium"
                                :class="isOverdue ? 'bg-red-50 text-red-600 border-red-100' : 'bg-gray-50 text-gray-600 border-gray-100'">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                <span>Batas: <strong>{{ formatDate(task.deadline) }}</strong></span>
                            </div>

                            <div v-if="isOverdue" class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg bg-red-100 text-red-700 text-sm font-bold animate-pulse border border-red-200">
                                ⚠️ Waktu Habis (Akan tercatat Terlambat)
                            </div>
                        </div>
                        
                        <div class="bg-emerald-50 border border-emerald-100 rounded-xl p-6 mb-8">
                            <h3 class="text-emerald-800 font-bold text-sm mb-2">Instruksi Tugas</h3>
                            <div class="text-emerald-700 text-sm leading-relaxed prose-sm" v-html="task.description"></div>
                        </div>

                        <form @submit.prevent="submitTask">
                            
                            <div class="mb-6">
                                <label class="block text-sm font-medium text-gray-700 mb-2">Deskripsi / Catatan (Opsional)</label>
                                <textarea 
                                    v-model="form.notes"
                                    rows="4" 
                                    class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm"
                                    placeholder="Jelaskan pendekatan Anda atau tambahkan catatan untuk mentor..."
                                ></textarea>
                            </div>

                            <div class="mb-8">
                                <label class="block text-sm font-medium text-gray-700 mb-2">Unggah File</label>
                                
                                <div 
                                    class="border-2 border-dashed rounded-xl p-10 text-center transition-colors cursor-pointer relative"
                                    :class="isDragging ? 'border-blue-500 bg-blue-50' : 'border-gray-300 hover:border-gray-400 hover:bg-gray-50'"
                                    @dragover.prevent="isDragging = true"
                                    @dragleave.prevent="isDragging = false"
                                    @drop.prevent="isDragging = false; handleFile($event)"
                                >
                                    <input type="file" @change="handleFile" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer" />
                                    
                                    <div v-if="!form.file">
                                        <svg class="w-10 h-10 text-gray-400 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" /></svg>
                                        <p class="text-sm text-gray-600 font-medium">Klik untuk mengunggah atau seret dan lepas</p>
                                        <p class="text-xs text-gray-400 mt-1">PDF, ZIP, DOCX, PPTX (Max 10MB)</p>
                                    </div>

                                    <div v-else class="flex items-center justify-center gap-3 text-blue-600">
                                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                                        <div class="text-left">
                                            <p class="text-sm font-bold">{{ form.file.name }}</p>
                                            <p class="text-xs text-gray-500">{{ (form.file.size / 1024).toFixed(1) }} KB</p>
                                        </div>
                                    </div>
                                </div>
                                <p v-if="form.errors.file" class="mt-2 text-sm text-red-600">{{ form.errors.file }}</p>
                            </div>

                            <div class="flex items-center justify-end gap-3 pt-6 border-t border-gray-100">
                                <Link :href="route('course.show', task.course_id)" class="px-5 py-2.5 rounded-lg text-gray-600 font-medium hover:bg-gray-100 border border-gray-200 transition">
                                    Batal
                                </Link>
                                <button 
                                    type="submit" 
                                    :disabled="form.processing || !form.file"
                                    class="px-6 py-2.5 rounded-lg text-white font-bold shadow-md flex items-center gap-2"
                                    :class="isOverdue ? 'bg-orange-600 hover:bg-orange-700' : 'bg-blue-900 hover:bg-blue-800 disabled:opacity-50 disabled:cursor-not-allowed'"
                                >
                                    <svg v-if="form.processing" class="animate-spin h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                    {{ isOverdue ? 'Kirim Terlambat' : 'Kirim Tugas' }}
                                </button>
                            </div>

                        </form>
                    </div>

                    <div v-else class="text-center py-10">
                        
                        <div v-if="submission.status === 'Graded'" class="w-20 h-20 bg-green-100 text-green-600 rounded-full flex items-center justify-center mx-auto mb-6">
                             <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                        </div>
                        <div v-else class="w-20 h-20 bg-yellow-50 text-yellow-600 rounded-full flex items-center justify-center mx-auto mb-6 animate-pulse">
                             <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                        </div>

                        <h2 class="text-2xl font-bold text-gray-900 mb-2">
                            {{ submission.status === 'Graded' ? 'Tugas Dinilai' : 'Pengajuan Diterima' }}
                        </h2>
                        <p class="text-gray-500 max-w-md mx-auto mb-8">
                            {{ submission.status === 'Graded' 
                                ? 'Mentor telah meninjau pengajuan Anda. Periksa skor dan umpan balik di bawah ini.' 
                                : 'Tugas berhasil dikirimkan dan sedang menunggu tinjauan dari mentor.' }}
                        </p>

                        <div class="bg-gray-50 rounded-xl p-6 max-w-lg mx-auto text-left border border-gray-200">
                            
                            <div class="grid grid-cols-2 gap-4 mb-4">
                                <div>
                                    <p class="text-xs text-gray-400 uppercase font-bold">Waktu Kirim</p>
                                    <p class="text-sm font-medium text-gray-800">{{ formatDate(submission.submitted_at || submission.submission_date) }}</p>
                                </div>
                                <div>
                                    <p class="text-xs text-gray-400 uppercase font-bold">Ketepatan</p>
                                    
                                    <span v-if="submission.is_late" class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-xs font-bold bg-orange-100 text-orange-700 border border-orange-200">
                                        ⌛ Terlambat
                                    </span>
                                    <span v-else class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-xs font-bold bg-green-100 text-green-700 border border-green-200">
                                        ✅ Tepat Waktu
                                    </span>
                                </div>
                            </div>

                            <div class="mb-4 pt-4 border-t border-gray-200">
                                <p class="text-xs text-gray-400 uppercase font-bold mb-1">File Anda</p>
                                <a :href="`/storage/${submission.submission_file_url || submission.file_path}`" target="_blank" class="text-sm font-bold text-blue-600 hover:underline flex items-center gap-1">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                                    Unduh Dokumen
                                </a>
                            </div>
                            
                            <div v-if="submission.status === 'Graded' || submission.is_graded" class="border-t border-gray-200 pt-4 mt-4">
                                <p class="text-xs text-gray-400 uppercase font-bold mb-2">Catatan Mentor</p>
                                <div class="bg-white p-4 rounded-lg border border-gray-200 text-sm text-gray-600 italic">
                                    "{{ submission.feedback_mentor || submission.feedback || 'Tidak ada catatan.' }}"
                                </div>
                                <div class="mt-4 text-center">
                                    <p class="text-sm text-gray-500">Skor Akhir</p>
                                    <p class="text-4xl font-black text-blue-900">
                                        {{ submission.score_mentor || submission.score }}<span class="text-lg text-gray-400 font-normal">/100</span>
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div class="mt-8">
                            <Link :href="route('course.show', task.course_id)" class="text-blue-600 hover:text-blue-800 font-medium">
                                ← Kembali Ke Kursus
                            </Link>
                        </div>
                    </div>

                </div>
            </div>

        </div>
    </div>
</template>