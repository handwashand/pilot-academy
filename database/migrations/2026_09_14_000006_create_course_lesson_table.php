<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * A lesson can be in more than one course.
 *
 * course_lesson says which courses a lesson is in, and where it sits in each.
 * lessons.course_id stays: it is the lesson's home course, which decides who may
 * edit it, so a creator cannot change another product's lesson just because it
 * was shared into their course.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('course_lesson', function (Blueprint $table): void {
            $table->foreignId('course_id')->constrained()->cascadeOnDelete();
            $table->foreignId('lesson_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->primary(['course_id', 'lesson_id']);
            $table->index('lesson_id');
        });

        // Every existing lesson starts in the course it is in now, in the same place.
        DB::table('lessons')->orderBy('id')->chunkById(500, function ($lessons): void {
            DB::table('course_lesson')->insert($lessons->map(fn ($lesson): array => [
                'course_id' => $lesson->course_id,
                'lesson_id' => $lesson->id,
                'sort_order' => $lesson->sort_order,
                'created_at' => now(),
                'updated_at' => now(),
            ])->all());
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('course_lesson');
    }
};
