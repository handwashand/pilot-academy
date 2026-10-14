<?php

namespace App\Models;

use App\Actions\NotifyContentOwners;
use Illuminate\Database\Eloquent\Relations\Pivot;

/**
 * One lesson's place in one course. A lesson can have several.
 *
 * Its events only fire when the link is made or removed through the relation
 * (attach, detach, sync), which is how the panel and the lesson form do it.
 */
class CourseLesson extends Pivot
{
    protected $table = 'course_lesson';

    protected static function booted(): void
    {
        // An existing lesson added to a course goes to the end of it.
        static::creating(function (CourseLesson $link): void {
            if ($link->sort_order === null) {
                $link->sort_order = (int) static::query()->where('course_id', $link->course_id)->max('sort_order') + 1;
            }
        });

        static::created(function (CourseLesson $link): void {
            // A lesson that was in no course belongs to the one it joins: its
            // old home no longer shows it, so it should not go on owning it.
            // A link to a deleted course does not count: nobody sees it there.
            $elsewhere = static::query()
                ->where('lesson_id', $link->lesson_id)
                ->where('course_id', '!=', $link->course_id)
                ->whereIn('course_id', Course::query()->select('id'))
                ->exists();
            $lesson = $elsewhere ? null : Lesson::query()->find($link->lesson_id);

            if ($lesson && (int) $lesson->course_id !== (int) $link->course_id) {
                $lesson->course_id = $link->course_id;
                $lesson->saveQuietly();
            }

            NotifyContentOwners::afterRequest($link->course_id);
        });

        static::deleted(function (CourseLesson $link): void {
            // Taken out of its home course: the next course it is in owns it now.
            $lesson = Lesson::query()->find($link->lesson_id);
            $next = static::query()
                ->where('lesson_id', $link->lesson_id)
                ->whereIn('course_id', Course::query()->select('id'))
                ->orderBy('created_at')
                ->value('course_id');

            if ($lesson && $next && (int) $lesson->course_id === (int) $link->course_id) {
                $lesson->course_id = $next;
                $lesson->saveQuietly();
            }

            NotifyContentOwners::afterRequest($link->course_id);
        });
    }
}
