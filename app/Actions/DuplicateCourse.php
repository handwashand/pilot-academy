<?php

namespace App\Actions;

use App\Models\Course;
use App\Models\Lesson;
use App\Models\Question;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Copy a course, its lessons, and every quiz question, as a fresh draft.
 *
 * Student records are deliberately left behind: completions, attempts and
 * certificates belong to the course people actually took, not to a copy of it.
 */
class DuplicateCourse
{
    public function handle(Course $course): Course
    {
        return DB::transaction(function () use ($course): Course {
            $copy = $this->copyCourse($course);

            // Old question id => new question id, so the final quiz bank can be
            // rebuilt pointing at this copy's questions rather than the
            // original's — otherwise editing one course would change the other.
            $questionMap = [];

            foreach ($course->lessons()->with('questions.options')->get() as $lesson) {
                $this->copyLesson($lesson, $copy, $questionMap);
            }

            $this->copyFinalQuestionBank($course, $copy, $questionMap);

            return $copy->fresh();
        });
    }

    private function copyCourse(Course $course): Course
    {
        $copy = $course->replicate([
            // Slugs must be unique, and a copy is never live on day one.
            'slug',
            'status',
            'created_at',
            'updated_at',
        ]);

        // A course loaded through the admin table carries aggregates such as
        // lessons_count from withCount(). replicate() keeps them, and the
        // insert then fails on a column that does not exist.
        $this->dropNonColumns($copy);

        $copy->title = $course->title.' (copy)';
        $copy->slug = $this->uniqueSlug(Course::class, $course->slug);
        $copy->status = Course::STATUS_DRAFT;
        $copy->save();

        return $copy;
    }

    /**
     * @param  array<int, int>  $questionMap
     */
    private function copyLesson(Lesson $lesson, Course $copy, array &$questionMap): void
    {
        $newLesson = $lesson->replicate(['slug', 'created_at', 'updated_at']);
        $newLesson->course_id = $copy->id;
        // lessons.slug has no unique index, but the lesson route resolves by
        // slug alone: a shared slug would resolve to the original's lesson and
        // 404, leaving the copy's lessons unreachable.
        $newLesson->slug = $this->uniqueSlug(Lesson::class, $lesson->slug);
        $newLesson->save();

        foreach ($lesson->questions as $question) {
            $newQuestion = $question->replicate(['created_at', 'updated_at']);
            $newQuestion->lesson_id = $newLesson->id;
            $newQuestion->save();

            $questionMap[$question->id] = $newQuestion->id;

            foreach ($question->options as $option) {
                $newOption = $option->replicate(['created_at', 'updated_at']);
                $newOption->question_id = $newQuestion->id;
                $newOption->save();
            }
        }
    }

    /**
     * Rebuild the final quiz bank against the copy's own questions. Questions
     * written straight into the bank have no lesson, so they are copied here.
     *
     * @param  array<int, int>  $questionMap
     */
    private function copyFinalQuestionBank(Course $course, Course $copy, array $questionMap): void
    {
        foreach ($course->finalQuestions()->with('options')->get() as $question) {
            $newId = $questionMap[$question->id] ?? null;

            if ($newId === null) {
                $courseOnly = $question->replicate(['created_at', 'updated_at']);
                $courseOnly->lesson_id = null;
                $courseOnly->save();

                foreach ($question->options as $option) {
                    $newOption = $option->replicate(['created_at', 'updated_at']);
                    $newOption->question_id = $courseOnly->id;
                    $newOption->save();
                }

                $newId = $courseOnly->id;
            }

            $copy->finalQuestions()->attach($newId, [
                'sort_order' => $question->pivot->sort_order,
            ]);
        }
    }

    /** Keep only attributes that are real columns on the model's table. */
    private function dropNonColumns(Model $model): void
    {
        $columns = array_flip(Schema::getColumnListing($model->getTable()));

        $model->setRawAttributes(array_intersect_key($model->getAttributes(), $columns));
    }

    /** "pilot-basics" becomes "pilot-basics-copy", then -copy-2, and so on. */
    private function uniqueSlug(string $model, string $slug): string
    {
        $base = Str::slug($slug.'-copy');
        $candidate = $base;
        $suffix = 1;

        while ($model::where('slug', $candidate)->exists()) {
            $suffix++;
            $candidate = $base.'-'.$suffix;
        }

        return $candidate;
    }
}
