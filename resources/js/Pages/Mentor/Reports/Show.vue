<script setup>
    import { Head, Link } from '@inertiajs/vue3';
    import AppLayout from '@/Layouts/AppLayout.vue';
    
    const props = defineProps({
        student: Object, // Berisi data user + is_online
        courses: Array,
        stats: Object,
    });
    
    // Helper Initials
    const getInitials = (name) => {
        if (!name) return '??';
        const parts = name.trim().split(/\s+/);
        if (parts.length === 1) return parts[0].substring(0, 2).toUpperCase();
        return (parts[0][0] + parts[parts.length - 1][0]).toUpperCase();
    };
    </script>
    
    <template>
        <Head :title="`Laporan: ${student.name}`" />
    
        <AppLayout>
            <div class="max-w-7xl mx-auto space-y-8">
                
                <nav class="flex items-center text-sm font-medium text-gray-500">
                    <Link :href="route('mentor.reports.index')" class="hover:text-blue-600 transition">Laporan</Link>
                    <svg class="w-4 h-4 mx-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                    <span class="text-gray-900 font-bold">Detail Peserta</span>
                </nav>
    
                <div class="bg-white rounded-2xl p-6 sm:p-8 border border-gray-200 shadow-sm relative overflow-hidden">
                    <div class="absolute top-0 right-0 -mt-10 -mr-10 w-40 h-40 bg-blue-50 rounded-full blur-3xl opacity-50 pointer-events-none"></div>
    
                    <div class="flex flex-col lg:flex-row gap-8 items-start lg:items-center relative z-10">
                        
                        <div class="relative shrink-0 mx-auto lg:mx-0">
                            <div class="w-24 h-24 sm:w-28 sm:h-28 rounded-full bg-gray-100 border-4 border-white shadow-lg flex items-center justify-center text-3xl font-bold text-gray-400 overflow-hidden">
                                <img v-if="student.profile_picture" :src="'/storage/' + student.profile_picture" class="w-full h-full object-cover">
                                <span v-else>{{ getInitials(student.name) }}</span>
                            </div>
                            
                            <div v-if="student.is_online" class="absolute bottom-1 right-1 sm:bottom-2 sm:right-2">
                                <span class="relative flex h-5 w-5">
                                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-green-400 opacity-75"></span>
                                    <span class="relative inline-flex rounded-full h-5 w-5 bg-green-500 border-2 border-white"></span>
                                </span>
                            </div>
                        </div>
    
                        <div class="flex-1 text-center lg:text-left space-y-2 w-full">
                            <div class="flex flex-col lg:flex-row items-center gap-3 justify-center lg:justify-start">
                                <h1 class="text-2xl sm:text-3xl font-bold text-gray-900">{{ student.name }}</h1>
                                
                                <span v-if="student.is_online" class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-green-100 text-green-700 border border-green-200 flex items-center gap-1">
                                    ● Online
                                </span>
                                <span v-else class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-gray-100 text-gray-500 border border-gray-200">
                                    Offline
                                </span>
                            </div>
    
                            <p class="text-gray-500 font-medium">{{ student.email }}</p>
                            
                            <div class="flex flex-wrap items-center justify-center lg:justify-start gap-2 pt-1">
                                <span v-if="student.department" class="px-3 py-1 bg-blue-50 text-blue-700 text-xs font-bold rounded-lg border border-blue-100 flex items-center gap-1">
                                    🏢 {{ student.department.department_name }}
                                </span>
                                <div class="h-4 w-px bg-gray-300 hidden sm:block"></div>
                                <span class="text-xs text-gray-500">Bergabung {{ new Date(student.created_at).toLocaleDateString('id-ID', {year: 'numeric', month: 'long'}) }}</span>
                            </div>
    
                            <div class="flex flex-wrap justify-center lg:justify-start gap-2 mt-3">
                                <div v-for="badge in student.badges.slice(0, 3)" :key="badge.id" class="flex items-center gap-1 bg-yellow-50 border border-yellow-200 px-2 py-1 rounded-md text-[10px] text-yellow-800 font-bold shadow-sm" :title="badge.name">
                                    🏅 {{ badge.name }}
                                </div>
                                <span v-if="student.badges.length > 3" class="text-xs text-gray-400 self-center">+{{ student.badges.length - 3 }} lainnya</span>
                            </div>
                        </div>
    
                        <div class="grid grid-cols-2 gap-4 w-full lg:w-auto">
                            <div class="bg-white p-4 rounded-xl border border-gray-100 shadow-sm text-center min-w-[120px]">
                                <p class="text-xs font-bold text-gray-400 uppercase tracking-wider">Total XP</p>
                                <p class="text-2xl font-black text-yellow-500 mt-1">{{ stats.total_xp.toLocaleString() }}</p>
                            </div>
                            <div class="bg-white p-4 rounded-xl border border-gray-100 shadow-sm text-center min-w-[120px]">
                                <p class="text-xs font-bold text-gray-400 uppercase tracking-wider">Rata-rata</p>
                                <p class="text-2xl font-black text-blue-600 mt-1">{{ stats.avg_global_score }}%</p>
                            </div>
                        </div>
                    </div>
                </div>
    
                <div class="space-y-6">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-gray-200 pb-4">
                        <h3 class="text-xl font-bold text-gray-900 flex items-center gap-2">
                            📚 Riwayat Pembelajaran
                            <span class="bg-gray-900 text-white px-2 py-0.5 rounded-md text-xs font-bold">{{ courses.length }}</span>
                        </h3>
                        
                        <div class="flex gap-4 text-sm text-gray-500">
                            <div class="flex items-center gap-1.5">
                                <span class="w-2.5 h-2.5 rounded-full bg-green-500"></span> Selesai ({{ stats.completed_courses }})
                            </div>
                            <div class="flex items-center gap-1.5">
                                <span class="w-2.5 h-2.5 rounded-full bg-blue-500"></span> Berjalan ({{ stats.total_courses - stats.completed_courses }})
                            </div>
                        </div>
                    </div>
    
                    <div v-if="courses.length > 0" class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div v-for="course in courses" :key="course.id" 
                             class="bg-white rounded-xl border border-gray-200 shadow-sm p-6 hover:shadow-md hover:border-blue-300 transition duration-300 group flex flex-col h-full">
                            
                            <div class="flex justify-between items-start mb-4">
                                <div class="pr-4">
                                    <h4 class="font-bold text-gray-900 text-lg group-hover:text-blue-700 transition line-clamp-2" :title="course.title">
                                        {{ course.title }}
                                    </h4>
                                    <p class="text-xs text-gray-400 mt-1 flex items-center gap-1">
                                        🕒 Akses terakhir: {{ course.last_activity }}
                                    </p>
                                </div>
                                <span class="shrink-0 inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-bold border"
                                    :class="course.progress >= 100 
                                        ? 'bg-green-50 text-green-700 border-green-100' 
                                        : 'bg-blue-50 text-blue-700 border-blue-100'">
                                    {{ course.status }}
                                </span>
                            </div>
    
                            <div class="mb-5 mt-auto">
                                <div class="flex justify-between text-sm font-medium mb-1.5">
                                    <span class="text-gray-600">Progres Modul</span>
                                    <span class="font-bold" :class="course.progress >= 100 ? 'text-green-600' : 'text-blue-600'">
                                        {{ course.completed_modules }} / {{ course.total_modules }}
                                    </span>
                                </div>
                                <div class="w-full bg-gray-100 rounded-full h-2.5 overflow-hidden">
                                    <div class="h-full rounded-full transition-all duration-1000 ease-out relative overflow-hidden" 
                                         :class="course.progress >= 100 ? 'bg-green-500' : 'bg-blue-600'" 
                                         :style="{ width: course.progress + '%' }">
                                         <div class="absolute inset-0 bg-white/20 animate-pulse" v-if="course.progress > 0 && course.progress < 100"></div>
                                    </div>
                                </div>
                            </div>
    
                            <div class="pt-4 border-t border-gray-100 flex items-center justify-between text-sm">
                                <div class="flex items-center gap-2" title="Nilai Rata-rata Kuis">
                                    <div class="p-1.5 rounded-md" :class="course.avg_score >= 70 ? 'bg-green-100 text-green-600' : 'bg-red-50 text-red-500'">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                    </div>
                                    <span class="text-gray-600 font-medium">Nilai Kuis:</span>
                                    <span class="font-bold text-gray-900">{{ course.avg_score }}</span>
                                </div>
                                
                                </div>
                        </div>
                    </div>
    
                    <div v-else class="bg-gray-50 rounded-xl border-2 border-dashed border-gray-300 p-12 text-center">
                        <div class="w-16 h-16 bg-gray-200 rounded-full flex items-center justify-center mx-auto mb-4 text-3xl grayscale opacity-50">📂</div>
                        <h3 class="text-lg font-bold text-gray-900">Belum Ada Kursus</h3>
                        <p class="text-gray-500 text-sm mt-1">Peserta ini belum mendaftar atau memulai kursus apapun.</p>
                    </div>
                </div>
    
            </div>
        </AppLayout>
    </template>