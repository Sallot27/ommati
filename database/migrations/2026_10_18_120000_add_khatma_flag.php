<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('activity_days', function (Blueprint $table) {
            $table->boolean('khatma_read')->default(false)->after('hadith_read');
        });
    }

    public function down(): void
    {
        Schema::table('activity_days', fn (Blueprint $table) => $table->dropColumn('khatma_read'));
    }
};
