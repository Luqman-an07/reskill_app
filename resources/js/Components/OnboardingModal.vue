<script setup>
import { ref, computed } from 'vue';
import { usePage, useForm } from '@inertiajs/vue3';

// 1. Data Global
const page = usePage();
const user = computed(() => page.props.auth.user);
const departments = computed(() => page.props.departments_global || []);

// 2. State Lokal
const searchQuery = ref('');
const isDropdownOpen = ref(false);
const step = ref(1); // State untuk mengatur Langkah 1 (Pilih) atau 2 (Konfirmasi)
const selectedDeptName = ref(''); // Menyimpan nama dept untuk ditampilkan di konfirmasi

// Filter Logic
const filteredDepartments = computed(() => {
    if (!searchQuery.value) return departments.value;
    return departments.value.filter(dept => 
        dept.department_name.toLowerCase().includes(searchQuery.value.toLowerCase())
    );
});

// 3. Form Inertia
const form = useForm({
    department_id: null,
});

// Helper: Pilih Dept
const selectDepartment = (dept) => {
    form.department_id = dept.id;
    searchQuery.value = dept.department_name;
    selectedDeptName.value = dept.department_name; // Simpan nama untuk konfirmasi
    isDropdownOpen.value = false;
};

// Logic Tombol "Lanjut" (Ke Langkah 2)
const goToConfirmation = () => {
    if (form.department_id) {
        step.value = 2;
    }
};

// Logic Tombol "Kembali" (Ke Langkah 1)
const backToSelection = () => {
    step.value = 1;
};

// 4. Submit Final (Ke Backend)
const submit = () => {
    form.put(route('onboarding.department.update'), {
        preserveScroll: true,
        onSuccess: () => {
            // Modal otomatis hilang karena data user terupdate
        }
    });
};

// 5. Trigger Modal
const showModal = computed(() => {
    return user.value && !user.value.department_id;
});
</script>

<template>
    <Teleport to="body">
        <div v-if="showModal" class="fixed inset-0 z-[9999] flex items-center justify-center px-4 font-sans">
            
            <div class="fixed inset-0 bg-gray-900/90 backdrop-blur-md transition-opacity"></div>

            <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md p-8 relative z-10 border border-gray-200">
                
                <div v-if="step === 1" class="animate-fade-in">
                    <div class="text-center mb-6">
                        <div class="mx-auto bg-blue-50 w-14 h-14 rounded-full flex items-center justify-center mb-4 text-2xl animate-bounce-slow">
                            👋
                        </div>
                        <h2 class="text-2xl font-bold text-gray-900">Halo, {{ user.name.split(' ')[0] }}!</h2>
                        <p class="text-gray-500 text-sm mt-2">
                            Untuk memulai, mohon pilih departemen tempat Kamu bekerja saat ini.
                        </p>
                    </div>

                    <div class="bg-blue-50 border border-blue-100 rounded-lg p-3 mb-6 flex gap-3 items-start">
                        <svg class="w-5 h-5 text-blue-600 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                        <p class="text-xs text-blue-800 leading-relaxed">
                            <strong>Penting:</strong> Data ini akan menentukan kurikulum training wajib Kamu. Pastikan Kamu memilih dengan benar.
                        </p>
                    </div>

                    <form @submit.prevent="goToConfirmation" class="space-y-5">
                        <div class="relative">
                            <label class="block text-xs font-bold text-gray-700 uppercase mb-2">Cari Departemen</label>
                            
                            <div class="relative">
                                <input 
                                    type="text" 
                                    v-model="searchQuery"
                                    @focus="isDropdownOpen = true"
                                    class="w-full pl-4 pr-10 py-3 rounded-xl border border-gray-300 focus:border-blue-500 focus:ring-blue-500 text-sm shadow-sm transition"
                                    placeholder="Ketik divisi Kamu..."
                                />
                                <div class="absolute inset-y-0 right-0 flex items-center pr-3 text-gray-400 pointer-events-none">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
                                </div>

                                <div v-if="isDropdownOpen" class="absolute z-50 w-full mt-1 bg-white border border-gray-200 rounded-xl shadow-2xl max-h-60 overflow-y-auto ring-1 ring-black ring-opacity-5">
                                    <ul class="py-1">
                                        <li v-if="filteredDepartments.length === 0" class="px-4 py-3 text-sm text-gray-400 italic text-center">Tidak ditemukan.</li>
                                        <li v-for="dept in filteredDepartments" :key="dept.id" @click="selectDepartment(dept)"
                                            class="px-4 py-2.5 text-sm text-gray-700 hover:bg-blue-50 hover:text-blue-700 cursor-pointer flex justify-between items-center group transition border-b border-gray-50 last:border-0">
                                            <span>{{ dept.department_name }}</span>
                                            <span v-if="form.department_id === dept.id" class="text-blue-600 font-bold">✓</span>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </div>

                        <button type="submit" :disabled="!form.department_id"
                            class="w-full bg-blue-900 text-white font-bold py-3 rounded-xl shadow-md hover:bg-blue-800 disabled:opacity-50 disabled:cursor-not-allowed transition transform active:scale-[0.98]">
                            Lanjut
                        </button>
                    </form>
                </div>

                <div v-else class="animate-slide-up text-center">
                    <div class="mx-auto bg-yellow-50 w-14 h-14 rounded-full flex items-center justify-center mb-4 text-2xl">
                        🔒
                    </div>
                    
                    <h3 class="text-xl font-bold text-gray-900">Konfirmasi Pilihan</h3>
                    <p class="text-sm text-gray-500 mt-2">Kamu memilih departemen:</p>
                    
                    <div class="my-6 bg-gray-50 border border-gray-200 p-4 rounded-xl">
                        <span class="text-lg font-black text-blue-900 block">{{ selectedDeptName }}</span>
                    </div>

                    <div class="bg-red-50 border border-red-100 rounded-lg p-3 mb-6 text-left flex gap-3">
                        <svg class="w-5 h-5 text-red-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" /></svg>
                        <div class="text-xs text-red-800">
                            <p class="font-bold mb-1">Perhatian:</p>
                            Perubahan data setelah ini hanya dapat dilakukan dengan menghubungi <strong>Admin</strong>. Pastikan pilihan Kamu benar.
                        </div>
                    </div>

                    <div class="flex gap-3">
                        <button @click="backToSelection" 
                            class="flex-1 px-4 py-3 bg-white border border-gray-300 text-gray-700 font-bold rounded-xl hover:bg-gray-50 transition">
                            Ubah
                        </button>
                        
                        <button @click="submit" :disabled="form.processing"
                            class="flex-[2] px-4 py-3 bg-blue-900 text-white font-bold rounded-xl shadow-md hover:bg-blue-800 transition flex items-center justify-center gap-2">
                            <svg v-if="form.processing" class="animate-spin h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                            <span>{{ form.processing ? 'Menyimpan...' : 'Ya, Simpan Permanen' }}</span>
                        </button>
                    </div>
                </div>

            </div>
        </div>
    </Teleport>
</template>

<style scoped>
/* Animasi Halus */
.animate-fade-in { animation: fadeIn 0.3s ease-out; }
.animate-slide-up { animation: slideUp 0.3s cubic-bezier(0.16, 1, 0.3, 1); }
.animate-bounce-slow { animation: bounce 2s infinite; }

@keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
@keyframes slideUp { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
@keyframes bounce { 0%, 100% { transform: translateY(0); } 50% { transform: translateY(-5px); } }
</style>