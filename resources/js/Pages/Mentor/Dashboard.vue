<script setup>
    import { Head, Link, usePage } from '@inertiajs/vue3';
    import AppLayout from '@/Layouts/AppLayout.vue';
    
    // Helper function untuk inisial nama
    const getInitials = (name) => {
        if (!name) return '??';
        const parts = name.trim().split(/\s+/);
        if (parts.length === 1) return parts[0].substring(0, 2).toUpperCase();
        return (parts[0][0] + parts[parts.length - 1][0]).toUpperCase();
    };

    const props = defineProps({
        stats: Object,
        courses: Array,
        pendingReviews: Array,
    });
</script>
    
<template>
    <Head title="Beranda Mentor" />

    <AppLayout>
        <div class="max-w-7xl mx-auto space-y-6 sm:space-y-8">
            
            <div>
                <h1 class="text-2xl sm:text-3xl font-bold text-gray-900">Beranda Mentor</h1>
                <p class="text-sm sm:text-base text-gray-500 mt-1">Pantau perkembangan pembelajaran dan kelola materi secara kolaboratif.</p>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-6">
                <div class="bg-white p-5 sm:p-6 rounded-xl border border-gray-200 shadow-sm flex justify-between items-start">
                    <div>
                        <p class="text-xs text-gray-500 font-bold uppercase tracking-wide">Total Kursus</p>
                        <h3 class="text-2xl sm:text-3xl font-bold text-gray-900 mt-2">{{ stats.courses_managed }}</h3>
                        <span class="text-xs text-gray-400 mt-1">Materi Perusahaan</span>
                    </div>
                    <div class="p-2.5 sm:p-3 bg-teal-50 text-teal-600 rounded-lg shrink-0">
                        <svg class="w-5 h-5 sm:w-6 sm:h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" /></svg>
                    </div>
                </div>

                <div class="bg-white p-5 sm:p-6 rounded-xl border border-gray-200 shadow-sm flex justify-between items-start">
                    <div>
                        <p class="text-xs text-gray-500 font-bold uppercase tracking-wide">Partisipan Aktif</p>
                        <h3 class="text-2xl sm:text-3xl font-bold text-gray-900 mt-2">{{ stats.active_participants }}</h3>
                        <span class="text-xs text-green-600 font-medium mt-1 flex items-center gap-1">
                            <span class="w-1.5 h-1.5 bg-green-500 rounded-full animate-pulse"></span>
                            Sedang Belajar
                        </span>
                    </div>
                    <div class="p-2.5 sm:p-3 bg-blue-50 text-blue-600 rounded-lg shrink-0">
                        <svg class="w-5 h-5 sm:w-6 sm:h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0z" /></svg>
                    </div>
                </div>

                <div class="bg-white p-5 sm:p-6 rounded-xl border border-gray-200 shadow-sm flex justify-between items-start">
                    <div>
                        <p class="text-xs text-gray-500 font-bold uppercase tracking-wide">Menunggu Tinjauan</p>
                        <h3 class="text-2xl sm:text-3xl font-bold text-gray-900 mt-2">{{ stats.pending_reviews }}</h3>
                        <span class="text-xs text-orange-600 font-medium mt-1">Perlu dinilai</span>
                    </div>
                    <div class="p-2.5 sm:p-3 bg-orange-50 text-orange-600 rounded-lg shrink-0">
                        <svg class="w-5 h-5 sm:w-6 sm:h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" /></svg>
                    </div>
                </div>

                <div class="bg-white p-5 sm:p-6 rounded-xl border border-gray-200 shadow-sm flex justify-between items-start">
                    <div>
                        <p class="text-xs text-gray-500 font-bold uppercase tracking-wide">Avg. Progres</p>
                        <h3 class="text-2xl sm:text-3xl font-bold text-gray-900 mt-2">{{ stats.avg_completion }}%</h3>
                        <span class="text-xs text-gray-400 mt-1">Semua Departemen</span>
                    </div>
                    <div class="p-2.5 sm:p-3 bg-green-50 text-green-600 rounded-lg shrink-0">
                        <svg class="w-5 h-5 sm:w-6 sm:h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" /></svg>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 lg:gap-8">
                
                <div class="lg:col-span-2 bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden flex flex-col">
                    <div class="px-6 py-5 border-b border-gray-100 flex justify-between items-center bg-white sticky top-0 z-10">
                        <h3 class="font-bold text-gray-900 text-lg">Semua Kursus</h3>
                        <Link :href="route('mentor.courses.index')" class="text-sm text-blue-600 hover:underline font-bold transition">
                            Lihat Semua
                        </Link>                    
                    </div>
                    
                    <div class="divide-y divide-gray-100">
                        <div v-for="course in courses" :key="course.id" class="p-5 sm:p-6 hover:bg-gray-50 transition group">
                            
                            <div class="flex flex-col sm:flex-row justify-between items-start gap-4 mb-4">
                                <div class="min-w-0 flex-1">
                                    <h4 class="font-bold text-gray-900 text-lg group-hover:text-blue-700 transition truncate" :title="course.title">
                                        {{ course.title }}
                                    </h4>
                                    
                                    <div v-if="course.pending_reviews > 0" class="mt-2 inline-flex items-center gap-1 text-xs text-orange-700 font-bold bg-orange-50 px-2 py-1 rounded border border-orange-100">
                                        <span class="w-1.5 h-1.5 bg-orange-500 rounded-full animate-pulse"></span>
                                        {{ course.pending_reviews }} tugas menunggu dinilai
                                    </div>
                                </div>

                                <Link :href="route('mentor.courses.edit', course.id)" class="w-full sm:w-auto text-center px-4 py-2 border border-gray-300 rounded-lg text-xs sm:text-sm font-bold text-gray-600 hover:bg-gray-100 hover:text-gray-900 transition shrink-0 shadow-sm">
                                    Kelola Kursus
                                </Link>
                            </div>

                            <div class="flex flex-wrap items-center gap-6 mb-4">
                                
                                <div class="flex items-center gap-2.5">
                                    <div class="w-8 h-8 rounded-full border border-gray-200 shadow-sm overflow-hidden bg-gray-100 flex items-center justify-center shrink-0">
                                        <img 
                                            v-if="course.mentor_avatar || (course.mentor && course.mentor.profile_photo_url)" 
                                            :src="course.mentor_avatar || course.mentor.profile_photo_url" 
                                            class="w-full h-full object-cover"
                                            :alt="course.mentor_name"
                                        >
                                        <span v-else class="text-xs font-bold text-gray-500">
                                            {{ getInitials(course.mentor_name || 'Tim HG') }}
                                        </span>
                                    </div>
                                    <div class="flex flex-col">
                                        <span class="text-xs font-bold text-gray-700 leading-none">
                                            {{ course.mentor_name || 'Tim Human Growth' }}
                                        </span>
                                        <span class="text-[10px] text-gray-400 font-medium mt-0.5">Mentor</span>
                                    </div>
                                </div>

                                <div class="flex items-center gap-1.5 bg-blue-50 px-2.5 py-1 rounded-lg border border-blue-100 text-blue-600">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0z" /></svg>
                                    <span class="text-xs font-bold">{{ course.participants || 0 }} Peserta</span>
                                </div>

                            </div>
                            
                            <div class="flex items-center gap-3 text-xs sm:text-sm">
                                <span class="text-gray-500 font-medium w-24 shrink-0">Avg. Progres</span>
                                <div class="flex-1 bg-gray-100 rounded-full h-2 overflow-hidden">
                                    <div class="bg-blue-900 h-2 rounded-full transition-all duration-1000" :style="{width: course.progress + '%'}"></div>
                                </div>
                                <span class="font-bold text-gray-700 w-8 text-right">{{ course.progress }}%</span>
                            </div>
                        </div>

                        <div v-if="courses.length === 0" class="p-10 text-center text-gray-400 text-sm italic">
                            Belum ada kursus di katalog perusahaan.
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden h-fit sticky top-6">
                    <div class="px-6 py-4 border-b border-gray-100 bg-orange-50/50 flex items-center gap-2">
                        <svg class="w-4 h-4 text-orange-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                        <h3 class="font-bold text-gray-900 text-sm sm:text-base">
                            Antrean Penilaian
                        </h3>
                    </div>

                    <div class="divide-y divide-gray-100 max-h-[400px] overflow-y-auto">
                        <div v-for="sub in pendingReviews" :key="sub.id" class="p-4 hover:bg-gray-50 transition group">
                            <div class="flex justify-between items-start mb-2">
                                <div class="flex items-center gap-2">
                                    <div class="w-8 h-8 rounded-full bg-gray-200 flex items-center justify-center text-xs font-bold text-gray-500 overflow-hidden border border-gray-300">
                                        <img v-if="sub.student_avatar" :src="sub.student_avatar" class="w-full h-full object-cover">
                                        <span v-else>{{ getInitials(sub.student_name) }}</span>
                                    </div>
                                    <div>
                                        <p class="text-sm font-bold text-gray-900 line-clamp-1">{{ sub.student_name }}</p>
                                        <p class="text-[10px] text-gray-500 mt-0.5 line-clamp-1" :title="sub.course_title">{{ sub.course_title }}</p>
                                    </div>
                                </div>
                                <span class="text-[10px] text-gray-400 whitespace-nowrap ml-2">{{ sub.time_ago }}</span>
                            </div>
                            
                            <p class="text-xs text-gray-600 mb-3 line-clamp-1 pl-10 border-l-2 border-gray-200 ml-4">
                                {{ sub.task_title }}
                            </p>
                            
                            <div class="flex items-center justify-end">
                                <Link :href="route('mentor.tasks.index', { search: sub.student_name })" class="text-xs bg-white border border-blue-200 text-blue-600 px-3 py-1 rounded-full font-bold hover:bg-blue-50 transition shadow-sm">
                                    Nilai Sekarang
                                </Link>
                            </div>
                        </div>

                        <div v-if="pendingReviews.length === 0" class="p-8 text-center text-gray-400 text-sm flex flex-col items-center gap-2">
                            <span class="text-2xl">🎉</span>
                            <span>Tidak ada antrean tugas. Kerja bagus, Tim!</span>
                        </div>
                    </div>
                    
                    <div class="p-3 bg-gray-50 text-center border-t border-gray-100">
                        <Link :href="route('mentor.tasks.index')" class="text-xs font-bold text-gray-600 hover:text-gray-900 transition">
                            Lihat Semua Pengajuan
                        </Link>
                    </div>
                </div>

            </div>
        </div>
    </AppLayout>
</template>