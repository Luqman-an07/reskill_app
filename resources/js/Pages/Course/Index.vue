<script setup>
    import { Head, Link } from '@inertiajs/vue3';
    import AppLayout from '@/Layouts/AppLayout.vue';
    import { ref, computed, watch } from 'vue';
    import CourseThumbnail from '@/Components/CourseThumbnail.vue';
    
    // --- PROPS DARI CONTROLLER ---
    const props = defineProps({
        sections: Object,      // { priority_1: [], priority_2: [], priority_3: [] }
        status_gates: Object,  // { general_done: bool, dept_done: bool }
        userDept: String       // Nama Departemen User
    });
    
    // --- STATE ---
    const activeTab = ref('priority_1');
    const search = ref('');
    const devFilter = ref('Semua');
    
    // Filter Kategori (Priority 3)
    const devCategories = computed(() => {
        const courses = props.sections?.priority_3 || [];
        const roles = courses.map(c => c.target_role).filter(r => r); 
        return ['Semua', ...new Set(roles)];
    });
    
    watch(activeTab, () => {
        devFilter.value = 'Semua';
    });
    
    const tabs = [
        { id: 'priority_1', label: '🥇 Wajib (General)', desc: 'Fondasi utama perusahaan.' },
        { id: 'priority_2', label: `🥈 Kompetensi ${props.userDept || 'Bidang'}`, desc: 'Keahlian spesifik divisi Anda.' },
        { id: 'priority_3', label: '🚀 Pengembangan Diri', desc: 'Eksplorasi skill tambahan.' }
    ];
    
    // --- LOGIKA FILTER ---
    const displayedCourses = computed(() => {
        let courses = props.sections[activeTab.value] || [];
    
        if (activeTab.value === 'priority_3' && devFilter.value !== 'Semua') {
            courses = courses.filter(c => c.target_role === devFilter.value);
        }
    
        if (search.value) {
            const query = search.value.toLowerCase();
            courses = courses.filter(c => 
                c.title.toLowerCase().includes(query) ||
                (c.description && c.description.toLowerCase().includes(query))
            );
        }
    
        return courses;
    });
    
    // --- INFO BAR (GATEKEEPER) ---
    const currentInfoBar = computed(() => {
        if (activeTab.value === 'priority_2' && !props.status_gates.general_done) {
            return { 
                show: true, type: 'locked', icon: 'lock',
                title: 'Akses Dibatasi',
                msg: 'Selesaikan semua materi Wajib (General) untuk membuka level ini.' 
            };
        }
        if (activeTab.value === 'priority_3' && (!props.status_gates.general_done || !props.status_gates.dept_done)) {
            return { 
                show: true, type: 'locked', icon: 'lock',
                title: 'Level Lanjutan',
                msg: 'Selesaikan Kompetensi Bidang terlebih dahulu untuk eksplorasi.' 
            };
        }
        if (activeTab.value === 'priority_1') {
            return { 
                show: true, type: 'info', icon: 'info',
                title: 'Level 1: Fondasi',
                msg: 'Ini adalah materi wajib bagi seluruh peserta. Mulailah dari sini.'
            };
        }
        return {
            show: true, type: 'success', icon: 'check',
            title: 'Akses Terbuka',
            msg: 'Silakan pelajari materi ini untuk meningkatkan performa Anda.'
        };
    });
    
    const getProgressColor = (progress) => {
        if (progress === 100) return 'bg-green-500';
        if (progress > 50) return 'bg-blue-600';
        return 'bg-yellow-500';
    };
    </script>
    
    <template>
        <Head title="Kursus Saya" />
    
        <AppLayout>
            <div class="max-w-7xl mx-auto space-y-8">
                
                <div class="flex flex-col md:flex-row justify-between items-start md:items-end gap-6">
                    <div>
                        <h1 class="text-3xl font-bold text-gray-900 tracking-tight">Peta Training Saya</h1>
                        <p class="text-gray-500 mt-2 text-base">Ikuti jalur training level demi level.</p>
                    </div>
                    
                    <div class="relative w-full md:w-80 group">
                        <span class="absolute inset-y-0 left-0 pl-4 flex items-center text-gray-400 group-focus-within:text-blue-600 transition-colors">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
                        </span>
                        <input 
                            v-model="search"
                            type="text" 
                            class="w-full pl-12 pr-4 py-3 bg-white border border-gray-200 rounded-2xl text-sm focus:ring-4 focus:ring-blue-100 focus:border-blue-500 shadow-sm transition-all duration-300 placeholder-gray-400"
                            placeholder="Cari judul kursus..."
                        >
                    </div>
                </div>
    
                <Transition
                    mode="out-in"
                    enter-active-class="transition ease-out duration-200"
                    enter-from-class="opacity-0 translate-y-1"
                    enter-to-class="opacity-100 translate-y-0"
                    leave-active-class="transition ease-in duration-150"
                    leave-from-class="opacity-100 translate-y-0"
                    leave-to-class="opacity-0 -translate-y-1"
                >
                    <div :key="activeTab" class="rounded-xl border-l-4 p-4 shadow-sm flex items-start gap-3 transition-colors duration-300"
                        :class="{
                            'bg-red-50 border-red-400': currentInfoBar.type === 'locked',
                            'bg-blue-50 border-blue-400': currentInfoBar.type === 'info',
                            'bg-green-50 border-green-400': currentInfoBar.type === 'success',
                        }"
                    >
                        <div class="shrink-0 mt-0.5">
                            <svg v-if="currentInfoBar.icon === 'lock'" class="h-5 w-5 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" /></svg>
                            <svg v-if="currentInfoBar.icon === 'info'" class="h-5 w-5 text-blue-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                            <svg v-if="currentInfoBar.icon === 'check'" class="h-5 w-5 text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                        </div>
    
                        <div>
                            <h3 class="text-sm font-bold" :class="{'text-red-800': currentInfoBar.type === 'locked', 'text-blue-800': currentInfoBar.type === 'info', 'text-green-800': currentInfoBar.type === 'success'}">
                                {{ currentInfoBar.title }}
                            </h3>
                            <p class="text-sm mt-0.5" :class="{'text-red-600': currentInfoBar.type === 'locked', 'text-blue-600': currentInfoBar.type === 'info', 'text-green-600': currentInfoBar.type === 'success'}">
                                {{ currentInfoBar.msg }}
                            </p>
                        </div>
                    </div>
                </Transition>
    
                <div class="flex items-center gap-3 overflow-x-auto no-scrollbar pb-2">
                    <button 
                        v-for="tab in tabs"
                        :key="tab.id"
                        @click="activeTab = tab.id"
                        class="flex flex-col items-start px-5 py-3 rounded-2xl text-sm transition-all duration-300 ease-out transform active:scale-95 border min-w-[200px]"
                        :class="activeTab === tab.id 
                            ? 'bg-blue-900 text-white border-blue-900 shadow-lg shadow-blue-900/20' 
                            : 'bg-white text-gray-600 border-gray-200 hover:border-blue-300 hover:bg-blue-50'"
                    >
                        <div class="flex items-center justify-between w-full mb-1">
                            <span class="font-bold text-base">{{ tab.label }}</span>
                            <span class="px-2 py-0.5 rounded-full text-xs font-bold"
                                :class="activeTab === tab.id ? 'bg-white/20 text-white' : 'bg-gray-100 text-gray-500'">
                                {{ props.sections ? (props.sections[tab.id]?.length || 0) : 0 }}
                            </span>
                        </div>
                        <span class="text-xs opacity-80 font-normal truncate w-full text-left">{{ tab.desc }}</span>
                    </button>
                </div>
    
                <Transition
                    enter-active-class="transition ease-out duration-300"
                    enter-from-class="opacity-0 -translate-y-2 h-0"
                    enter-to-class="opacity-100 translate-y-0 h-auto"
                    leave-active-class="transition ease-in duration-200"
                    leave-from-class="opacity-100 translate-y-0 h-auto"
                    leave-to-class="opacity-0 -translate-y-2 h-0"
                >
                    <div v-if="activeTab === 'priority_3' && devCategories.length > 1" class="flex items-center gap-2 overflow-x-auto no-scrollbar py-2 border-b border-gray-100 mb-2">
                        <span class="text-xs font-bold text-gray-400 mr-2 uppercase tracking-wide">Filter:</span>
                        <button 
                            v-for="cat in devCategories" 
                            :key="cat"
                            @click="devFilter = cat"
                            class="px-4 py-1.5 rounded-full text-xs font-bold border transition-all duration-200 whitespace-nowrap"
                            :class="devFilter === cat 
                                ? 'bg-gray-800 text-white border-gray-800 shadow-md' 
                                : 'bg-white text-gray-600 border-gray-200 hover:border-gray-400 hover:bg-gray-50'"
                        >
                            {{ cat }}
                        </button>
                    </div>
                </Transition>
    
                <div class="min-h-[50vh] relative">
                    <Transition
                        enter-active-class="transition-all duration-300 ease-out"
                        enter-from-class="opacity-0 translate-y-2 scale-[0.98]"
                        enter-to-class="opacity-100 translate-y-0 scale-100"
                        leave-active-class="transition-all duration-150 ease-in"
                        leave-from-class="opacity-100 translate-y-0 scale-100"
                        leave-to-class="opacity-0 -translate-y-2 scale-[0.98]"
                        mode="out-in"
                    >
                        <div v-if="displayedCourses.length > 0" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6" :key="activeTab">
                            
                            <div v-for="course in displayedCourses" :key="course.id" 
                                 class="group relative bg-white rounded-2xl border border-gray-100 shadow-sm transition-all duration-300 flex flex-col overflow-hidden transform-gpu"
                                 :class="[
                                     course.is_locked 
                                        ? 'opacity-75 grayscale bg-gray-50' 
                                        : 'hover:shadow-xl hover:-translate-y-1'
                                 ]"
                            >
                                <div v-if="course.is_locked" class="absolute inset-0 z-50 flex flex-col items-center justify-center bg-gray-100/60 backdrop-blur-[2px]">
                                    <div class="bg-white p-3 rounded-full shadow-lg mb-2">
                                        <svg class="w-6 h-6 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" /></svg>
                                    </div>
                                    <span class="font-bold text-xs text-gray-600 bg-white/90 px-3 py-1 rounded-full shadow-sm border border-gray-200">
                                        Terkunci
                                    </span>
                                </div>
    
                                <div class="h-48 bg-gray-100 relative overflow-hidden">
                                    <CourseThumbnail 
                                        :image="course.image_url" 
                                        :title="course.title" 
                                        :id="course.id" 
                                        class-name="group-hover:scale-105 transition-transform duration-700 w-full h-full object-cover" 
                                    />
                                    
                                    <div v-if="!course.is_locked">
                                        <div v-if="course.is_completed" class="absolute top-3 right-3 bg-green-500 text-white text-xs font-bold px-3 py-1 rounded-full shadow-md flex items-center gap-1">
                                            <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" /></svg>
                                            Selesai
                                        </div>
                                        <div v-else-if="course.is_enrolled" class="absolute top-3 right-3 bg-blue-600 text-white text-xs font-bold px-3 py-1 rounded-full shadow-md">
                                            Sedang Berjalan
                                        </div>
                                    </div>
                                </div>
    
                                <div class="p-5 flex flex-col flex-1">
                                    <h3 class="text-lg font-bold text-gray-900 mb-2 line-clamp-2 leading-snug group-hover:text-blue-700 transition-colors" :title="course.title">
                                        {{ course.title }}
                                    </h3>
    
                                    <div class="flex flex-wrap items-center gap-2 mb-3 text-xs">
                                        <div class="flex items-center gap-1 text-gray-500 bg-gray-50 px-2 py-1 rounded border border-gray-100" title="Jumlah Modul">
                                            <svg class="w-3.5 h-3.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" /></svg>
                                            <span class="font-medium">{{ course.modules_count }} Materi</span>
                                        </div>
                                        <div class="flex items-center gap-1 text-yellow-700 bg-yellow-50 px-2 py-1 rounded border border-yellow-100" title="Total Potensi Poin">
                                            <svg class="w-3.5 h-3.5 text-yellow-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M11.3 1.046A1 1 0 0112 2v5h4a1 1 0 01.82 1.573l-7 10A1 1 0 018 18v-5H4a1 1 0 01-.82-1.573l7-10a1 1 0 011.12-.38z" clip-rule="evenodd"/></svg>
                                            <span class="font-bold">{{ course.total_xp }} XP</span>
                                        </div>
                                    </div>
    
                                    <p class="text-sm text-gray-500 line-clamp-2 mb-4 leading-relaxed">{{ course.description }}</p>
    
                                    <div class="mt-auto space-y-3">
                                        <div v-if="course.is_enrolled">
                                            <div class="flex justify-between text-xs font-bold mb-1.5 text-gray-600">
                                                <span>Progres</span>
                                                <span>{{ course.progress }}%</span>
                                            </div>
                                            <div class="w-full bg-gray-100 rounded-full h-2 overflow-hidden">
                                                <div class="h-full rounded-full transition-all duration-1000 ease-out"
                                                     :class="getProgressColor(course.progress)"
                                                     :style="{ width: course.progress + '%' }">
                                                </div>
                                            </div>
                                        </div>
    
                                        <Link 
                                            v-if="!course.is_locked"
                                            :href="route('course.show', course.id)" 
                                            class="block w-full text-center py-2.5 rounded-xl text-sm font-bold transition-all shadow-sm active:scale-95"
                                            :class="[
                                                course.is_completed 
                                                    ? 'bg-green-600 text-white hover:bg-green-700 hover:shadow-green-200' 
                                                    : (course.is_enrolled 
                                                        ? 'bg-blue-900 text-white hover:bg-blue-800 hover:shadow-blue-200' 
                                                        : 'bg-white text-gray-700 border border-gray-200 hover:border-gray-300 hover:bg-gray-50')
                                            ]"
                                        >
                                            <span v-if="course.is_completed" class="flex items-center justify-center gap-2">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                                Ulas Materi
                                            </span>
                                            <span v-else-if="course.is_enrolled">Lanjutkan Belajar</span>
                                            <span v-else>Mulai Training</span>
                                        </Link>
    
                                        <button v-else disabled class="block w-full text-center py-2.5 rounded-xl text-sm font-bold bg-gray-100 text-gray-400 cursor-not-allowed border border-gray-200">
                                            Terkunci
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
    
                        <div v-else class="flex flex-col items-center justify-center py-20 text-center bg-white rounded-3xl border-2 border-dashed border-gray-200" :key="'empty-'+activeTab">
                            <div class="w-16 h-16 bg-gray-50 rounded-full flex items-center justify-center mb-4 text-3xl grayscale opacity-50">
                                📭
                            </div>
                            <h3 class="text-lg font-bold text-gray-900 mb-1">Belum ada kursus di level ini</h3>
                            <p class="text-gray-500 text-sm max-w-xs mx-auto">
                                Saat ini belum ada materi untuk kategori ini. Silakan cek level lainnya.
                            </p>
                        </div>
                    </Transition>
                </div>
            </div>
        </AppLayout>
    </template>
    
    <style scoped>
    .no-scrollbar::-webkit-scrollbar { display: none; }
    .no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
    </style>