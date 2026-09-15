<?php

namespace App\Models\Relations;

use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * Course::lessons(). Only create() differs: a lesson written inside a course
 * gets that course as its home, and Lesson::saved() files it there in its own
 * sort_order. The stock create() would insert the lesson with no home course
 * and attach it afterwards, which the lessons table does not allow.
 */
class CourseLessons extends BelongsToMany
{
    public function create(array $attributes = [], array $joining = [], $touch = true)
    {
        // Written in its course's language unless told otherwise.
        $lesson = $this->related->newInstance([
            'course_id' => $this->parent->getKey(),
            'language' => $this->parent->getAttribute('language'),
            ...$attributes,
        ]);
        $lesson->save(['touch' => false]);

        if ($joining !== []) {
            $this->updateExistingPivot($lesson->getKey(), $joining, $touch);
        }

        return $lesson;
    }
}
