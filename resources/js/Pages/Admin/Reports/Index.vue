<script setup>
import { ref, watch, computed } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import debounce from 'lodash/debounce';
import { Bar, Doughnut, Line } from 'vue-chartjs';
import { Chart as ChartJS, Title, Tooltip, Legend, BarElement, CategoryScale, LinearScale, ArcElement, PointElement, LineElement } from 'chart.js';
import LoadingOverlay from '@/Components/LoadingOverlay.vue';
import axios from 'axios';

// Registrasi Komponen Chart.js
ChartJS.register(Title, Tooltip, Legend, BarElement, CategoryScale, LinearScale, ArcElement, PointElement, LineElement);

const props = defineProps({
    participants: Object,
    departments: Array,
    courses: Array,
    stats: Object,
    filters: Object,
    charts: Object,
    baseRoute: String,
    exportRoute: String,
    detailRouteName: String, // [OPSIONAL] Nama route untuk detail (misal: 'mentor.reports.show')
});

// --- STATE ---
const filterDept = ref(props.filters.department_id || '');
const filterCourse = ref(props.filters.course_id || '');
const activeTab = ref('progres_individu');
const isLoadingData = ref(false);

// --- STATE SORTING ---
const sortField = ref(props.filters.sort || 'name'); 
const sortDirection = ref(props.filters.direction || 'asc');

const sort = (field) => {
    if (sortField.value === field) {
        sortDirection.value = sortDirection.value === 'asc' ? 'desc' : 'asc';
    } else {
        sortField.value = field;
        sortDirection.value = 'asc';
    }
};

// --- WATCHER (Filter & Sort) ---
watch([filterDept, filterCourse, sortField, sortDirection], debounce(() => {
    isLoadingData.value = true;
    router.get(route(props.baseRoute), { 
        department_id: filterDept.value,
        course_id: filterCourse.value,
        sort: sortField.value,       
        direction: sortDirection.value 
    }, { 
        preserveState: true, 
        replace: true,
        onFinish: () => isLoadingData.value = false
    });
}, 300));

// --- CHART CONFIGURATIONS ---
const weeklyData = computed(() => ({
    labels: props.charts?.weekly?.labels || [],
    datasets: [
        { label: 'Selesai', data: props.charts?.weekly?.completed || [], backgroundColor: '#10B981', borderRadius: 4 },
        { label: 'Berlangsung', data: props.charts?.weekly?.started || [], backgroundColor: '#3B82F6', borderRadius: 4 },
        { label: 'Belum Mulai', data: props.charts?.weekly?.not_started || [], backgroundColor: '#E5E7EB', borderRadius: 4 }
    ]
}));

const completionData = computed(() => ({
    labels: props.charts?.completion?.map(c => c.title) || [],
    datasets: [{
        label: 'Tingkat Penyelesaian (%)',
        data: props.charts?.completion?.map(c => c.rate) || [],
        borderColor: '#14B8A6', 
        backgroundColor: '#14B8A6',
        borderWidth: 3,
        pointBackgroundColor: '#FFFFFF',
        pointBorderColor: '#0F766E',
        pointBorderWidth: 2,
        pointRadius: 6,
        pointHoverRadius: 8,
        tension: 0.4,
        fill: false
    }]
}));

const performanceData = computed(() => ({
    labels: props.charts?.performance ? Object.keys(props.charts.performance) : [],
    datasets: [{
        data: props.charts?.performance ? Object.values(props.charts.performance) : [],
        backgroundColor: ['#10B981', '#34D399', '#FBBF24', '#9CA3AF', '#EF4444'],
        borderWidth: 0,
        hoverOffset: 4
    }]
}));

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

// --- ACTION & HELPER ---
const downloadReport = () => {
    window.location.href = route(props.exportRoute, { 
        department_id: filterDept.value,
        course_id: filterCourse.value
    });
};

const getInitials = (name) => name ? name.split(' ').map(n => n[0]).join('').substring(0, 2).toUpperCase() : 'UR';

// --- STATE: STUDENT DETAIL MODAL ---
const isStudentModalOpen = ref(false);
const isLoadingStudent = ref(false);
const studentData = ref(null);
const studentTab = ref('progres_kursus'); 

// Modifikasi fungsi open detail agar bisa redirect jika ada route detail
const openDetail = (userId) => {
    if (props.detailRouteName) {
        // Jika ada route detail (misal mode Mentor), pindah halaman
        router.visit(route(props.detailRouteName, userId));
    } else {
        // Jika tidak (mode Admin biasa), buka modal
        openStudentDetailModal(userId);
    }
};

const openStudentDetailModal = async (userId) => {
    isStudentModalOpen.value = true;
    isLoadingStudent.value = true;
    studentTab.value = 'progres_kursus';
    studentData.value = null;

    try {
        const response = await axios.get(route('admin.reports.user.details', userId));
        studentData.value = response.data;
    } catch (error) {
        console.error("Error fetching student details:", error);
        alert("Gagal memuat data peserta.");
        isStudentModalOpen.value = false;
    } finally {
        isLoadingStudent.value = false;
    }
};
</script>

<template>
    <Head title="Laporan Peserta" />

    <AppLayout>
        <div class="max-w-7xl mx-auto space-y-6 sm:space-y-8">
            
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-end gap-4">
                <div>
                    <h1 class="text-2xl sm:text-3xl font-bold text-gray-900">Laporan Peserta</h1>
                    <p class="text-sm sm:text-base text-gray-500 mt-1">Pantau dan analisis metrik kinerja peserta.</p>
                </div>
                <button @click="downloadReport" class="w-full sm:w-auto bg-gray-900 hover:bg-gray-800 text-white px-5 py-2.5 rounded-xl text-sm font-medium flex items-center justify-center gap-2 shadow-sm transition transform active:scale-95">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                    Unduh Laporan
                </button>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-6">
                <div class="bg-white p-5 rounded-xl border border-gray-200 shadow-sm flex items-center gap-4 hover:shadow-md transition">
                    <div class="p-3 bg-gray-100 rounded-xl text-gray-600"><svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0z"/></svg></div>
                    <div><p class="text-xs font-bold text-gray-500 uppercase">Total Peserta</p><p class="text-2xl font-black text-gray-900">{{ stats?.total || 0 }}</p></div>
                </div>
                <div class="bg-white p-5 rounded-xl border border-gray-200 shadow-sm flex items-center gap-4 hover:shadow-md transition">
                    <div class="p-3 bg-green-50 rounded-xl text-green-600"><svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" /></svg></div>
                    <div><p class="text-xs font-bold text-gray-500 uppercase">Rata-rata Progres</p><p class="text-2xl font-black text-gray-900">{{ stats?.avg_progress || 0 }}%</p></div>
                </div>
                <div class="bg-white p-5 rounded-xl border border-gray-200 shadow-sm flex items-center gap-4 hover:shadow-md transition">
                    <div class="p-3 bg-purple-50 rounded-xl text-purple-600"><svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg></div>
                    <div><p class="text-xs font-bold text-gray-500 uppercase">Rata-rata Nilai</p><p class="text-2xl font-black text-gray-900">{{ stats?.avg_score || 0 }}%</p></div>
                </div>
                <div class="bg-white p-5 rounded-xl border border-gray-200 shadow-sm flex items-center gap-4 hover:shadow-md transition">
                    <div class="p-3 bg-orange-50 rounded-xl text-orange-600"><svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg></div>
                    <div><p class="text-xs font-bold text-gray-500 uppercase">Aktif (7 Hari)</p><p class="text-2xl font-black text-gray-900">{{ stats?.active || 0 }}</p></div>
                </div>
            </div>

            <div class="bg-gray-100 p-4 rounded-2xl flex flex-col md:flex-row gap-4 items-center border border-gray-200">
                <div v-if="departments.length > 0" class="w-full md:w-1/2">
                    <label class="text-xs font-bold text-gray-500 uppercase mb-1 block">Filter Departemen</label>
                    <select v-model="filterDept" class="w-full bg-white border-transparent rounded-xl text-sm shadow-sm focus:ring-blue-500 focus:border-blue-500 py-2.5">
                        <option value="">Semua Departemen</option>
                        <option v-for="dept in departments" :key="dept.id" :value="dept.id">{{ dept.department_name }}</option>
                    </select>
                </div>
                <div :class="departments.length > 0 ? 'w-full md:w-1/2' : 'w-full'">
                    <label class="text-xs font-bold text-gray-500 uppercase mb-1 block">Filter Kursus</label>
                    <select v-model="filterCourse" class="w-full bg-white border-transparent rounded-xl text-sm shadow-sm focus:ring-blue-500 focus:border-blue-500 py-2.5">
                        <option value="">Semua Kursus</option>
                        <option v-for="c in courses" :key="c.id" :value="c.id">{{ c.title }}</option>
                    </select>
                </div>
            </div>

            <div class="flex items-center gap-3 overflow-x-auto no-scrollbar pb-2">
                <button 
                    v-for="tab in ['Progres Individu', 'Tren Mingguan', 'Penyelesaian Kursus', 'Performa']"
                    :key="tab"
                    @click="activeTab = tab.toLowerCase().replace(' ', '_')"
                    class="px-6 py-3 rounded-full text-sm font-bold transition-all duration-300 ease-out transform active:scale-95 border whitespace-nowrap"
                    :class="activeTab === tab.toLowerCase().replace(' ', '_') 
                        ? 'bg-blue-900 text-white border-blue-900 shadow-lg shadow-blue-900/20' 
                        : 'bg-white text-gray-600 border-gray-200 hover:border-gray-300 hover:bg-gray-50'"
                >
                    {{ tab }}
                </button>
            </div>

            <div class="min-h-[400px]">
                
                <Transition
                    enter-active-class="transition ease-out duration-300"
                    enter-from-class="opacity-0 translate-y-4"
                    enter-to-class="opacity-100 translate-y-0"
                    leave-active-class="transition ease-in duration-200"
                    leave-from-class="opacity-100 translate-y-0"
                    leave-to-class="opacity-0 translate-y-4"
                    mode="out-in"
                >
                    <div v-if="activeTab === 'progres_individu'" class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden relative">                        
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider whitespace-nowrap cursor-pointer hover:bg-gray-100 transition group" @click="sort('name')">
                                            <div class="flex items-center gap-1">
                                                Peserta
                                                <span class="flex flex-col text-[8px] leading-none text-gray-400">
                                                    <i :class="sortField === 'name' && sortDirection === 'asc' ? 'text-gray-800' : ''">▲</i>
                                                    <i :class="sortField === 'name' && sortDirection === 'desc' ? 'text-gray-800' : ''">▼</i>
                                                </span>
                                            </div>
                                        </th>
    
                                        <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider whitespace-nowrap">Dept</th>
                                        <th class="px-6 py-4 text-center text-xs font-bold text-gray-500 uppercase tracking-wider whitespace-nowrap">Terdaftar</th>
                                        <th class="px-6 py-4 text-center text-xs font-bold text-gray-500 uppercase tracking-wider whitespace-nowrap">Selesai</th>
                                        <th class="px-6 py-4 text-center text-xs font-bold text-gray-500 uppercase tracking-wider whitespace-nowrap">Rata-rata</th>
                                        
                                        <th scope="col" class="px-6 py-4 text-center text-xs font-bold text-gray-500 uppercase tracking-wider whitespace-nowrap cursor-pointer hover:bg-gray-100 transition group" @click="sort('total_points')">
                                            <div class="flex items-center justify-center gap-1">
                                                Poin
                                                <span class="flex flex-col text-[8px] leading-none text-gray-400">
                                                    <i :class="sortField === 'total_points' && sortDirection === 'asc' ? 'text-gray-800' : ''">▲</i>
                                                    <i :class="sortField === 'total_points' && sortDirection === 'desc' ? 'text-gray-800' : ''">▼</i>
                                                </span>
                                            </div>
                                        </th>
    
                                        <th class="px-6 py-4 text-center text-xs font-bold text-gray-500 uppercase tracking-wider whitespace-nowrap">Lencana</th>
                                        
                                        <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider whitespace-nowrap cursor-pointer hover:bg-gray-100 transition group" @click="sort('last_active')">
                                            <div class="flex items-center gap-1">
                                                Terakhir Aktif
                                                <span class="flex flex-col text-[8px] leading-none text-gray-400">
                                                    <i :class="sortField === 'last_active' && sortDirection === 'asc' ? 'text-gray-800' : ''">▲</i>
                                                    <i :class="sortField === 'last_active' && sortDirection === 'desc' ? 'text-gray-800' : ''">▼</i>
                                                </span>
                                            </div>
                                        </th>
    
                                        <th class="px-6 py-4 text-right text-xs font-bold text-gray-500 uppercase tracking-wider whitespace-nowrap">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <tr v-for="p in participants.data" :key="p.id" class="hover:bg-gray-50 transition">
                                        <td class="px-6 py-4 whitespace-nowrap flex items-center gap-3">
                                            
                                            <div class="relative inline-block shrink-0">
                                                <div class="h-9 w-9 rounded-full bg-blue-100 flex items-center justify-center text-xs font-bold text-blue-700 border border-blue-200 overflow-hidden">
                                                    <img v-if="p.avatar" :src="'/storage/'+p.avatar" class="w-full h-full object-cover">
                                                    <span v-else>{{ getInitials(p.name) }}</span>
                                                </div>
                                                <span 
                                                    v-if="p.is_online" 
                                                    class="absolute bottom-0 right-0 block h-2.5 w-2.5 rounded-full bg-green-500 ring-2 ring-white animate-pulse"
                                                    title="Sedang Online"
                                                ></span>
                                            </div>

                                            <div class="ml-1">
                                                <div class="text-sm font-bold text-gray-900">
                                                    {{ p.name }}
                                                </div>
                                                <div class="text-xs text-gray-500">{{ p.email }}</div>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">{{ p.department }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-center"><span class="bg-blue-50 text-blue-700 px-2.5 py-0.5 rounded-full text-xs font-bold">{{ p.enrolled }} kursus</span></td>
                                        <td class="px-6 py-4 whitespace-nowrap text-center font-bold text-gray-700">
                                            <span class="text-sm font-bold text-gray-700">{{ p.completed }}</span>
                                            <span class="text-xs text-gray-400 ml-1">Modul</span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-center">
                                            <span class="px-2.5 py-1 rounded text-xs font-bold" :class="p.avg_score >= 70 ? 'bg-green-100 text-green-700' : 'bg-yellow-100 text-yellow-700'">{{ p.avg_score }}%</span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-center font-mono text-sm text-yellow-600 font-bold">{{ p.total_xp }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-center">
                                            <div class="flex items-center justify-center gap-1">
                                                <svg class="w-4 h-4 text-yellow-500" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                                                <span class="text-sm font-medium text-gray-700">{{ p.badges_count }}</span>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-xs text-gray-500 font-mono">{{ p.last_active }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-right">
                                            <div class="flex justify-end gap-3">
                                                <button @click="openDetail(p.id)" title="Lihat Detail" class="text-gray-400 hover:text-blue-600 transition">
                                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                                </button>
                                                <a :href="route('admin.reports.export.user', p.id)" title="Unduh Laporan" class="text-gray-400 hover:text-green-600 transition"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg></a>
                                            </div>
                                        </td>
                                    </tr>
                                    <tr v-if="participants.data.length === 0">
                                        <td colspan="9" class="px-6 py-10 text-center text-gray-400 italic bg-gray-50">Tidak ada peserta ditemukan. Coba ubah filter.</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <div class="px-6 py-4 border-t border-gray-200 flex flex-col sm:flex-row justify-between items-center gap-4 bg-gray-50/50">
                            <div class="text-sm text-gray-500">
                                Menampilkan <span class="font-bold text-gray-900">{{ participants.from || 0 }}</span> sampai <span class="font-bold text-gray-900">{{ participants.to || 0 }}</span> dari <span class="font-bold text-gray-900">{{ participants.total }}</span> peserta
                            </div>
    
                            <div class="flex items-center gap-1 overflow-x-auto" v-if="participants.links.length > 3">
                                <Link v-for="(link, k) in participants.links" :key="k" :href="link.url" v-html="link.label" 
                                    class="px-3 py-1 border rounded text-sm transition whitespace-nowrap" 
                                    :class="link.active ? 'bg-gray-900 text-white border-gray-900' : 'text-gray-600 bg-white hover:bg-gray-100'" 
                                />
                            </div>
                        </div>
                    </div>

                    <div v-else-if="activeTab === 'tren_mingguan'" class="bg-white rounded-2xl border border-gray-200 shadow-sm p-5 sm:p-8">
                        <div class="flex justify-between mb-8">
                            <div><h3 class="text-lg font-bold text-gray-900">Tren Aktivitas</h3><p class="text-sm text-gray-500">Penyelesaian modul mingguan vs dimulai</p></div>
                        </div>
                        <div class="relative h-72 sm:h-80 w-full"><Bar :data="weeklyData" :options="chartOptions" /></div>
                    </div>

                    <div v-else-if="activeTab === 'penyelesaian_kursus'">
                        <div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-5 sm:p-8 mb-6">
                            <div class="flex justify-between mb-8">
                                <div><h3 class="text-lg font-bold text-gray-900">Tingkat Penyelesaian Kursus</h3><p class="text-sm text-gray-500">Persentase peserta yang menyelesaikan setiap kursus</p></div>
                            </div>
                            <div class="relative h-72 sm:h-80 w-full"><Line :data="completionData" :options="chartOptions" /></div>
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                            <div v-for="course in charts?.completion" :key="course.title" class="bg-white p-6 rounded-xl border border-gray-200 shadow-sm flex flex-col items-center text-center">
                                <h4 class="text-3xl font-black" :class="course.rate >= 70 ? 'text-teal-600' : 'text-yellow-600'">{{ course.rate }}%</h4>
                                <p class="text-xs font-bold text-gray-500 mt-1 uppercase tracking-wide truncate w-full" :title="course.title">{{ course.title }}</p>
                            </div>
                        </div>
                    </div>

                    <div v-else-if="activeTab === 'performa'" class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <div class="md:col-span-1 bg-white rounded-2xl border border-gray-200 shadow-sm p-8 flex flex-col items-center justify-center relative min-h-[300px]">
                            <div class="absolute top-6 left-6"><h3 class="text-lg font-bold text-gray-900">Distribusi Nilai</h3></div>
                            <div class="relative w-full h-64"><Doughnut :data="performanceData" :options="{maintainAspectRatio: false, plugins: {legend: {display: false}}}" />
                                <div class="absolute inset-0 flex flex-col items-center justify-center pointer-events-none"><span class="text-3xl font-black text-gray-800">{{ stats?.total || 0 }}</span><span class="text-xs font-bold text-gray-400 uppercase">Peserta</span></div>
                            </div>
                        </div>
                        <div class="md:col-span-2 bg-white rounded-2xl border border-gray-200 shadow-sm p-8">
                            <h3 class="text-lg font-bold text-gray-900 mb-6">Rincian Detail</h3>
                            <div class="space-y-6">
                                <div v-for="(count, label, idx) in charts?.performance" :key="label">
                                    <div class="flex justify-between items-end mb-2">
                                        <div class="flex items-center gap-3"><div class="w-3 h-3 rounded-full shadow-sm" :style="{backgroundColor: performanceData.datasets[0].backgroundColor[idx]}"></div><span class="text-sm font-bold text-gray-700">Nilai {{ label }}</span></div>
                                        <span class="text-sm font-bold text-gray-900">{{ count }} <span class="text-xs text-gray-400 font-normal">peserta</span></span>
                                    </div>
                                    <div class="w-full bg-gray-100 rounded-full h-2.5 overflow-hidden"><div class="h-full rounded-full transition-all duration-1000" :style="{width: stats.total > 0 ? (count / stats.total * 100) + '%' : '0%', backgroundColor: performanceData.datasets[0].backgroundColor[idx]}"></div></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </Transition>

            </div>
        </div>
    </AppLayout>

    <Teleport to="body">
        <div v-if="isStudentModalOpen" class="fixed inset-0 z-[1000] flex items-center justify-center px-4">
            <div class="fixed inset-0 bg-gray-900/75 backdrop-blur-sm transition-opacity" @click="isStudentModalOpen = false"></div>
            
            <div class="bg-white rounded-2xl shadow-2xl w-full max-w-4xl relative z-10 flex flex-col max-h-[90vh] overflow-hidden animate-fade-in-up">
                
                <div v-if="isLoadingStudent" class="p-20 text-center">
                    <svg class="animate-spin h-10 w-10 text-blue-600 mx-auto mb-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                    <p class="text-gray-400">Memuat profil peserta...</p>
                </div>

                <div v-else class="flex flex-col h-full">
                    
                    <div class="p-6 sm:p-8 border-b border-gray-100 bg-white flex flex-col sm:flex-row justify-between items-start sm:items-center gap-6">
                        <div class="flex items-center gap-5">
                            
                            <div class="relative inline-block shrink-0">
                                <div class="w-16 h-16 rounded-full bg-blue-100 flex items-center justify-center text-2xl font-bold text-blue-700 overflow-hidden border-2 border-white shadow-md">
                                    <img v-if="studentData.user.avatar" :src="'/storage/'+studentData.user.avatar" class="w-full h-full object-cover">
                                    <span v-else>{{ getInitials(studentData.user.name) }}</span>
                                </div>
                                
                                <span 
                                    v-if="studentData.user.is_online" 
                                    class="absolute bottom-1 right-1 block h-4 w-4 rounded-full bg-green-500 ring-2 ring-white animate-pulse"
                                    title="Sedang Online"
                                ></span>
                            </div>

                            <div>
                                <div class="flex items-center gap-3">
                                    <h2 class="text-xl sm:text-2xl font-bold text-gray-900">{{ studentData.user.name }}</h2>
                                    <span v-if="studentData.user.is_online" class="text-xs bg-green-100 text-green-700 px-2 py-0.5 rounded-full font-bold">Online</span>
                                </div>
                                <p class="text-sm text-gray-500">{{ studentData.user.email }}</p>
                                <div class="flex flex-wrap items-center gap-2 sm:gap-3 mt-2 text-xs text-gray-400">
                                    <span class="bg-gray-100 px-2 py-0.5 rounded text-gray-600 font-medium">{{ studentData.user.department }}</span>
                                    <span>Bergabung {{ studentData.user.join_date }}</span>
                                    <span>•</span>
                                    <span>Aktif {{ studentData.user.last_active }}</span>
                                </div>
                            </div>
                        </div>
                        <button @click="isStudentModalOpen = false" class="absolute top-4 right-4 sm:top-6 sm:right-6 text-gray-400 hover:text-gray-600"><svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg></button>
                    </div>

                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 sm:gap-4 px-6 sm:px-8 py-6 bg-gray-50 border-b border-gray-200">
                        <div class="bg-white p-3 sm:p-4 rounded-xl border border-gray-200 shadow-sm flex items-center gap-3">
                            <div class="p-2 bg-blue-50 text-blue-600 rounded-lg hidden sm:block"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg></div>
                            <div><p class="text-[10px] font-bold text-gray-400 uppercase">Terdaftar</p><p class="text-lg sm:text-xl font-bold text-gray-900">{{ studentData.stats.enrolled }}</p></div>
                        </div>
                        <div class="bg-white p-3 sm:p-4 rounded-xl border border-gray-200 shadow-sm flex items-center gap-3">
                            <div class="p-2 bg-green-50 text-green-600 rounded-lg hidden sm:block"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg></div>
                            <div><p class="text-[10px] font-bold text-gray-400 uppercase">Selesai</p><p class="text-lg sm:text-xl font-bold text-gray-900">{{ studentData.stats.completed }}</p></div>
                        </div>
                        <div class="bg-white p-3 sm:p-4 rounded-xl border border-gray-200 shadow-sm flex items-center gap-3">
                            <div class="p-2 bg-yellow-50 text-yellow-600 rounded-lg hidden sm:block"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/></svg></div>
                            <div><p class="text-[10px] font-bold text-gray-400 uppercase">Rata-rata</p><p class="text-lg sm:text-xl font-bold text-gray-900">{{ studentData.stats.avg_score }}%</p></div>
                        </div>
                        <div class="bg-white p-3 sm:p-4 rounded-xl border border-gray-200 shadow-sm flex items-center gap-3">
                            <div class="p-2 bg-purple-50 text-purple-600 rounded-lg hidden sm:block"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg></div>
                            <div><p class="text-[10px] font-bold text-gray-400 uppercase">Poin</p><p class="text-lg sm:text-xl font-bold text-gray-900">{{ studentData.stats.total_points }}</p></div>
                        </div>
                    </div>

                    <div class="flex-1 flex flex-col overflow-hidden bg-white">
                        <div class="px-6 sm:px-8 border-b border-gray-100 flex gap-4 sm:gap-6 overflow-x-auto no-scrollbar">
                            <button v-for="tab in ['Progres Kursus', 'Lencana', 'Catatan Aktivitas']" :key="tab" @click="studentTab = tab.toLowerCase().replace(' ', '_')" class="py-4 text-sm font-medium border-b-2 transition-colors capitalize whitespace-nowrap" :class="studentTab === tab.toLowerCase().replace(' ', '_') ? 'border-blue-600 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700'">
                                {{ tab }} <span class="text-xs bg-gray-100 px-1.5 rounded ml-1 text-gray-500" v-if="tab === 'Lencana'">{{ studentData.badges.length }}</span>
                            </button>
                        </div>
                        
                        <div class="flex-1 overflow-y-auto p-6 sm:p-8 bg-gray-50/50">
                            <div v-if="studentTab === 'progres_kursus'" class="space-y-4">
                                <div v-for="c in studentData.courses" :key="c.id" class="bg-white p-5 rounded-xl border border-gray-200 shadow-sm">
                                    <div class="flex justify-between items-start mb-3">
                                        <h4 class="font-bold text-gray-900 text-sm sm:text-base">{{ c.title }}</h4>
                                        <span class="text-xs font-bold px-2 py-1 rounded" :class="c.progress === 100 ? 'bg-green-100 text-green-700' : 'bg-blue-100 text-blue-700'">{{ c.status === 'Completed' ? 'Selesai' : 'Jalan' }}</span>
                                    </div>
                                    <div class="w-full bg-gray-100 rounded-full h-2 mb-3"><div class="h-2 rounded-full transition-all duration-500" :class="c.progress === 100 ? 'bg-green-500' : 'bg-blue-600'" :style="{width: c.progress + '%'}"></div></div>
                                    <div class="flex justify-between text-xs text-gray-500"><span>Nilai: <span class="font-bold text-gray-900">{{ c.score !== null ? c.score + '%' : '-' }}</span></span><span>Akses: {{ c.last_access || '-' }}</span></div>
                                </div>
                                <div v-if="studentData.courses.length === 0" class="text-center text-gray-400 py-8 text-sm">Belum ada kursus.</div>
                            </div>

                            <div v-else-if="studentTab === 'lencana'" class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div v-for="badge in studentData.badges" :key="badge.id" class="bg-white p-4 rounded-xl border border-gray-200 shadow-sm flex items-center gap-4">
                                    <div class="w-12 h-12 bg-yellow-50 rounded-full flex items-center justify-center text-2xl shrink-0 overflow-hidden border border-yellow-100">
                                        <img v-if="badge.icon_url" :src="'/storage/'+badge.icon_url" class="w-8 h-8 object-contain"><span v-else>{{ badge.icon_emoji || '🏆' }}</span>
                                    </div>
                                    <div><h4 class="font-bold text-gray-900 text-sm">{{ badge.badge_name }}</h4><p class="text-xs text-gray-500 mt-0.5 line-clamp-1">{{ badge.description }}</p></div>
                                </div>
                                <div v-if="studentData.badges.length === 0" class="col-span-2 text-center py-8 text-gray-400 text-sm">Belum ada lencana.</div>
                            </div>

                            <div v-else-if="studentTab === 'catatan_aktivitas'" class="space-y-0">
                                <div v-for="(act, idx) in studentData.activities" :key="act.id" class="flex gap-4 pb-6 relative last:pb-0">
                                    <div v-if="idx !== studentData.activities.length - 1" class="absolute left-4 top-8 bottom-0 w-0.5 bg-gray-200"></div>
                                    <div class="w-8 h-8 rounded-full bg-blue-50 border border-blue-100 flex items-center justify-center text-blue-600 shrink-0 z-10 text-xs">
                                        <span v-if="act.type === 'QUIZ_PASS'">📝</span>
                                        <span v-else-if="act.type === 'MODULE_COMPLETE'">✅</span>
                                        <span v-else>✨</span>
                                    </div>
                                    <div><p class="text-sm font-bold text-gray-900">{{ act.title }}</p><p class="text-xs text-gray-500">{{ act.description }}</p><p class="text-[10px] text-gray-400 mt-1">{{ act.date }}</p></div>
                                </div>
                                <div v-if="studentData.activities.length === 0" class="text-center text-gray-400 py-8 text-sm">Tidak ada aktivitas.</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </Teleport>

</template>

<style scoped>
.no-scrollbar::-webkit-scrollbar { display: none; }
.no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
.animate-fade-in-up { animation: fadeInUp 0.3s ease-out forwards; }
@keyframes fadeInUp { from { opacity: 0; transform: scale(0.95) translateY(10px); } to { opacity: 1; transform: scale(1) translateY(0); } }
</style>