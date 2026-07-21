<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Compose a ready-to-use final quiz for every published course that has
     * lesson questions: enable it, keep the 80% pass mark, and fill the bank
     * with all of the course's lesson questions (the same default the admin
     * gets when enabling the final quiz).
     *
     * A data migration (not a seeder) so production picks it up through the
     * regular deploy (`php artisan migrate --force`) — there is no SSH step to
     * run seeders. Only touches courses that don't already have a final quiz
     * enabled or a bank composed, so anything set in the admin is preserved.
     */
    public function up(): void
    {
        $courses = DB::table('courses')->where('is_published', true)->get();

        foreach ($courses as $course) {
            $alreadyComposed = DB::table('course_final_questions')
                ->where('course_id', $course->id)
                ->exists();

            if ($course->final_quiz_enabled || $alreadyComposed) {
                continue;
            }

            $questionIds = DB::table('questions')
                ->join('lessons', 'lessons.id', '=', 'questions.lesson_id')
                ->where('lessons.course_id', $course->id)
                ->orderBy('lessons.sort_order')
                ->orderBy('questions.sort_order')
                ->pluck('questions.id');

            if ($questionIds->isEmpty()) {
                continue;
            }

            DB::table('courses')->where('id', $course->id)->update([
                'final_quiz_enabled' => true,
                'pass_percent' => 80,
            ]);

            $now = now();
            $rows = $questionIds->values()->map(fn ($id, $i) => [
                'course_id' => $course->id,
                'question_id' => $id,
                'sort_order' => $i + 1,
                'created_at' => $now,
                'updated_at' => $now,
            ])->all();

            DB::table('course_final_questions')->insert($rows);
        }
    }

    /**
     * Content composition — nothing sensible to reverse (admins may have
     * curated the bank since). Intentionally a no-op.
     */
    public function down(): void
    {
        //
    }
};
