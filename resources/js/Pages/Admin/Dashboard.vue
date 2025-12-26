<script setup>
import { Head } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { Bar } from 'vue-chartjs';
import { Chart as ChartJS, Title, Tooltip, Legend, BarElement, CategoryScale, LinearScale } from 'chart.js';

// Registrasi Komponen Chart
ChartJS.register(Title, Tooltip, Legend, BarElement, CategoryScale, LinearScale);

const props = defineProps({
    stats: Object,
    activityChart: Object,
    recentActivities: Array,
});

// Config Chart
const chartData = {
    labels: props.activityChart.labels,
    datasets: [{
        label: 'Pengguna Aktif',
        data: props.activityChart.data,
        backgroundColor: '#3B82F6', // Blue-500
        borderRadius: 4,
        barThickness: 'flex', // Responsif bar width
        maxBarThickness: 32,
    }]
};

const chartOptions = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: { display: false } // Sembunyikan legenda biar bersih
    },
    scales: {
        y: { 
            beginAtZero: true, 
            grid: { display: true, color: '#f3f4f6' },
            ticks: { precision: 0 } // Hindari angka desimal untuk orang
        },
        x: { grid: { display: false } }
    }
};
</script>

<template>
    <Head title="Beranda Admin" />

    <AppLayout>
        <div class="max-w-7xl mx-auto space-y-6 sm:space-y-8">
            
            <div>
                <h1 class="text-2xl sm:text-3xl font-bold text-gray-900">Beranda Admin</h1>
                <p class="text-sm sm:text-base text-gray-500 mt-1">Pusat Informasi Statistik dan Kontrol Admin</p>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-6">
                
                <div class="bg-white p-5 sm:p-6 rounded-xl border border-gray-200 shadow-sm flex justify-between items-start hover:shadow-md transition">
                    <div>
                        <p class="text-xs text-gray-500 font-bold uppercase tracking-wide">Total Pengguna</p>
                        <h3 class="text-2xl sm:text-3xl font-bold text-gray-900 mt-2">{{ stats.total_users }}</h3>
                        <span class="text-xs text-green-600 font-medium mt-1 inline-flex items-center gap-1">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" /></svg>
                            +{{ stats.new_users_week }} Minggu ini
                        </span>
                    </div>
                    <div class="p-2.5 sm:p-3 bg-blue-50 text-blue-600 rounded-lg shrink-0">
                        <svg class="w-5 h-5 sm:w-6 sm:h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" /></svg>
                    </div>
                </div>

                <div class="bg-white p-5 sm:p-6 rounded-xl border border-gray-200 shadow-sm flex justify-between items-start hover:shadow-md transition">
                    <div>
                        <p class="text-xs text-gray-500 font-bold uppercase tracking-wide">Kursus Aktif</p>
                        <h3 class="text-2xl sm:text-3xl font-bold text-gray-900 mt-2">{{ stats.active_courses }}</h3>
                        <span class="text-xs text-orange-600 font-medium mt-1 inline-block">
                            {{ stats.pending_courses }} Draf tertunda
                        </span>
                    </div>
                    <div class="p-2.5 sm:p-3 bg-indigo-50 text-indigo-600 rounded-lg shrink-0">
                        <svg class="w-5 h-5 sm:w-6 sm:h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" /></svg>
                    </div>
                </div>

                <div class="bg-white p-5 sm:p-6 rounded-xl border border-gray-200 shadow-sm flex justify-between items-start hover:shadow-md transition">
                    <div>
                        <p class="text-xs text-gray-500 font-bold uppercase tracking-wide">Mentor Aktif</p>
                        <h3 class="text-2xl sm:text-3xl font-bold text-gray-900 mt-2">{{ stats.active_mentors }}</h3>
                        <span class="text-xs text-gray-400 mt-1 inline-block">Ahli bidangnya</span>
                    </div>
                    <div class="p-2.5 sm:p-3 bg-purple-50 text-purple-600 rounded-lg shrink-0">
                        <svg class="w-5 h-5 sm:w-6 sm:h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" /></svg>
                    </div>
                </div>

                <div class="bg-white p-5 sm:p-6 rounded-xl border border-gray-200 shadow-sm flex justify-between items-start hover:shadow-md transition">
                    <div>
                        <p class="text-xs text-gray-500 font-bold uppercase tracking-wide">Poin Musim Ini</p>
                        <h3 class="text-2xl sm:text-3xl font-bold text-gray-900 mt-2">{{ (stats.season_points / 1000).toFixed(1) }}K</h3>
                        <span class="text-xs text-green-600 font-medium mt-1 inline-block">
                            Total Diperoleh
                        </span>
                    </div>
                    <div class="p-2.5 sm:p-3 bg-yellow-50 text-yellow-600 rounded-lg shrink-0">
                        <svg class="w-5 h-5 sm:w-6 sm:h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" /></svg>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 lg:gap-8">
                
                <div class="lg:col-span-2 bg-white p-5 sm:p-6 rounded-xl border border-gray-200 shadow-sm">
                    <div class="flex items-center justify-between mb-6">
                        <h3 class="text-lg font-bold text-gray-900 flex items-center gap-2">
                            <svg class="w-5 h-5 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" /></svg>
                            Pengguna Aktif Harian
                        </h3>
                    </div>
                    <div class="h-64 sm:h-80 relative w-full">
                        <Bar :data="chartData" :options="chartOptions" />
                    </div>
                </div>

                <div class="bg-white p-5 sm:p-6 rounded-xl border border-gray-200 shadow-sm h-fit">
                    <h3 class="text-lg font-bold text-gray-900 mb-6 flex items-center gap-2">
                        <svg class="w-5 h-5 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                        Aktivitas Terbaru
                    </h3>
                    
                    <div class="space-y-0">
                        <div v-for="(log, idx) in recentActivities" :key="log.id" class="flex gap-4 relative pb-6 last:pb-0">
                            <div v-if="idx !== recentActivities.length - 1" class="absolute left-[17px] sm:left-[19px] top-8 bottom-0 w-0.5 bg-gray-100"></div>
                            
                            <div class="relative z-10 shrink-0">
                                <div class="w-9 h-9 sm:w-10 sm:h-10 rounded-full bg-gray-100 border border-gray-200 flex items-center justify-center text-xs font-bold overflow-hidden shadow-sm">
                                    <img v-if="log.user_avatar" :src="'/storage/' + log.user_avatar" class="w-full h-full object-cover">
                                    <span v-else>{{ log.user_name.charAt(0) }}</span>
                                </div>
                            </div>
                            
                            <div class="min-w-0 flex-1 pt-0.5">
                                <p class="text-sm text-gray-800 leading-snug">
                                    <span class="font-bold text-blue-900">{{ log.user_name }}</span> 
                                    {{ log.action }}
                                </p>
                                
                                <p class="text-xs text-gray-400 mt-1 cursor-help flex items-center gap-1" :title="log.exact_time">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                    {{ log.time_ago }}
                                </p>
                                
                                <span v-if="log.points > 0" class="inline-block mt-2 text-[10px] font-bold text-green-600 bg-green-50 px-2 py-0.5 rounded border border-green-100 shadow-sm">
                                    +{{ log.points }} XP
                                </span>
                            </div>
                        </div>

                        <div v-if="recentActivities.length === 0" class="text-center text-gray-400 text-sm py-4">
                            Belum ada aktivitas tercatat.
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </AppLayout>
</template>