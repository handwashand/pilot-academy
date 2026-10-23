<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Badges a student has earned. `subject` says which product or course a badge
 * is for ("product:3", "course:7"), or is empty for one-off badges. It is a
 * string, not a nullable id, so the unique key below holds on PostgreSQL too
 * (two NULLs never collide there).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_badges', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('badge', 50);
            $table->string('subject', 50)->default('');
            $table->timestamp('awarded_at');
            $table->timestamps();

            $table->unique(['user_id', 'badge', 'subject']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_badges');
    }
};
