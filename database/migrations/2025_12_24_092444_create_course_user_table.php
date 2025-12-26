<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Nama tabel HARUS 'course_user' sesuai definisi di Model Anda
        Schema::create('course_user', function (Blueprint $table) {
            $table->id();

            // Foreign Keys
            // constrained() otomatis mencari id di tabel courses dan users
            // onDelete('cascade') artinya jika User/Course dihapus, data enroll-nya ikut terhapus
            $table->foreignId('course_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');

            // Kolom Pivot (WAJIB ADA karena dipanggil di ->withPivot(...))
            $table->string('status')->default('active'); // status: active, completed, dropped
            $table->integer('progress')->default(0);     // progress: 0 - 100

            // Kolom timestamps (WAJIB ADA karena dipanggil di ->withTimestamps())
            $table->timestamps();

            // Mencegah duplikasi data (Satu user tidak bisa daftar kursus yang sama 2x)
            $table->unique(['course_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('course_user');
    }
};
