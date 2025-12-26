<script setup>
import { Head, Link, usePage, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { ref, computed } from 'vue';
import CourseThumbnail from '@/Components/CourseThumbnail.vue';

// --- PROPS DARI CONTROLLER ---
const props = defineProps({
    myCourses: Array,          // Kursus Aktif
    recommendedCourses: Array, // Kursus General (Jika User Baru)
    leaderboardGlobal: Array, 
    leaderboardDept: Array,
    userStats: Object,
    latestBadges: Array,
});

const user = usePage().props.auth.user;

// --- STATE ---
const leaderboardFilter = ref('global'); // 'global' atau 'dept'
const enrollingId = ref(null); // State loading enroll

// --- LOGIKA TAMPILAN ---

// 1. Sapaan Waktu
const greeting = computed(() => {
    const hour = new Date().getHours();
    if (hour < 11) return 'Selamat Pagi';
    if (hour < 15) return 'Selamat Siang';
    if (hour < 19) return 'Selamat Sore';
    return 'Selamat Malam';
});

// 2. Logika Kursus (Switching Logic)
const hasActiveCourses = computed(() => {
    return props.myCourses && props.myCourses.length > 0;
});

// Jika punya kursus aktif -> Tampilkan myCourses
// Jika TIDAK -> Tampilkan recommendedCourses
const displayCourses = computed(() => {
    if (hasActiveCourses.value) {
        return props.myCourses;
    }
    return props.recommendedCourses || [];
});

const courseSectionTitle = computed(() => {
    return hasActiveCourses.value ? 'Lanjutkan Pembelajaran' : 'Rekomendasi Untukmu';
});

// 3. Logika Leaderboard
const currentLeaderboard = computed(() => {
    return leaderboardFilter.value === 'global' 
        ? props.leaderboardGlobal 
        : props.leaderboardDept;
});

// Helper Initials
const getInitials = (name) => {
    if (!name) return 'UR';
    return name.split(' ').map(n => n[0]).join('').substring(0, 2).toUpperCase();
};

// Fungsi Enroll / Mulai Kursus
const enrollCourse = (courseId) => {
    enrollingId.value = courseId;
    // Gunakan route show, nanti di controller show logic firstOrCreate progress akan jalan
    router.get(route('course.show', courseId), {}, {
        onFinish: () => enrollingId.value = null
    });
};
</script>

<template>
    <Head title="Dashboard" />

    <AppLayout>
        <div class="max-w-7xl mx-auto space-y-6 sm:space-y-8">
            
            <div>
                <h1 class="text-2xl sm:text-3xl font-bold text-gray-900">
                    {{ greeting }}, {{ user.name.split(' ')[0] }}!
                </h1>
                <p class="text-sm sm:text-base text-gray-500 mt-1">
                    Siap untuk meningkatkan skill hari ini? Mari lanjutkan progresmu!
                </p>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-6">
                
                <div class="bg-white p-5 rounded-xl shadow-sm border border-gray-100 flex items-start justify-between hover:shadow-md transition group">
                    <div>
                        <p class="text-gray-500 text-xs font-bold uppercase tracking-wide">Total XP</p>
                        <h3 class="text-2xl font-bold text-gray-900 mt-1 group-hover:text-blue-600 transition">
                            {{ userStats?.total_xp?.toLocaleString() || 0 }}
                        </h3>
                        <span class="text-[10px] sm:text-xs text-green-600 font-medium bg-green-50 px-2 py-0.5 rounded mt-2 inline-block">
                            +{{ userStats?.seasonal_points || 0 }} musim ini
                        </span>
                    </div>
                    <div class="p-2 bg-green-50 text-green-600 rounded-lg group-hover:scale-110 transition">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" /></svg>
                    </div>
                </div>

                <div class="bg-white p-5 rounded-xl shadow-sm border border-gray-100 flex items-start justify-between hover:shadow-md transition group">
                    <div>
                        <p class="text-gray-500 text-xs font-bold uppercase tracking-wide">Kursus Selesai</p>
                        <h3 class="text-2xl font-bold text-gray-900 mt-1 group-hover:text-blue-600 transition">
                            {{ userStats?.completed_courses || 0 }}
                        </h3>
                        <span class="text-[10px] sm:text-xs text-blue-600 font-medium bg-blue-50 px-2 py-0.5 rounded mt-2 inline-block">
                            {{ userStats?.in_progress_courses || 0 }} Sedang Berjalan
                        </span>
                    </div>
                    <div class="p-2 bg-blue-50 text-blue-600 rounded-lg group-hover:scale-110 transition">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" /></svg>
                    </div>
                </div>

                <div class="bg-white p-5 rounded-xl shadow-sm border border-gray-100 flex items-start justify-between hover:shadow-md transition group">
                    <div>
                        <p class="text-gray-500 text-xs font-bold uppercase tracking-wide">Lencana</p>
                        <h3 class="text-2xl font-bold text-gray-900 mt-1 group-hover:text-blue-600 transition">
                            {{ userStats?.badges_count || 0 }}
                        </h3>
                        <span class="text-[10px] sm:text-xs text-gray-400 mt-2 inline-block">Total Penghargaan</span>
                    </div>
                    <div class="p-2 bg-yellow-50 text-yellow-600 rounded-lg group-hover:scale-110 transition">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z" /></svg>
                    </div>
                </div>

                <div class="bg-white p-5 rounded-xl shadow-sm border border-gray-100 flex items-start justify-between hover:shadow-md transition group">
                    <div>
                        <p class="text-gray-500 text-xs font-bold uppercase tracking-wide">Peringkat Global</p>
                        <h3 class="text-2xl font-bold text-gray-900 mt-1 group-hover:text-blue-600 transition">
                            #{{ userStats?.rank || '-' }}
                        </h3>
                        <span class="text-[10px] sm:text-xs text-purple-600 font-medium bg-purple-50 px-2 py-0.5 rounded mt-2 inline-block">
                            Peserta Terbaik
                        </span>
                    </div>
                    <div class="p-2 bg-purple-50 text-purple-600 rounded-lg group-hover:scale-110 transition">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" /></svg>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 lg:gap-8">
                
                <div class="lg:col-span-2 space-y-6">
                    <div class="flex items-center justify-between">
                        <h3 class="text-lg font-bold text-gray-900">{{ courseSectionTitle }}</h3>
                        <Link :href="route('my-courses')" class="text-sm text-blue-600 hover:text-blue-800 font-medium hover:underline">
                            Lihat Semua Kursus →
                        </Link>
                    </div>

                    <div v-if="displayCourses.length > 0" class="space-y-4">
                        <div v-for="course in displayCourses" :key="course.id" 
                            class="bg-white p-4 rounded-xl shadow-sm border border-gray-100 flex flex-col sm:flex-row gap-4 items-start sm:items-center hover:shadow-md transition group relative overflow-hidden">
                            
                            <div class="w-full sm:w-32 h-32 sm:h-24 rounded-lg flex-shrink-0 overflow-hidden shadow-sm relative">
                                <CourseThumbnail 
                                    :image="course.image_path" 
                                    :title="course.title" 
                                    :id="course.id"
                                    class-name="group-hover:scale-105 transition-transform duration-700" 
                                />
                                <div v-if="!hasActiveCourses" class="absolute top-0 left-0 bg-green-500 text-white text-[10px] px-1.5 py-0.5 rounded-br-lg font-bold shadow-sm z-10">BARU</div>
                            </div>

                            <div class="flex-1 min-w-0 w-full">
                                <h4 class="font-bold text-gray-900 text-lg truncate group-hover:text-blue-700 transition" :title="course.title">
                                    {{ course.title }}
                                </h4>
                                <p class="text-sm text-gray-500 mb-3 truncate">
                                    {{ course.description || 'Tidak ada deskripsi.' }}
                                </p>
                                
                                <div v-if="hasActiveCourses">
                                    <div class="w-full bg-gray-100 rounded-full h-2 mb-2">
                                        <div class="bg-blue-900 h-2 rounded-full transition-all duration-1000" :style="{ width: course.progress + '%' }"></div>
                                    </div>
                                    
                                    <div class="flex justify-between items-center text-xs">
                                        <span class="text-gray-400 font-medium">Progres {{ course.progress }}%</span>
                                        
                                        <div class="flex items-center gap-1 scale-90 origin-right">
                                            <div class="flex items-center gap-1 bg-gray-50 text-gray-600 px-1.5 py-0.5 rounded-l border border-gray-100" title="Total XP Modul">
                                                <svg class="w-3 h-3 text-blue-500" fill="currentColor" viewBox="0 0 20 20"><path d="M11.3 1.046A1 1 0 0112 2v5h4a1 1 0 01.82 1.573l-7 10A1 1 0 018 18v-5H4a1 1 0 01-.82-1.573l7-10a1 1 0 011.12-.38z"/></svg>
                                                <span class="font-bold">{{ course.module_total_points }}</span>
                                            </div>
                                            <div v-if="course.course_bonus_points > 0" class="flex items-center gap-1 bg-yellow-50 text-yellow-700 px-1.5 py-0.5 rounded-r border border-yellow-100 border-l-0 -ml-1">
                                                <span class="text-gray-300 mx-0.5 text-[9px]">+</span>
                                                <svg class="w-3 h-3 text-yellow-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 2a1 1 0 011 1v1.323l3.954 1.582 1.699-3.181a1 1 0 011.827 1.035l1.184 5.922 1.628 3.255a1 1 0 01-1.077 1.417l-3.385.847-.847 3.385a1 1 0 01-1.417 1.077l-3.255-1.628-5.922 1.184a1 1 0 01-1.035-1.827L6.677 15H5.414l-3.181 1.699a1 1 0 01-1.417-1.077l.847-3.385-3.385-.847a1 1 0 01-1.077-1.417l1.628-3.255 1.184-5.922a1 1 0 011.827-1.035L5.677 4.323V3a1 1 0 011-1h6zM6 9a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd"/></svg>
                                                <span class="font-bold">{{ course.course_bonus_points }}</span>
                                            </div>
                                            <div v-else class="bg-gray-50 text-gray-600 px-1.5 py-0.5 rounded-r border border-gray-100 border-l-0 -ml-1 font-medium">XP</div>
                                        </div>
                                    </div>
                                </div>

                                <div v-else>
                                    <div class="flex gap-3 text-xs text-gray-500 font-medium items-center">
                                        <span class="flex items-center gap-1">
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" /></svg> 
                                            {{ course.modules_count }} Modul
                                        </span>
                                        
                                        <div class="flex items-center gap-1">
                                            <div class="flex items-center gap-1 bg-gray-50 text-gray-600 px-1.5 py-0.5 rounded-l border border-gray-100" title="Total XP Modul">
                                                <svg class="w-3 h-3 text-blue-500" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                                                <span class="font-bold">{{ course.module_total_points }}</span>
                                            </div>
                                            <div v-if="course.course_bonus_points > 0" class="flex items-center gap-1 bg-yellow-50 text-yellow-700 px-1.5 py-0.5 rounded-r border border-yellow-100 border-l-0 -ml-1">
                                                <span class="text-gray-300 mx-0.5 text-[9px]">+</span>
                                                <svg class="w-3 h-3 text-yellow-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M11.3 1.046A1 1 0 0112 2v5h4a1 1 0 01.82 1.573l-7 10A1 1 0 018 18v-5H4a1 1 0 01-.82-1.573l7-10a1 1 0 011.12-.38z" clip-rule="evenodd"/></svg>
                                                <span class="font-bold">{{ course.course_bonus_points }}</span>
                                            </div>
                                            <div v-else class="bg-gray-50 text-gray-600 px-1.5 py-0.5 rounded-r border border-gray-100 border-l-0 -ml-1 font-medium">XP</div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="w-full sm:w-auto mt-2 sm:mt-0">
                                <button 
                                    v-if="!hasActiveCourses"
                                    @click="enrollCourse(course.id)"
                                    :disabled="enrollingId === course.id"
                                    class="w-full bg-gray-900 text-white px-5 py-2.5 rounded-lg text-sm font-bold hover:bg-gray-800 transition shadow-sm relative z-20 flex items-center justify-center gap-2"
                                >
                                    <svg v-if="enrollingId === course.id" class="animate-spin h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                    {{ enrollingId === course.id ? 'Mendaftar...' : 'Mulai' }}
                                </button>
                                <Link 
                                    v-else 
                                    :href="route('course.show', course.id)" 
                                    class="inline-flex justify-center w-full sm:w-auto items-center px-5 py-2.5 bg-blue-900 text-white rounded-lg text-sm font-bold hover:bg-blue-800 transition shadow-sm relative z-20"
                                >
                                    Lanjutkan
                                </Link>
                            </div>
                            
                            <Link :href="route('course.show', course.id)" class="absolute inset-0 z-10"></Link>
                        </div>
                    </div>

                    <div v-else class="text-center py-16 bg-white rounded-xl border border-dashed border-gray-300">
                        <div class="text-5xl mb-4 grayscale opacity-50">📭</div>
                        <h3 class="text-lg font-medium text-gray-900">Belum ada kursus</h3>
                        <p class="text-gray-500 text-sm mt-1">Tidak ada kursus yang tersedia saat ini.</p>
                    </div>
                </div>

                <div class="space-y-8">
                    
                    <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-lg font-bold text-gray-900 flex items-center gap-2">🏆 Papan Peringkat</h3>
                            <div class="bg-gray-100 p-1 rounded-lg flex text-[10px] font-bold">
                                <button @click="leaderboardFilter = 'global'" class="px-2 py-1 rounded-md transition-all" :class="leaderboardFilter === 'global' ? 'bg-white text-blue-700 shadow-sm' : 'text-gray-500 hover:text-gray-700'">Global</button>
                                <button @click="leaderboardFilter = 'dept'" class="px-2 py-1 rounded-md transition-all" :class="leaderboardFilter === 'dept' ? 'bg-white text-blue-700 shadow-sm' : 'text-gray-500 hover:text-gray-700'">Dept</button>
                            </div>
                        </div>
                        
                        <div class="space-y-3">
                            <div v-for="(person, idx) in currentLeaderboard" :key="person.id" 
                                 class="flex items-center justify-between p-2 rounded-lg transition"
                                 :class="person.id === user.id ? 'bg-blue-50 border border-blue-100' : 'hover:bg-gray-50'">
                                <div class="flex items-center gap-3">
                                    <span class="text-sm font-bold w-5 text-center" :class="person.id === user.id ? 'text-blue-600' : 'text-gray-400'">{{ idx + 1 }}</span>
                                    <div class="w-8 h-8 rounded-full overflow-hidden bg-gray-200 flex items-center justify-center text-xs font-bold text-gray-500 border border-gray-100">
                                        <img v-if="person.profile_picture" :src="'/storage/' + person.profile_picture" class="w-full h-full object-cover">
                                        <span v-else>{{ getInitials(person.name) }}</span>
                                    </div>
                                    <div>
                                        <p class="text-sm font-bold line-clamp-1 w-24 sm:w-28" :title="person.name">{{ person.id === user.id ? 'Kamu' : person.name }}</p>
                                        <p class="text-[10px] text-gray-500">{{ person.total_points }} XP</p>
                                    </div>
                                </div>
                                <span v-if="idx === 0" class="text-lg">🥇</span>
                                <span v-else-if="idx === 1" class="text-lg">🥈</span>
                                <span v-else-if="idx === 2" class="text-lg">🥉</span>
                            </div>
                            <div v-if="currentLeaderboard.length === 0" class="text-center py-4 text-xs text-gray-400">Belum ada data peringkat.</div>
                        </div>
                        
                        <div class="mt-4 text-center">
                             <Link :href="route('leaderboard', { filter: leaderboardFilter === 'dept' ? 'department' : 'global' })" class="text-xs text-blue-600 font-bold hover:underline">Lihat Selengkapnya →</Link>
                        </div>
                    </div>

                    <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-lg font-bold text-gray-900 flex items-center gap-2">🎖 Prestasi Terbaru</h3>
                            <span class="text-xs text-gray-400">{{ userStats?.badges_count || 0 }} Diraih</span>
                        </div>
                        
                        <div class="grid grid-cols-3 gap-3">
                            <div v-for="badge in latestBadges" :key="badge.id" class="aspect-square bg-yellow-50 rounded-xl flex flex-col items-center justify-center p-2 border border-yellow-100 hover:shadow-md transition group cursor-pointer" :title="badge.badge_name">
                                <div class="w-8 h-8 sm:w-10 sm:h-10 flex items-center justify-center mb-1 overflow-hidden">
                                    <img v-if="badge.icon_url" :src="'/storage/'+badge.icon_url" class="w-full h-full object-contain drop-shadow-sm">
                                    <span v-else class="text-2xl sm:text-3xl leading-none">{{ badge.icon_emoji || '🏆' }}</span>
                                </div>
                                <span class="text-[9px] sm:text-[10px] font-bold text-yellow-700 text-center leading-tight line-clamp-1 w-full">{{ badge.badge_name }}</span>
                            </div>
                            <div v-for="n in Math.max(0, 3 - (latestBadges?.length || 0))" :key="n" class="aspect-square bg-gray-50 rounded-xl flex items-center justify-center border-2 border-dashed border-gray-200"><span class="text-gray-300 text-xl sm:text-2xl opacity-50">🔒</span></div>
                        </div>
                        
                        <div class="mt-4 text-center">
                             <Link :href="route('achievements')" class="text-xs text-blue-600 font-bold hover:underline">Lihat Semua Prestasi →</Link>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </AppLayout>
</template>