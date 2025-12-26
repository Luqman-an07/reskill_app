<script setup>
import { ref, watch } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    submissions: Object,
    filters: Object,
});

const statusFilter = ref(props.filters.status || '');

watch(statusFilter, (value) => {
    router.get(route('my-tasks'), { status: value }, { preserveState: true, replace: true });
});

const getScoreColor = (score) => {
    if (score >= 80) return 'text-green-700 bg-green-50 border-green-200 ring-green-100';
    if (score >= 60) return 'text-yellow-700 bg-yellow-50 border-yellow-200 ring-yellow-100';
    return 'text-red-700 bg-red-50 border-red-200 ring-red-100';
};
</script>

<template>
    <Head title="Riwayat Tugas" />

    <AppLayout>
        <div class="max-w-5xl mx-auto space-y-6 pb-10">
            
            <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 bg-white p-5 sm:p-6 rounded-2xl border border-gray-200 shadow-sm">
                <div>
                    <h1 class="text-xl sm:text-2xl font-bold text-gray-900">Riwayat Tugas</h1>
                    <p class="text-sm text-gray-500 mt-1">Pantau progres dan nilai tugas Anda.</p>
                </div>
                
                <div class="w-full md:w-auto overflow-x-auto pb-2 md:pb-0 no-scrollbar">
                    <div class="flex items-center gap-1 bg-gray-100 p-1 rounded-lg min-w-max">
                        <button @click="statusFilter = ''" 
                            :class="['px-4 py-2 text-xs font-bold rounded-md transition whitespace-nowrap', !statusFilter ? 'bg-white text-blue-900 shadow-sm' : 'text-gray-500 hover:text-gray-700']">
                            Semua
                        </button>
                        <button @click="statusFilter = 'pending'" 
                            :class="['px-4 py-2 text-xs font-bold rounded-md transition whitespace-nowrap', statusFilter === 'pending' ? 'bg-white text-blue-900 shadow-sm' : 'text-gray-500 hover:text-gray-700']">
                            Menunggu Review
                        </button>
                        <button @click="statusFilter = 'graded'" 
                            :class="['px-4 py-2 text-xs font-bold rounded-md transition whitespace-nowrap', statusFilter === 'graded' ? 'bg-white text-blue-900 shadow-sm' : 'text-gray-500 hover:text-gray-700']">
                            Selesai Dinilai
                        </button>
                    </div>
                </div>
            </div>

            <div class="space-y-4">
                <div v-if="submissions.data.length === 0" class="text-center py-16 bg-white rounded-2xl border border-gray-200 border-dashed mx-4 sm:mx-0">
                    <div class="bg-blue-50 w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-4">
                        <span class="text-3xl">📂</span>
                    </div>
                    <h3 class="text-lg font-bold text-gray-900">Belum ada tugas</h3>
                    <p class="text-gray-500 text-sm mt-1 max-w-xs mx-auto">Tugas yang Anda kerjakan akan muncul di sini.</p>
                </div>

                <div v-for="sub in submissions.data" :key="sub.id" 
                    class="group bg-white rounded-xl sm:rounded-2xl border border-gray-200 shadow-sm hover:border-blue-300 hover:shadow-md transition duration-200 relative overflow-hidden mx-2 sm:mx-0"
                >
                    <div class="absolute left-0 top-0 bottom-0 w-1.5" 
                        :class="sub.status === 'Dinilai' ? 'bg-green-500' : 'bg-yellow-400'">
                    </div>

                    <div class="p-4 sm:p-5 pl-5 sm:pl-6 flex flex-col md:flex-row gap-4 md:gap-6">
                        
                        <div class="flex-1 min-w-0"> <div class="flex flex-wrap items-center gap-2 mb-2">
                                <span class="text-[10px] sm:text-xs font-bold px-2 py-1 rounded bg-gray-100 text-gray-600 truncate max-w-[200px]">
                                    {{ sub.course_title }}
                                </span>
                                <span v-if="sub.is_late" class="text-[10px] font-bold px-2 py-1 rounded bg-red-50 text-red-600 border border-red-100 flex items-center gap-1">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                    TERLAMBAT
                                </span>
                            </div>

                            <h3 class="text-base sm:text-lg font-bold text-gray-900 leading-tight group-hover:text-blue-700 transition">
                                <Link :href="sub.link" class="focus:outline-none">
                                    <span class="absolute inset-0 md:hidden"></span> {{ sub.task_title }}
                                </Link>
                            </h3>
                            
                            <p class="text-xs text-gray-400 mt-2 flex items-center gap-1">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                                Dikumpulkan: <span class="font-medium text-gray-600">{{ sub.full_date }}</span>
                            </p>
                        </div>

                        <div class="flex items-center justify-between md:flex-col md:items-end md:justify-center gap-3 pt-3 mt-1 border-t border-gray-100 md:border-t-0 md:border-l md:pt-0 md:mt-0 md:pl-6 relative z-10">
                            
                            <div v-if="sub.status === 'Dinilai'" class="flex items-center md:flex-col md:items-end gap-3">
                                <span class="text-xs text-gray-400 font-bold uppercase md:hidden">Nilai Akhir:</span>
                                <div class="text-xl sm:text-2xl font-black px-3 py-1 rounded-lg border ring-1" :class="getScoreColor(sub.score)">
                                    {{ sub.score }}
                                </div>
                            </div>

                            <div v-else class="flex items-center md:flex-col md:items-end">
                                <span class="px-3 py-1.5 bg-yellow-50 text-yellow-700 text-xs font-bold rounded-lg border border-yellow-200 flex items-center gap-2">
                                    <span class="relative flex h-2 w-2">
                                      <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-yellow-400 opacity-75"></span>
                                      <span class="relative inline-flex rounded-full h-2 w-2 bg-yellow-500"></span>
                                    </span>
                                    <span class="hidden sm:inline">Menunggu Review</span>
                                    <span class="sm:hidden">Pending</span>
                                </span>
                            </div>

                            <Link :href="sub.link" class="hidden md:flex p-2 text-gray-400 hover:text-blue-600 bg-gray-50 hover:bg-blue-50 rounded-lg transition border border-transparent hover:border-blue-100">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" /></svg>
                            </Link>
                        </div>
                    </div>

                    <div v-if="sub.feedback" class="bg-gray-50 px-5 py-3 border-t border-gray-100 flex gap-3 items-start relative z-20">
                        <div class="mt-0.5 min-w-[20px]">
                            <svg class="w-4 h-4 text-gray-400" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10c0 3.866-3.582 7-8 7a8.841 8.841 0 01-4.083-.98L2 17l1.338-3.123C2.493 12.767 2 11.434 2 10c0-3.866 3.582-7 8-7s8 3.134 8 7zM7 9H5v2h2V9zm8 0h-2v2h2V9zM9 9h2v2H9V9z" clip-rule="evenodd"></path></svg>
                        </div>
                        <div>
                            <p class="text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-0.5">Komentar Mentor</p>
                            <p class="text-xs sm:text-sm text-gray-600 italic leading-relaxed">"{{ sub.feedback }}"</p>
                        </div>
                    </div>
                </div>
            </div>

            <div v-if="submissions.links.length > 3" class="flex justify-center mt-8 pb-8">
                <div class="flex flex-wrap justify-center gap-1">
                    <Link v-for="(link, i) in submissions.links" :key="i" 
                        :href="link.url || '#'" 
                        v-html="link.label"
                        class="px-3 py-2 text-xs sm:text-sm rounded-lg transition"
                        :class="[
                            link.active ? 'bg-blue-900 text-white font-bold shadow-md' : 'bg-white text-gray-600 hover:bg-gray-100 border border-gray-200',
                            !link.url ? 'opacity-50 cursor-not-allowed' : ''
                        ]"
                    />
                </div>
            </div>

        </div>
    </AppLayout>
</template>

<style scoped>
/* Sembunyikan scrollbar di filter mobile tapi tetap bisa di-scroll */
.no-scrollbar::-webkit-scrollbar { display: none; }
.no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
</style>