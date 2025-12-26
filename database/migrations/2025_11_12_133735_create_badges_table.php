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
        Schema::create('badges', function (Blueprint $table) {
            $table->id();
            $table->string('badge_name');
            $table->text('description')->nullable();
            $table->string('icon_url')->nullable(); // Path gambar ikon

            // Pemicu otomatis badge
            $table->string('trigger_type')->nullable(); // e.g. 'POINTS_REACHED'
            $table->string('trigger_value')->nullable(); // e.g. '1000'

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('badges');
    }
};
