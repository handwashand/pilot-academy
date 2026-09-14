<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * One extra quiz attempt for one student, given by an admin.
 *
 * Before this the only way to help a student who ran out of attempts was to
 * raise Max attempts on the course or lesson — for everyone. A grant is scoped
 * to one person and one quiz, and records who gave it and why.
 *
 * Exactly one of course_id (the final quiz) and lesson_id (a lesson's knowledge
 * check) is set.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attempt_grants', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('course_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('lesson_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('granted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('reason')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'course_id']);
            $table->index(['user_id', 'lesson_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attempt_grants');
    }
};
