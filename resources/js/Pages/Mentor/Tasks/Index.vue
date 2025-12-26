<script setup>
    import { ref, watch } from 'vue';
    import { Head, Link, useForm, router } from '@inertiajs/vue3';
    import AppLayout from '@/Layouts/AppLayout.vue';
    import debounce from 'lodash/debounce';
    
    const props = defineProps({
        submissions: Object,
        filters: Object,
        courses: Array,
    });
    
    // --- STATE FILTER & SORT ---
    const search = ref(props.filters.search || '');
    const courseFilter = ref(props.filters.course_id || '');
    const statusFilter = ref(props.filters.status || ''); // 'pending' | 'graded' | ''
    
    const sortField = ref(props.filters.sort || 'submission_date');
    const sortDirection = ref(props.filters.direction || 'asc'); // Default asc (Tugas lama harus dikerjakan duluan)
    
    // Fungsi Sort
    const sort = (field) => {
        if (sortField.value === field) {
            sortDirection.value = sortDirection.value === 'asc' ? 'desc' : 'asc';
        } else {
            sortField.value = field;
            sortDirection.value = 'asc';
        }
    };
    
    // Watcher Global
    watch([search, courseFilter, statusFilter, sortField, sortDirection], debounce(() => {
        router.get(route('mentor.tasks.index'), { 
            search: search.value,
            course_id: courseFilter.value,
            status: statusFilter.value,
            sort: sortField.value,
            direction: sortDirection.value
        }, { preserveState: true, replace: true });
    }, 300));
    
    // --- MODAL GRADING ---
    const isGradeModalOpen = ref(false);
    const selectedSub = ref(null);
    
    const form = useForm({
        score_mentor: '',
        feedback_mentor: '',
    });
    
    const openGradeModal = (submission) => {
        selectedSub.value = submission;
        // Isi form dengan nilai lama jika sudah dinilai (Edit Mode)
        form.score_mentor = submission.score || ''; 
        form.feedback_mentor = submission.feedback || '';
        form.clearErrors();
        isGradeModalOpen.value = true;
    };
    
    const submitGrade = () => {
        if (!selectedSub.value) return;
        form.put(route('mentor.tasks.update', selectedSub.value.id), {
            onSuccess: () => {
                isGradeModalOpen.value = false;
                selectedSub.value = null;
                form.reset();
            },
        });
    };
    
    const getInitials = (name) => {
        if (!name) return '??';
        const parts = name.trim().split(/\s+/);
        if (parts.length === 1) return parts[0].substring(0, 2).toUpperCase();
        return (parts[0][0] + parts[parts.length - 1][0]).toUpperCase();
    };
    </script>
    
    <template>
        <Head title="Tinjauan Tugas" />
    
        <AppLayout>
            <div class="max-w-7xl mx-auto space-y-6">
                
                <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900">Tinjauan Tugas</h1>
                        <p class="text-sm text-gray-500">Kelola dan nilai pengumpulan tugas peserta.</p>
                    </div>
                </div>
    
                <div class="bg-white p-4 rounded-xl border border-gray-200 shadow-sm flex flex-col md:flex-row gap-4">
                    
                    <div class="relative flex-1">
                        <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-400">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                        </span>
                        <input 
                            v-model="search" 
                            type="text" 
                            placeholder="Cari peserta atau tugas..." 
                            class="w-full pl-10 pr-4 py-2 bg-gray-50 border border-gray-300 rounded-lg text-sm focus:ring-blue-500 focus:border-blue-500 transition"
                        >
                    </div>
    
                    <div class="flex gap-2 w-full md:w-auto overflow-x-auto">
                        <select v-model="statusFilter" class="bg-gray-50 border border-gray-300 text-gray-700 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 p-2 min-w-[140px]">
                            <option value="">Semua Status</option>
                            <option value="pending">Perlu Dinilai</option>
                            <option value="graded">Selesai</option>
                        </select>
    
                        <select v-if="courses.length > 0" v-model="courseFilter" class="bg-gray-50 border border-gray-300 text-gray-700 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 p-2 min-w-[160px] max-w-[200px]">
                            <option value="">Semua Kursus</option>
                            <option v-for="c in courses" :key="c.id" :value="c.id">{{ c.title }}</option>
                        </select>
                    </div>
                </div>
    
                <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Peserta</th>
                                    <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Detail Tugas</th>
                                    <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Kursus</th>
                                    
                                    <th @click="sort('is_graded')" class="px-6 py-3 text-center text-xs font-bold text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100 group select-none">
                                        <div class="flex items-center justify-center gap-1">
                                            Status
                                            <span class="flex flex-col text-[8px] leading-none text-gray-400">
                                                <i :class="sortField === 'is_graded' && sortDirection === 'asc' ? 'text-gray-800' : ''">▲</i>
                                                <i :class="sortField === 'is_graded' && sortDirection === 'desc' ? 'text-gray-800' : ''">▼</i>
                                            </span>
                                        </div>
                                    </th>
    
                                    <th @click="sort('submission_date')" class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100 group select-none">
                                        <div class="flex items-center gap-1">
                                            Waktu Submit
                                            <span class="flex flex-col text-[8px] leading-none text-gray-400">
                                                <i :class="sortField === 'submission_date' && sortDirection === 'asc' ? 'text-gray-800' : ''">▲</i>
                                                <i :class="sortField === 'submission_date' && sortDirection === 'desc' ? 'text-gray-800' : ''">▼</i>
                                            </span>
                                        </div>
                                    </th>
                                    <th class="px-6 py-3 text-right text-xs font-bold text-gray-500 uppercase tracking-wider">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 bg-white">
                                <tr v-for="sub in submissions.data" :key="sub.id" class="hover:bg-gray-50 transition">
                                    
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center">
                                            <div class="flex-shrink-0 h-9 w-9">
                                                <img v-if="sub.student_avatar" class="h-9 w-9 rounded-full object-cover border border-gray-200" :src="sub.student_avatar">
                                                <div v-else class="h-9 w-9 rounded-full bg-blue-100 text-blue-600 flex items-center justify-center text-xs font-bold border border-blue-200">
                                                    {{ getInitials(sub.student_name) }}
                                                </div>
                                            </div>
                                            <div class="ml-3">
                                                <div class="text-sm font-medium text-gray-900">{{ sub.student_name }}</div>
                                            </div>
                                        </div>
                                    </td>
    
                                    <td class="px-6 py-4">
                                        <div class="text-sm font-bold text-gray-900 line-clamp-1" :title="sub.task_title">{{ sub.task_title }}</div>
                                        <a v-if="sub.file_url" :href="sub.file_url" target="_blank" class="text-xs text-blue-600 hover:underline flex items-center gap-1 mt-1 font-medium">
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                                            Unduh Berkas
                                        </a>
                                        <span v-else class="text-xs text-gray-400 italic">Tidak ada file</span>
                                    </td>
    
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-600 border border-gray-200">
                                            {{ sub.course_title }}
                                        </span>
                                    </td>
    
                                    <td class="px-6 py-4 whitespace-nowrap text-center">
                                        <span v-if="!sub.is_graded" class="px-2 py-1 inline-flex text-xs leading-5 font-bold rounded-full bg-yellow-100 text-yellow-800 border border-yellow-200">
                                            Menunggu
                                        </span>
                                        <div v-else class="flex flex-col items-center">
                                            <span class="px-2 py-1 inline-flex text-xs leading-5 font-bold rounded-full bg-green-100 text-green-800 border border-green-200">
                                                Selesai
                                            </span>
                                            <span class="text-xs text-gray-500 font-bold mt-1">Skor: {{ sub.score }}</span>
                                        </div>
                                    </td>
    
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-900">{{ sub.submitted_at }}</div>
                                        <div class="text-xs text-gray-400">{{ sub.submitted_date_full }}</div>
                                    </td>
    
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                        <button 
                                            @click="openGradeModal(sub)" 
                                            class="px-4 py-2 rounded-lg text-xs font-bold transition shadow-sm border"
                                            :class="sub.is_graded 
                                                ? 'bg-white text-gray-700 border-gray-300 hover:bg-gray-50' 
                                                : 'bg-blue-900 text-white border-transparent hover:bg-blue-800'"
                                        >
                                            {{ sub.is_graded ? 'Edit Nilai' : 'Beri Nilai' }}
                                        </button>
                                    </td>
                                </tr>
                                
                                <tr v-if="submissions.data.length === 0">
                                    <td colspan="6" class="px-6 py-12 text-center text-gray-500">
                                        <div class="flex flex-col items-center justify-center gap-2">
                                            <span class="text-4xl">📭</span>
                                            <p class="font-medium">Tidak ada tugas yang ditemukan.</p>
                                            <p class="text-xs">Coba ubah filter atau status pencarian Anda.</p>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
    
                    <div class="px-6 py-4 border-t border-gray-200 flex flex-col sm:flex-row justify-between items-center gap-4 bg-gray-50">
                        <div class="text-xs text-gray-500">
                            Menampilkan <span class="font-bold">{{ submissions.from || 0 }}</span> - <span class="font-bold">{{ submissions.to || 0 }}</span> dari total <span class="font-bold">{{ submissions.total }}</span>
                        </div>
                        <div class="flex gap-1" v-if="submissions.links.length > 3">
                            <Link v-for="(link, k) in submissions.links" :key="k" :href="link.url" v-html="link.label"
                                class="px-3 py-1 border rounded text-xs transition min-w-[30px] text-center"
                                :class="[
                                    link.active ? 'bg-blue-900 text-white border-blue-900' : 'bg-white text-gray-600 hover:bg-gray-100',
                                    !link.url ? 'opacity-50 cursor-not-allowed' : ''
                                ]"
                            />
                        </div>
                    </div>
                </div>
            </div>
    
            <div v-if="isGradeModalOpen" class="fixed inset-0 z-[9999] flex items-center justify-center px-4">
                <div class="fixed inset-0 bg-gray-900/75 backdrop-blur-sm transition-opacity" @click="isGradeModalOpen = false"></div>
                
                <div class="bg-white rounded-xl shadow-2xl w-full max-w-lg p-6 relative z-10 animate-fade-in-up max-h-[90vh] overflow-y-auto">
                    <div class="flex justify-between items-start mb-6">
                        <h3 class="text-lg font-bold text-gray-900">
                            {{ selectedSub.is_graded ? 'Edit Penilaian' : 'Penilaian Baru' }}
                        </h3>
                        <button @click="isGradeModalOpen = false" class="text-gray-400 hover:text-gray-600 transition">✕</button>
                    </div>
    
                    <div v-if="selectedSub" class="space-y-6">
                        <div class="bg-blue-50 p-4 rounded-xl border border-blue-100 text-sm space-y-3">
                            <div class="flex items-center gap-3 pb-3 border-b border-blue-100">
                                <div class="w-10 h-10 rounded-full bg-white flex items-center justify-center text-blue-600 font-bold border border-blue-100">
                                    {{ getInitials(selectedSub.student_name) }}
                                </div>
                                <div>
                                    <p class="font-bold text-gray-900">{{ selectedSub.student_name }}</p>
                                    <p class="text-xs text-blue-600">{{ selectedSub.course_title }}</p>
                                </div>
                            </div>
                            
                            <div>
                                <span class="text-xs text-gray-500 uppercase font-bold tracking-wide">Tugas</span>
                                <p class="font-medium text-gray-900">{{ selectedSub.task_title }}</p>
                            </div>
    
                            <div v-if="selectedSub.description" class="bg-white p-3 rounded-lg border border-blue-100">
                                <span class="text-xs text-gray-400 block mb-1">Catatan Peserta:</span>
                                <p class="text-gray-600 italic">"{{ selectedSub.description }}"</p>
                            </div>
    
                            <div class="flex justify-between items-center pt-1">
                                <span class="text-gray-500">Lampiran:</span>
                                <a v-if="selectedSub.file_url" :href="selectedSub.file_url" target="_blank" class="bg-white text-blue-600 px-3 py-1.5 rounded-lg border border-blue-200 text-xs font-bold hover:bg-blue-50 transition flex items-center gap-2">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" /></svg>
                                    Unduh File
                                </a>
                                <span v-else class="text-gray-400 italic text-xs">Tidak ada file</span>
                            </div>
                        </div>
    
                        <form @submit.prevent="submitGrade" class="space-y-5">
                            <div>
                                <label class="block text-sm font-bold text-gray-700 mb-1">Nilai (0-100)</label>
                                <div class="relative">
                                    <input 
                                        v-model="form.score_mentor" 
                                        type="number" 
                                        min="0" 
                                        max="100" 
                                        class="w-full pl-4 pr-12 py-3 rounded-xl border-gray-300 focus:ring-blue-500 focus:border-blue-500 text-lg font-bold"
                                        placeholder="0"
                                        required
                                    >
                                    <span class="absolute right-4 top-3.5 text-gray-400 font-bold text-sm">/100</span>
                                </div>
                                <InputError :message="form.errors.score_mentor" class="mt-1" />
                            </div>
    
                            <div>
                                <label class="block text-sm font-bold text-gray-700 mb-1">Umpan Balik</label>
                                <textarea 
                                    v-model="form.feedback_mentor" 
                                    rows="4" 
                                    class="w-full rounded-xl border-gray-300 focus:ring-blue-500 focus:border-blue-500 text-sm"
                                    placeholder="Berikan saran, apresiasi, atau alasan nilai..."
                                ></textarea>
                                <p class="text-xs text-gray-400 mt-1 text-right">Opsional, tapi sangat membantu peserta.</p>
                                <InputError :message="form.errors.feedback_mentor" class="mt-1" />
                            </div>
    
                            <div class="flex justify-end gap-3 pt-2">
                                <button type="button" @click="isGradeModalOpen = false" class="px-5 py-2.5 bg-gray-50 text-gray-700 rounded-xl font-bold hover:bg-gray-100 transition">Batal</button>
                                <button type="submit" class="px-5 py-2.5 bg-blue-900 text-white rounded-xl font-bold hover:bg-blue-800 shadow-lg shadow-blue-900/20 transition disabled:opacity-50" :disabled="form.processing">
                                    {{ form.processing ? 'Menyimpan...' : 'Simpan Penilaian' }}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </AppLayout>
    </template>
    
    <style scoped>
    .animate-fade-in-up { animation: fadeInUp 0.3s ease-out forwards; }
    @keyframes fadeInUp { from { opacity: 0; transform: scale(0.95) translateY(10px); } to { opacity: 1; transform: scale(1) translateY(0); } }
    </style>