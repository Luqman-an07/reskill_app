<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('modules', function (Blueprint $table) {
            $table->id();
            // Relasi ke Course
            $table->foreignId('course_id')->constrained()->onDelete('cascade');

            $table->string('module_title');

            // Jenis Konten (Text, Video, PDF, Quiz, Task)
            $table->string('content_type');

            // Link video youtube atau path file PDF
            $table->text('content_url')->nullable();

            $table->integer('module_order')->default(1); // Urutan Bab 1, 2, 3

            // Self-Referencing (Prasyarat Modul)
            // Jika modul ini dihapus, set null pada modul yang mensyaratkannya
            $table->foreignId('prerequisite_module_id')
                ->nullable()
                ->constrained('modules')
                ->onDelete('set null');

            // Gamifikasi Level Modul
            $table->integer('completion_points')->default(0);

            // Estimasi waktu baca (detik) untuk validasi time_spent
            $table->integer('required_time')->default(0);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('modules');
    }
};
