<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_admin')->default(false)->after('password');
        });

        Schema::create('settings', function (Blueprint $table) {
            $table->string('key')->primary();
            $table->text('value')->nullable();
            $table->timestamps();
        });

        Schema::create('books', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('author')->nullable();
            $table->string('category')->nullable()->index();
            $table->text('description')->nullable();
            $table->string('disk');
            $table->string('path');
            $table->string('original_name')->nullable();
            $table->string('mime')->nullable();
            $table->unsignedBigInteger('size')->default(0);
            $table->string('source')->default('upload'); // upload | telegram
            $table->string('telegram_file_unique_id')->nullable()->unique();
            $table->timestamp('last_opened_at')->nullable();
            $table->timestamps();
        });

        Schema::create('hadith_pool', function (Blueprint $table) {
            $table->unsignedBigInteger('id')->primary(); // HadeethEnc id
            $table->string('title');
            $table->unsignedInteger('category_id')->index();
            $table->timestamps();
        });

        Schema::create('hadiths', function (Blueprint $table) {
            $table->unsignedBigInteger('id')->primary(); // HadeethEnc id
            $table->string('title');
            $table->text('text');
            $table->string('attribution')->nullable();
            $table->string('grade')->nullable();
            $table->text('explanation')->nullable();
            $table->json('hints')->nullable();
            $table->json('words_meanings')->nullable();
            $table->text('reference')->nullable();
            $table->boolean('is_favorite')->default(false);
            $table->timestamps();
        });

        Schema::create('daily_picks', function (Blueprint $table) {
            $table->date('date')->primary();
            $table->unsignedBigInteger('hadith_id')->nullable();
            $table->string('ayah_ref')->nullable(); // "surah:ayah"
            $table->timestamps();
        });

        Schema::create('memorized_ayahs', function (Blueprint $table) {
            $table->id();
            $table->unsignedSmallInteger('surah');
            $table->unsignedSmallInteger('ayah');
            $table->unsignedTinyInteger('level')->default(0); // spaced-repetition step
            $table->date('next_review_on')->nullable()->index();
            $table->timestamp('memorized_at')->nullable();
            $table->timestamp('last_reviewed_at')->nullable();
            $table->timestamps();
            $table->unique(['surah', 'ayah']);
        });

        Schema::create('activity_days', function (Blueprint $table) {
            $table->date('date')->primary();
            $table->unsignedInteger('ayahs_memorized')->default(0);
            $table->unsignedInteger('ayahs_reviewed')->default(0);
            $table->unsignedInteger('adhkar_sessions')->default(0);
            $table->timestamps();
        });

        Schema::create('messages', function (Blueprint $table) {
            $table->id();
            $table->text('body');
            $table->string('from_name')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('sent_reminders', function (Blueprint $table) {
            $table->string('key')->primary(); // e.g. 2026-10-17:morning
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sent_reminders');
        Schema::dropIfExists('messages');
        Schema::dropIfExists('activity_days');
        Schema::dropIfExists('memorized_ayahs');
        Schema::dropIfExists('daily_picks');
        Schema::dropIfExists('hadiths');
        Schema::dropIfExists('hadith_pool');
        Schema::dropIfExists('books');
        Schema::dropIfExists('settings');
        Schema::table('users', fn (Blueprint $table) => $table->dropColumn('is_admin'));
    }
};
