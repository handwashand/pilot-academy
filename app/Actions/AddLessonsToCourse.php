<?php

namespace App\Actions;

use App\Models\Course;
use App\Models\Lesson;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Puts existing lessons into a course — the one rule for the course form, the
 * Lessons tab's Browse lessons and the Lessons list's Add to course.
 *
 * Only lessons the user may edit are taken; ones already in the course are
 * skipped; nothing is added if any would clash on slug. The rest go to the end
 * of the course in the order they were picked (CourseLesson::creating).
 */
class AddLessonsToCourse
{
    /**
     * @param  array<int|string>  $lessonIds
     * @return array{added: Collection<int, Lesson>, already: Collection<int, Lesson>, clashes: Collection<int, string>}
     */
    public function handle(Course $course, array $lessonIds, User $user): array
    {
        $none = ['added' => collect(), 'already' => collect(), 'clashes' => collect()];

        if (! $user->canManageCourse($course)) {
            return $none;
        }

        $order = array_values(array_map('intval', $lessonIds));

        $lessons = Lesson::query()
            ->whereIn('id', $order)
            ->withCount('courses')
            ->with('course')
            ->get()
            ->filter(fn (Lesson $lesson): bool => $user->canManageLesson($lesson))
            ->sortBy(fn (Lesson $lesson): int => (int) array_search($lesson->id, $order, true))
            ->values();

        $inCourse = $course->lessons()->pluck('lessons.id')->all();
        [$already, $new] = $lessons->partition(fn (Lesson $lesson): bool => in_array($lesson->id, $inCourse, true));

        $clashes = static::clashes($course, $new);

        if ($clashes->isNotEmpty()) {
            return [...$none, 'already' => $already->values(), 'clashes' => $clashes];
        }

        foreach ($new as $lesson) {
            $course->lessons()->attach($lesson->id);
        }

        return ['added' => $new->values(), 'already' => $already->values(), 'clashes' => collect()];
    }

    /**
     * Titles of lessons that cannot join this course: the student URL finds a
     * lesson by its slug inside the course, so two with one slug cannot share
     * it — including two picked together. A lesson in no live course moves its
     * home here (CourseLesson::created), so a hidden lesson holding its slug at
     * this home blocks it too. A course not saved yet has none of either.
     *
     * @param  Collection<int, Lesson>  $adding  loaded with courses_count
     */
    public static function clashes(Course $course, Collection $adding): Collection
    {
        $taken = $course->exists ? $course->lessons()->pluck('lessons.slug')->all() : [];
        $seen = [];

        return $adding
            ->filter(function (Lesson $lesson) use ($course, $taken, &$seen): bool {
                $twice = in_array($lesson->slug, $seen, true);
                $seen[] = $lesson->slug;

                return $twice
                    || in_array($lesson->slug, $taken, true)
                    || ($course->exists
                        && (int) ($lesson->courses_count ?? $lesson->courses()->count()) === 0
                        && Lesson::holdingSlugAtHome($course->getKey(), $lesson->slug, $lesson->getKey()));
            })
            ->pluck('title')
            ->values();
    }
}
