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
        Schema::create('user_module_progress', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('module_id')->constrained()->onDelete('cascade');

            // Status: 'started', 'completed'
            $table->string('status')->default('started');

            // Validasi anti-cheat: Total detik yang dihabiskan
            $table->integer('time_spent')->default(0);

            // Fitur Resume: Posisi scroll terakhir atau detik video
            $table->decimal('last_position', 8, 2)->default(0);

            $table->timestamp('last_access_at')->useCurrent();
            $table->timestamp('completion_date')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_module_progress');
    }
};
