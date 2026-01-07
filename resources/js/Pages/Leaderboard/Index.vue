<script setup>
    import { Head, Link, router } from '@inertiajs/vue3';
    import AppLayout from '@/Layouts/AppLayout.vue';
    
    const props = defineProps({
        leaderboard: Array,
        myRank: [Number, String], 
        currentFilter: String,
        userStats: Object,
    });
    
    // Fungsi Ganti Tab (Global / Department)
    const setFilter = (filter) => {
        router.get(route('leaderboard'), { filter }, { preserveState: true, preserveScroll: true });
    };
    
    // Helper untuk Warna Ranking 1, 2, 3
    const getRankIcon = (rank) => {
        if (rank === 1) return '🥇';
        if (rank === 2) return '🥈';
        if (rank === 3) return '🥉';
        return rank;
    };
    
    // Helper untuk Inisial Avatar
    const getInitials = (name) => {
        if (!name) return 'UR'; // Default jika nama kosong
        return name.split(' ').map(n => n[0]).join('').substring(0, 2).toUpperCase();
    };
    </script>
    
    <template>
        <Head title="Papan Peringkat" />
    
        <AppLayout>
            <div class="max-w-7xl mx-auto space-y-6 sm:space-y-8">
                
                <div>
                    <h1 class="text-2xl sm:text-3xl font-bold text-gray-900 flex items-center gap-2">
                        Papan Peringkat 
                    </h1>
                    <p class="text-sm sm:text-base text-gray-500 mt-1">Lihat bagaimana peringkat Kamu dibandingkan dengan peserta lainnya.</p>
                </div>
    
                <div class="bg-green-50 border border-green-100 rounded-xl p-5 sm:p-6 flex flex-col sm:flex-row items-center justify-between gap-4 shadow-sm text-center sm:text-left">
                    <div class="flex flex-col sm:flex-row items-center gap-3 sm:gap-4">
                        <div class="w-12 h-12 sm:w-14 sm:h-14 bg-green-200 rounded-lg flex items-center justify-center text-2xl sm:text-3xl text-green-700 shrink-0">
                            🏆
                        </div>
                        <div>
                            <p class="text-gray-500 text-xs sm:text-sm font-medium">Peringkat Kamu Saat Ini</p>
                            <div class="flex items-center justify-center sm:justify-start gap-2 sm:gap-3 mt-1">
                                <h2 class="text-2xl sm:text-3xl font-bold text-gray-900">#{{ myRank }}</h2>
                                <span class="bg-green-500 text-white text-[10px] sm:text-xs font-bold px-2 py-1 rounded-full flex items-center gap-1">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 10l7-7m0 0l7 7m-7-7v18" /></svg>
                                    Peserta Terbaik
                                </span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="w-full h-px bg-green-200 sm:hidden"></div>
    
                    <div class="text-center sm:text-right w-full sm:w-auto">
                        <p class="text-gray-500 text-xs uppercase font-bold tracking-wide">Total XP</p>
                        <p class="text-2xl sm:text-3xl font-bold text-green-600">{{ userStats.total_xp.toLocaleString() }}</p>
                    </div>
                </div>
    
                <div class="flex flex-wrap gap-2 border-b border-gray-200 pb-4">
                    <button 
                        @click="setFilter('global')"
                        :class="['px-4 sm:px-5 py-2 rounded-full text-xs sm:text-sm font-medium transition-all flex items-center gap-2', 
                            currentFilter === 'global' 
                            ? 'bg-blue-900 text-white shadow-md' 
                            : 'bg-white text-gray-600 border border-gray-200 hover:bg-gray-50']"
                    >
                        Global
                    </button>
                    <button 
                        @click="setFilter('department')"
                        :class="['px-4 sm:px-5 py-2 rounded-full text-xs sm:text-sm font-medium transition-all flex items-center gap-2', 
                            currentFilter === 'department' 
                            ? 'bg-blue-900 text-white shadow-md' 
                            : 'bg-white text-gray-600 border border-gray-200 hover:bg-gray-50']"
                    >
                        Departemen Saya
                    </button>
                </div>
    
                <div class="bg-white border border-gray-200 rounded-xl shadow-sm overflow-hidden">
                    <div v-for="(user, index) in leaderboard" :key="user.id" 
                         class="flex items-center justify-between p-3 sm:p-4 border-b border-gray-100 last:border-0 hover:bg-gray-50 transition">
                        
                        <div class="flex items-center gap-3 sm:gap-6 flex-1 min-w-0">
                            <div class="w-6 sm:w-8 text-center font-bold text-base sm:text-lg shrink-0" 
                                 :class="{
                                     'text-yellow-500 text-xl sm:text-2xl': user.rank === 1,
                                     'text-gray-400 text-xl sm:text-2xl': user.rank === 2,
                                     'text-orange-500 text-xl sm:text-2xl': user.rank === 3,
                                     'text-blue-900': user.rank > 3
                                 }">
                                {{ getRankIcon(user.rank) }}
                            </div>
    
                            <div class="relative inline-block shrink-0">
                                <div class="w-8 h-8 sm:w-10 sm:h-10 rounded-full flex items-center justify-center text-xs sm:text-sm font-bold text-gray-500 bg-gray-200 border border-gray-100 overflow-hidden">
                                    <img v-if="user.profile_picture" :src="'/storage/' + user.profile_picture" class="w-full h-full object-cover">
                                    <span v-else>{{ getInitials(user.name) }}</span>
                                </div>
                                
                                <span 
                                    v-if="user.is_online" 
                                    class="absolute bottom-0 right-0 block h-2.5 w-2.5 sm:h-3 sm:w-3 rounded-full bg-green-500 ring-2 ring-white animate-pulse"
                                    title="Sedang Online"
                                ></span>
                            </div>
    
                            <div class="min-w-0 flex-1">
                                <h4 class="font-bold text-gray-900 text-sm sm:text-base truncate pr-2">
                                    {{ user.name }}
                                </h4>
                                <p class="text-[10px] sm:text-xs text-gray-500 truncate">
                                    {{ user.department ? user.department.department_name : 'General' }}
                                </p>
                            </div>
                        </div>
    
                        <div class="flex items-center gap-2 sm:gap-8 shrink-0">
                            <div class="text-right">
                                <p class="font-bold text-gray-900 text-sm sm:text-base">{{ user.total_points.toLocaleString() }}</p>
                                <p class="text-[8px] sm:text-[10px] text-gray-400 uppercase">XP</p>
                            </div>
                            
                            <div class="w-8 sm:w-12 text-right text-[10px] sm:text-xs font-bold">
                                <span v-if="user.trend > 0" class="text-green-500 flex items-center justify-end gap-0.5 sm:gap-1">
                                    <svg class="w-2.5 h-2.5 sm:w-3 sm:h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" /></svg>
                                    {{ user.trend }}
                                </span>
                                
                                <span v-else-if="user.trend < 0" class="text-red-500 flex items-center justify-end gap-0.5 sm:gap-1">
                                    <svg class="w-2.5 h-2.5 sm:w-3 sm:h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M13 17h8m0 0V9m0 8l-8-8-4 4-6-6" /></svg>
                                    {{ Math.abs(user.trend) }}
                                </span>
                                
                                <span v-else class="text-gray-400 flex items-center justify-end gap-1">
                                    <svg class="w-2.5 h-2.5 sm:w-3 sm:h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M20 12H4" /></svg>
                                    -
                                </span>
                            </div>
                        </div>
                    </div>
                    
                    <div v-if="leaderboard.length === 0" class="p-8 text-center text-gray-500 text-sm">
                        Belum ada data papan peringkat untuk kategori ini.
                    </div>
                </div>
    
                <div>
                    <h3 class="font-bold text-gray-900 mb-4 text-lg">Statistik Musim Ini</h3>
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-3 sm:gap-4">
                        <div class="bg-white p-3 sm:p-4 rounded-xl border border-gray-100 shadow-sm text-center">
                            <p class="text-xl sm:text-2xl font-bold text-green-600">{{ userStats.total_xp.toLocaleString() }}</p>
                            <p class="text-[10px] sm:text-xs text-gray-500 uppercase font-semibold">Total XP</p>
                        </div>
                        <div class="bg-white p-3 sm:p-4 rounded-xl border border-gray-100 shadow-sm text-center">
                            <p class="text-xl sm:text-2xl font-bold text-blue-600">{{ userStats.badges }}</p>
                            <p class="text-[10px] sm:text-xs text-gray-500 uppercase font-semibold">Prestasi</p>
                        </div>
                        <div class="bg-white p-3 sm:p-4 rounded-xl border border-gray-100 shadow-sm text-center">
                            <p class="text-xl sm:text-2xl font-bold text-blue-400">{{ userStats.streak }}</p>
                            <p class="text-[10px] sm:text-xs text-gray-500 uppercase font-semibold">Streak Hari</p>
                        </div>
                        <div class="bg-white p-3 sm:p-4 rounded-xl border border-gray-100 shadow-sm text-center">
                            <p class="text-xl sm:text-2xl font-bold text-orange-500">{{ userStats.completed }}</p>
                            <p class="text-[10px] sm:text-xs text-gray-500 uppercase font-semibold">Selesai</p>
                        </div>
                    </div>
                </div>
    
            </div>
        </AppLayout>
    </template>