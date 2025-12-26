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
        Schema::create('tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('module_id')->constrained()->onDelete('cascade');

            $table->string('task_title');
            $table->text('description');

            $table->integer('max_score')->default(100);
            $table->dateTime('due_date')->nullable(); // Batas waktu

            // Gamifikasi reward jika lulus/dinilai bagus
            $table->integer('points_reward')->default(200);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tasks');
    }
};
