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
        Schema::create('quizzes', function (Blueprint $table) {
            $table->id();
            // Relasi ke Module (Satu modul punya satu kuis)
            $table->foreignId('module_id')->constrained()->onDelete('cascade');

            $table->string('quiz_title');
            $table->integer('duration_minutes')->default(10);
            $table->integer('passing_score')->default(70); // KKM
            $table->integer('max_attempts')->default(3); // Batas percobaan

            // Gamifikasi reward jika lulus
            $table->integer('points_reward')->default(100);

            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('quizzes');
    }
};
