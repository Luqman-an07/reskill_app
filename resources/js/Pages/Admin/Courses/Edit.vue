<script setup>
import { ref, computed, watch } from 'vue';
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import draggable from 'vuedraggable';

const props = defineProps({
    course: Object,
    modules: Array,
    departments: Array, // Data departemen dari controller
    isMentorView: Boolean,
});

// --- STATE ---
const activeTab = ref('all'); // 'all', 'content', 'quizzes', 'tasks'

// --- LOCAL STATE FOR DRAGGABLE ---
const localModules = ref([...props.modules]);

watch(() => props.modules, (newVal) => {
    localModules.value = [...newVal];
});

// --- FILTER MODULES ---
const contentModules = computed(() => localModules.value.filter(m => ['TEXT', 'VIDEO', 'PDF', 'PPT'].includes(m.content_type)));
const quizModules = computed(() => localModules.value.filter(m => m.content_type === 'QUIZ'));
const taskModules = computed(() => localModules.value.filter(m => m.content_type === 'TASK'));

const currentModules = computed(() => {
    if (activeTab.value === 'all') return localModules.value;
    if (activeTab.value === 'content') return contentModules.value;
    if (activeTab.value === 'quizzes') return quizModules.value;
    if (activeTab.value === 'tasks') return taskModules.value;
    return [];
});

// --- FORM EDIT COURSE ---
const formCourse = useForm({
    title: props.course.title,
    description: props.course.description,
    
    // [UPDATED] Menggunakan department_id
    // Default null jika General
    department_id: props.course.department_id, 
    
    is_published: Boolean(props.course.is_published),
    completion_points: props.course.completion_points || 0,
});

const saveChanges = () => {
    const routeName = props.isMentorView ? 'mentor.courses.update' : 'admin.courses.update';
    formCourse.put(route(routeName, props.course.id), {
        preserveScroll: true,
    });
};

// --- DRAG AND DROP ---
const onDragEnd = () => {
    const orderedIds = localModules.value.map(m => m.id);
    router.put(route('admin.courses.reorder', props.course.id), { modules: orderedIds }, {
        preserveScroll: true,
        onError: () => alert("Gagal menyimpan urutan modul.")
    });
};

// --- MODAL STATE ---
const isModalOpen = ref(false);
const isEditContentMode = ref(false);
const editingModuleId = ref(null);

// Form Content
const formContent = useForm({
    type: 'TEXT', title: '', completion_points: 50, content_url: '',
    pdf_file: null, ppt_file: null, video_file: null, required_time: 5,
    duration_minutes: 10, passing_score: 70, max_attempts: 3,
    attachments: [],
    description: '', max_score: 100, 
    deadline_type: 'none', due_date: '', duration_days: '',
});

// Helper: Attachment
const handleAttachmentUpload = (event) => {
    const newFiles = Array.from(event.target.files);
    formContent.attachments = [...(formContent.attachments || []), ...newFiles];
    event.target.value = null; 
};
const removeAttachment = (index) => formContent.attachments.splice(index, 1);

// Open Modal ADD
const openAddModal = () => {
    isEditContentMode.value = false;
    formContent.reset(); formContent.clearErrors(); formContent.attachments = [];
    formContent.deadline_type = 'none';
    formContent.due_date = '';
    formContent.duration_days = '';

    if (activeTab.value === 'quizzes') formContent.type = 'QUIZ';
    else if (activeTab.value === 'tasks') formContent.type = 'TASK';
    else formContent.type = 'TEXT';
    isModalOpen.value = true;
};

// Open Modal EDIT
const editModule = (module) => {
    isEditContentMode.value = true;
    editingModuleId.value = module.id;
    formContent.reset(); formContent.clearErrors(); formContent.attachments = [];
    
    formContent.type = module.content_type;
    formContent.title = module.module_title;
    formContent.completion_points = module.completion_points;
    formContent.required_time = Math.ceil(module.required_time / 60);
    formContent.content_url = module.content_url;

    if (module.content_type === 'QUIZ' && module.quiz) {
        formContent.duration_minutes = module.quiz.duration_minutes;
        formContent.passing_score = module.quiz.passing_score;
        formContent.max_attempts = module.quiz.max_attempts;
    } 
    else if (module.content_type === 'TASK' && module.task) {
        formContent.description = module.task.description;
        formContent.max_score = module.task.max_score;
        
        if (module.task.due_date) {
            formContent.deadline_type = 'fixed';
            formContent.due_date = new Date(module.task.due_date).toISOString().slice(0, 16);
            formContent.duration_days = '';
        } else if (module.task.duration_days) {
            formContent.deadline_type = 'relative';
            formContent.duration_days = module.task.duration_days;
            formContent.due_date = '';
        } else {
            formContent.deadline_type = 'none';
            formContent.due_date = '';
            formContent.duration_days = '';
        }
    }
    isModalOpen.value = true;
};

const submitContent = () => {
    if (isEditContentMode.value) {
        formContent.transform((data) => ({ ...data, _method: 'PUT' }))
            .post(route('admin.modules.update', editingModuleId.value), { onSuccess: () => isModalOpen.value = false });
    } else {
        formContent.post(route('admin.courses.content.store', props.course.id), { onSuccess: () => isModalOpen.value = false });
    }
};

// --- DELETE MODAL ---
const isDeleteModalOpen = ref(false);
const moduleToDelete = ref(null);
const deleteProcessing = ref(false);

const openDeleteModal = (module) => { moduleToDelete.value = module; isDeleteModalOpen.value = true; };
const confirmDeleteModule = () => {
    if (!moduleToDelete.value) return;
    deleteProcessing.value = true;
    router.delete(route('admin.modules.destroy', moduleToDelete.value.id), {
        onSuccess: () => { isDeleteModalOpen.value = false; moduleToDelete.value = null; },
        onFinish: () => deleteProcessing.value = false
    });
};

const getDuration = (seconds) => Math.ceil(seconds / 60);
</script>

<template>
    <Head :title="'Kelola: ' + course.title" />

    <AppLayout>
        <div class="max-w-5xl mx-auto space-y-6 sm:space-y-8 px-2 sm:px-0">
            
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                <div>
                    <Link 
                        :href="isMentorView ? route('mentor.courses.index') : route('admin.courses.index')" 
                        class="text-sm text-gray-500 hover:text-gray-700 flex items-center gap-1 mb-2 transition"
                    >
                        <span>←</span> Kembali ke {{ isMentorView ? 'Kursus Saya' : 'Daftar Kursus' }}
                    </Link>
                    <h1 class="text-2xl sm:text-3xl font-bold text-gray-900">Kelola Detail Kursus</h1>
                </div>
                <button @click="saveChanges" class="w-full sm:w-auto bg-blue-900 text-white px-6 py-3 rounded-xl text-sm font-bold hover:bg-blue-800 shadow-lg shadow-blue-900/20 flex items-center justify-center gap-2 transition transform active:scale-95" :disabled="formCourse.processing">
                    <svg v-if="formCourse.processing" class="animate-spin h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                    Simpan Perubahan
                </button>
            </div>

            <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5 sm:p-8">
                <h3 class="text-lg font-bold text-gray-900 mb-6 border-b border-gray-100 pb-4">Informasi Kursus</h3>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Nama Kursus</label>
                        <input v-model="formCourse.title" type="text" class="w-full rounded-lg border-gray-300 text-sm focus:ring-blue-500 focus:border-blue-500">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Sasaran Peserta</label>
                        <select v-model="formCourse.department_id" class="w-full rounded-lg border-gray-300 text-sm focus:ring-blue-500 focus:border-blue-500">
                            <option :value="null">Umum / Semua Departemen (General)</option>
                            <option disabled>──────────────</option>
                            <option v-for="dept in departments" :key="dept.id" :value="dept.id">
                                {{ dept.department_name }}
                            </option>
                        </select>
                        <p class="text-xs text-gray-500 mt-1">
                            <span v-if="!formCourse.department_id" class="text-blue-600 font-bold">Wajib untuk semua karyawan (Prioritas 1).</span>
                            <span v-else>Hanya untuk departemen yang dipilih.</span>
                        </p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Bonus Poin (XP)</label>
                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-yellow-500 font-bold">⚡</span>
                            <input v-model="formCourse.completion_points" type="number" min="0" class="w-full pl-8 rounded-lg border-gray-300 text-sm focus:ring-blue-500 focus:border-blue-500" placeholder="e.g. 500">
                        </div>
                        <p class="text-xs text-gray-500 mt-1">Diberikan setelah menyelesaikan semua modul.</p>
                    </div>
                </div>

                <div class="mb-6">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Deskripsi</label>
                    <textarea v-model="formCourse.description" rows="3" class="w-full rounded-lg border-gray-300 text-sm focus:ring-blue-500 focus:border-blue-500"></textarea>
                </div>

                <div class="flex items-center gap-3 bg-blue-50 p-4 rounded-lg border border-blue-100 cursor-pointer" @click="formCourse.is_published = !formCourse.is_published">
                    <input v-model="formCourse.is_published" type="checkbox" id="publish" class="rounded text-blue-900 w-5 h-5 cursor-pointer focus:ring-blue-900 pointer-events-none">
                    <div class="flex-1">
                        <label for="publish" class="text-sm font-bold text-blue-900 cursor-pointer pointer-events-none">Publikasikan Kursus Ini</label>
                        <p class="text-xs text-blue-700">Jika dicentang, kursus akan terlihat oleh semua peserta.</p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
                
                <div class="border-b border-gray-200 bg-white">
                    <div class="px-6 flex flex-col sm:flex-row justify-between items-center">
                        
                        <nav class="-mb-px flex space-x-6 overflow-x-auto no-scrollbar w-full sm:w-auto" aria-label="Tabs">
                            <button @click="activeTab = 'all'" :class="[activeTab === 'all' ? 'border-blue-900 text-blue-900' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300', 'whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm transition-colors']">
                                Struktur (Semua)
                            </button>
                            <button @click="activeTab = 'content'" :class="[activeTab === 'content' ? 'border-blue-600 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300', 'whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm transition-colors flex items-center gap-2']">
                                Konten <span :class="activeTab === 'content' ? 'bg-blue-100 text-blue-600' : 'bg-gray-100 text-gray-600'" class="hidden sm:inline-flex py-0.5 px-2 rounded-full text-xs">{{ contentModules.length }}</span>
                            </button>
                            <button @click="activeTab = 'quizzes'" :class="[activeTab === 'quizzes' ? 'border-purple-600 text-purple-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300', 'whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm transition-colors flex items-center gap-2']">
                                Kuis <span :class="activeTab === 'quizzes' ? 'bg-purple-100 text-purple-600' : 'bg-gray-100 text-gray-600'" class="hidden sm:inline-flex py-0.5 px-2 rounded-full text-xs">{{ quizModules.length }}</span>
                            </button>
                            <button @click="activeTab = 'tasks'" :class="[activeTab === 'tasks' ? 'border-orange-600 text-orange-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300', 'whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm transition-colors flex items-center gap-2']">
                                Tugas <span :class="activeTab === 'tasks' ? 'bg-orange-100 text-orange-600' : 'bg-gray-100 text-gray-600'" class="hidden sm:inline-flex py-0.5 px-2 rounded-full text-xs">{{ taskModules.length }}</span>
                            </button>
                        </nav>

                        <div class="py-3 sm:py-0 w-full sm:w-auto">
                            <button @click="openAddModal" class="w-full sm:w-auto bg-gray-50 text-gray-700 border border-gray-300 px-4 py-2 rounded-lg text-xs font-bold hover:bg-white hover:text-blue-600 hover:border-blue-500 flex items-center justify-center gap-2 shadow-sm transition active:scale-95">
                                <span class="text-lg leading-none font-black">+</span> Tambah Konten
                            </button>
                        </div>

                    </div>
                </div>

                <div class="p-4 sm:p-6 bg-gray-50 min-h-[300px]">
                    <draggable v-if="activeTab === 'all' && localModules.length > 0" v-model="localModules" item-key="id" handle=".cursor-move" @end="onDragEnd" class="space-y-3" animation="200">
                        <template #item="{ element: module, index }">
                            <div class="group flex flex-col sm:flex-row sm:items-center justify-between p-4 bg-white rounded-xl border border-gray-200 hover:border-blue-300 hover:shadow-sm transition cursor-default select-none gap-3 sm:gap-0">
                                <div class="flex items-center gap-3 sm:gap-4 overflow-hidden">
                                    <div class="text-gray-300 cursor-move hover:text-gray-600 p-2 shrink-0 touch-manipulation"><svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8h16M4 16h16"></path></svg></div>
                                    <div class="text-gray-400 font-mono text-xs w-5 text-center font-bold shrink-0">{{ index + 1 }}</div>
                                    <div class="w-10 h-10 rounded-lg flex items-center justify-center text-lg border shrink-0" :class="{'bg-blue-50 text-blue-600 border-blue-100': ['TEXT','PDF','PPT','VIDEO'].includes(module.content_type), 'bg-purple-50 text-purple-600 border-purple-100': module.content_type === 'QUIZ', 'bg-orange-50 text-orange-600 border-orange-100': module.content_type === 'TASK'}">
                                        <span v-if="module.content_type === 'VIDEO'">🎥</span>
                                        <span v-else-if="module.content_type === 'QUIZ'">❓</span>
                                        <span v-else-if="module.content_type === 'TASK'">⚡</span>
                                        <span v-else-if="module.content_type === 'PPT'">📊</span>
                                        <span v-else>📄</span>
                                    </div>
                                    <div class="min-w-0">
                                        <h4 class="text-sm font-bold text-gray-900 truncate pr-2">{{ module.module_title }}</h4>
                                        <div class="flex gap-2 sm:gap-3 mt-1 text-xs text-gray-500 items-center">
                                            <span class="font-semibold bg-gray-100 px-1.5 rounded">{{ module.content_type }}</span><span>•</span><span>{{ getDuration(module.required_time) }} mnt</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="flex gap-2 sm:opacity-0 group-hover:opacity-100 transition-opacity pl-0 sm:pl-2 w-full sm:w-auto justify-end border-t sm:border-0 pt-2 sm:pt-0 border-gray-100 mt-1 sm:mt-0">
                                    <Link v-if="module.content_type === 'QUIZ' && module.quiz" :href="route('admin.quizzes.builder', module.quiz.id)" class="text-xs bg-purple-100 text-purple-700 px-3 py-1.5 rounded-md font-bold hover:bg-purple-200 transition flex-1 sm:flex-none text-center flex items-center justify-center">Kelola Kuis</Link>
                                    <button @click="editModule(module)" class="p-2 text-gray-400 hover:text-blue-600 bg-gray-50 hover:bg-blue-50 rounded-lg border border-transparent hover:border-blue-200 transition flex-1 sm:flex-none" title="Edit">✎</button>
                                    <button @click="openDeleteModal(module)" class="p-2 text-red-400 hover:text-red-600 bg-gray-50 hover:bg-red-50 rounded-lg border border-transparent hover:border-red-200 transition flex-1 sm:flex-none" title="Delete">🗑</button>
                                </div>
                            </div>
                        </template>
                    </draggable>

                    <div v-else-if="currentModules.length > 0" class="space-y-3">
                        <div v-for="module in currentModules" :key="module.id" class="flex flex-col sm:flex-row sm:items-center justify-between p-4 bg-white rounded-xl border border-gray-200 hover:border-blue-300 shadow-sm transition group gap-3 sm:gap-0">
                             <div class="flex items-center gap-4 overflow-hidden">
                                <div class="w-10 h-10 rounded-lg flex items-center justify-center text-lg border shrink-0 bg-gray-50">
                                    <span v-if="module.content_type === 'VIDEO'">🎥</span>
                                    <span v-else-if="module.content_type === 'QUIZ'">❓</span>
                                    <span v-else-if="module.content_type === 'TASK'">⚡</span>
                                    <span v-else-if="module.content_type === 'PPT'">📊</span>
                                    <span v-else>📄</span>
                                </div>
                                <div class="min-w-0">
                                    <h4 class="text-sm font-bold text-gray-900 truncate">{{ module.module_title }}</h4>
                                    <div class="flex gap-2 sm:gap-3 mt-1 text-xs text-gray-500">
                                        <span class="font-semibold">{{ module.content_type }}</span><span>•</span><span>{{ getDuration(module.required_time) }} mnt</span>
                                    </div>
                                </div>
                             </div>
                             <div class="flex gap-2 w-full sm:w-auto justify-end border-t sm:border-0 pt-2 sm:pt-0 border-gray-100">
                                <button @click="editModule(module)" class="p-2 text-gray-400 hover:text-blue-600 bg-gray-50 hover:bg-blue-50 rounded-lg transition flex-1 sm:flex-none">✎</button>
                                <button @click="openDeleteModal(module)" class="p-2 text-red-400 hover:text-red-600 bg-gray-50 hover:bg-red-50 rounded-lg transition flex-1 sm:flex-none">🗑</button>
                            </div>
                        </div>
                    </div>

                    <div v-else class="text-center py-12 border-2 border-dashed border-gray-300 rounded-xl text-gray-400">
                        Tidak ada modul ditemukan. Klik "Tambah Konten" untuk memulai.
                    </div>
                </div>
            </div>
        </div>

        <Teleport to="body">
            <div v-if="isModalOpen" class="fixed inset-0 z-[9999] flex items-center justify-center px-4">
                <div class="fixed inset-0 bg-gray-900/75 backdrop-blur-sm transition-opacity" @click="isModalOpen = false"></div>
                <div class="bg-white rounded-xl shadow-2xl w-full max-w-lg p-6 relative z-10 max-h-[90vh] overflow-y-auto animate-fade-in-up">
                    <h3 class="text-xl font-bold text-gray-900 mb-6">{{ isEditContentMode ? 'Ubah Konten' : 'Tambah Konten Baru' }}</h3>
                    
                    <form @submit.prevent="submitContent" class="space-y-5">
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Jenis Konten</label>
                            <select v-model="formContent.type" class="w-full rounded-lg border-gray-300 text-sm" :disabled="isEditContentMode">
                                <option value="TEXT">Teks / Artikel</option>
                                <option value="VIDEO">Video (YouTube/MP4)</option>
                                <option value="PDF">PDF Dokumen</option>
                                <option value="PPT">Slide Presentasi (PPT/PPTX)</option>
                                <option value="QUIZ">Kuis (Penilaian)</option>
                                <option value="TASK">Tugas (Pengiriman Berkas)</option>
                            </select>
                        </div>
                        
                        <div><label class="block text-sm font-medium text-gray-700 mb-1">Judul Konten</label><input v-model="formContent.title" type="text" class="w-full rounded-lg border-gray-300 text-sm" required placeholder="Contoh: Pengenalan Dasar"></div>
                        <div><label class="block text-sm font-medium text-gray-700 mb-1">Poin Hadiah (XP)</label><input v-model="formContent.completion_points" type="number" class="w-full rounded-lg border-gray-300 text-sm"></div>

                        <div v-if="['TEXT', 'VIDEO', 'PDF', 'PPT'].includes(formContent.type)" class="space-y-4 border-t pt-4">
                            <div v-if="formContent.type === 'TEXT'">
                                <label class="block text-sm font-medium text-gray-700 mb-1">Isi Teks / Link Artikel</label>
                                <textarea v-model="formContent.content_url" rows="4" class="w-full rounded-lg border-gray-300 text-sm" placeholder="Tulis konten atau tempel URL..."></textarea>
                            </div>
                            <div v-else-if="formContent.type === 'VIDEO'">
                                <label class="block text-sm font-medium text-gray-700 mb-1">Sumber Video</label>
                                <div class="space-y-3">
                                    <input v-model="formContent.content_url" type="text" class="w-full rounded-lg border-gray-300 text-sm" placeholder="URL YouTube (https://...)">
                                    <div class="text-center text-xs text-gray-400 font-bold">- ATAU -</div>
                                    <input type="file" accept="video/mp4,video/*" @input="formContent.video_file = $event.target.files[0]" class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100" />
                                </div>
                            </div>
                            <div v-else-if="formContent.type === 'PDF'"><label class="block text-sm font-medium text-gray-700 mb-1">Unggah PDF</label><input type="file" accept="application/pdf" @input="formContent.pdf_file = $event.target.files[0]" class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:bg-blue-50 file:text-blue-700" /></div>
                            <div v-else-if="formContent.type === 'PPT'"><label class="block text-sm font-medium text-gray-700 mb-1">Unggah PPT</label><input type="file" accept=".ppt,.pptx,.doc,.docx" @input="formContent.ppt_file = $event.target.files[0]" class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:bg-orange-50 file:text-orange-700" /></div>
                            
                            <div><label class="block text-sm font-medium text-gray-700 mb-1">Estimasi Waktu (Menit)</label><input v-model="formContent.required_time" type="number" class="w-full rounded-lg border-gray-300 text-sm"></div>
                            
                            <div class="border-t pt-4 border-gray-100">
                                <label class="block text-sm font-medium text-gray-700 mb-2">Lampiran Tambahan (PDF/PPT)</label>
                                <div class="border-2 border-dashed border-gray-300 rounded-lg p-4 text-center hover:bg-gray-50 transition relative cursor-pointer">
                                    <input type="file" multiple @change="handleAttachmentUpload" class="absolute inset-0 w-full h-full opacity-0" accept=".pdf,.ppt,.pptx,.doc,.docx">
                                    <div class="text-gray-500 text-xs">Klik untuk memilih file</div>
                                </div>
                                <div v-if="formContent.attachments && formContent.attachments.length > 0" class="mt-2 space-y-2">
                                    <div v-for="(file, index) in formContent.attachments" :key="index" class="flex items-center justify-between bg-blue-50 p-2 rounded-lg border border-blue-100 text-xs">
                                        <span class="truncate max-w-[80%]">{{ file.name }}</span>
                                        <button type="button" @click="removeAttachment(index)" class="text-red-500 hover:text-red-700">✕</button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div v-if="formContent.type === 'QUIZ'" class="space-y-4 border-t pt-4 bg-purple-50 p-4 rounded-lg">
                            <div class="grid grid-cols-2 gap-4">
                                <div><label class="block text-xs font-bold text-purple-900 mb-1">Durasi (Mnt)</label><input v-model="formContent.duration_minutes" type="number" class="w-full border-purple-200 rounded-lg text-sm"></div>
                                <div><label class="block text-xs font-bold text-purple-900 mb-1">Nilai Lulus</label><input v-model="formContent.passing_score" type="number" class="w-full border-purple-200 rounded-lg text-sm"></div>
                            </div>
                            <div><label class="block text-xs font-bold text-purple-900 mb-1">Batas Percobaan</label><input v-model="formContent.max_attempts" type="number" class="w-full border-purple-200 rounded-lg text-sm"></div>
                        </div>

                        <div v-if="formContent.type === 'TASK'" class="space-y-4 border-t pt-4 bg-orange-50 p-4 rounded-lg">
                            <div><label class="block text-xs font-bold text-orange-900 mb-1">Instruksi Tugas</label><textarea v-model="formContent.description" rows="3" class="w-full border-orange-200 rounded-lg text-sm"></textarea></div>
                            
                            <div><label class="block text-xs font-bold text-orange-900 mb-1">Skor Maksimal</label><input v-model="formContent.max_score" type="number" class="w-full border-orange-200 rounded-lg text-sm"></div>
                            
                            <div>
                                <label class="block text-xs font-bold text-orange-900 mb-2">Jenis Tenggat Waktu (Deadline)</label>
                                <div class="flex flex-col sm:flex-row gap-3">
                                    <label class="flex items-center gap-2 cursor-pointer bg-white px-3 py-2 rounded border border-orange-100 shadow-sm hover:border-orange-300">
                                        <input type="radio" v-model="formContent.deadline_type" value="none" class="text-orange-600 focus:ring-orange-500">
                                        <span class="text-xs font-bold text-gray-700">Tanpa Batas</span>
                                    </label>
                                    <label class="flex items-center gap-2 cursor-pointer bg-white px-3 py-2 rounded border border-orange-100 shadow-sm hover:border-orange-300">
                                        <input type="radio" v-model="formContent.deadline_type" value="fixed" class="text-orange-600 focus:ring-orange-500">
                                        <div class="flex flex-col">
                                            <span class="text-xs font-bold text-gray-700">Tanggal Pasti</span>
                                            <span class="text-[10px] text-gray-400">Fixed Date</span>
                                        </div>
                                    </label>
                                    <label class="flex items-center gap-2 cursor-pointer bg-white px-3 py-2 rounded border border-orange-100 shadow-sm hover:border-orange-300">
                                        <input type="radio" v-model="formContent.deadline_type" value="relative" class="text-orange-600 focus:ring-orange-500">
                                        <div class="flex flex-col">
                                            <span class="text-xs font-bold text-gray-700">Durasi (Relatif)</span>
                                            <span class="text-[10px] text-gray-400">Sejak Enroll</span>
                                        </div>
                                    </label>
                                </div>
                            </div>

                            <div v-if="formContent.deadline_type === 'fixed'" class="animate-fade-in-up">
                                <label class="block text-xs font-bold text-orange-900 mb-1">Pilih Tanggal Jatuh Tempo</label>
                                <input v-model="formContent.due_date" type="datetime-local" class="w-full border-orange-200 rounded-lg text-sm">
                            </div>

                            <div v-if="formContent.deadline_type === 'relative'" class="animate-fade-in-up">
                                <label class="block text-xs font-bold text-orange-900 mb-1">Durasi Pengerjaan (Hari)</label>
                                <div class="flex items-center gap-2">
                                    <input v-model="formContent.duration_days" type="number" min="1" placeholder="7" class="w-24 border-orange-200 rounded-lg text-sm">
                                    <span class="text-xs text-gray-500">Hari setelah peserta mulai.</span>
                                </div>
                            </div>

                        </div>

                        <div class="flex justify-end gap-3 mt-6 border-t pt-4">
                            <button type="button" @click="isModalOpen = false" class="px-4 py-2 bg-white border rounded-lg text-sm text-gray-700">Batal</button>
                            <button type="submit" class="px-4 py-2 bg-blue-900 text-white rounded-lg text-sm hover:bg-blue-800 disabled:opacity-50" :disabled="formContent.processing">
                                <span v-if="formContent.processing">Menyimpan...</span>
                                <span v-else>{{ isEditContentMode ? 'Perbarui Konten' : 'Tambah Konten' }}</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </Teleport>

        <Teleport to="body">
            <div v-if="isDeleteModalOpen" class="fixed inset-0 z-[10000] flex items-center justify-center px-4">
                <div class="fixed inset-0 bg-gray-900/75 backdrop-blur-sm transition-opacity" @click="isDeleteModalOpen = false"></div>
                <div class="bg-white rounded-xl shadow-2xl w-full max-w-sm p-6 relative z-10 text-center animate-bounce-in">
                    <div class="mx-auto flex items-center justify-center h-14 w-14 rounded-full bg-red-100 mb-4">
                        <svg class="h-8 w-8 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" /></svg>
                    </div>
                    <h3 class="text-lg font-bold text-gray-900 mb-2">Hapus Konten?</h3>
                    <p class="text-sm text-gray-500 mb-6">Apakah Anda yakin ingin menghapus <strong>"{{ moduleToDelete?.module_title }}"</strong>?<br><span class="text-xs text-red-500 mt-1 block">Semua data terkait akan hilang permanen.</span></p>
                    <div class="flex justify-center gap-3">
                        <button @click="isDeleteModalOpen = false" class="px-4 py-2 bg-white border rounded-lg text-sm hover:bg-gray-50" :disabled="deleteProcessing">Batal</button>
                        <button @click="confirmDeleteModule" class="px-4 py-2 bg-red-600 text-white rounded-lg text-sm hover:bg-red-700 shadow-sm" :disabled="deleteProcessing">{{ deleteProcessing ? 'Menghapus...' : 'Ya, Hapus' }}</button>
                    </div>
                </div>
            </div>
        </Teleport>

    </AppLayout>
</template>

<style scoped>
.no-scrollbar::-webkit-scrollbar { display: none; }
.no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
.animate-fade-in-up { animation: fadeInUp 0.3s ease-out forwards; }
@keyframes fadeInUp { from { opacity: 0; transform: scale(0.95) translateY(10px); } to { opacity: 1; transform: scale(1) translateY(0); } }
</style>