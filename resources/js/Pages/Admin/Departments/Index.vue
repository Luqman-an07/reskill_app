<script setup>
    import { ref, watch } from 'vue';
    import { Head, useForm, router } from '@inertiajs/vue3';
    import AppLayout from '@/Layouts/AppLayout.vue';
    import debounce from 'lodash/debounce';
    
    const props = defineProps({
        departments: Object, // Pagination Object
        filters: Object,
    });
    
    // --- STATE PENCARIAN ---
    const search = ref(props.filters.search || '');
    
    watch(search, debounce((value) => {
        router.get(route('admin.departments.index'), { search: value }, { preserveState: true, replace: true });
    }, 300));
    
    // --- STATE MODAL CRUD ---
    const isModalOpen = ref(false);
    const isEditMode = ref(false);
    const form = useForm({
        id: null,
        department_name: '',
        description: '',
    });
    
    // --- ACTIONS ---
    const openCreateModal = () => {
        isEditMode.value = false;
        form.reset();
        form.clearErrors();
        isModalOpen.value = true;
    };
    
    const openEditModal = (dept) => {
        isEditMode.value = true;
        form.id = dept.id;
        form.department_name = dept.department_name;
        form.description = dept.description;
        form.clearErrors();
        isModalOpen.value = true;
    };
    
    const submit = () => {
        if (isEditMode.value) {
            form.put(route('admin.departments.update', form.id), {
                onSuccess: () => isModalOpen.value = false,
            });
        } else {
            form.post(route('admin.departments.store'), {
                onSuccess: () => isModalOpen.value = false,
            });
        }
    };
    
    const deleteDept = (id, name) => {
        if (confirm(`Apakah Anda yakin ingin menghapus departemen "${name}"?`)) {
            router.delete(route('admin.departments.destroy', id));
        }
    };
    </script>
    
    <template>
        <Head title="Kelola Departemen" />
    
        <AppLayout>
            <div class="max-w-7xl mx-auto space-y-6">
                
                <div class="flex flex-col sm:flex-row justify-between items-center gap-4">
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900">Daftar Departemen</h1>
                        <p class="text-sm text-gray-500">Kelola unit kerja dan divisi perusahaan.</p>
                    </div>
                    <button 
                        @click="openCreateModal" 
                        class="bg-blue-900 text-white px-5 py-2.5 rounded-xl text-sm font-bold hover:bg-blue-800 shadow-lg shadow-blue-900/20 transition flex items-center gap-2"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                        Tambah Departemen
                    </button>
                </div>
    
                <div class="bg-white p-4 rounded-xl border border-gray-200 shadow-sm">
                    <div class="relative max-w-md">
                        <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-400">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                        </span>
                        <input 
                            v-model="search" 
                            type="text" 
                            placeholder="Cari nama departemen..." 
                            class="w-full pl-10 pr-4 py-2 bg-gray-50 border border-gray-300 rounded-lg text-sm focus:ring-blue-500 focus:border-blue-500 transition"
                        >
                    </div>
                </div>
    
                <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Nama Departemen</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Deskripsi</th>
                                    <th scope="col" class="px-6 py-3 text-center text-xs font-bold text-gray-500 uppercase tracking-wider">Jumlah Peserta</th>
                                    <th scope="col" class="px-6 py-3 text-right text-xs font-bold text-gray-500 uppercase tracking-wider">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 bg-white">
                                <tr v-for="dept in departments.data" :key="dept.id" class="hover:bg-gray-50 transition">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-bold text-gray-900">{{ dept.department_name }}</div>
                                        <div class="text-xs text-gray-500 font-mono mt-0.5">{{ dept.department_code }}</div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="text-sm text-gray-500 line-clamp-2 max-w-xs">
                                            {{ dept.description || '-' }}
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-center">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-blue-100 text-blue-800">
                                            {{ dept.users_count }} Orang
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                        <button @click="openEditModal(dept)" class="text-blue-600 hover:text-blue-900 mr-3 font-bold hover:underline">Edit</button>
                                        <button @click="deleteDept(dept.id, dept.department_name)" class="text-red-600 hover:text-red-900 font-bold hover:underline">Hapus</button>
                                    </td>
                                </tr>
                                
                                <tr v-if="departments.data.length === 0">
                                    <td colspan="4" class="px-6 py-10 text-center text-gray-500 italic">
                                        Belum ada departemen yang dibuat.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
    
                    <div class="px-6 py-4 border-t border-gray-200 flex items-center justify-between bg-gray-50">
                        <div class="text-xs text-gray-500">
                            Menampilkan <span class="font-bold">{{ departments.from || 0 }}</span> sampai <span class="font-bold">{{ departments.to || 0 }}</span> dari <span class="font-bold">{{ departments.total }}</span> data
                        </div>
                        </div>
                </div>
            </div>
    
            <Teleport to="body">
                <div v-if="isModalOpen" class="fixed inset-0 z-50 flex items-center justify-center px-4">
                    <div class="fixed inset-0 bg-gray-900/75 backdrop-blur-sm transition-opacity" @click="isModalOpen = false"></div>
                    
                    <div class="bg-white rounded-xl shadow-2xl w-full max-w-md p-6 relative z-10 animate-fade-in-up">
                        <div class="flex justify-between items-center mb-4">
                            <h3 class="text-lg font-bold text-gray-900">{{ isEditMode ? 'Edit Departemen' : 'Tambah Departemen' }}</h3>
                            <button @click="isModalOpen = false" class="text-gray-400 hover:text-gray-600">✕</button>
                        </div>
    
                        <form @submit.prevent="submit" class="space-y-4">
                            <div>
                                <label class="block text-sm font-bold text-gray-700 mb-1">Nama Departemen</label>
                                <input 
                                    v-model="form.department_name" 
                                    type="text" 
                                    class="w-full rounded-lg border-gray-300 focus:ring-blue-500 focus:border-blue-500 text-sm" 
                                    placeholder="Contoh: Human Resources"
                                    required
                                >
                                <p v-if="form.errors.department_name" class="text-red-500 text-xs mt-1">{{ form.errors.department_name }}</p>
                            </div>
    
                            <div>
                                <label class="block text-sm font-bold text-gray-700 mb-1">Deskripsi Singkat</label>
                                <textarea 
                                    v-model="form.description" 
                                    rows="3" 
                                    class="w-full rounded-lg border-gray-300 focus:ring-blue-500 focus:border-blue-500 text-sm"
                                    placeholder="Opsional..."
                                ></textarea>
                            </div>
    
                            <div class="flex justify-end gap-3 pt-4 border-t border-gray-100">
                                <button type="button" @click="isModalOpen = false" class="px-4 py-2 bg-white text-gray-700 border border-gray-300 rounded-lg text-sm font-bold hover:bg-gray-50">Batal</button>
                                <button type="submit" class="px-4 py-2 bg-blue-900 text-white rounded-lg text-sm font-bold hover:bg-blue-800 shadow-sm" :disabled="form.processing">
                                    {{ form.processing ? 'Menyimpan...' : 'Simpan' }}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </Teleport>
        </AppLayout>
    </template>
    
    <style scoped>
    .animate-fade-in-up { animation: fadeInUp 0.3s ease-out forwards; }
    @keyframes fadeInUp { from { opacity: 0; transform: scale(0.95) translateY(10px); } to { opacity: 1; transform: scale(1) translateY(0); } }
    </style>