<script setup>
import { ref, watch } from 'vue';
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import debounce from 'lodash/debounce';

const props = defineProps({
    submissions: Object,
    filters: Object,
    mentors: Array,
    is_admin: Boolean,
});

// --- STATE FILTER ---
const search = ref(props.filters.search || '');
const statusFilter = ref(props.filters.status || '');
const mentorFilter = ref(props.filters.mentor_id || '');

// --- STATE SORTING ---
const sortField = ref(props.filters.sort || 'submission_date');
const sortDirection = ref(props.filters.direction || 'desc');

const sort = (field) => {
    if (sortField.value === field) {
        sortDirection.value = sortDirection.value === 'asc' ? 'desc' : 'asc';
    } else {
        sortField.value = field;
        sortDirection.value = 'asc';
    }
};

// Watcher: Reload halaman saat filter/sort berubah
watch([search, statusFilter, mentorFilter, sortField, sortDirection], debounce(() => {
    router.get(route('admin.submissions.index'), { 
        search: search.value,
        status: statusFilter.value,
        mentor_id: mentorFilter.value,
        sort: sortField.value,
        direction: sortDirection.value
    }, { preserveState: true, replace: true });
}, 300));

// --- STATE MODAL GRADING ---
const isGradeModalOpen = ref(false);
const selectedSubmission = ref(null);

const form = useForm({
    score_mentor: '',
    feedback_mentor: '',
});

// --- ACTIONS ---
const openGradeModal = (submission) => {
    selectedSubmission.value = submission;
    form.score_mentor = submission.score || '';
    form.feedback_mentor = submission.feedback || '';
    form.clearErrors();
    isGradeModalOpen.value = true;
};

const closeGradeModal = () => {
    isGradeModalOpen.value = false;
    selectedSubmission.value = null;
    form.reset();
};

const submitGrade = () => {
    if (!selectedSubmission.value) return;
    form.put(route('admin.submissions.grade', selectedSubmission.value.id), {
        onSuccess: () => closeGradeModal(),
    });
};

const getInitials = (name) => name.split(' ').map(n => n[0]).join('').substring(0, 2).toUpperCase();
</script>

<template>
    <Head title="Daftar Tugas" />

    <AppLayout>
        <div class="max-w-7xl mx-auto space-y-6">
            
            <div>
                <h1 class="text-2xl sm:text-3xl font-bold text-gray-900">Daftar Tugas</h1>
                <p class="text-sm text-gray-500 mt-1">Meninjau dan menilai tugas peserta yang masuk.</p>
            </div>

            <div class="flex flex-col md:flex-row gap-4 bg-white p-4 rounded-xl border border-gray-200 shadow-sm">
                <div class="relative flex-1">
                    <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-400">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                    </span>
                    <input 
                        v-model="search" 
                        type="text" 
                        placeholder="Cari nama peserta atau tugas..." 
                        class="w-full pl-10 pr-4 py-2 bg-gray-50 border border-gray-300 rounded-lg text-sm focus:ring-blue-500 focus:border-blue-500 transition"
                    >
                </div>
                
                <div v-if="is_admin" class="w-full md:w-48">
                    <select v-model="mentorFilter" class="w-full bg-gray-50 border border-gray-300 text-gray-700 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 p-2">
                        <option value="">Semua Mentor</option>
                        <option v-for="m in mentors" :key="m.id" :value="m.id">{{ m.name }}</option>
                    </select>
                </div>

                <div class="w-full md:w-40">
                    <select 
                        v-model="statusFilter" 
                        class="w-full bg-gray-50 border border-gray-300 text-gray-700 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 p-2"
                    >
                        <option value="">Semua Status</option>
                        <option value="pending">Menunggu</option>
                        <option value="graded">Selesai</option>
                    </select>
                </div>
            </div>

            <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase whitespace-nowrap cursor-pointer hover:bg-gray-100 transition group" @click="sort('student_name')">
                                    <div class="flex items-center gap-1">
                                        Peserta
                                        <span class="flex flex-col text-[8px] leading-none text-gray-400">
                                            <i :class="sortField === 'student_name' && sortDirection === 'asc' ? 'text-gray-800' : ''">▲</i>
                                            <i :class="sortField === 'student_name' && sortDirection === 'desc' ? 'text-gray-800' : ''">▼</i>
                                        </span>
                                    </div>
                                </th>

                                <th scope="col" class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase whitespace-nowrap cursor-pointer hover:bg-gray-100 transition group" @click="sort('task_title')">
                                    <div class="flex items-center gap-1">
                                        Detail Tugas
                                        <span class="flex flex-col text-[8px] leading-none text-gray-400">
                                            <i :class="sortField === 'task_title' && sortDirection === 'asc' ? 'text-gray-800' : ''">▲</i>
                                            <i :class="sortField === 'task_title' && sortDirection === 'desc' ? 'text-gray-800' : ''">▼</i>
                                        </span>
                                    </div>
                                </th>

                                <th scope="col" class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase whitespace-nowrap cursor-pointer hover:bg-gray-100 transition group" @click="sort('submission_date')">
                                    <div class="flex items-center gap-1">
                                        Dikirim
                                        <span class="flex flex-col text-[8px] leading-none text-gray-400">
                                            <i :class="sortField === 'submission_date' && sortDirection === 'asc' ? 'text-gray-800' : ''">▲</i>
                                            <i :class="sortField === 'submission_date' && sortDirection === 'desc' ? 'text-gray-800' : ''">▼</i>
                                        </span>
                                    </div>
                                </th>

                                <th scope="col" class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase whitespace-nowrap cursor-pointer hover:bg-gray-100 transition group" @click="sort('status')">
                                    <div class="flex items-center gap-1">
                                        Status
                                        <span class="flex flex-col text-[8px] leading-none text-gray-400">
                                            <i :class="sortField === 'status' && sortDirection === 'asc' ? 'text-gray-800' : ''">▲</i>
                                            <i :class="sortField === 'status' && sortDirection === 'desc' ? 'text-gray-800' : ''">▼</i>
                                        </span>
                                    </div>
                                </th>

                                <th class="px-6 py-3 text-right text-xs font-bold text-gray-500 uppercase whitespace-nowrap">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 bg-white">
                            <tr v-for="sub in submissions.data" :key="sub.id" class="hover:bg-gray-50 transition">
                                
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center gap-3">
                                        <div class="h-9 w-9 rounded-full bg-blue-100 flex items-center justify-center text-xs font-bold text-blue-700 border border-blue-200 overflow-hidden shrink-0">
                                            <img v-if="sub.student_avatar" :src="'/storage/'+sub.student_avatar" class="w-full h-full object-cover">
                                            <span v-else>{{ getInitials(sub.student_name) }}</span>
                                        </div>
                                        <div>
                                            <div class="font-medium text-sm text-gray-900">{{ sub.student_name }}</div>
                                            <div v-if="is_admin" class="text-[10px] text-gray-400">Mentor: {{ sub.mentor_name }}</div>
                                        </div>
                                    </div>
                                </td>

                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-bold text-gray-800 max-w-[200px] truncate" :title="sub.task_title">{{ sub.task_title }}</div>
                                    <div class="text-xs text-gray-500 max-w-[200px] truncate">{{ sub.course_title }}</div>
                                </td>

                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    {{ sub.submitted_at }}
                                </td>

                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 py-1 rounded-full text-xs font-bold border"
                                        :class="sub.status === 'Graded' 
                                            ? 'bg-green-100 text-green-700 border-green-200' 
                                            : 'bg-yellow-100 text-yellow-700 border-yellow-200'">
                                        {{ sub.status === 'Graded' ? 'Selesai' : 'Menunggu' }}
                                    </span>
                                    <span v-if="sub.status === 'Graded'" class="ml-2 text-sm font-bold text-gray-900">
                                        {{ sub.score }}/100
                                    </span>
                                </td>

                                <td class="px-6 py-4 whitespace-nowrap text-right">
                                    <button 
                                        @click="openGradeModal(sub)" 
                                        class="text-sm font-bold px-3 py-1.5 rounded-lg transition shadow-sm flex items-center gap-1 ml-auto"
                                        :class="sub.status === 'Graded' 
                                            ? 'bg-gray-100 text-gray-600 hover:bg-gray-200' 
                                            : 'bg-blue-900 text-white hover:bg-blue-800'"
                                    >
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>
                                        {{ sub.status === 'Graded' ? 'Ubah Nilai' : 'Tinjau' }}
                                    </button>
                                </td>
                            </tr>

                            <tr v-if="submissions.data.length === 0">
                                <td colspan="5" class="px-6 py-10 text-center text-gray-400 italic">
                                    Data tidak ditemukan.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="px-6 py-4 border-t border-gray-200 flex flex-col sm:flex-row justify-between items-center gap-4 bg-gray-50/50">
                    
                    <div class="text-sm text-gray-500">
                        Menampilkan <span class="font-bold text-gray-900">{{ submissions.from || 0 }}</span> sampai <span class="font-bold text-gray-900">{{ submissions.to || 0 }}</span> dari <span class="font-bold text-gray-900">{{ submissions.total }}</span> tugas
                    </div>

                    <div class="flex items-center gap-1 overflow-x-auto" v-if="submissions.links.length > 3">
                        <Link v-for="(link, k) in submissions.links" :key="k" :href="link.url" v-html="link.label"
                            class="px-3 py-1 border rounded text-sm whitespace-nowrap transition"
                            :class="[
                                link.active 
                                    ? 'bg-blue-900 text-white border-blue-900' 
                                    : 'text-gray-600 bg-white border-gray-300 hover:bg-gray-50',
                                !link.url ? 'opacity-50 cursor-not-allowed' : ''
                            ]" 
                        />
                    </div>
                </div>
            </div>
        </div>

        <Teleport to="body">
            <div v-if="isGradeModalOpen" class="fixed inset-0 z-[1000] flex items-center justify-center px-4">
                <div class="fixed inset-0 bg-gray-900/75 backdrop-blur-sm transition-opacity" @click="closeGradeModal"></div>
                <div class="bg-white rounded-xl shadow-2xl w-full max-w-lg p-0 relative z-10 overflow-hidden flex flex-col max-h-[90vh] animate-fade-in-up">
                    
                    <div class="px-6 py-4 border-b border-gray-100 flex justify-between items-center bg-gray-50">
                        <h3 class="font-bold text-gray-900 text-lg">Tinjauan Tugas</h3>
                        <button @click="closeGradeModal" class="text-gray-400 hover:text-gray-600"><svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg></button>
                    </div>

                    <div class="p-6 flex-1 overflow-y-auto">
                        <div class="flex items-center gap-4 mb-6 p-4 bg-blue-50 rounded-lg border border-blue-100">
                            <div class="h-12 w-12 rounded-full bg-blue-200 flex items-center justify-center text-blue-800 font-bold text-lg shrink-0 overflow-hidden border-2 border-white">
                                <img v-if="selectedSubmission?.student_avatar" :src="'/storage/'+selectedSubmission.student_avatar" class="w-full h-full object-cover">
                                <span v-else>{{ getInitials(selectedSubmission?.student_name || '') }}</span>
                            </div>
                            <div class="flex-1 min-w-0">
                                <h4 class="font-bold text-gray-900 truncate">{{ selectedSubmission?.student_name }}</h4>
                                <p class="text-xs text-gray-500 truncate">{{ selectedSubmission?.task_title }}</p>
                            </div>
                            <div class="ml-auto text-right">
                                <a :href="selectedSubmission?.file_url" target="_blank" class="text-sm text-blue-600 hover:underline font-bold flex items-center gap-1 bg-white px-3 py-1.5 rounded-lg border border-blue-200 shadow-sm transition hover:bg-blue-50">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                                    Unduh
                                </a>
                            </div>
                        </div>

                        <form @submit.prevent="submitGrade" class="space-y-5">
                            <div>
                                <label class="block text-sm font-bold text-gray-700 mb-1">Skor (0-100)</label>
                                <input v-model="form.score_mentor" type="number" min="0" max="100" class="w-full rounded-lg border-gray-300 focus:ring-blue-500 focus:border-blue-500 font-mono text-lg" placeholder="Contoh: 85">
                                <p v-if="form.errors.score_mentor" class="text-red-500 text-xs mt-1">{{ form.errors.score_mentor }}</p>
                            </div>

                            <div>
                                <label class="block text-sm font-bold text-gray-700 mb-1">Umpan Balik</label>
                                <textarea v-model="form.feedback_mentor" rows="4" class="w-full rounded-lg border-gray-300 focus:ring-blue-500 focus:border-blue-500" placeholder="Berikan saran atau komentar untuk peserta..."></textarea>
                                <p v-if="form.errors.feedback_mentor" class="text-red-500 text-xs mt-1">{{ form.errors.feedback_mentor }}</p>
                            </div>

                            <div class="flex justify-end gap-3 pt-4 border-t border-gray-100">
                                <button type="button" @click="closeGradeModal" class="px-4 py-2 bg-white border rounded-lg text-sm text-gray-700 hover:bg-gray-50 transition">Batal</button>
                                <button type="submit" class="px-6 py-2 bg-green-600 text-white font-bold rounded-lg text-sm hover:bg-green-700 shadow-sm flex items-center gap-2 transition" :disabled="form.processing">
                                    <svg v-if="form.processing" class="animate-spin h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                    {{ form.processing ? 'Menyimpan...' : 'Kirim Penilaian' }}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </Teleport>

    </AppLayout>
</template>

<style scoped>
.animate-fade-in-up { animation: fadeInUp 0.3s ease-out forwards; }
@keyframes fadeInUp { from { opacity: 0; transform: scale(0.95) translateY(10px); } to { opacity: 1; transform: scale(1) translateY(0); } }
</style>