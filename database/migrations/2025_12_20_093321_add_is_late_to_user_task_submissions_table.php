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
        Schema::table('user_task_submissions', function (Blueprint $table) {
            $table->boolean('is_late')->default(false)->after('status')->comment('Menandai jika tugas dikumpulkan terlambat');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_task_submissions', function (Blueprint $table) {
            $table->dropColumn('is_late');
        });
    }
};
