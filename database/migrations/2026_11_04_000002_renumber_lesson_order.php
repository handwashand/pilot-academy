<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Data only. New lessons used to be saved with order 0, so lessons in a course
 * shared numbers. Each course is renumbered 1, 2, 3… keeping its current order,
 * ties broken by which lesson was created first. Each lesson's own number then
 * matches its place in its home course.
 */
return new class extends Migration
{
    public function up(): void
    {
        $courseIds = DB::table('course_lesson')->distinct()->pluck('course_id');

        foreach ($courseIds as $courseId) {
            $lessonIds = DB::table('course_lesson')
                ->where('course_id', $courseId)
                ->orderBy('sort_order')
                ->orderBy('lesson_id')
                ->pluck('lesson_id');

            foreach ($lessonIds as $index => $lessonId) {
                DB::table('course_lesson')
                    ->where('course_id', $courseId)
                    ->where('lesson_id', $lessonId)
                    ->update(['sort_order' => $index + 1]);

                DB::table('lessons')
                    ->where('id', $lessonId)
                    ->where('course_id', $courseId)
                    ->update(['sort_order' => $index + 1]);
            }
        }
    }

    // The old numbers had ties and carried no meaning beyond the order kept here.
    public function down(): void {}
};
