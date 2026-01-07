# RE:SKILL - Corporate Gamified E-Learning System 🚀

**RE:SKILL** adalah Sistem Manajemen Pembelajaran (LMS) internal yang dikembangkan untuk **PT. Ayo Menebar Kebaikan (AMK Group)**. Sistem ini bertujuan untuk meningkatkan kompetensi dan motivasi karyawan melalui integrasi metode **Gamifikasi** (Poin, Badge, Leaderboard) ke dalam proses pelatihan.

![Project Status](https://img.shields.io/badge/Status-Development-orange)
![Laravel](https://img.shields.io/badge/Backend-Laravel-red)
![Vue.js](https://img.shields.io/badge/Frontend-Vue.js%20%2B%20Inertia-green)

---

## 📋 Fitur Utama

### 1. 🎓 Manajemen Pembelajaran (LMS)
* **Gatekeeper Logic (3 Prioritas):** Materi dikelompokkan menjadi *Wajib (General)*, *Kompetensi Bidang*, dan *Pengembangan Diri*. Tab berikutnya terkunci sebelum tab sebelumnya selesai.
* **Sequential Learning:** Modul harus diakses secara berurutan.
* **Multi-Format Content:** Mendukung materi Teks, Video, PDF, dan PPT.

### 2. 🎮 Gamifikasi (Gamification Engine)
* **XP System:** Poin otomatis didapat dari membaca materi, lulus kuis, dan menyelesaikan kursus.
* **Badges (Lencana):** Reward visual otomatis (Contoh: *Quiz Master* untuk nilai 100, *Course Finisher*).
* **Leaderboard:** Papan peringkat kompetisi antar karyawan (Global & Departemen).
* **Daily Streak:** Mencatat aktivitas harian berturut-turut.

### 3. 📝 Evaluasi & Penilaian
* **Kuis Dinamis:**
    * Dilengkapi **Timer Mundur** (Anti-refresh & Server-side validation).
    * Mode *Review* jika kesempatan habis.
    * Navigasi soal interaktif.
* **Tugas Praktik (Assignments):** Fitur upload file tugas dengan status *Pending/Graded* dan *Feedback* dari mentor.

### 4. 📊 Monitoring (Admin/Mentor)
* Dashboard Statistik Real-time.
* Laporan Progres Peserta per Departemen.
* Manajemen User & Role (Admin, Mentor, Peserta).

---

## 🛠️ Teknologi yang Digunakan

* **Backend:** Laravel 10/11
* **Frontend:** Vue.js 3 (Composition API)
* **Adapter:** Inertia.js
* **Styling:** Tailwind CSS
* **Database:** MySQL
* **Icons:** Heroicons / FontAwesome

---

## ⚙️ Instalasi & Setup Project

Ikuti langkah ini untuk menjalankan proyek di komputer lokal (Localhost):

### 1. Clone Repository
```bash
git clone [https://github.com/username/reskill-lms.git](https://github.com/username/reskill-lms.git)
cd reskill-lms

### 2. Install Dependencies
```bash
# Install PHP Dependencies
composer install

# Install Node Modules
npm install

### 3. Konfigurasi Environment
cp .env.example .env
php artisan key:generate

### 4. Migrasi & Seeding Data (PENTING)
php artisan migrate:fresh --seed

### 5. Jalankan Aplikasi
# Terminal 1 (Backend)
php artisan serve

# Terminal 2 (Frontend)
npm run dev

Akses aplikasi di: http://127.0.0.1:8000

Struktur Kode Penting (Developer Notes)
Untuk pengembang selanjutnya, harap perhatikan lokasi logika utama berikut:

1. App\Services\GamificationService.php
Logika inti perhitungan poin dan pengecekan badge.

Function utama: awardPoints(), checkBadges(), tryFinishCourse().

Note: Gunakan service ini di Controller, jangan menulis logika poin manual.

2. App\Http\Controllers\QuizController.php
Menangani logika Timer (Server-side calculation menggunakan remaining_seconds).

Menangani logika Anti-Cheat dan Scoring.

3. App\Http\Controllers\Admin\CourseContentController.php
Menangani CRUD Modul (Text, Quiz, Task).

Penting: Saat menyimpan Quiz, durasi disimpan ke tabel quizzes (kolom duration_minutes), bukan hanya di tabel module.

4. Database Schema
gamification_ledgers: Mencatat riwayat transaksi poin.

badges: Master data lencana.

user_badges: Pivot table user <-> badge.

user_module_progress: Mencatat status penyelesaian modul.

🐛 Known Issues & Troubleshooting
Timer Kuis Reset: Jika timer kuis tidak sesuai, pastikan kolom duration_minutes di tabel quizzes terisi integer (bukan 0/null). Gunakan form admin yang sudah diperbarui.

Bonus Kursus Tidak Cair: Pastikan semua modul dalam kursus statusnya completed di tabel user_module_progress.

👤 Kontributor
Luqman Anas Naufal

Role: Fullstack Developer Intern

Instansi: Universitas Duta Bangsa Surakarta

Magang di: PT. Ayo Menebar Kebaikan