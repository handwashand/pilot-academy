<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * What a student thought of a course, asked once they finish it.
     *
     * Deliberately not public star ratings — that is a marketplace mechanic for
     * helping strangers choose between competing sellers, and this is training
     * a company assigns. A thumb and an optional sentence, visible to staff
     * only. The dashboard can already show which lessons students *fail*; this
     * is the different question of which ones they found useless.
     */
    public function up(): void
    {
        Schema::create('course_feedback', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('course_id')->constrained()->cascadeOnDelete();
            $table->boolean('is_positive');
            $table->text('comment')->nullable();
            $table->timestamps();

            // One verdict per student per course; they can change their mind.
            $table->unique(['user_id', 'course_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('course_feedback');
    }
};
