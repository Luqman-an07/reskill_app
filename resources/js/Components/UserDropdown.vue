<script setup>
    import { ref, computed, onMounted, onUnmounted, nextTick } from 'vue';
    import { Link, usePage } from '@inertiajs/vue3';
    
    const user = usePage().props.auth.user;
    const isOpen = ref(false);
    const dropdownPosition = ref({ top: 0, right: 0 });
    const buttonRef = ref(null);
    
    const emit = defineEmits(['open-logout-modal']);
    
    // Helper: Initials
    const getInitials = (name) => {
        if (!name) return 'UR'; 
        return name.split(' ').map(n => n[0]).join('').substring(0, 2).toUpperCase();
    };
    
    // Cek Role
    const isParticipant = computed(() => {
        return user.roles && user.roles.some(r => r.name === 'peserta');
    });
    
    // Calculate dropdown position
    const calculatePosition = () => {
        if (buttonRef.value) {
            const rect = buttonRef.value.getBoundingClientRect();
            dropdownPosition.value = {
                top: rect.bottom + 12, // 12px = mt-3
                right: window.innerWidth - rect.right
            };
        }
    };
    
    // Toggle Dropdown
    const toggle = async () => {
        isOpen.value = !isOpen.value;
        if (isOpen.value) {
            await nextTick();
            calculatePosition();
        }
    };
    
    const close = () => isOpen.value = false;
    
    // Logout Action
    const triggerLogout = () => {
        isOpen.value = false;
        emit('open-logout-modal'); 
    };
    
    // Update position on scroll/resize
    const updatePosition = () => {
        if (isOpen.value) {
            calculatePosition();
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
            
            <button ref="buttonRef" @click="toggle" class="flex items-center gap-3 focus:outline-none transition group">
                
                <div class="text-right hidden md:block">
                    <p class="text-sm font-bold text-gray-900 group-hover:text-blue-700 transition">{{ user.name }}</p>
                    <p class="text-xs text-gray-500 font-medium flex items-center justify-end gap-1">
                        <span class="capitalize">{{ user.roles?.[0]?.name || 'User' }}</span>
                        <span v-if="user.department">• {{ user.department.department_code }}</span>
                    </p>
                </div>
    
                <div class="relative inline-block">
                    <div class="w-10 h-10 rounded-full overflow-hidden flex items-center justify-center text-sm font-bold border-2 border-gray-100 group-hover:border-blue-200 transition"
                         :class="user.profile_picture ? 'bg-gray-200' : 'bg-blue-100 text-blue-700'">
                        <img v-if="user.profile_picture" :src="`/storage/${user.profile_picture}`" class="w-full h-full object-cover" alt="Profile">
                        <span v-else>{{ getInitials(user.name) }}</span>
                    </div>
    
                    <span 
                        class="absolute bottom-0 right-0 block h-3 w-3 rounded-full ring-2 ring-white bg-green-500 animate-pulse"
                        title="Online"
                    ></span>
                </div>
                
                <svg class="w-4 h-4 text-gray-400 group-hover:text-gray-600 transition" :class="{'rotate-180': isOpen}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
            </button>
    
            <Teleport to="body">
                <div v-if="isOpen" @click="close" class="fixed inset-0 z-[99998] cursor-default"></div>
    
                <div 
                    v-if="isOpen" 
                    class="fixed w-64 bg-white rounded-xl shadow-xl border border-gray-100 z-[99999] overflow-hidden origin-top-right animate-fade-in-up"
                    :style="{
                        top: dropdownPosition.top + 'px',
                        right: dropdownPosition.right + 'px'
                    }"
                >
                
                <div class="px-6 py-4 border-b border-gray-50 bg-gray-50/50 text-center">
                    <div class="relative inline-block mx-auto mb-2">
                        <div class="w-16 h-16 rounded-full bg-blue-100 text-blue-700 flex items-center justify-center text-xl font-bold overflow-hidden border-2 border-white shadow-sm">
                            <img v-if="user.profile_picture" :src="`/storage/${user.profile_picture}`" class="w-full h-full object-cover">
                            <span v-else>{{ getInitials(user.name) }}</span>
                        </div>
                        
                        <span class="absolute bottom-1 right-1 block h-3.5 w-3.5 rounded-full ring-2 ring-white bg-green-500 animate-pulse"></span>
                    </div>
    
                    <p class="font-bold text-gray-900 truncate">{{ user.name }}</p>
                    <p class="text-xs text-gray-500">{{ user.email }}</p>
                </div>
    
                <div class="py-2">
                    <Link :href="route('profile.edit')" class="block px-6 py-2.5 text-sm text-gray-700 hover:bg-blue-50 hover:text-blue-600 transition flex items-center gap-3">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                        Profil Saya
                    </Link>
    
                    <Link v-if="isParticipant" :href="route('achievements')" class="block px-6 py-2.5 text-sm text-gray-700 hover:bg-yellow-50 hover:text-yellow-600 transition flex items-center gap-3">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"></path></svg>
                        Prestasi Saya
                    </Link>
                    
                    <a href="#" class="block px-6 py-2.5 text-sm text-gray-700 hover:bg-gray-50 transition flex items-center gap-3">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        Bantuan & Dukungan
                    </a>
                </div>
    
                <div class="border-t border-gray-100 my-1"></div>
    
                <button 
                    @click="triggerLogout" 
                    class="w-full text-left block px-6 py-3 text-sm text-red-600 hover:bg-red-50 transition flex items-center gap-3 font-medium"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path></svg>
                    Keluar
                </button>
                </div>
            </Teleport>
        </div>
    </template>
    
    <style scoped>
    .animate-fade-in-up {
        animation: fadeInUp 0.2s ease-out forwards;
    }
    @keyframes fadeInUp {
        from { opacity: 0; transform: translateY(-10px) scale(0.95); }
        to { opacity: 1; transform: translateY(0) scale(1); }
    }
    </style>