<script setup>
import { ref } from 'vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    badges: Array,
});

const activeTab = ref('badges'); // 'badges', 'seasons', 'rules'

// --- OPTIONS CONSTANTS (BAHASA INDONESIA) ---
const triggerOptions = [
    { value: 'POINTS_REACHED', label: 'Total XP Tercapai' },
    { value: 'QUIZ_PERFECT', label: 'Nilai Kuis Sempurna (100)' },
    { value: 'COURSE_COMPLETE', label: 'Kursus/Modul Selesai' },
    { value: 'TASK_COUNT', label: 'Total Tugas Dikumpulkan' },
    { value: 'STREAK_DAYS', label: 'Login Harian Berturut-turut' },
];

// --- STATE MODAL CRUD ---
const isModalOpen = ref(false);
const isEditMode = ref(false);

const form = useForm({
    id: null,
    badge_name: '',
    description: '',
    bonus_points: 0,
    trigger_type: 'POINTS_REACHED',
    trigger_value: '',
    
    // Icon Logic
    icon_type: 'EMOJI', // 'UPLOAD' or 'EMOJI'
    icon: null,         // File object
    icon_emoji: '🏆',   // String emoji
    preview_url: null   // URL untuk preview gambar
});

// --- ACTIONS ---
const openCreate = () => {
    isEditMode.value = false;
    form.reset();
    form.clearErrors();
    form.icon_type = 'EMOJI';
    form.icon_emoji = '🏆';
    isModalOpen.value = true;
};

const openEdit = (badge) => {
    isEditMode.value = true;
    form.id = badge.id;
    form.badge_name = badge.badge_name;
    form.description = badge.description;
    form.bonus_points = badge.bonus_points;
    form.trigger_type = badge.trigger_type;
    form.trigger_value = badge.trigger_value;
    
    if (badge.icon_url) {
        form.icon_type = 'UPLOAD';
        form.preview_url = '/storage/' + badge.icon_url;
        form.icon_emoji = '';
    } else {
        form.icon_type = 'EMOJI';
        form.icon_emoji = badge.icon_emoji || '🏅';
        form.preview_url = null;
    }
    
    form.clearErrors();
    isModalOpen.value = true;
};

const handleFile = (e) => {
    const file = e.target.files[0];
    if (file) {
        form.icon = file;
        form.preview_url = URL.createObjectURL(file);
    }
};

const submit = () => {
    if (isEditMode.value) {
        form.transform((data) => ({
            ...data,
            _method: 'put',
        })).post(route('admin.gamification.update', form.id), {
            onSuccess: () => isModalOpen.value = false,
        });
    } else {
        form.post(route('admin.gamification.store'), {
            onSuccess: () => isModalOpen.value = false,
        });
    }
};

// --- DELETE MODAL STATE (BARU) ---
const isDeleteModalOpen = ref(false);
const badgeToDelete = ref(null);
const isDeleting = ref(false);

const confirmDeleteBadge = (badge) => {
    badgeToDelete.value = badge;
    isDeleteModalOpen.value = true;
};

const executeDeleteBadge = () => {
    if (!badgeToDelete.value) return;
    
    isDeleting.value = true;
    router.delete(route('admin.gamification.destroy', badgeToDelete.value.id), {
        onSuccess: () => {
            isDeleteModalOpen.value = false;
            badgeToDelete.value = null;
        },
        onFinish: () => isDeleting.value = false
    });
};
</script>

<template>
    <Head title="Kelola Gamifikasi" />

    <AppLayout>
        <div class="max-w-7xl mx-auto space-y-6 sm:space-y-8">
            
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                <div>
                    <h1 class="text-2xl sm:text-3xl font-bold text-gray-900">Gamifikasi</h1>
                    <p class="text-sm text-gray-500 mt-1">Kelola lencana, poin, dan kompetisi musiman</p>
                </div>
                <button @click="openCreate" class="w-full sm:w-auto bg-blue-900 text-white px-5 py-2.5 rounded-xl text-sm font-bold hover:bg-blue-800 flex items-center justify-center gap-2 shadow-sm transition active:scale-95">
                    <span class="text-lg leading-none">+</span> Tambah Lencana
                </button>
            </div>

            <div class="flex items-center gap-3 overflow-x-auto no-scrollbar pb-2">
                <button 
                    @click="activeTab = 'badges'"
                    class="flex items-center gap-2 px-6 py-3 rounded-full text-sm font-bold transition-all duration-300 ease-out transform active:scale-95 border whitespace-nowrap"
                    :class="activeTab === 'badges' 
                        ? 'bg-blue-900 text-white border-blue-900 shadow-lg shadow-blue-900/20' 
                        : 'bg-white text-gray-600 border-gray-200 hover:border-gray-300 hover:bg-gray-50'"
                >
                    <span>Lencana</span>
                    <span 
                        class="px-2 py-0.5 rounded-full text-xs transition-colors"
                        :class="activeTab === 'badges' ? 'bg-white/20 text-white' : 'bg-gray-100 text-gray-500'"
                    >
                        {{ badges.length }}
                    </span>
                </button>

                <button 
                    @click="activeTab = 'seasons'"
                    class="flex items-center gap-2 px-6 py-3 rounded-full text-sm font-bold transition-all duration-300 ease-out transform active:scale-95 border whitespace-nowrap"
                    :class="activeTab === 'seasons' 
                        ? 'bg-blue-900 text-white border-blue-900 shadow-lg shadow-blue-900/20' 
                        : 'bg-white text-gray-600 border-gray-200 hover:border-gray-300 hover:bg-gray-50'"
                >
                    <span>Musim</span>
                </button>

                <button 
                    @click="activeTab = 'rules'"
                    class="flex items-center gap-2 px-6 py-3 rounded-full text-sm font-bold transition-all duration-300 ease-out transform active:scale-95 border whitespace-nowrap"
                    :class="activeTab === 'rules' 
                        ? 'bg-blue-900 text-white border-blue-900 shadow-lg shadow-blue-900/20' 
                        : 'bg-white text-gray-600 border-gray-200 hover:border-gray-300 hover:bg-gray-50'"
                >
                    <span>Aturan Poin</span>
                </button>
            </div>

            <div v-if="activeTab === 'badges'" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <div v-for="badge in badges" :key="badge.id" class="bg-white p-6 rounded-xl border border-gray-200 shadow-sm hover:shadow-md transition relative group flex flex-col">
                    
                    <div class="flex items-start gap-4 mb-4">
                        <div class="w-16 h-16 rounded-full bg-gray-50 flex items-center justify-center shrink-0 overflow-hidden border border-gray-100 shadow-sm">
                            <img v-if="badge.icon_url" :src="'/storage/'+badge.icon_url" class="w-10 h-10 object-contain">
                            <span v-else class="text-4xl">{{ badge.icon_emoji || '🏅' }}</span>
                        </div>
                        
                        <div class="flex-1 min-w-0">
                            <div class="flex justify-between items-start">
                                <h3 class="font-bold text-gray-900 truncate pr-2">{{ badge.badge_name }}</h3>
                                <div class="flex gap-1 opacity-100 sm:opacity-0 group-hover:opacity-100 transition-opacity">
                                    <button @click="openEdit(badge)" class="p-1.5 text-gray-400 hover:text-blue-600 hover:bg-blue-50 rounded transition"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg></button>
                                    <button @click="confirmDeleteBadge(badge)" class="p-1.5 text-gray-400 hover:text-red-600 bg-gray-50 rounded hover:bg-red-50 transition" title="Hapus">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                    </button>                                
                                </div>
                            </div>
                            
                            <p class="text-xs text-gray-500 mt-1 leading-relaxed min-h-[40px] line-clamp-2">{{ badge.description }}</p>
                        </div>
                    </div>
                    
                    <div class="mt-auto pt-4 border-t border-gray-50 flex items-center justify-between text-xs">
                        <div class="flex gap-2">
                             <span class="px-2 py-1 bg-blue-50 text-blue-700 font-bold rounded-md border border-blue-100">
                                +{{ badge.bonus_points }} XP
                            </span>
                            <span class="px-2 py-1 bg-gray-100 text-gray-600 rounded-md border border-gray-200">
                                {{ badge.users_count }}x Diraih
                            </span>
                        </div>
                    </div>
                    
                    <div class="mt-2 text-[10px] text-gray-400 font-mono flex items-center gap-1">
                        <span class="uppercase font-bold">{{ badge.trigger_type.replace('_', ' ') }}</span>
                        <span>: {{ badge.trigger_value }}</span>
                    </div>
                </div>
            </div>

            <div v-else class="text-center py-20 bg-white rounded-xl border-2 border-dashed border-gray-200 text-gray-400">
                <div class="text-4xl mb-2 grayscale opacity-50">🚧</div>
                <p>Fitur {{ activeTab === 'seasons' ? 'Musim' : 'Aturan Poin' }} sedang dalam pengembangan.</p>
            </div>

        </div>

        <Teleport to="body">
            <div v-if="isModalOpen" class="fixed inset-0 z-[9999] flex items-center justify-center px-4">
                <div class="fixed inset-0 bg-gray-900/75 backdrop-blur-sm transition-opacity" @click="isModalOpen = false"></div>
                
                <div class="bg-white rounded-xl shadow-2xl w-full max-w-md p-6 relative z-10 max-h-[90vh] overflow-y-auto animate-fade-in-up">
                    <h3 class="text-xl font-bold text-gray-900 mb-6">{{ isEditMode ? 'Ubah Lencana' : 'Tambah Lencana Baru' }}</h3>
                    
                    <form @submit.prevent="submit" class="space-y-5">
                        
                        <div>
                            <label class="block text-xs font-bold text-gray-700 uppercase mb-2">Ikon Lencana</label>
                            <div class="flex gap-4 mb-4 bg-gray-50 p-1.5 rounded-lg border border-gray-200">
                                <label class="flex-1 flex items-center justify-center gap-2 cursor-pointer py-2 rounded-md transition" :class="form.icon_type === 'EMOJI' ? 'bg-white shadow-sm text-blue-700 font-bold' : 'text-gray-500 hover:text-gray-700'">
                                    <input type="radio" v-model="form.icon_type" value="EMOJI" class="hidden">
                                    <span>Emoji</span>
                                </label>
                                <label class="flex-1 flex items-center justify-center gap-2 cursor-pointer py-2 rounded-md transition" :class="form.icon_type === 'UPLOAD' ? 'bg-white shadow-sm text-blue-700 font-bold' : 'text-gray-500 hover:text-gray-700'">
                                    <input type="radio" v-model="form.icon_type" value="UPLOAD" class="hidden">
                                    <span>Unggah Gambar</span>
                                </label>
                            </div>

                            <div v-if="form.icon_type === 'EMOJI'" class="flex items-center gap-4 bg-gray-50 p-4 rounded-xl border border-gray-100">
                                <div class="w-16 h-16 rounded-full bg-white flex items-center justify-center text-4xl border border-gray-200 shadow-sm shrink-0">
                                    {{ form.icon_emoji }}
                                </div>
                                <div class="flex-1">
                                    <input v-model="form.icon_emoji" type="text" class="w-full rounded-lg border-gray-300 text-center text-2xl focus:ring-blue-500 focus:border-blue-500" placeholder="🏆">
                                    <p class="text-[10px] text-gray-500 mt-2 text-center">Tempel emoji di sini atau tekan <strong>Win + .</strong></p>
                                </div>
                            </div>

                            <div v-else class="flex justify-center bg-gray-50 p-4 rounded-xl border border-gray-100">
                                <div class="w-24 h-24 rounded-full bg-white border-2 border-dashed border-gray-300 flex items-center justify-center relative overflow-hidden hover:border-blue-500 transition cursor-pointer group shadow-sm">
                                    <img v-if="form.preview_url" :src="form.preview_url" class="w-full h-full object-cover opacity-90">
                                    <span v-else class="text-gray-400 text-2xl group-hover:text-blue-500">+</span>
                                    <input type="file" @change="handleFile" accept="image/*" class="absolute inset-0 opacity-0 cursor-pointer">
                                </div>
                            </div>
                            <p v-if="form.errors.icon || form.errors.icon_emoji" class="text-red-500 text-xs mt-2 text-center">{{ form.errors.icon || form.errors.icon_emoji }}</p>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Nama Lencana</label>
                            <input v-model="form.badge_name" type="text" class="w-full rounded-lg border-gray-300 text-sm focus:ring-blue-500 focus:border-blue-500" required placeholder="Contoh: Ahli Kode">
                            <p v-if="form.errors.badge_name" class="text-red-500 text-xs mt-1">{{ form.errors.badge_name }}</p>
                        </div>
                        
                        <div>
                            <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Deskripsi</label>
                            <textarea v-model="form.description" rows="2" class="w-full rounded-lg border-gray-300 text-sm focus:ring-blue-500 focus:border-blue-500" required placeholder="Contoh: Menyelesaikan 5 modul koding."></textarea>
                            <p v-if="form.errors.description" class="text-red-500 text-xs mt-1">{{ form.errors.description }}</p>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Bonus Poin (XP)</label>
                            <div class="relative">
                                <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-500 font-bold">+</span>
                                <input v-model="form.bonus_points" type="number" class="w-full pl-8 rounded-lg border-gray-300 text-sm focus:ring-blue-500 focus:border-blue-500">
                            </div>
                        </div>

                        <div class="bg-blue-50 p-4 rounded-lg border border-blue-100">
                            <div class="text-xs font-bold text-blue-800 uppercase mb-2">Syarat Pembukaan (Trigger)</div>
                            <div class="grid grid-cols-1 gap-3">
                                <div>
                                    <select v-model="form.trigger_type" class="w-full rounded-lg border-gray-300 text-xs focus:ring-blue-500 focus:border-blue-500">
                                        <option v-for="opt in triggerOptions" :key="opt.value" :value="opt.value">
                                            {{ opt.label }}
                                        </option>
                                    </select>
                                </div>
                                <div>
                                    <input v-model="form.trigger_value" type="text" class="w-full rounded-lg border-gray-300 text-xs focus:ring-blue-500 focus:border-blue-500" placeholder="Nilai Batas (e.g. 1000 atau 5)">
                                </div>
                            </div>
                            <p class="text-[10px] text-blue-600 mt-2 italic leading-tight">
                                * Contoh: Pilih “Total XP Tercapai” dan nilai “1000” untuk diberikan saat pengguna mendapatkan 1000 XP.
                            </p>
                        </div>

                        <div class="flex justify-end gap-3 mt-8 pt-4 border-t border-gray-100">
                            <button type="button" @click="isModalOpen = false" class="px-4 py-2 bg-white border border-gray-300 rounded-lg text-sm text-gray-700 hover:bg-gray-50 font-medium">Batal</button>
                            <button type="submit" class="px-4 py-2 bg-blue-900 text-white rounded-lg text-sm hover:bg-blue-800 shadow-sm font-medium transition" :disabled="form.processing">
                                {{ isEditMode ? 'Perbarui Lencana' : 'Simpan Lencana' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            <div v-if="isDeleteModalOpen" class="fixed inset-0 z-[10000] flex items-center justify-center px-4">
                
                <div class="fixed inset-0 bg-gray-900/75 backdrop-blur-sm transition-opacity" @click="isDeleteModalOpen = false"></div>
                
                <div class="bg-white rounded-xl shadow-2xl w-full max-w-sm p-6 relative z-10 text-center animate-bounce-in">
                    
                    <div class="mx-auto flex items-center justify-center h-14 w-14 rounded-full bg-red-100 mb-4">
                        <svg class="h-8 w-8 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                    </div>
                    
                    <h3 class="text-lg font-bold text-gray-900 mb-2">Hapus Lencana?</h3>
                    
                    <p class="text-sm text-gray-500 mb-6">
                        Apakah Anda yakin ingin menghapus lencana <strong>"{{ badgeToDelete?.badge_name }}"</strong>?
                        <br><span class="text-xs text-red-500 mt-1 block">Tindakan ini permanen dan tidak dapat dibatalkan.</span>
                    </p>
                    
                    <div class="flex justify-center gap-3">
                        <button 
                            @click="isDeleteModalOpen = false" 
                            class="px-4 py-2 bg-white border border-gray-300 rounded-lg text-sm text-gray-700 hover:bg-gray-50 transition font-medium"
                            :disabled="isDeleting"
                        >
                            Batal
                        </button>
                        
                        <button 
                            @click="executeDeleteBadge" 
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
.no-scrollbar::-webkit-scrollbar { display: none; }
.no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
.animate-fade-in-up { animation: fadeInUp 0.3s ease-out forwards; }
@keyframes fadeInUp { from { opacity: 0; transform: scale(0.95) translateY(10px); } to { opacity: 1; transform: scale(1) translateY(0); } }
.animate-bounce-in {
    animation: bounceIn 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275) forwards;
}
@keyframes bounceIn {
    0% { opacity: 0; transform: scale(0.9); }
    100% { opacity: 1; transform: scale(1); }
}
</style>