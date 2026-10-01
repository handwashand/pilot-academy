<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Standalone videos for the Tutorials page: a short how-to that belongs to no
 * course. One video each, a YouTube link or an uploaded file, the same two
 * sources a lesson takes.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tutorials', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title');
            $table->string('language', 10)->nullable();
            $table->string('slug')->unique();
            $table->text('summary')->nullable();
            $table->string('type')->default('youtube');
            $table->string('youtube_url', 2048)->nullable();
            $table->string('video_path')->nullable();
            $table->unsignedSmallInteger('duration_minutes')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->string('status')->default('draft');
            $table->timestamp('published_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tutorials');
    }
};
