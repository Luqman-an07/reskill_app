<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { Link, usePage, router } from '@inertiajs/vue3';
import AchievementModal from '@/Components/AchievementModal.vue';
import NotificationDropdown from '@/Components/NotificationDropdown.vue';
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import Toast from '@/Components/Toast.vue';
import LoadingOverlay from '@/Components/LoadingOverlay.vue';
import UserDropdown from '@/Components/UserDropdown.vue';
import OnboardingModal from '@/Components/OnboardingModal.vue';

const user = usePage().props.auth.user;

// State Global Search
const globalSearch = ref('');

// Logika Smart Redirect berdasarkan Role
const handleGlobalSearch = () => {
    if (!globalSearch.value.trim()) return; // Jangan cari jika kosong

    const query = { search: globalSearch.value };

    if (isAdmin.value) {
        // Admin: Cari Kursus Global (Bisa diganti Users jika mau)
        router.get(route('admin.courses.index'), query);
    } 
    else if (isMentor.value) {
        // Mentor: Cari Kursus Miliknya
        router.get(route('mentor.courses.index'), query);
    } 
    else {
        // Peserta: Cari di Katalog Kursus
        router.get(route('my-courses'), { 
            active_tab: 'available', // Opsional: Paksa cari di tab Available
            search: globalSearch.value 
        });
    }
};

// --- STATE GLOBAL LOADING ---
const isGlobalLoading = ref(false);

// Computed Property untuk Menentukan Tujuan Route
const taskIndexRoute = computed(() => {
    // const user = page.props.auth.user;
    
    // Cek Role (Sesuaikan logika ini dengan struktur data user Anda)
    // Contoh 1: Jika pakai kolom string biasa (user.role)
    // const isMentor = user.role === 'mentor';
    
    // Contoh 2: Jika pakai Spatie/Relasi (user.roles array)
    const isMentor = user.roles.some(r => r.name === 'mentor');

    if (isMentor) {
        // Arahkan ke Halaman Mentor Task (yang baru kita buat)
        return route('mentor.tasks.index');
    } else {
        // Default ke Halaman Admin (jika admin)
        return route('admin.submissions.index');
    }
});

// --- REAL-TIME LISTENER (BARU DISINI) ---
onMounted(() => {
    // 1. Loading Listener
    router.on('start', () => isGlobalLoading.value = true);
    router.on('finish', () => isGlobalLoading.value = false);

    // 2. REVERB / WEBSOCKET LISTENER
    if (user) {
        // Subscribe ke Channel Pribadi User: App.Models.User.{id}
        window.Echo.private(`App.Models.User.${user.id}`)
            
            // A. LISTEN: TUGAS MASUK (Untuk Mentor)
            .listen('.TaskSubmitted', (e) => {
                if (isMentor.value) {
                    console.log('Realtime: Tugas Masuk', e.submission);
                    
                    // Tampilkan Notifikasi Browser / Alert
                    window.dispatchEvent(new CustomEvent('trigger-toast', { 
                        detail: { 
                            message: `🔔 Tugas Baru! ${e.submission.user.name} mengumpulkan tugas.`, 
                            type: 'success' 
                        } 
                    }));
                    router.reload({ 
                        only: ['auth'], // Cukup reload 'auth', karena counter ada di dalamnya
                        preserveScroll: true 
                    });
                }
            })
            
            // B. LISTEN: NILAI KELUAR (Untuk Peserta)
            .listen('.TaskGraded', (e) => {
                if (!isMentor.value && !isAdmin.value) {
                    console.log('Realtime: Nilai Keluar', e.submission);
                    
                    // Tampilkan Notifikasi
                    window.dispatchEvent(new CustomEvent('trigger-toast', { 
                        detail: { 
                            message: `🎉 Tugas Dinilai! Skor: ${e.submission.score_mentor}/100`, 
                            type: 'success' 
                        } 
                    }));
                    
                    setTimeout(() => {
                        router.reload({ 
                            only: ['auth'], 
                            preserveScroll: true 
                        });
                    }, 800);
                    
                }
            })

            // C. LISTEN: ACHIEVEMENT / BADGE BARU
            .listen('.BadgeWon', (e) => {
                
                // 1. Munculkan Toast (Kecil di pojok)
                window.dispatchEvent(new CustomEvent('trigger-toast', { 
                    detail: { 
                        message: `🏆 Luar Biasa! Anda mendapatkan lencana: "${e.badge.badge_name}"`, 
                        type: 'success' 
                    } 
                }));

                // 2. TRIGGER MODAL (JALUR KHUSUS) - FIX BUG ANDA
                // Kirim event langsung ke AchievementModal.vue agar tidak tertimpa reload
                window.dispatchEvent(new CustomEvent('trigger-achievement-modal', { 
                    detail: e.badge 
                }));

                // 3. Update Data Latar Belakang (Reload Inertia)
                // Reload ini akan memperbarui XP dan Badge Count di header,
                // tapi TIDAK akan menutup modal karena modal sekarang punya state sendiri.
                setTimeout(() => {
                    router.reload({ 
                        only: ['auth', 'userStats', 'latestBadges'], 
                        preserveScroll: true 
                    });
                }, 1000);
            })

            // Ini akan menangkap NewCourseContent & TaskDeadlineWarning
            .notification((notification) => {
                console.log('Notifikasi Masuk:', notification);

                // 1. Munculkan Toast
                window.dispatchEvent(new CustomEvent('trigger-toast', { 
                    detail: { 
                        message: notification.message || 'Anda memiliki notifikasi baru.', 
                        type: notification.type === 'warning' ? 'error' : 'info' 
                    } 
                }));

                // 2. Reload Data Auth (Agar angka di lonceng bertambah)
                router.reload({ only: ['auth'] });
            });
    }
});

// Bersihkan listener saat komponen hancur (opsional tapi good practice)
onUnmounted(() => {
    if (user) {
        window.Echo.leave(`App.Models.User.${user.id}`);
    }
});

// --- HELPER: INITIALS ---
const getInitials = (name) => {
    if (!name) return 'UR'; 
    return name.split(' ').map(n => n[0]).join('').substring(0, 2).toUpperCase();
};

// --- STATE SIDEBAR ---
const storedState = localStorage.getItem('isSidebarOpen');
const isSidebarOpen = ref(storedState === null ? true : storedState === 'true');
const isMobileMenuOpen = ref(false);
const menuRefs = ref({});
const tooltipPositions = ref({});
const hoveredMenuIndex = ref(null);

const setMenuRef = (el, index) => {
    if (el) {
        menuRefs.value[index] = el;
    }
};

const updateTooltipPosition = (index, event) => {
    if (isSidebarOpen.value) {
        hoveredMenuIndex.value = null;
        return;
    }
    
    const menuItem = menuRefs.value[index];
    if (menuItem) {
        hoveredMenuIndex.value = index;
        const rect = menuItem.getBoundingClientRect();
        tooltipPositions.value[index] = {
            left: rect.right + 8,
            top: rect.top + rect.height / 2
        };
    }
};

const hideTooltip = () => {
    hoveredMenuIndex.value = null;
};

const toggleSidebar = () => {
    isSidebarOpen.value = !isSidebarOpen.value;
    localStorage.setItem('isSidebarOpen', isSidebarOpen.value);
    // Clear tooltip positions when sidebar opens
    if (isSidebarOpen.value) {
        tooltipPositions.value = {};
    }
};

const toggleMobileMenu = () => {
    isMobileMenuOpen.value = !isMobileMenuOpen.value;
    // Pastikan sidebar terbuka saat mobile menu dibuka
    if (isMobileMenuOpen.value) {
        isSidebarOpen.value = true;
    }
};

const closeMobileMenu = () => {
    isMobileMenuOpen.value = false;
};

// --- STATE LOGOUT ---
const isLogoutModalOpen = ref(false);
const openLogoutModal = () => isLogoutModalOpen.value = true;
const closeLogoutModal = () => isLogoutModalOpen.value = false;

const logout = () => {
    router.post(route('logout'));
}

// --- LOGIKA MENU BERDASARKAN ROLE ---
// Cek apakah user adalah admin
const isAdmin = computed(() => {
    return user.roles && user.roles.length > 0 && user.roles[0].name === 'admin';
});

// Cek apakah user adalah mentor
const isMentor = computed(() => {
    return user.roles && user.roles.length > 0 && user.roles[0].name === 'mentor';
});

// Cek apakah user berhak menilai (Admin atau Mentor) - Untuk Header Icon
const canGrade = computed(() => isAdmin.value || isMentor.value);

const pendingSubmissions = computed(() => usePage().props.auth.pendingSubmissionsCount || 0);

// 1. Menu untuk PESERTA (Intern)
const participantMenu = [
    { 
        name: 'Beranda', 
        route: 'dashboard', 
        activeCheck: ['dashboard'],
        icon: 'M3.75 6A2.25 2.25 0 016 3.75h2.25A2.25 2.25 0 0110.5 6v2.25a2.25 2.25 0 01-2.25 2.25H6a2.25 2.25 0 01-2.25-2.25V6zM3.75 15.75A2.25 2.25 0 016 13.5h2.25a2.25 2.25 0 012.25 2.25V18a2.25 2.25 0 01-2.25 2.25H6A2.25 2.25 0 013.75 18v-2.25zM13.5 6a2.25 2.25 0 012.25-2.25H18A2.25 2.25 0 0120.25 6v2.25A2.25 2.25 0 0118 10.5h-2.25a2.25 2.25 0 01-2.25-2.25V6zM13.5 15.75a2.25 2.25 0 012.25-2.25H18a2.25 2.25 0 012.25 2.25V18A2.25 2.25 0 0118 20.25h-2.25A2.25 2.25 0 0113.5 18v-2.25z' 
    },
    { 
        name: 'Kursus Saya', 
        route: 'my-courses', 
        activeCheck: ['my-courses', 'course.show', 'module.read', 'quiz.show', 'task.show'],
        icon: 'M4.26 10.147a60.436 60.436 0 00-.491 6.347A48.627 48.627 0 0112 20.904a48.627 48.627 0 018.232-4.41 60.46 60.46 0 00-.491-6.347m-15.482 0a50.57 50.57 0 00-2.658-.813A59.905 59.905 0 0112 3.493a59.902 59.902 0 0110.499 5.221 69.17 69.17 0 00-2.658.814m-15.482 0A50.697 50.697 0 0112 13.489a50.702 50.702 0 017.74-3.342M6.75 15a.75.75 0 100-1.5.75.75 0 000 1.5zm0 0v-3.675A55.378 55.378 0 0112 8.443m-7.007 11.55A5.981 5.981 0 006.75 15.75v-1.5' 
    },
    { 
        name: 'Papan Peringkat', 
        route: 'leaderboard', 
        activeCheck: ['leaderboard'],
        icon: 'M16.5 18.75h-9v-9.999h9v9.999z M7.5 8.75V1.5h9v7.25 M12 1.5v7.25' 
    },
    { 
        name: 'Penghargaan', 
        route: 'achievements', 
        activeCheck: ['achievements'],
        icon: 'M11.48 3.499a.562.562 0 011.04 0l2.125 5.111a.563.563 0 00.475.345l5.518.442c.499.04.701.663.321.988l-4.204 3.602a.563.563 0 00-.182.557l1.285 5.385a.562.562 0 01-.84.61l-4.725-2.885a.563.563 0 00-.586 0L6.982 20.54a.562.562 0 01-.84-.61l1.285-5.386a.562.562 0 00-.182-.557l-4.204-3.602a.563.563 0 01.321-.988l5.518-.442a.563.563 0 00.475-.345L11.48 3.5z' 
    },
    { 
        name: 'Riwayat Tugas', 
        route: 'my-tasks', 
        activeCheck: ['my-tasks'], 
        icon: 'M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 002.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 00-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 00.75-.75 2.25 2.25 0 00-.1-.664m-5.8 0A2.251 2.251 0 0113.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25zM6.75 12h.008v.008H6.75V12zm0 3h.008v.008H6.75V15zm0 3h.008v.008H6.75V18z' 
    },
    { 
        name: 'Laporan', 
        route: 'progress', 
        activeCheck: ['progress'],
        icon: 'M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z' 
    },
];

// 2. Menu untuk ADMIN
const adminMenu = [
    {
        name: 'Beranda',
        route: 'admin.dashboard',
        activeCheck: ['admin.dashboard'],
        icon: 'M3.75 6A2.25 2.25 0 016 3.75h2.25A2.25 2.25 0 0110.5 6v2.25a2.25 2.25 0 01-2.25 2.25H6a2.25 2.25 0 01-2.25-2.25V6zM3.75 15.75A2.25 2.25 0 016 13.5h2.25a2.25 2.25 0 012.25 2.25V18a2.25 2.25 0 01-2.25 2.25H6A2.25 2.25 0 013.75 18v-2.25zM13.5 6a2.25 2.25 0 012.25-2.25H18A2.25 2.25 0 0120.25 6v2.25A2.25 2.25 0 0118 10.5h-2.25a2.25 2.25 0 01-2.25-2.25V6zM13.5 15.75a2.25 2.25 0 012.25-2.25H18a2.25 2.25 0 012.25 2.25V18A2.25 2.25 0 0118 20.25h-2.25A2.25 2.25 0 0113.5 18v-2.25z'
    },
    {
        name: 'Kelola Pengguna',
        route: 'admin.users.index',
        activeCheck: ['admin.users'],
        icon: 'M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z'
    },
    {
        name: 'Kelola Departemen',
        route: 'admin.departments.index',
        activeCheck: ['admin.departments'],
        icon: 'M2.25 21h19.5m-18-18v18m10.5-18v18m6-13.5V21M6.75 6.75h.75m-.75 3h.75m-.75 3h.75m3-6h.75m-.75 3h.75m-.75 3h.75M6.75 21v-3.375c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21M3 3h12m-.75 4.5H21m-3.75 3.75h.008v.008h-.008v-.008zm0 3h.008v.008h-.008v-.008zm0 3h.008v.008h-.008v-.008z'
    },
    {
        name: 'Kelola Kursus',
        route: 'admin.courses.index',
        activeCheck: ['admin.courses','admin.courses.edit','admin.quizzes.builder'],
        icon: 'M12 6.042A8.967 8.967 0 006 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 016 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 016-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0018 18a8.967 8.967 0 00-6 2.292m0-14.25v14.25'
    },
    {
        name: 'Daftar Tugas',
        route: 'admin.submissions.index', 
        activeCheck: ['admin.submissions.index'],
        icon: 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4' 
    },
    {
        name: 'Laporan Peserta',
        route: 'admin.reports.index',
        activeCheck: ['admin.reports'],
        icon: 'M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z'
    },
    {
        name: 'Kelola Gamifikasi',
        route: 'admin.gamification.index',
        activeCheck: ['admin.gamification'],
        icon: 'M16.5 18.75h-9v-9.999h9v9.999zM7.5 8.75V1.5h9v7.25M12 1.5v7.25'
    }
]

// 3. Menu untuk MENTOR
const mentorMenu = [
    {
        name: 'Beranda',
        route: 'mentor.dashboard', 
        activeCheck: ['mentor.dashboard'],
        icon: 'M3.75 6A2.25 2.25 0 016 3.75h2.25A2.25 2.25 0 0110.5 6v2.25a2.25 2.25 0 01-2.25 2.25H6a2.25 2.25 0 01-2.25-2.25V6zM3.75 15.75A2.25 2.25 0 016 13.5h2.25a2.25 2.25 0 012.25 2.25V18a2.25 2.25 0 01-2.25 2.25H6A2.25 2.25 0 013.75 18v-2.25zM13.5 6a2.25 2.25 0 012.25-2.25H18A2.25 2.25 0 0120.25 6v2.25A2.25 2.25 0 0118 10.5h-2.25a2.25 2.25 0 01-2.25-2.25V6zM13.5 15.75a2.25 2.25 0 012.25-2.25H18a2.25 2.25 0 012.25 2.25V18A2.25 2.25 0 0118 20.25h-2.25A2.25 2.25 0 0113.5 18v-2.25z' 
    },
    {
        name: 'Kelola Kursus',
        route: 'mentor.courses.index', 
        activeCheck: ['mentor.courses.index', 'mentor.courses.edit'],
        icon: 'M12 6.042A8.967 8.967 0 006 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 016 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 016-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0018 18a8.967 8.967 0 00-6 2.292m0-14.25v14.25'
    },
    {
        name: 'Daftar Tugas',
        route: 'mentor.tasks.index', 
        activeCheck: ['mentor.tasks.index'],
        icon: 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4' 
    },
    {
        name: 'Laporan Peserta',
        route: 'mentor.reports.index', 
        activeCheck: ['mentor.reports.index'],
        icon: 'M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z'
    }
];
// Pilih menu yang aktif berdasarkan role
const currentMenuItems = computed(() => {
    if (isAdmin.value) return adminMenu;
    if (isMentor.value) return mentorMenu;
    return participantMenu;
});
</script>

<template>
    <!-- Header -->
    <div class="flex h-screen w-full bg-gray-50 font-sans overflow-hidden">
        
        <!-- Mobile Backdrop -->
        <div 
            v-if="isMobileMenuOpen"
            @click="closeMobileMenu"
            class="fixed inset-0 bg-gray-900 bg-opacity-50 z-30 lg:hidden transition-opacity duration-300"
        ></div>
        
        <aside 
            class="bg-blue-900 text-white flex flex-col fixed h-full z-40 transition-all duration-300 ease-in-out shadow-xl flex-shrink-0 overflow-x-hidden
                   lg:translate-x-0 lg:static lg:shadow-none lg:z-50"
            :class="[
                isMobileMenuOpen ? 'translate-x-0 w-64' : '-translate-x-full lg:translate-x-0',
                isSidebarOpen ? 'lg:w-64' : 'lg:w-20'
            ]"
        >
            <div class="h-16 flex items-center justify-between px-4 border-b border-blue-800 shrink-0 overflow-x-hidden">
                <div v-if="isSidebarOpen || isMobileMenuOpen" class="flex items-center gap-2 transition-opacity duration-200 overflow-hidden whitespace-nowrap min-w-0">
                    <ApplicationLogo class="w-8 h-8 text-white shrink-0" />
                    <span class="text-xl font-bold tracking-wide">RE:SKILL</span>
                    <span v-if="isAdmin" class="bg-red-500 text-white text-[10px] px-1.5 py-0.5 rounded font-bold">ADM</span>
                    <span v-if="isMentor" class="bg-green-500 text-white text-[10px] px-1.5 py-0.5 rounded font-bold">MNT</span>
                </div>
                <div v-else class="mx-auto w-full flex justify-center">
                    <ApplicationLogo class="w-8 h-8 text-white" />
                </div>
                <div class="flex items-center gap-2">
                    <!-- Close button for mobile -->
                    <button 
                        v-if="isMobileMenuOpen" 
                        @click="closeMobileMenu" 
                        class="lg:hidden p-1 rounded-md text-blue-300 hover:text-white hover:bg-blue-800 transition"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                    <!-- Toggle sidebar button for desktop -->
                    <button 
                        v-if="isSidebarOpen" 
                        @click="toggleSidebar" 
                        class="hidden lg:block ml-auto p-1 rounded-md text-blue-300 hover:text-white hover:bg-blue-800 transition"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 19l-7-7 7-7m8 14l-7-7 7-7" /></svg>
                    </button>
                </div>
            </div>
            <div v-if="!isSidebarOpen" class="hidden lg:flex justify-center py-2 border-b border-blue-800">
                <button @click="toggleSidebar" class="p-1 rounded-md hover:bg-blue-700 focus:outline-none text-blue-200 hover:text-white">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 5l7 7-7 7M5 5l7 7-7 7" /></svg>
                </button>
            </div>

            <nav class="flex-1 py-6 px-3 space-y-2 overflow-y-auto overflow-x-hidden">
                <div v-for="(item, index) in currentMenuItems" :key="item.name" class="relative group" :ref="el => setMenuRef(el, index)">
                    <Link 
                        :href="route(item.route)" 
                        @click="closeMobileMenu"
                        @mouseenter="updateTooltipPosition(index, $event)"
                        @mouseleave="hideTooltip"
                        :class="[
                            'flex items-center px-3 py-3 rounded-lg transition-all duration-200',
                            (route().current(item.route) || (item.activeCheck && item.activeCheck.some(r => route().current(r))))
                                ? 'bg-blue-800 text-white shadow-md' 
                                : 'text-blue-200 hover:bg-blue-800 hover:text-white',
                            (!isSidebarOpen && !isMobileMenuOpen) ? 'justify-center lg:justify-center' : ''
                        ]">
                        
                        <svg class="w-6 h-6 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="item.icon"></path>
                        </svg>
                        
                        <span 
                            class="ml-3 text-sm font-medium whitespace-nowrap transition-all duration-300"
                            :class="(isSidebarOpen || isMobileMenuOpen) ? 'opacity-100' : 'opacity-0 w-0 overflow-hidden lg:opacity-0 lg:w-0'"
                        >
                            {{ item.name }}
                        </span>
                    </Link>

                    <Teleport to="body">
                        <div 
                            v-if="!isSidebarOpen && hoveredMenuIndex === index && tooltipPositions[index]" 
                            class="hidden lg:block fixed px-3 py-1 bg-gray-900 text-white text-xs font-bold rounded opacity-100 transition-opacity whitespace-nowrap z-[99999] pointer-events-none shadow-lg"
                            :style="{
                                left: tooltipPositions[index].left + 'px',
                                top: tooltipPositions[index].top + 'px',
                                transform: 'translateY(-50%)'
                            }"
                        >
                            {{ item.name }}
                            <div class="absolute top-1/2 right-full -translate-y-1/2 -mr-1 border-4 border-transparent border-r-gray-900"></div>
                        </div>
                    </Teleport>
                </div>
            </nav>
        </aside>

        <main class="flex-1 flex flex-col min-h-screen overflow-hidden w-full overflow-x-hidden relative z-0">
            
            <header class="h-16 bg-white border-b border-gray-200 flex items-center justify-between px-3 sm:px-4 lg:px-8 shadow-sm shrink-0 z-50 overflow-x-hidden overflow-y-hidden relative">
                
                <div class="flex items-center gap-2 sm:gap-4 flex-1 min-w-0">
                    <button @click="toggleMobileMenu" class="lg:hidden p-2 rounded-md text-gray-600 hover:bg-gray-100 focus:outline-none flex-shrink-0">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" /></svg>
                    </button>

                    <div class="relative w-full max-w-md hidden sm:block">
                        <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-400">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
                        </span>
                        
                        <input 
                            v-model="globalSearch"
                            @keyup.enter="handleGlobalSearch"
                            type="text" 
                            class="w-full pl-10 pr-4 py-2 bg-gray-50 border-none rounded-lg text-sm focus:ring-2 focus:ring-blue-500 transition-all placeholder-gray-400" 
                            :placeholder="isAdmin || isMentor ? 'Cari data...' : 'Cari kursus...'"
                        >
                    </div>
                </div>

                <div class="flex items-center gap-2 sm:gap-4 flex-shrink-0">
                    
                    <Link 
                        v-if="canGrade" 
                        :href="taskIndexRoute" 
                        class="relative p-2 text-gray-400 hover:text-blue-600 transition rounded-full hover:bg-blue-50 focus:outline-none group z-[100]"
                        title="Daftar Tugas"
                    >
                        <svg class="w-6 h-6 sm:w-6 sm:h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
                        </svg>
                        
                        <span v-if="pendingSubmissions > 0" 
                            class="absolute -top-1 -right-1 inline-flex items-center justify-center px-1.5 py-0.5 text-[10px] font-bold leading-none text-white bg-red-600 rounded-full border-2 border-white shadow-sm min-w-[20px]">
                            {{ pendingSubmissions > 99 ? '99+' : pendingSubmissions }}
                        </span>
                    </Link>

                    <div v-else class="relative z-[100]">
                        <NotificationDropdown />
                    </div>
                    
                    <div class="pl-2 sm:pl-4 border-l border-gray-200 sm:ml-2 relative z-[100]">
                        <UserDropdown @open-logout-modal="openLogoutModal"/>
                    </div>
                </div>
            </header>

            <div class="flex-1 overflow-y-auto overflow-x-hidden p-4 sm:p-6 lg:p-8 relative z-0">
                <slot />
            </div>

        </main>
    </div>

    <!-- Logout Modal -->
    <div v-if="isLogoutModalOpen" class="fixed inset-0 z-50 flex items-center justify-center px-4 sm:px-6">
        <div class="fixed inset-0 bg-gray-900 opacity-60 transition-opacity" @click="closeLogoutModal"></div>
        <div class="bg-white rounded-2xl overflow-hidden shadow-2xl transform transition-all sm:max-w-md w-full p-4 sm:p-6 relative z-10 scale-100">
            <div class="text-center">
                <div class="mx-auto flex items-center justify-center h-16 w-16 rounded-full bg-red-100 mb-6">
                    <svg class="h-8 w-8 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
                    </svg>
                </div>
                
                <h3 class="text-xl font-bold text-gray-900 mb-2">Keluar dari Akun?</h3>
                <p class="text-sm text-gray-500 mb-8">Apakah Anda yakin ingin keluar? Anda perlu masuk kembali untuk mengakses akun Anda.</p>
                
                <div class="flex gap-3">
                    <button 
                        @click="closeLogoutModal"
                        class="w-full px-4 py-3 bg-white border border-gray-300 rounded-xl text-sm font-bold text-gray-700 hover:bg-gray-50 focus:outline-none transition"
                    >
                        Batal
                    </button>
                    <button 
                        @click="logout"
                        class="w-full px-4 py-3 bg-red-600 border border-transparent rounded-xl text-sm font-bold text-white hover:bg-red-700 focus:outline-none shadow-lg shadow-red-200 transition"
                    >
                        Iya, Keluar
                    </button> 
                </div>
            </div>
        </div>
    </div>

    <OnboardingModal />
    <AchievementModal />
    <Toast />
    <LoadingOverlay :show="isGlobalLoading" text="Memuat halaman..." />

</template>