<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('books', function (Blueprint $table) {
            $table->string('cover_path')->nullable()->after('path');
            $table->unsignedInteger('pages')->nullable()->after('size');
        });

        // Where each person stopped in each book.
        Schema::create('book_progress', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('book_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('page')->default(1);
            $table->string('note')->nullable(); // for non-PDF books: "where I stopped"
            $table->timestamps();
            $table->unique(['user_id', 'book_id']);
        });

        Schema::create('book_bookmarks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('book_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('page');
            $table->string('note', 300)->nullable();
            $table->timestamps();
            $table->index(['user_id', 'book_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('book_bookmarks');
        Schema::dropIfExists('book_progress');
        Schema::table('books', fn (Blueprint $table) => $table->dropColumn(['cover_path', 'pages']));
    }
};
