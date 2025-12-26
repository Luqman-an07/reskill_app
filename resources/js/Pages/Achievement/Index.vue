<script setup>
import { Head } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { ref } from 'vue';

const props = defineProps({
    stats: Object,
    earnedBadges: Array,
    lockedBadges: Array,
    xpBreakdown: Array,
});

// Tab State (Earned vs In Progress)
const activeTab = ref('earned');

// Helper untuk format tanggal
const formatDate = (dateString) => {
    if (!dateString) return '-';
    return new Date(dateString).toLocaleDateString('id-ID', { 
        day: 'numeric', month: 'long', year: 'numeric' 
    });
};
</script>

<template>
    <Head title="Penghargaan" />

    <AppLayout>
        <div class="max-w-7xl mx-auto space-y-6 sm:space-y-8">
            
            <div>
                <h1 class="text-2xl sm:text-3xl font-bold text-gray-900 flex items-center gap-2">
                    Penghargaan Kamu 🏆
                </h1>
                <p class="text-sm sm:text-base text-gray-500 mt-1">Lacak lencana, XP, dan pencapaian training kamu.</p>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-6">
                <div class="bg-white p-5 sm:p-6 rounded-xl border border-gray-200 shadow-sm flex items-center justify-between">
                    <div>
                        <p class="text-xs text-gray-500 font-bold uppercase tracking-wide">Total XP</p>
                        <h3 class="text-2xl sm:text-3xl font-bold text-gray-900 mt-1">{{ stats.total_xp.toLocaleString() }}</h3>
                    </div>
                    <div class="p-3 bg-green-50 text-green-600 rounded-lg">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" /></svg>
                    </div>
                </div>
                
                <div class="bg-white p-5 sm:p-6 rounded-xl border border-gray-200 shadow-sm flex items-center justify-between">
                    <div>
                        <p class="text-xs text-gray-500 font-bold uppercase tracking-wide">Lencana Diperoleh</p>
                        <h3 class="text-2xl sm:text-3xl font-bold text-gray-900 mt-1">{{ stats.badges_count }}</h3>
                    </div>
                    <div class="p-3 bg-teal-50 text-teal-600 rounded-lg">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z" /></svg>
                    </div>
                </div>

                <div class="bg-white p-5 sm:p-6 rounded-xl border border-gray-200 shadow-sm flex items-center justify-between">
                    <div>
                        <p class="text-xs text-gray-500 font-bold uppercase tracking-wide">Peringkat Saat Ini</p>
                        <h3 class="text-2xl sm:text-3xl font-bold text-gray-900 mt-1">#{{ stats.rank }}</h3>
                    </div>
                    <div class="p-3 bg-yellow-50 text-yellow-600 rounded-lg">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" /></svg>
                    </div>
                </div>

                <div class="bg-white p-5 sm:p-6 rounded-xl border border-gray-200 shadow-sm flex items-center justify-between">
                    <div>
                        <p class="text-xs text-gray-500 font-bold uppercase tracking-wide">Streak Hari</p>
                        <h3 class="text-2xl sm:text-3xl font-bold text-gray-900 mt-1">{{ stats.streak }}</h3>
                    </div>
                    <div class="p-3 bg-blue-50 text-blue-600 rounded-lg">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 18.657A8 8 0 016.343 7.343S7 9 9 10c0-2 .5-5 2.986-7C14 5 16.09 5.777 17.656 7.343A7.975 7.975 0 0120 13a7.975 7.975 0 01-2.343 5.657z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.879 16.121A3 3 0 1012.015 11L11 14H9c0 .768.293 1.536.879 2.121z" /></svg>
                    </div>
                </div>
            </div>

            <div class="space-y-6">
                
                <div class="flex items-center gap-3 overflow-x-auto no-scrollbar pb-2">
                    <button 
                        @click="activeTab = 'earned'"
                        class="flex items-center gap-2 px-6 py-3 rounded-full text-sm font-bold transition-all duration-300 ease-out transform active:scale-95 border whitespace-nowrap"
                        :class="activeTab === 'earned' 
                            ? 'bg-blue-900 text-white border-blue-900 shadow-lg shadow-blue-900/20' 
                            : 'bg-white text-gray-600 border-gray-200 hover:border-gray-300 hover:bg-gray-50'"
                    >
                        <span>Diperoleh</span>
                        <span 
                            class="px-2 py-0.5 rounded-full text-xs transition-colors"
                            :class="activeTab === 'earned' ? 'bg-green-500 text-white' : 'bg-green-100 text-green-700'"
                        >
                            {{ earnedBadges.length }}
                        </span>
                    </button>
                    
                    <button 
                        @click="activeTab = 'locked'"
                        class="flex items-center gap-2 px-6 py-3 rounded-full text-sm font-bold transition-all duration-300 ease-out transform active:scale-95 border whitespace-nowrap"
                        :class="activeTab === 'locked' 
                            ? 'bg-blue-900 text-white border-blue-900 shadow-lg shadow-blue-900/20' 
                            : 'bg-white text-gray-600 border-gray-200 hover:border-gray-300 hover:bg-gray-50'"
                    >
                        <span>Berlangsung</span>
                        <span 
                            class="px-2 py-0.5 rounded-full text-xs transition-colors"
                            :class="activeTab === 'locked' ? 'bg-white/20 text-white' : 'bg-gray-100 text-gray-500'"
                        >
                            {{ lockedBadges.length }}
                        </span>
                    </button>
                </div>

                <Transition
                    enter-active-class="transition ease-out duration-300"
                    enter-from-class="opacity-0 translate-y-4"
                    enter-to-class="opacity-100 translate-y-0"
                    leave-active-class="transition ease-in duration-200"
                    leave-from-class="opacity-100 translate-y-0"
                    leave-to-class="opacity-0 translate-y-4"
                    mode="out-in"
                >
                    <div v-if="activeTab === 'earned' && earnedBadges.length > 0" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 sm:gap-6" key="earned-list">
                        <div v-for="badge in earnedBadges" :key="badge.id" class="bg-white p-6 sm:p-8 rounded-xl border border-gray-200 shadow-sm flex flex-col items-center text-center hover:shadow-xl hover:-translate-y-1 transition-all duration-300">
                            <div class="w-16 h-16 sm:w-20 sm:h-20 rounded-full bg-green-50 flex items-center justify-center mb-4 overflow-hidden border border-green-100 relative">
                                <div class="absolute inset-0 bg-white opacity-20 animate-pulse"></div>
                                <img v-if="badge.icon_url" :src="'/storage/'+badge.icon_url" class="w-10 h-10 object-contain relative z-10">
                                <span v-else class="text-3xl sm:text-4xl relative z-10">{{ badge.icon_emoji || '🏆' }}</span>
                            </div>
                            <h4 class="font-bold text-gray-900 text-base sm:text-lg">{{ badge.badge_name }}</h4>
                            <p class="text-xs sm:text-sm text-gray-500 mt-1 mb-4 line-clamp-2">{{ badge.description }}</p>
                            
                            <span class="bg-green-100 text-green-700 text-[10px] sm:text-xs font-bold px-3 py-1 rounded-full border border-green-200 shadow-sm">
                                Diraih {{ formatDate(badge.pivot.achieved_at) }}
                            </span>
                        </div>
                    </div>

                    <div v-else-if="activeTab === 'earned'" class="flex flex-col items-center justify-center py-20 text-center bg-white rounded-3xl border-2 border-dashed border-gray-200" key="earned-empty">
                        <div class="w-20 h-20 bg-gray-50 rounded-full flex items-center justify-center mb-4 text-4xl grayscale opacity-40">🛡️</div>
                        <h3 class="text-lg font-bold text-gray-900">Belum ada lencana</h3>
                        <p class="text-gray-500 text-sm mt-1 max-w-xs mx-auto">Selesaikan modul dan kuis untuk mulai mengoleksi lencana!</p>
                    </div>

                    <div v-else-if="activeTab === 'locked' && lockedBadges.length > 0" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 sm:gap-6" key="locked-list">
                        <div v-for="badge in lockedBadges" :key="badge.id" class="bg-white p-6 sm:p-8 rounded-xl border border-gray-200 shadow-sm flex flex-col items-center text-center group relative overflow-hidden hover:shadow-md transition-all duration-300">
                            
                            <div class="w-16 h-16 sm:w-20 sm:h-20 rounded-full bg-gray-50 flex items-center justify-center mb-4 text-3xl sm:text-4xl grayscale opacity-60 group-hover:grayscale-0 group-hover:opacity-100 transition duration-500 border border-gray-100 group-hover:border-blue-100 group-hover:bg-blue-50">
                                 <img v-if="badge.icon_url" :src="'/storage/'+badge.icon_url" class="w-8 h-8 sm:w-10 sm:h-10 object-contain">
                                 <span v-else>{{ badge.icon_emoji || '🔒' }}</span>
                            </div>
                            
                            <h4 class="font-bold text-gray-900 text-base sm:text-lg group-hover:text-blue-700 transition">{{ badge.badge_name }}</h4>
                            <p class="text-xs sm:text-sm text-gray-500 mt-1 mb-4 line-clamp-2">{{ badge.description }}</p>
                            
                            <div class="w-full bg-gray-50 rounded-lg p-2 text-[10px] sm:text-xs text-gray-600 border border-gray-100 font-medium group-hover:bg-blue-50 group-hover:text-blue-700 group-hover:border-blue-100 transition-colors">
                                <span class="font-bold">Misi:</span> 
                                <span v-if="badge.trigger_type === 'POINTS_REACHED'">Capai {{ badge.trigger_value }} XP</span>
                                <span v-else-if="badge.trigger_type === 'QUIZ_PERFECT'">Nilai Kuis 100</span>
                                <span v-else-if="badge.trigger_type === 'COURSE_COMPLETE'">Selesaikan {{ badge.trigger_value }} Modul</span>
                                <span v-else-if="badge.trigger_type === 'TASK_COUNT'">Kumpulkan {{ badge.trigger_value }} Tugas</span>
                                <span v-else-if="badge.trigger_type === 'STREAK_DAYS'">Login {{ badge.trigger_value }} Hari</span>
                                <span v-else>Selesaikan Misi Rahasia</span>
                            </div>

                            <div class="absolute top-3 right-3 text-gray-300 group-hover:text-blue-300 transition">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" /></svg>
                            </div>
                        </div>
                    </div>
                    
                    <div v-else-if="activeTab === 'locked'" class="text-center py-20 bg-white rounded-3xl border-2 border-dashed border-gray-200" key="locked-empty">
                        <div class="text-5xl mb-4">🎉</div>
                        <h3 class="text-xl font-bold text-gray-900">Luar Biasa!</h3>
                        <p class="text-gray-500 text-sm mt-1">Kamu sudah membuka semua badge yang tersedia saat ini.</p>
                    </div>
                </Transition>
            </div>

            <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5 sm:p-6">
                <h3 class="font-bold text-gray-900 mb-4 sm:mb-6 text-lg flex items-center gap-2">
                    <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" /></svg>
                    Rincian XP
                </h3>
                
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-1 gap-3 sm:gap-4">
                    <div v-for="item in xpBreakdown" :key="item.label" 
                         class="flex items-center justify-between p-3 sm:p-4 bg-gray-50 border border-gray-100 rounded-xl hover:bg-blue-50 hover:border-blue-100 transition group"
                    >
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 sm:w-10 sm:h-10 rounded-full flex items-center justify-center flex-shrink-0 transition-colors"
                                 :class="{
                                     'bg-teal-100 text-teal-600 group-hover:bg-teal-200': item.icon_type === 'book',
                                     'bg-orange-100 text-orange-600 group-hover:bg-orange-200': item.icon_type === 'star',
                                     'bg-purple-100 text-purple-600 group-hover:bg-purple-200': item.icon_type === 'target',
                                     'bg-blue-100 text-blue-600 group-hover:bg-blue-200': item.icon_type === 'zap',
                                 }">
                                 <svg v-if="item.icon_type === 'book'" class="w-4 h-4 sm:w-5 sm:h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" /></svg>
                                 <svg v-else-if="item.icon_type === 'star'" class="w-4 h-4 sm:w-5 sm:h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.784.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z" /></svg>
                                 <svg v-else-if="item.icon_type === 'target'" class="w-4 h-4 sm:w-5 sm:h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                 <svg v-else class="w-4 h-4 sm:w-5 sm:h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" /></svg>
                            </div>
                            <span class="text-gray-700 font-bold text-xs sm:text-sm line-clamp-1">{{ item.label }}</span>
                        </div>
                        
                        <span class="bg-green-100 text-green-700 border border-green-200 px-2 py-1 rounded-lg text-[10px] sm:text-xs font-bold whitespace-nowrap shadow-sm group-hover:bg-green-200 transition">
                            +{{ item.amount }} XP
                        </span>
                    </div>
                </div>
            </div>

        </div>
    </AppLayout>
</template>

<style scoped>
.no-scrollbar::-webkit-scrollbar { display: none; }
.no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
</style>