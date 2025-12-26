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
        Schema::create('user_task_submissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('task_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');

            // File yang diupload peserta
            $table->string('submission_file_url');

            // Penilaian Mentor
            $table->integer('score_mentor')->nullable();
            $table->text('feedback_mentor')->nullable();

            // Status: Apakah sudah dinilai?
            $table->boolean('is_graded')->default(false);

            $table->timestamp('submission_date')->useCurrent();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_task_submissions');
    }
};
