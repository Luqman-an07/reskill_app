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
        Schema::create('gamification_ledgers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');

            // Jumlah poin (+ atau -)
            $table->integer('point_amount');

            // Kode alasan (QUIZ_PASS, TASK_PASS, LEVEL_UP)
            $table->string('reason_code');

            // Polymorphic Relation (Bisa connect ke Quiz, Task, atau Module)
            // Ini akan membuat kolom: related_id (INT) dan related_type (STRING)
            $table->nullableMorphs('related');

            $table->text('description')->nullable();
            $table->timestamp('transaction_date')->useCurrent();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('gamification_ledgers');
    }
};
