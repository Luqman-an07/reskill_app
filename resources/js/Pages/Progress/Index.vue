<script setup>
import { Head } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { ref } from 'vue';
import { Bar, Doughnut, Line } from 'vue-chartjs';
import { Chart as ChartJS, Title, Tooltip, Legend, BarElement, CategoryScale, LinearScale, ArcElement, PointElement, LineElement } from 'chart.js';

ChartJS.register(Title, Tooltip, Legend, BarElement, CategoryScale, LinearScale, ArcElement, PointElement, LineElement);

const props = defineProps({
    stats: Object,
    coursesProgress: Array,
    chartData: Object,
});

const activeTab = ref('activity');

// --- CONFIG CHART ---
const weeklyChartData = {
    labels: props.chartData.weekly.labels, 
    datasets: [{ 
        label: 'Jam', 
        data: props.chartData.weekly.data, 
        backgroundColor: '#34D399', 
        borderRadius: 5 
    }]
};

const distributionChartData = {
    labels: ['Video (mnt)', 'Membaca (mnt)', 'Kuis (mnt)', 'Tugas (mnt)'],
    datasets: [{ 
        data: props.chartData.distribution, 
        backgroundColor: ['#34D399', '#1E3A8A', '#F59E0B', '#1F2937'] 
    }]
};

const performanceChartData = {
    labels: props.chartData.monthly.labels,
    datasets: [{ 
        label: 'Modul Selesai', 
        data: props.chartData.monthly.data, 
        borderColor: '#10B981', 
        tension: 0.4, 
        fill: false,
        pointBackgroundColor: '#10B981'
    }]
};

const chartOptions = { 
    responsive: true, 
    maintainAspectRatio: false,
    plugins: {
        legend: {
            position: 'bottom',
            labels: { usePointStyle: true, padding: 20 }
        }
    }
};
</script>

<template>
    <Head title="Laporan Training" />

    <AppLayout>
        <div class="max-w-7xl mx-auto space-y-6 sm:space-y-8">
            
            <div>
                <h1 class="text-2xl sm:text-3xl font-bold text-gray-900">Laporan Training</h1>
                <p class="text-sm sm:text-base text-gray-500 mt-1">Lacak perjalanan Training Kamu dan analisis kinerja Kamu.</p>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-6">
                
                <div class="bg-white p-5 sm:p-6 rounded-xl border border-gray-200 shadow-sm flex justify-between items-start hover:shadow-md transition">
                    <div>
                        <p class="text-xs text-gray-500 mb-1 font-bold uppercase tracking-wide">Minggu Ini</p>
                        <h3 class="text-2xl sm:text-3xl font-bold text-gray-900">{{ stats.hours_week }}h</h3>
                        <p class="text-[10px] sm:text-xs text-gray-400 mt-1 font-medium">
                            Rata-rata {{ stats.daily_average }}h / Hari
                        </p>
                    </div>
                    <div class="p-2.5 bg-green-50 text-green-600 rounded-full shrink-0">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                    </div>
                </div>

                <div class="bg-white p-5 sm:p-6 rounded-xl border border-gray-200 shadow-sm flex justify-between items-start hover:shadow-md transition">
                    <div>
                        <p class="text-xs text-gray-500 mb-1 font-bold uppercase tracking-wide">Modul Selesai</p>
                        <h3 class="text-2xl sm:text-3xl font-bold text-gray-900">{{ stats.modules_completed }}<span class="text-lg text-gray-400 font-normal">/{{ stats.total_modules }}</span></h3>
                        <p class="text-[10px] sm:text-xs text-gray-400 mt-1 font-medium">{{ stats.completion_rate }}% Keseluruhan</p>
                    </div>
                    <div class="p-2.5 bg-blue-50 text-blue-600 rounded-full shrink-0">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                    </div>
                </div>

                <div class="bg-white p-5 sm:p-6 rounded-xl border border-gray-200 shadow-sm flex justify-between items-start hover:shadow-md transition">
                    <div>
                        <p class="text-xs text-gray-500 mb-1 font-bold uppercase tracking-wide">Rata-rata Skor</p>
                        <h3 class="text-2xl sm:text-3xl font-bold text-gray-900">{{ stats.avg_score }}%</h3>
                        <p class="text-[10px] sm:text-xs mt-1 font-bold flex items-center gap-1" 
                           :class="stats.score_trend >= 0 ? 'text-green-500' : 'text-red-500'">
                           <span v-if="stats.score_trend > 0">+</span>{{ stats.score_trend }}% bulan lalu
                        </p>
                    </div>
                    <div class="p-2.5 bg-purple-50 text-purple-600 rounded-full shrink-0">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" /></svg>
                    </div>
                </div>

                <div class="bg-white p-5 sm:p-6 rounded-xl border border-gray-200 shadow-sm flex justify-between items-start hover:shadow-md transition">
                    <div>
                        <p class="text-xs text-gray-500 mb-1 font-bold uppercase tracking-wide">Streak</p>
                        <h3 class="text-2xl sm:text-3xl font-bold text-gray-900">{{ stats.streak }} Hari</h3>
                        <p class="text-[10px] sm:text-xs text-gray-400 mt-1 font-medium">Pertahankan!</p>
                    </div>
                    <div class="p-2.5 bg-orange-50 text-orange-600 rounded-full shrink-0">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" /></svg>
                    </div>
                </div>
            </div>

            <div class="flex items-center gap-3 overflow-x-auto no-scrollbar pb-2">
                <button 
                    v-for="tab in ['activity', 'courses', 'performance']"
                    :key="tab"
                    @click="activeTab = tab"
                    class="px-6 py-3 rounded-full text-sm font-bold transition-all duration-300 ease-out transform active:scale-95 border whitespace-nowrap"
                    :class="activeTab === tab 
                        ? 'bg-blue-900 text-white border-blue-900 shadow-lg shadow-blue-900/20' 
                        : 'bg-white text-gray-600 border-gray-200 hover:border-gray-300 hover:bg-gray-50'"
                >
                    {{ tab === 'activity' ? 'Kegiatan' : (tab === 'courses' ? 'Kursus' : 'Performa') }}
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
                <div v-if="activeTab === 'activity'" class="grid grid-cols-1 lg:grid-cols-2 gap-6" key="activity">
                    <div class="bg-white p-6 rounded-xl border border-gray-200 shadow-sm">
                        <h3 class="text-lg font-bold text-gray-900 mb-6">Jam Aktif Mingguan</h3>
                        <div class="h-64 sm:h-72 relative">
                            <Bar :data="weeklyChartData" :options="chartOptions" />
                        </div>
                    </div>
                    <div class="bg-white p-6 rounded-xl border border-gray-200 shadow-sm">
                        <h3 class="text-lg font-bold text-gray-900 mb-6">Distribusi Waktu</h3>
                        <div class="h-64 sm:h-72 flex justify-center relative">
                            <Doughnut :data="distributionChartData" :options="chartOptions" />
                        </div>
                    </div>
                </div>

                <div v-else-if="activeTab === 'courses'" class="bg-white p-6 rounded-xl border border-gray-200 shadow-sm" key="courses">
                    <h3 class="text-lg font-bold text-gray-900 mb-6">Ringkasan Progres Kursus</h3>
                    <div v-if="coursesProgress.length > 0" class="space-y-6">
                        <div v-for="course in coursesProgress" :key="course.id">
                            <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center mb-2 gap-1">
                                <span class="text-sm font-bold text-gray-800">{{ course.title }}</span>
                                <span class="text-sm font-bold text-blue-600">{{ course.percent }}%</span>
                            </div>
                            <div class="w-full bg-gray-100 rounded-full h-2.5 mb-2 overflow-hidden">
                                <div class="bg-blue-900 h-2.5 rounded-full transition-all duration-1000" :style="{ width: course.percent + '%' }"></div>
                            </div>
                            <div class="text-xs text-gray-400 font-medium">{{ course.completed_modules }} dari {{ course.total_modules }} modul selesai</div>
                        </div>
                    </div>
                    <div v-else class="text-center py-10 text-gray-400 text-sm">
                        Belum ada kursus yang diambil.
                    </div>
                </div>

                <div v-else-if="activeTab === 'performance'" class="bg-white p-6 rounded-xl border border-gray-200 shadow-sm" key="performance">
                    <h3 class="text-lg font-bold text-gray-900 mb-6">Modul Bulanan yang Telah Diselesaikan</h3>
                    <div class="h-72 sm:h-96 relative">
                        <Line :data="performanceChartData" :options="chartOptions" />
                    </div>
                </div>
            </Transition>

        </div>
    </AppLayout>
</template>

<style scoped>
.no-scrollbar::-webkit-scrollbar { display: none; }
.no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
</style>