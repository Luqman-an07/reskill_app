<script setup>
import { ref, onMounted, onUnmounted, nextTick, computed } from 'vue';
import axios from 'axios';
import { usePage, router } from '@inertiajs/vue3';

const page = usePage();
const isOpen = ref(false);
const notifications = ref([]);
const buttonRef = ref(null);
const dropdownPosition = ref({ top: 0, right: 0, left: null });
const isLoading = ref(false);
const isMarkingAll = ref(false); // State khusus loading tombol mark-all

// Ambil Count Real-time
const unreadCount = computed(() => page.props.auth?.unread_notifications_count || 0);

// --- FETCH DATA ---
const fetchNotifications = async () => {
    isLoading.value = true;
    try {
        const response = await axios.get('/notifications');
        notifications.value = response.data.notifications;
    } catch (error) {
        console.error("Gagal load notifikasi", error);
    } finally {
        isLoading.value = false;
    }
};

// --- LOGIKA POSISI ---
const calculatePosition = () => {
    if (buttonRef.value) {
        const rect = buttonRef.value.getBoundingClientRect();
        const isMobile = window.innerWidth < 640; 
        if (isMobile) {
            dropdownPosition.value = { top: rect.bottom + 10, right: 16, left: 16 };
        } else {
            dropdownPosition.value = { top: rect.bottom + 12, right: window.innerWidth - rect.right, left: null };
        }
    }
};

const updatePosition = () => { if (isOpen.value) calculatePosition(); };

// --- ACTIONS ---

const toggleDropdown = async () => {
    isOpen.value = !isOpen.value;
    if (isOpen.value) {
        fetchNotifications();
        await nextTick();
        calculatePosition();
    }
};

const clickNotification = async (notif) => {
    if (notif.read_at) {
        isOpen.value = false;
        router.visit(notif.data.link);
        return;
    }
    try {
        notif.read_at = new Date().toISOString(); 
        await axios.patch(`/notifications/${notif.id}/read`);
        router.reload({ only: ['auth'] });
        isOpen.value = false;
        router.visit(notif.data.link);
    } catch (error) {
        router.visit(notif.data.link);
    }
};

// --- FITUR MARK ALL READ (DIPERBAIKI) ---
const markAllRead = async () => {
    if (unreadCount.value === 0 || isMarkingAll.value) return;

    isMarkingAll.value = true;
    try {
        // 1. Request Backend
        await axios.post('/notifications/read-all');
        
        // 2. Update Lokal (Visual List) agar langsung berubah jadi putih
        notifications.value.forEach(n => {
            if (!n.read_at) n.read_at = new Date().toISOString();
        });
        
        // 3. Update Badge Global (Tanpa Refresh)
        router.reload({ only: ['auth'] });
        
    } catch (error) {
        console.error("Gagal menandai baca semua", error);
    } finally {
        isMarkingAll.value = false;
    }
};

onMounted(() => {
    window.addEventListener('scroll', updatePosition, true);
    window.addEventListener('resize', updatePosition);
});
onUnmounted(() => {
    window.removeEventListener('scroll', updatePosition, true);
    window.removeEventListener('resize', updatePosition);
});
</script>

<template>
    <div class="relative">
        
        <button ref="buttonRef" @click="toggleDropdown" class="relative p-2 text-gray-400 hover:text-blue-600 transition rounded-full hover:bg-blue-50 focus:outline-none group">
            <svg class="w-6 h-6 group-hover:animate-swing" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" /></svg>
            
            <transition enter-active-class="transition ease-out duration-200" enter-from-class="transform scale-0" enter-to-class="transform scale-100" leave-active-class="transition ease-in duration-75" leave-from-class="transform scale-100" leave-to-class="transform scale-0">
                <span v-if="unreadCount > 0" class="absolute top-0 right-0 inline-flex items-center justify-center px-1.5 py-0.5 text-[10px] font-bold leading-none text-white bg-red-600 rounded-full border-2 border-white shadow-sm min-w-[20px]">
                    {{ unreadCount > 99 ? '99+' : unreadCount }}
                </span>
            </transition>
        </button>

        <Teleport to="body">
            <div v-if="isOpen" @click="isOpen = false" class="fixed inset-0 z-[99998] cursor-default bg-transparent"></div>

            <div 
                v-if="isOpen" 
                class="fixed bg-white rounded-xl shadow-2xl border border-gray-100 z-[99999] overflow-hidden origin-top-right animate-fade-in-up flex flex-col"
                :class="[ dropdownPosition.left ? 'left-4 right-4' : 'w-80 sm:w-96' ]"
                :style="{ top: dropdownPosition.top + 'px', right: dropdownPosition.right + 'px', ...(dropdownPosition.left ? { left: dropdownPosition.left + 'px' } : {}) }"
            >
                <div class="px-4 py-3 border-b border-gray-100 bg-white flex justify-between items-center shrink-0">
                    <div class="flex items-center gap-2">
                        <h3 class="text-sm font-bold text-gray-800">Notifikasi</h3>
                        <span v-if="unreadCount > 0" class="text-[10px] font-bold text-white bg-red-500 px-1.5 py-0.5 rounded-md shadow-sm animate-pulse">
                            {{ unreadCount }} Baru
                        </span>
                    </div>

                    <button 
                        v-if="unreadCount > 0" 
                        @click="markAllRead" 
                        class="flex items-center gap-1.5 px-2 py-1 text-xs font-medium text-blue-600 bg-blue-50 hover:bg-blue-100 rounded-lg transition active:scale-95 disabled:opacity-50 disabled:cursor-not-allowed"
                        :disabled="isMarkingAll"
                        title="Tandai semua sudah dibaca"
                    >
                        <svg v-if="isMarkingAll" class="animate-spin h-3.5 w-3.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                        
                        <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7m-7 6l4-4"/></svg>
                        
                        <span>Tandai dibaca</span>
                    </button>
                    
                    <span v-else class="text-[10px] text-gray-400 font-medium flex items-center gap-1">
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        Sudah dibaca semua
                    </span>
                </div>

                <div class="overflow-y-auto max-h-[60vh] sm:max-h-80 overscroll-contain bg-white">
                    <div v-if="isLoading && notifications.length === 0" class="p-8 text-center text-gray-400">
                        <svg class="animate-spin h-6 w-6 mx-auto mb-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                        <span class="text-xs">Memuat notifikasi...</span>
                    </div>

                    <div v-else-if="notifications.length === 0" class="p-8 text-center flex flex-col items-center justify-center text-gray-400 min-h-[150px]">
                        <div class="bg-gray-50 p-3 rounded-full mb-3">
                            <svg class="w-6 h-6 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path></svg>
                        </div>
                        <span class="text-sm font-medium">Belum ada notifikasi</span>
                    </div>

                    <div v-else>
                        <div 
                            v-for="notif in notifications" 
                            :key="notif.id" 
                            @click="clickNotification(notif)"
                            class="relative px-4 py-3.5 border-b border-gray-50 last:border-0 group cursor-pointer transition-all duration-200 hover:bg-gray-50"
                            :class="!notif.read_at ? 'bg-blue-50/30' : 'bg-white'"
                        >
                            <div v-if="!notif.read_at" class="absolute left-0 top-0 bottom-0 w-[3px] bg-blue-500"></div>

                            <div class="flex gap-3 items-start pl-1">
                                <div class="flex-shrink-0 w-9 h-9 rounded-full flex items-center justify-center text-lg overflow-hidden border transition mt-0.5"
                                    :class="!notif.read_at ? 'bg-white border-blue-200 text-blue-600 shadow-sm' : 'bg-gray-50 border-gray-200 text-gray-400'">
                                    <img v-if="notif.data.icon" :src="notif.data.icon.startsWith('http') ? notif.data.icon : '/storage/'+notif.data.icon" class="w-full h-full object-cover">
                                    <span v-else>📣</span>
                                </div>

                                <div class="flex-1 min-w-0">
                                    <div class="flex justify-between items-start gap-2">
                                        <p class="text-sm leading-tight pr-4" :class="!notif.read_at ? 'font-bold text-gray-900' : 'font-medium text-gray-600'">
                                            {{ notif.data.title }}
                                        </p>
                                        <span class="text-[10px] text-gray-400 whitespace-nowrap pt-0.5">
                                            {{ new Date(notif.created_at).toLocaleDateString('id-ID', { day: 'numeric', month: 'short' }) }}
                                        </span>
                                    </div>
                                    <p class="text-xs mt-1 leading-relaxed line-clamp-2" :class="!notif.read_at ? 'text-gray-700' : 'text-gray-400'">
                                        {{ notif.data.message }}
                                    </p>
                                </div>
                                
                                <div v-if="!notif.read_at" class="flex-shrink-0 self-center">
                                    <div class="w-2 h-2 rounded-full bg-blue-500 ring-2 ring-white"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </Teleport>
    </div>
</template>

<style scoped>
.animate-fade-in-up { animation: fadeInUp 0.2s cubic-bezier(0.16, 1, 0.3, 1) forwards; }
@keyframes fadeInUp { from { opacity: 0; transform: translateY(-8px) scale(0.98); } to { opacity: 1; transform: translateY(0) scale(1); } }
.animate-swing { animation: swing 2s ease-in-out infinite; }
@keyframes swing { 0%, 100% { transform: rotate(0deg); } 20% { transform: rotate(15deg); } 40% { transform: rotate(-10deg); } 60% { transform: rotate(5deg); } 80% { transform: rotate(-5deg); } }
::-webkit-scrollbar { width: 4px; }
::-webkit-scrollbar-track { background: transparent; }
::-webkit-scrollbar-thumb { background: #e5e7eb; border-radius: 10px; }
::-webkit-scrollbar-thumb:hover { background: #d1d5db; }
</style>