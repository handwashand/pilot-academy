<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Live sessions partners can join, and their recordings afterwards. Times are
 * stored in UTC and shown with the zone named, so nobody joins an hour late.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('webinars', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('cover_media_item_id')->nullable()->constrained('media_items')->nullOnDelete();
            $table->string('title');
            $table->string('language', 10)->nullable();
            $table->string('slug')->unique();
            $table->text('summary')->nullable();
            $table->text('description')->nullable();
            $table->string('presenter')->nullable();
            $table->timestamp('starts_at')->nullable();
            $table->unsignedSmallInteger('duration_minutes')->nullable();
            $table->string('join_url', 2048)->nullable();
            $table->string('recording_url', 2048)->nullable();
            $table->string('status')->default('draft');
            $table->timestamp('published_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'starts_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('webinars');
    }
};
