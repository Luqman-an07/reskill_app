<script setup>
import { ref, watch, computed } from 'vue'; // Tambahkan computed
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import debounce from 'lodash/debounce';

const props = defineProps({
    users: Object,
    stats: Object,
    departments: Array,
    roles: Array,
    filters: Object,
});

// --- STATE ---
const search = ref(props.filters.search || '');
const filterRole = ref(props.filters.role_id || '');
const filterDept = ref(props.filters.department_id || '');

// --- STATE SORTING ---
const sortField = ref(props.filters.sort || 'name');
const sortDirection = ref(props.filters.direction || 'asc');

const sort = (field) => {
    if (sortField.value === field) {
        sortDirection.value = sortDirection.value === 'asc' ? 'desc' : 'asc';
    } else {
        sortField.value = field;
        sortDirection.value = 'asc';
    }
};

// --- ACTIONS ---
const openActionId = ref(null);
const toggleAction = (id) => {
    openActionId.value = openActionId.value === id ? null : id;
};
const closeActions = () => openActionId.value = null;

// --- FILTERING WATCHER ---
watch([search, filterRole, filterDept, sortField, sortDirection], debounce(() => {
    router.get(route('admin.users.index'), { 
        search: search.value,
        role_id: filterRole.value,
        department_id: filterDept.value,
        sort: sortField.value,       
        direction: sortDirection.value 
    }, { preserveState: true, replace: true });
}, 300));

// --- MODAL CRUD ---
const isModalOpen = ref(false);
const isEditMode = ref(false);
const form = useForm({
    id: null, name: '', email: '', employee_id: '', department_id: '', role_id: '', password: ''
});

// --- LOGIKA BARU: DETEKSI ROLE & RESET DEPARTEMEN ---
// 1. Ambil Nama Role yang sedang dipilih di form untuk logic UI
const selectedRoleName = computed(() => {
    const role = props.roles.find(r => r.id === form.role_id);
    return role ? role.name : '';
});

// 2. Watcher: Jika Role berubah dan BUKAN 'peserta', kosongkan department_id
watch(() => form.role_id, (newVal) => {
    const role = props.roles.find(r => r.id === newVal);
    if (role && role.name !== 'peserta') {
        form.department_id = null; // Reset departemen
    }
});
// -----------------------------------------------------

const openCreateModal = () => {
    isEditMode.value = false;
    form.reset();
    form.clearErrors();
    isModalOpen.value = true;
};

const openEditModal = (user) => {
    isEditMode.value = true;
    form.id = user.id;
    form.name = user.name;
    form.email = user.email;
    form.employee_id = user.employee_id;
    form.department_id = user.department_id;
    
    // Pastikan role diambil dengan benar (asumsi user punya 1 role utama)
    form.role_id = user.roles && user.roles.length > 0 ? user.roles[0].id : '';
    
    form.password = '';
    isModalOpen.value = true;
    openActionId.value = null;
};

const submit = () => {
    const url = isEditMode.value ? route('admin.users.update', form.id) : route('admin.users.store');
    const method = isEditMode.value ? 'put' : 'post';
    
    form[method](url, {
        onSuccess: () => isModalOpen.value = false,
    });
};

const closeModal = () => {
    isModalOpen.value = false;
    form.reset();
    form.clearErrors();
};

// --- MODAL CONFIRMATION ---
const isConfirmModalOpen = ref(false);
const confirmAction = ref(null);
const confirmTargetUser = ref(null);
const confirmMessage = ref({ title: '', body: '', confirmText: '', type: 'danger' });

const confirmDeleteUser = (user) => {
    confirmTargetUser.value = user;
    confirmAction.value = 'delete';
    confirmMessage.value = {
        title: 'Hapus Pengguna?',
        body: `Apakah Anda yakin ingin menghapus ${user.name}? Tindakan ini tidak dapat dibatalkan.`,
        confirmText: 'Ya, Hapus',
        type: 'danger'
    };
    isConfirmModalOpen.value = true;
    openActionId.value = null;
};

const confirmToggleStatus = (user) => {
    confirmTargetUser.value = user;
    confirmAction.value = 'toggle';
    const actionText = user.is_active ? 'Nonaktifkan' : 'Aktifkan';
    
    confirmMessage.value = {
        title: `${actionText} Pengguna?`,
        body: `Apakah Anda yakin ingin me-${actionText.toLowerCase()} ${user.name}?`,
        confirmText: `Ya, ${actionText}`,
        type: user.is_active ? 'danger' : 'success'
    };
    isConfirmModalOpen.value = true;
    openActionId.value = null;
};

const executeAction = () => {
    if (!confirmTargetUser.value) return;

    if (confirmAction.value === 'delete') {
        router.delete(route('admin.users.destroy', confirmTargetUser.value.id), {
            onSuccess: () => closeConfirmModal(),
        });
    } else if (confirmAction.value === 'toggle') {
        router.patch(route('admin.users.toggle-status', confirmTargetUser.value.id), {}, {
            onSuccess: () => closeConfirmModal(),
        });
    }
};

const closeConfirmModal = () => {
    isConfirmModalOpen.value = false;
    confirmTargetUser.value = null;
    confirmAction.value = null;
};

const getInitials = (name) => name ? name.split(' ').map(n => n[0]).join('').substring(0, 2).toUpperCase() : 'UR';
</script>

<template>
    <Head title="Kelola Pengguna" />

    <AppLayout>
        <div 
            v-if="openActionId !== null" 
            class="fixed inset-0 z-30 cursor-default bg-transparent" 
            @click="closeActions"
        ></div>

        <div class="max-w-7xl mx-auto space-y-6 sm:space-y-8">
            
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                <div>
                    <h1 class="text-2xl sm:text-3xl font-bold text-gray-900">Kelola Pengguna</h1>
                    <p class="text-sm text-gray-500 mt-1">Kelola pengguna, role, dan hak akses.</p>
                </div>
                <button @click="openCreateModal" class="w-full sm:w-auto bg-blue-900 hover:bg-blue-800 text-white px-5 py-2.5 rounded-xl text-sm font-bold flex items-center justify-center gap-2 transition shadow-sm active:scale-95">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    Tambah Pengguna
                </button>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-6">
                <div class="bg-white p-5 rounded-xl border border-gray-200 shadow-sm flex flex-col justify-between h-full">
                    <p class="text-xs font-bold text-gray-400 uppercase tracking-wide">Total Pengguna</p>
                    <p class="text-3xl font-black text-gray-900 mt-2">{{ stats.total_users.toLocaleString() }}</p>
                </div>
                <div class="bg-white p-5 rounded-xl border border-gray-200 shadow-sm flex flex-col justify-between h-full">
                    <p class="text-xs font-bold text-green-600 uppercase tracking-wide">Aktif</p>
                    <p class="text-3xl font-black text-green-700 mt-2">{{ stats.active_users.toLocaleString() }}</p>
                </div>
                <div class="bg-white p-5 rounded-xl border border-gray-200 shadow-sm flex flex-col justify-between h-full">
                    <p class="text-xs font-bold text-blue-600 uppercase tracking-wide">Mentor</p>
                    <p class="text-3xl font-black text-blue-700 mt-2">{{ stats.mentors }}</p>
                </div>
                <div class="bg-white p-5 rounded-xl border border-gray-200 shadow-sm flex flex-col justify-between h-full">
                    <p class="text-xs font-bold text-red-600 uppercase tracking-wide">Admin</p>
                    <p class="text-3xl font-black text-red-700 mt-2">{{ stats.admins }}</p>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-12 gap-4 bg-white p-4 rounded-xl border border-gray-200 shadow-sm">
                <div class="relative md:col-span-6">
                    <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-400">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                    </span>
                    <input 
                        v-model="search" 
                        type="text" 
                        placeholder="Cari pengguna..." 
                        class="w-full pl-10 pr-4 py-2.5 bg-gray-50 border border-gray-300 rounded-lg text-sm focus:ring-blue-500 focus:border-blue-500 transition"
                    >
                </div>
                
                <div class="md:col-span-3">
                    <select v-model="filterDept" class="w-full bg-gray-50 border border-gray-300 text-gray-700 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 p-2.5">
                        <option value="">Semua Departemen</option>
                        <option v-for="dept in departments" :key="dept.id" :value="dept.id">{{ dept.department_name }}</option>
                    </select>
                </div>
                
                <div class="md:col-span-3">
                    <select v-model="filterRole" class="w-full bg-gray-50 border border-gray-300 text-gray-700 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 p-2.5">
                        <option value="">Semua Role</option>
                        <option v-for="role in roles" :key="role.id" :value="role.id">{{ role.name }}</option>
                    </select>
                </div>
            </div>

            <div class="bg-white border border-gray-200 rounded-xl shadow-sm overflow-hidden">
                <div class="overflow-x-auto min-h-[400px]">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider whitespace-nowrap cursor-pointer hover:bg-gray-100 transition group" @click="sort('name')">
                                    <div class="flex items-center gap-1">
                                        Nama
                                        <span class="flex flex-col text-[8px] leading-none text-gray-400">
                                            <i :class="sortField === 'name' && sortDirection === 'asc' ? 'text-gray-800' : ''">▲</i>
                                            <i :class="sortField === 'name' && sortDirection === 'desc' ? 'text-gray-800' : ''">▼</i>
                                        </span>
                                    </div>
                                </th>

                                <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider whitespace-nowrap cursor-pointer hover:bg-gray-100 transition group" @click="sort('email')">
                                    <div class="flex items-center gap-1">
                                        Email
                                        <span class="flex flex-col text-[8px] leading-none text-gray-400">
                                            <i :class="sortField === 'email' && sortDirection === 'asc' ? 'text-gray-800' : ''">▲</i>
                                            <i :class="sortField === 'email' && sortDirection === 'desc' ? 'text-gray-800' : ''">▼</i>
                                        </span>
                                    </div>
                                </th>

                                <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider whitespace-nowrap cursor-pointer hover:bg-gray-100 transition group" @click="sort('department_name')">
                                    <div class="flex items-center gap-1">
                                        Departemen
                                        <span class="flex flex-col text-[8px] leading-none text-gray-400">
                                            <i :class="sortField === 'department_name' && sortDirection === 'asc' ? 'text-gray-800' : ''">▲</i>
                                            <i :class="sortField === 'department_name' && sortDirection === 'desc' ? 'text-gray-800' : ''">▼</i>
                                        </span>
                                    </div>
                                </th>

                                <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider whitespace-nowrap">Role</th>

                                <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider whitespace-nowrap cursor-pointer hover:bg-gray-100 transition group" @click="sort('is_active')">
                                    <div class="flex items-center gap-1">
                                        Status
                                        <span class="flex flex-col text-[8px] leading-none text-gray-400">
                                            <i :class="sortField === 'is_active' && sortDirection === 'asc' ? 'text-gray-800' : ''">▲</i>
                                            <i :class="sortField === 'is_active' && sortDirection === 'desc' ? 'text-gray-800' : ''">▼</i>
                                        </span>
                                    </div>
                                </th>
                                <th class="px-6 py-4 text-right text-xs font-bold text-gray-500 uppercase tracking-wider whitespace-nowrap">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <tr v-for="user in users.data" :key="user.id" class="hover:bg-gray-50 transition">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div class="h-9 w-9 rounded-full bg-blue-100 flex items-center justify-center text-xs font-bold text-blue-700 shrink-0 overflow-hidden border border-blue-200">
                                            <img v-if="user.profile_picture" :src="'/storage/'+user.profile_picture" class="w-full h-full object-cover">
                                            <span v-else>{{ getInitials(user.name) }}</span>
                                        </div>
                                        <div class="ml-3">
                                            <div class="text-sm font-bold text-gray-900">{{ user.name }}</div>
                                            <div class="text-xs text-gray-500">{{ user.employee_id || '-' }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ user.email }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    {{ user.department ? user.department.department_name : '-' }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span v-if="user.roles[0]" 
                                        class="px-2.5 py-0.5 rounded-full text-xs font-bold capitalize border"
                                        :class="{
                                            'bg-purple-50 text-purple-700 border-purple-200': user.roles[0].name === 'admin',
                                            'bg-blue-50 text-blue-700 border-blue-200': user.roles[0].name === 'mentor',
                                            'bg-green-50 text-green-700 border-green-200': user.roles[0].name === 'peserta'
                                        }">
                                        {{ user.roles[0].name }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2.5 py-0.5 rounded-full text-xs font-bold border"
                                        :class="user.is_active 
                                            ? 'bg-green-50 text-green-700 border-green-200' 
                                            : 'bg-red-50 text-red-700 border-red-200'">
                                        {{ user.is_active ? 'Aktif' : 'Nonaktif' }}
                                    </span>
                                </td>
                                
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium relative">
                                    <button 
                                        @click.stop="toggleAction(user.id)" 
                                        class="text-gray-400 hover:text-blue-600 p-1.5 rounded-full hover:bg-gray-100 transition focus:outline-none"
                                    >
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z" /></svg>
                                    </button>

                                    <div 
                                        v-if="openActionId === user.id" 
                                        class="absolute right-8 top-1/2 -translate-y-1/2 w-40 bg-white rounded-lg shadow-xl border border-gray-100 z-40 overflow-hidden text-left animate-fade-in-up"
                                    >
                                        <button @click="openEditModal(user)" class="block w-full text-left px-4 py-2.5 text-sm text-gray-700 hover:bg-blue-50 hover:text-blue-700 transition font-medium">
                                            ✏️ Ubah
                                        </button>
                                        <button 
                                            @click="confirmToggleStatus(user)" 
                                            class="block w-full text-left px-4 py-2.5 text-sm transition font-medium"
                                            :class="user.is_active ? 'text-orange-600 hover:bg-orange-50' : 'text-green-600 hover:bg-green-50'"
                                        >
                                            <span v-if="user.is_active">⛔ Nonaktifkan</span>
                                            <span v-else>✅ Aktifkan</span>
                                        </button>
                                        <div class="border-t border-gray-100 my-1"></div>
                                        <button @click="confirmDeleteUser(user)" class="block w-full text-left px-4 py-2.5 text-sm text-red-600 hover:bg-red-50 transition font-medium">
                                            🗑️ Hapus
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            
                            <tr v-if="users.data.length === 0">
                                <td colspan="6" class="px-6 py-12 text-center text-gray-400 italic">
                                    Tidak ada pengguna ditemukan.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div class="px-6 py-4 border-t border-gray-200 flex flex-col sm:flex-row justify-between items-center gap-4 bg-gray-50/50">
                    <div class="text-sm text-gray-500">
                        Menampilkan <span class="font-bold text-gray-900">{{ users.from || 0 }}</span> sampai <span class="font-bold text-gray-900">{{ users.to || 0 }}</span> dari <span class="font-bold text-gray-900">{{ users.total }}</span> pengguna
                    </div>
                    <div class="flex items-center gap-1 overflow-x-auto" v-if="users.links.length > 3">
                        <Link v-for="(link, k) in users.links" :key="k" :href="link.url" v-html="link.label" class="px-3 py-1 border rounded text-sm transition whitespace-nowrap" :class="link.active ? 'bg-blue-900 text-white border-blue-900' : 'text-gray-600 bg-white hover:bg-gray-50'" :preserve-scroll="true" />
                    </div>
                </div>
            </div>
        </div>

        <Teleport to="body">
            <div v-if="isModalOpen" class="fixed inset-0 z-[1000] flex items-center justify-center px-4">
                <div class="fixed inset-0 bg-gray-900/75 backdrop-blur-sm transition-opacity" @click="closeModal"></div>
                <div class="bg-white rounded-xl shadow-2xl w-full max-w-lg p-6 relative z-10 overflow-y-auto max-h-[90vh] animate-fade-in-up">
                    <div class="flex justify-between items-center mb-6">
                        <h3 class="text-xl font-bold text-gray-900">{{ isEditMode ? 'Ubah Pengguna' : 'Tambah Pengguna Baru' }}</h3>
                        <button @click="closeModal" class="text-gray-400 hover:text-gray-600"><svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg></button>
                    </div>

                    <form @submit.prevent="submit" class="space-y-5">
                        <div>
                            <label class="block text-sm font-bold text-gray-700 mb-1">Nama Lengkap</label>
                            <input v-model="form.name" type="text" class="w-full rounded-lg border-gray-300 focus:ring-blue-500 focus:border-blue-500 text-sm">
                            <p v-if="form.errors.name" class="text-red-500 text-xs mt-1">{{ form.errors.name }}</p>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-bold text-gray-700 mb-1">Email</label>
                            <input v-model="form.email" type="email" class="w-full rounded-lg border-gray-300 focus:ring-blue-500 focus:border-blue-500 text-sm">
                            <p v-if="form.errors.email" class="text-red-500 text-xs mt-1">{{ form.errors.email }}</p>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-bold text-gray-700 mb-1">ID Karyawan</label>
                                <input :value="isEditMode ? form.employee_id : '(Auto)'" type="text" class="w-full rounded-lg border-gray-200 bg-gray-100 text-gray-500 text-sm" disabled>
                            </div>
                            
                            <div>
                                <label class="block text-sm font-bold text-gray-700 mb-1">Role</label>
                                <select v-model="form.role_id" class="w-full rounded-lg border-gray-300 focus:ring-blue-500 focus:border-blue-500 text-sm">
                                    <option v-for="role in roles" :key="role.id" :value="role.id">{{ role.name }}</option>
                                </select>
                            </div>
                        </div>

                        <div v-if="selectedRoleName === 'peserta'" class="animate-fade-in">
                            <label class="block text-sm font-bold text-gray-700 mb-1">Departemen</label>
                            <select v-model="form.department_id" class="w-full rounded-lg border-gray-300 focus:ring-blue-500 focus:border-blue-500 text-sm">
                                <option value="">Pilih Departemen</option>
                                <option v-for="dept in departments" :key="dept.id" :value="dept.id">{{ dept.department_name }}</option>
                            </select>
                            <p class="text-xs text-gray-500 mt-1">Wajib diisi untuk Peserta agar materi sesuai.</p>
                        </div>
                        
                        <div v-else-if="form.role_id" class="p-3 bg-blue-50 text-blue-800 text-sm rounded-lg border border-blue-100 flex items-start gap-2">
                            <span class="text-lg">ℹ️</span>
                            <div>
                                <strong>Info Akses:</strong>
                                <p class="text-xs mt-0.5">Role <strong>{{ selectedRoleName }}</strong> bersifat lintas departemen (Human Growth / Admin), sehingga tidak terikat pada satu departemen spesifik.</p>
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-bold text-gray-700 mb-1">Kata Sandi <span v-if="isEditMode" class="text-gray-400 font-normal text-xs">(Opsional)</span></label>
                            <input v-model="form.password" type="password" class="w-full rounded-lg border-gray-300 focus:ring-blue-500 focus:border-blue-500 text-sm" placeholder="••••••••">
                        </div>

                        <div class="flex justify-end gap-3 mt-8 pt-4 border-t border-gray-100">
                            <button type="button" @click="closeModal" class="px-4 py-2 bg-white text-gray-700 border border-gray-300 rounded-lg text-sm font-bold hover:bg-gray-50">Batal</button>
                            <button type="submit" class="px-4 py-2 bg-blue-900 text-white rounded-lg text-sm font-bold hover:bg-blue-800 shadow-sm" :disabled="form.processing">Simpan</button>
                        </div>
                    </form>
                </div>
            </div>
        </Teleport>

        <Teleport to="body">
            <div v-if="isConfirmModalOpen" class="fixed inset-0 z-[1000] flex items-center justify-center px-4">
                <div class="fixed inset-0 bg-gray-900/75 backdrop-blur-sm transition-opacity" @click="closeConfirmModal"></div>
                <div class="bg-white rounded-2xl shadow-2xl w-full max-w-sm p-6 relative z-10 text-center animate-bounce-in">
                    <div class="mx-auto flex items-center justify-center h-14 w-14 rounded-full mb-4" :class="confirmMessage.type === 'danger' ? 'bg-red-100 text-red-600' : 'bg-green-100 text-green-600'">
                        <svg class="h-8 w-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path v-if="confirmMessage.type === 'danger'" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                            <path v-else stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <h3 class="text-lg font-bold text-gray-900 mb-2">{{ confirmMessage.title }}</h3>
                    <p class="text-sm text-gray-500 mb-6">{{ confirmMessage.body }}</p>
                    <div class="flex justify-center gap-3">
                        <button @click="closeConfirmModal" class="px-4 py-2 bg-white border border-gray-300 rounded-lg text-sm font-bold text-gray-700 hover:bg-gray-50">Batal</button>
                        <button @click="executeAction" class="px-4 py-2 text-white rounded-lg text-sm font-bold shadow-sm" :class="confirmMessage.type === 'danger' ? 'bg-red-600 hover:bg-red-700' : 'bg-green-600 hover:bg-green-700'">{{ confirmMessage.confirmText }}</button>
                    </div>
                </div>
            </div>
        </Teleport>

    </AppLayout>
</template>

<style scoped>
.animate-fade-in-up { animation: fadeInUp 0.3s ease-out forwards; }
.animate-fade-in { animation: fadeIn 0.3s ease-out forwards; }
@keyframes fadeInUp { from { opacity: 0; transform: scale(0.95) translateY(10px); } to { opacity: 1; transform: scale(1) translateY(0); } }
@keyframes fadeIn { from { opacity: 0; transform: translateY(-5px); } to { opacity: 1; transform: translateY(0); } }
</style>