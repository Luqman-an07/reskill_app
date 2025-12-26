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
        Schema::table('users', function (Blueprint $table) {
            // Core HR
            $table->string('employee_id')->unique()->nullable()->after('id');
            $table->foreignId('department_id')->nullable()->constrained('departments')->onDelete('set null')->after('email');
            $table->string('profile_picture')->nullable()->after('password');
            $table->boolean('is_active')->default(true)->after('profile_picture');

            // Gamifikasi
            $table->integer('total_points')->default(0);
            $table->integer('seasonal_points')->default(0);
            $table->integer('current_level')->default(1);
            $table->timestamp('last_activity_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'employee_id',
                'department_id',
                'profile_picture',
                'is_active',
                'total_points',
                'seasonal_points',
                'current_level',
                'last_activity_at'
            ]);
        });
    }
};
