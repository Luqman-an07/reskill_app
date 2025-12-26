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
        Schema::table('tasks', function (Blueprint $table) {
            $table->dateTime('due_date')->nullable()->change();

            // Tambah kolom durasi (hari), misal: 3 hari, 7 hari
            $table->integer('duration_days')->nullable()->after('due_date')
                ->comment('Durasi pengerjaan dalam hari (Relative Deadline)');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->dropColumn('duration_days');
        });
    }
};
