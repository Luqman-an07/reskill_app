<script setup>
    import { ref, watch, computed } from 'vue';
    import { Head, Link, useForm, router } from '@inertiajs/vue3';
    import AppLayout from '@/Layouts/AppLayout.vue';
    import debounce from 'lodash/debounce';
    
    // 1. [UPDATED] Tambahkan props departments
    const props = defineProps({
        courses: Object,
        mentors: Array,
        filters: Object,
        departments: Array, // Data departemen dari DB
    });
    
    // --- STATE FILTER ---
    const search = ref(props.filters.search || '');
    const statusFilter = ref(props.filters.status || '');
    
    // --- STATE SORTING ---
    const sortField = ref(props.filters.sort || 'created_at');
    const sortDirection = ref(props.filters.direction || 'desc');
    
    const sort = (field) => {
        if (sortField.value === field) {
            sortDirection.value = sortDirection.value === 'asc' ? 'desc' : 'asc';
        } else {
            sortField.value = field;
            sortDirection.value = 'asc';
        }
    };
    
    // Filtering Watcher
    watch([search, statusFilter, sortField, sortDirection], debounce(() => {
        router.get(route('admin.courses.index'), { 
            search: search.value, 
            status: statusFilter.value,
            sort: sortField.value,
            direction: sortDirection.value
        }, { preserveState: true, replace: true });
    }, 300));
    
    // --- MODAL CREATE STATE ---
    const isCreateModalOpen = ref(false);
    
    // 2. [UPDATED] Ubah target_role string menjadi department_id
    const form = useForm({
        title: '',
        department_id: null, // Default null untuk General
        mentor_id: '',
        completion_points: 100,
    });
    
    // 3. [UPDATED] Logika Filter Mentor Berdasarkan ID
    const filteredMentors = computed(() => {
        // Jika department_id null (General), tampilkan semua mentor
        if (!form.department_id) {
            return props.mentors;
        }
        
        // Filter mentor yang memiliki department_id sama dengan pilihan kursus
        // Asumsi: Object mentor (User) memiliki kolom department_id
        return props.mentors.filter(mentor => 
            mentor.department_id === form.department_id
        );
    });
    
    // Reset mentor saat departemen berubah
    watch(() => form.department_id, () => {
        form.mentor_id = ''; 
    });
    
    const submitCreate = () => {
        form.post(route('admin.courses.store'), {
            onSuccess: () => {
                isCreateModalOpen.value = false;
                form.reset();
            },
        });
    };
    
    // --- DELETE MODAL STATE ---
    const isDeleteModalOpen = ref(false);
    const courseToDelete = ref(null);
    const isDeleting = ref(false);
    
    const confirmDelete = (course) => {
        courseToDelete.value = course;
        isDeleteModalOpen.value = true;
    };
    
    const deleteCourse = () => {
        if (!courseToDelete.value) return;
        
        isDeleting.value = true;
        router.delete(route('admin.courses.destroy', courseToDelete.value.id), {
            preserveScroll: true,
            onSuccess: () => {
                isDeleteModalOpen.value = false;
                courseToDelete.value = null;
            },
            onFinish: () => isDeleting.value = false,
        });
    };
    </script>
    
    <template>
        <Head title="Kelola Kursus" />
    
        <AppLayout>
            <div class="max-w-7xl mx-auto space-y-6 sm:space-y-8">
                
                <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                    <div>
                        <h1 class="text-2xl sm:text-3xl font-bold text-gray-900">Kelola Kursus</h1>
                        <p class="text-sm text-gray-500 mt-1">Buat dan kelola kurikulum pembelajaran perusahaan.</p>
                    </div>
                    <button @click="isCreateModalOpen = true" class="w-full sm:w-auto bg-blue-900 text-white px-5 py-2.5 rounded-xl text-sm font-bold hover:bg-blue-800 flex items-center justify-center gap-2 shadow-sm transition active:scale-95">
                        <span class="text-lg leading-none">+</span> Tambah Kursus
                    </button>
                </div>
    
                <div class="flex flex-col md:flex-row gap-4 bg-white p-4 rounded-xl border border-gray-200 shadow-sm">
                    <div class="relative flex-1">
                        <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-400">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                        </span>
                        <input 
                            v-model="search" 
                            type="text" 
                            placeholder="Cari berdasarkan nama kursus..." 
                            class="w-full pl-10 pr-4 py-2.5 bg-gray-50 border border-gray-300 rounded-lg text-sm focus:ring-blue-500 focus:border-blue-500 transition"
                        >
                    </div>
    
                    <div class="w-full md:w-48">
                        <select 
                            v-model="statusFilter" 
                            class="w-full bg-gray-50 border border-gray-300 text-gray-700 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 p-2.5"
                        >
                            <option value="">Semua Status</option>
                            <option value="published">Diterbitkan</option>
                            <option value="draft">Draf</option>
                        </select>
                    </div>
                </div>
    
                <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
                    <div class="overflow-x-auto min-h-[300px]">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider whitespace-nowrap cursor-pointer hover:bg-gray-100 transition group" @click="sort('title')">
                                        <div class="flex items-center gap-1">
                                            Nama Kursus
                                            <span class="flex flex-col text-[8px] leading-none text-gray-400">
                                                <i :class="sortField === 'title' && sortDirection === 'asc' ? 'text-gray-800' : ''">▲</i>
                                                <i :class="sortField === 'title' && sortDirection === 'desc' ? 'text-gray-800' : ''">▼</i>
                                            </span>
                                        </div>
                                    </th>
    
                                    <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider whitespace-nowrap cursor-pointer hover:bg-gray-100 transition group" @click="sort('target_role')">
                                        <div class="flex items-center gap-1">
                                            Kategori
                                            <span class="flex flex-col text-[8px] leading-none text-gray-400">
                                                <i :class="sortField === 'target_role' && sortDirection === 'asc' ? 'text-gray-800' : ''">▲</i>
                                                <i :class="sortField === 'target_role' && sortDirection === 'desc' ? 'text-gray-800' : ''">▼</i>
                                            </span>
                                        </div>
                                    </th>
    
                                    <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider whitespace-nowrap cursor-pointer hover:bg-gray-100 transition group" @click="sort('mentor_name')">
                                        <div class="flex items-center gap-1">
                                            Mentor
                                            <span class="flex flex-col text-[8px] leading-none text-gray-400">
                                                <i :class="sortField === 'mentor_name' && sortDirection === 'asc' ? 'text-gray-800' : ''">▲</i>
                                                <i :class="sortField === 'mentor_name' && sortDirection === 'desc' ? 'text-gray-800' : ''">▼</i>
                                            </span>
                                        </div>
                                    </th>
    
                                    <th scope="col" class="px-6 py-4 text-center text-xs font-bold text-gray-500 uppercase tracking-wider whitespace-nowrap cursor-pointer hover:bg-gray-100 transition group" @click="sort('is_published')">
                                        <div class="flex items-center justify-center gap-1">
                                            Status
                                            <span class="flex flex-col text-[8px] leading-none text-gray-400">
                                                <i :class="sortField === 'is_published' && sortDirection === 'asc' ? 'text-gray-800' : ''">▲</i>
                                                <i :class="sortField === 'is_published' && sortDirection === 'desc' ? 'text-gray-800' : ''">▼</i>
                                            </span>
                                        </div>
                                    </th>
    
                                    <th scope="col" class="px-6 py-4 text-center text-xs font-bold text-gray-500 uppercase tracking-wider whitespace-nowrap cursor-pointer hover:bg-gray-100 transition group" @click="sort('students_count')">
                                        <div class="flex items-center justify-center gap-1">
                                            Peserta
                                            <span class="flex flex-col text-[8px] leading-none text-gray-400">
                                                <i :class="sortField === 'students_count' && sortDirection === 'asc' ? 'text-gray-800' : ''">▲</i>
                                                <i :class="sortField === 'students_count' && sortDirection === 'desc' ? 'text-gray-800' : ''">▼</i>
                                            </span>
                                        </div>
                                    </th>
    
                                    <th class="px-6 py-4 text-center text-xs font-bold text-gray-500 uppercase tracking-wider whitespace-nowrap">Penugasan</th>
    
                                    <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider whitespace-nowrap cursor-pointer hover:bg-gray-100 transition group" @click="sort('created_at')">
                                        <div class="flex items-center gap-1">
                                            Dibuat
                                            <span class="flex flex-col text-[8px] leading-none text-gray-400">
                                                <i :class="sortField === 'created_at' && sortDirection === 'asc' ? 'text-gray-800' : ''">▲</i>
                                                <i :class="sortField === 'created_at' && sortDirection === 'desc' ? 'text-gray-800' : ''">▼</i>
                                            </span>
                                        </div>
                                    </th>
    
                                    <th class="px-6 py-4 text-right text-xs font-bold text-gray-500 uppercase tracking-wider whitespace-nowrap">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 bg-white">
                                <tr v-for="course in courses.data" :key="course.id" class="hover:bg-gray-50 transition">
                                    
                                    <td class="px-6 py-4 font-bold text-gray-900 whitespace-nowrap">
                                        {{ course.title }}
                                    </td>
    
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-600 border border-gray-200">
                                            {{ course.target_role }}
                                        </span>
                                    </td>
    
                                    <td class="px-6 py-4 text-sm text-gray-600 whitespace-nowrap">
                                        {{ course.mentor_name }}
                                    </td>
    
                                    <td class="px-6 py-4 text-center whitespace-nowrap">
                                        <span v-if="course.is_published" class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-green-100 text-green-800 border border-green-200">Diterbitkan</span>
                                        <span v-else class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-gray-100 text-gray-500 border border-gray-200">Draf</span>
                                    </td>
    
                                    <td class="px-6 py-4 text-sm text-gray-600 text-center whitespace-nowrap font-medium">
                                        {{ course.students_count }}
                                    </td>
    
                                    <td class="px-6 py-4 text-sm text-gray-600 text-center whitespace-nowrap">
                                        <span class="flex items-center justify-center gap-1 bg-blue-50 px-2 py-0.5 rounded text-blue-700 text-xs font-bold border border-blue-100">
                                            📝 {{ course.assessments_count }}
                                        </span>
                                    </td>
                                    
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex flex-col">
                                            <span class="text-sm font-medium text-gray-900">{{ course.created_at }}</span>
                                            <span class="text-xs text-gray-400 italic">Diperbarui: {{ course.updated_at_diff }}</span>
                                        </div>
                                    </td>
    
                                    <td class="px-6 py-4 text-right whitespace-nowrap space-x-3">
                                        <Link :href="route('admin.courses.edit', course.id)" class="text-blue-600 hover:text-blue-800 font-bold text-sm transition">
                                            Kelola
                                        </Link>
                                        <button @click="confirmDelete(course)" class="text-red-500 hover:text-red-700 font-bold text-sm transition">
                                            Hapus
                                        </button>
                                    </td>
                                </tr>
                                
                                <tr v-if="courses.data.length === 0">
                                    <td colspan="8" class="px-6 py-12 text-center text-gray-400 italic bg-gray-50">
                                        Belum ada kursus. Silakan tambah baru.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
    
                    <div class="px-6 py-4 border-t border-gray-200 flex flex-col sm:flex-row justify-between items-center gap-4 bg-gray-50/50">
                        <div class="text-sm text-gray-500">
                            Menampilkan <span class="font-bold text-gray-900">{{ courses.from || 0 }}</span> sampai <span class="font-bold text-gray-900">{{ courses.to || 0 }}</span> dari <span class="font-bold text-gray-900">{{ courses.total }}</span> kursus
                        </div>
    
                        <div class="flex items-center gap-1 overflow-x-auto" v-if="courses.links.length > 3">
                            <Link v-for="(link, k) in courses.links" :key="k" :href="link.url" v-html="link.label"
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
                <div v-if="isCreateModalOpen" class="fixed inset-0 z-[1000] flex items-center justify-center px-4">
                    <div class="fixed inset-0 bg-gray-900/75 backdrop-blur-sm transition-opacity" @click="isCreateModalOpen = false"></div>
                    <div class="bg-white rounded-xl shadow-2xl w-full max-w-md p-6 relative z-10 transform transition-all scale-100 animate-fade-in-up">
                        <h3 class="text-lg font-bold mb-4 text-gray-900">Tambah Kursus Baru</h3>
                        <form @submit.prevent="submitCreate" class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Nama Kursus</label>
                                <input v-model="form.title" type="text" class="w-full rounded-lg border-gray-300 text-sm focus:ring-blue-500 focus:border-blue-500" required placeholder="Contoh: Manajemen Proyek Dasar">
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Target Peserta (Departemen)</label>
                                <p class="text-xs text-gray-500 mb-2">Pilih departemen khusus atau biarkan "Umum".</p>
                                
                                <select v-model="form.department_id" class="w-full rounded-lg border-gray-300 text-sm focus:ring-blue-500 focus:border-blue-500">
                                    <option :value="null">Umum / Semua Departemen (General)</option>
                                    <option disabled>----------------------------------------</option>
                                    <option v-for="dept in departments" :key="dept.id" :value="dept.id">
                                        {{ dept.department_name }}
                                    </option>
                                </select>
                            </div>
    
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Mentor Pengampu</label>
                                <select 
                                    v-model="form.mentor_id" 
                                    class="w-full rounded-lg border-gray-300 text-sm focus:ring-blue-500 focus:border-blue-500" 
                                    required
                                >
                                    <option value="" disabled>Pilih Mentor</option>
                                    <option v-for="m in filteredMentors" :key="m.id" :value="m.id">
                                        {{ m.name }}
                                    </option>
                                </select>
                                
                                <p v-if="form.department_id && filteredMentors.length === 0" class="text-xs text-red-500 mt-1">
                                    Tidak ditemukan mentor di departemen ini.
                                </p>
                            </div>
    
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Bonus Poin (XP)</label>
                                <div class="relative">
                                    <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-yellow-500 font-bold">⚡</span>
                                    <input v-model="form.completion_points" type="number" min="0" class="w-full pl-8 rounded-lg border-gray-300 text-sm focus:ring-blue-500 focus:border-blue-500" placeholder="Contoh: 500">
                                </div>
                                <p class="text-xs text-gray-500 mt-1">XP diberikan setelah peserta menyelesaikan semua modul.</p>
                            </div>
    
                            <div class="flex justify-end gap-3 mt-6 border-t pt-4">
                                <button type="button" @click="isCreateModalOpen = false" class="px-4 py-2 border rounded-lg text-sm text-gray-700 hover:bg-gray-50 transition">Batal</button>
                                <button type="submit" class="px-4 py-2 bg-blue-900 text-white rounded-lg text-sm hover:bg-blue-800 transition shadow-sm font-bold" :disabled="form.processing">
                                    {{ form.processing ? 'Memproses...' : 'Buat Kursus' }}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </Teleport>
    
            <Teleport to="body">
                <div v-if="isDeleteModalOpen" class="fixed inset-0 z-[1000] flex items-center justify-center px-4">
                    <div class="fixed inset-0 bg-gray-900/75 backdrop-blur-sm transition-opacity" @click="isDeleteModalOpen = false"></div>
                    
                    <div class="bg-white rounded-xl shadow-2xl w-full max-w-sm p-6 relative z-10 text-center transform transition-all scale-100 animate-bounce-in">
                        <div class="mx-auto flex items-center justify-center h-14 w-14 rounded-full bg-red-100 mb-4">
                            <svg class="h-8 w-8 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                        </div>
                        
                        <h3 class="text-lg font-bold text-gray-900 mb-2">Hapus Kursus?</h3>
                        
                        <p class="text-sm text-gray-500 mb-6">
                            Apakah Anda yakin ingin menghapus kursus <strong>"{{ courseToDelete?.title }}"</strong>?
                            <br><span class="text-xs text-red-500 mt-1 block">Tindakan ini tidak dapat dibatalkan.</span>
                        </p>
                        
                        <div class="flex justify-center gap-3">
                            <button 
                                @click="isDeleteModalOpen = false" 
                                class="px-4 py-2 bg-white border border-gray-300 rounded-lg text-sm text-gray-700 hover:bg-gray-50 transition"
                                :disabled="isDeleting"
                            >
                                Batal
                            </button>
                            
                            <button 
                                @click="deleteCourse" 
                                class="px-4 py-2 bg-red-600 text-white rounded-lg text-sm hover:bg-red-700 transition shadow-sm flex items-center gap-2 font-bold"
                                :disabled="isDeleting"
                            >
                                <svg v-if="isDeleting" class="animate-spin h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                {{ isDeleting ? 'Menghapus...' : 'Ya, Hapus' }}
                            </button>
                        </div>
                    </div>
                </div>
            </Teleport>
    
        </AppLayout>
    </template>
    
    <style scoped>
    .animate-fade-in-up {
        animation: fadeInUp 0.3s ease-out forwards;
    }
    @keyframes fadeInUp {
        from { opacity: 0; transform: scale(0.95) translateY(10px); }
        to { opacity: 1; transform: scale(1) translateY(0); }
    }
    </style>