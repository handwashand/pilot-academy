<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Where a student got to inside a lesson video, so being interrupted 18
     * minutes into a 25-minute video does not mean starting again.
     *
     * Deliberately NOT a column on the lesson_user pivot. That pivot is read as
     * "lessons this user completed" — `completedLessons()` does not filter on
     * completed_at, and neither do the dashboard's raw queries — so a row
     * written when someone merely pressed play would count as a completion
     * everywhere: progress bars, partner reports, and the gate that unlocks the
     * final quiz and issues a certificate.
     */
    public function up(): void
    {
        Schema::create('video_positions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('lesson_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('seconds')->default(0);
            $table->timestamps();

            // One position per student per lesson; the write path upserts on it.
            $table->unique(['user_id', 'lesson_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('video_positions');
    }
};
