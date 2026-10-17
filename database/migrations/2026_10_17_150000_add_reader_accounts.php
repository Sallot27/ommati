<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Each family member signs in with their name only, and keeps their own
 * memorization, review schedule, favourites and preferences.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->json('prefs')->nullable()->after('is_admin');
        });

        // No real data existed before accounts, so these are rebuilt per user.
        Schema::dropIfExists('memorized_ayahs');
        Schema::dropIfExists('activity_days');

        Schema::create('memorized_ayahs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('surah');
            $table->unsignedSmallInteger('ayah');
            $table->unsignedTinyInteger('level')->default(0);
            $table->date('next_review_on')->nullable()->index();
            $table->timestamp('memorized_at')->nullable();
            $table->timestamp('last_reviewed_at')->nullable();
            $table->timestamps();
            $table->unique(['user_id', 'surah', 'ayah']);
        });

        Schema::create('activity_days', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            $table->unsignedInteger('ayahs_memorized')->default(0);
            $table->unsignedInteger('ayahs_reviewed')->default(0);
            $table->unsignedInteger('adhkar_sessions')->default(0);
            $table->boolean('morning_done')->default(false);
            $table->boolean('evening_done')->default(false);
            $table->boolean('ayah_read')->default(false);
            $table->boolean('hadith_read')->default(false);
            $table->timestamps();
            $table->unique(['user_id', 'date']);
        });

        Schema::create('hadith_favorites', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('hadith_id');
            $table->timestamps();
            $table->unique(['user_id', 'hadith_id']);
        });

        Schema::table('hadiths', function (Blueprint $table) {
            $table->dropColumn('is_favorite');
        });
    }

    public function down(): void
    {
        Schema::table('hadiths', fn (Blueprint $table) => $table->boolean('is_favorite')->default(false));
        Schema::dropIfExists('hadith_favorites');
        Schema::table('users', fn (Blueprint $table) => $table->dropColumn('prefs'));
    }
};
