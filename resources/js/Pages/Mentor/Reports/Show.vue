<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    student: Object,
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
        <div class="max-w-7xl mx-auto space-y-6">
            
            <div class="flex items-center gap-2 text-sm text-gray-500">
                <Link :href="route('mentor.reports.index')" class="hover:text-blue-600 transition">Laporan</Link>
                <span>/</span>
                <span class="text-gray-900 font-bold">Detail Peserta</span>
            </div>

            <div class="bg-white rounded-2xl p-6 border border-gray-200 shadow-sm flex flex-col md:flex-row gap-6 items-start md:items-center">
                <div class="w-20 h-20 md:w-24 md:h-24 rounded-full bg-gray-100 border-4 border-white shadow-md flex items-center justify-center text-2xl font-bold text-gray-400 overflow-hidden shrink-0">
                    <img v-if="student.profile_picture" :src="student.profile_picture" class="w-full h-full object-cover">
                    <span v-else>{{ getInitials(student.name) }}</span>
                </div>

                <div class="flex-1">
                    <div class="flex flex-col md:flex-row md:items-center gap-2 mb-1">
                        <h1 class="text-2xl font-bold text-gray-900">{{ student.name }}</h1>
                        <span v-if="student.department" class="w-fit px-3 py-1 bg-blue-50 text-blue-700 text-xs font-bold rounded-full border border-blue-100">
                            {{ student.department.department_name }}
                        </span>
                    </div>
                    <p class="text-gray-500 text-sm mb-4">{{ student.email }}</p>
                    
                    <div class="flex flex-wrap gap-2">
                        <div v-for="badge in student.badges" :key="badge.id" class="flex items-center gap-1 bg-yellow-50 border border-yellow-200 px-2 py-1 rounded text-xs text-yellow-800" :title="badge.description">
                            <span>🏅</span> {{ badge.name }}
                        </div>
                        <div v-if="student.badges.length === 0" class="text-xs text-gray-400 italic">Belum ada lencana.</div>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4 w-full md:w-auto bg-gray-50 p-4 rounded-xl border border-gray-100">
                    <div class="text-center">
                        <div class="text-xs text-gray-500 uppercase font-bold">Total XP</div>
                        <div class="text-xl font-black text-yellow-600">{{ stats.total_xp }}</div>
                    </div>
                    <div class="text-center border-l border-gray-200 pl-4">
                        <div class="text-xs text-gray-500 uppercase font-bold">Rata Nilai</div>
                        <div class="text-xl font-black text-blue-600">{{ stats.avg_global_score }}</div>
                    </div>
                </div>
            </div>

            <div class="space-y-4">
                <h3 class="text-lg font-bold text-gray-900 flex items-center gap-2">
                    Riwayat Training
                    <span class="bg-gray-100 text-gray-600 px-2 py-0.5 rounded-full text-xs">{{ courses.length }} Kursus</span>
                </h3>

                <div v-if="courses.length > 0" class="grid grid-cols-1 gap-4">
                    <div v-for="course in courses" :key="course.id" class="bg-white p-5 rounded-xl border border-gray-200 shadow-sm hover:border-blue-300 transition group">
                        
                        <div class="flex flex-col sm:flex-row justify-between gap-4 mb-4">
                            <div>
                                <h4 class="font-bold text-gray-900 text-lg group-hover:text-blue-700 transition">{{ course.title }}</h4>
                                <p class="text-sm text-gray-500 mt-1">
                                    Aktivitas terakhir: {{ course.last_activity }}
                                </p>
                            </div>
                            <div class="text-right">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                                    :class="course.progress >= 100 ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800'">
                                    {{ course.status }}
                                </span>
                            </div>
                        </div>

                        <div class="space-y-2">
                            <div class="flex justify-between text-sm font-medium">
                                <span class="text-gray-600">Progres Modul ({{ course.completed_modules }}/{{ course.total_modules }})</span>
                                <span class="text-blue-700 font-bold">{{ course.progress }}%</span>
                            </div>
                            <div class="w-full bg-gray-100 rounded-full h-2.5 overflow-hidden">
                                <div class="bg-blue-600 h-2.5 rounded-full transition-all duration-1000" :style="{ width: course.progress + '%' }"></div>
                            </div>
                        </div>

                        <div class="flex items-center gap-6 mt-4 pt-4 border-t border-gray-100 text-sm">
                            <div class="flex items-center gap-2">
                                <span class="text-gray-400">📝</span>
                                <span class="text-gray-600">Rata-rata Kuis:</span>
                                <span class="font-bold" :class="course.avg_score >= 70 ? 'text-green-600' : 'text-red-500'">
                                    {{ course.avg_score }}
                                </span>
                            </div>
                            
                            </div>

                    </div>
                </div>

                <div v-else class="bg-white p-12 rounded-xl border border-dashed border-gray-300 text-center text-gray-500">
                    <span class="text-4xl block mb-2">📂</span>
                    Peserta ini belum mengambil kursus apapun.
                </div>
            </div>

        </div>
    </AppLayout>
</template>