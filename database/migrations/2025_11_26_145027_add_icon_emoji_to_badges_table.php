<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('badges', function (Blueprint $table) {
            $table->string('icon_emoji')->nullable()->after('icon_url');
            // Jadikan icon_url nullable karena sekarang opsional (bisa pakai emoji)
            $table->string('icon_url')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('badges', function (Blueprint $table) {
            $table->dropColumn('icon_emoji');
            // Kembalikan ke nullable false jika perlu (tapi hati-hati data lama)
            // $table->string('icon_url')->nullable(false)->change();
        });
    }
};
