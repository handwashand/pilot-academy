<?php

namespace App\Actions;

use App\Filament\Resources\Courses\CourseResource;
use App\Filament\Resources\Lessons\LessonResource;
use App\Models\Course;
use App\Models\Lesson;
use App\Models\Question;
use App\Models\User;
use Closure;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;

/**
 * Content that is broken for students right now.
 *
 * Each of these is silent: the course looks configured in the panel, and the
 * student hits a dead end. One definition, used everywhere the academy talks
 * about it — Content health and its sidebar badge, the flags on the Courses and
 * Lessons lists, the banner on an edit page, the check before publishing, and
 * the notification to the content's owner.
 *
 * A problem is an array: `key` (stable, so the same problem is never notified
 * twice), `severity`, `what`, `name`, `fix`, `url`, `course_id`, `course_ids`
 * (every course it affects — a lesson can be in several), `lesson_id`.
 */
class FindContentProblems
{
    /**
     * Everything the signed-in person is responsible for — every course for an
     * admin, their own products' for a creator, nothing for anyone else.
     * Remembered for the request: the badge, the list and each table row ask.
     */
    public static function forCurrentUser(): Collection
    {
        $user = auth()->user();
        $key = 'content-problems.'.($user?->getKey() ?? 'guest');

        if (app()->bound($key)) {
            return app($key);
        }

        $problems = app(static::class)->forViewer($user);
        app()->instance($key, $problems);

        return $problems;
    }

    public function forViewer(?User $viewer): Collection
    {
        if (! ($viewer?->isAdmin() || $viewer?->isCreator())) {
            return collect();
        }

        return $this->find(fn (Builder $courses): Builder => $viewer->isCreator()
            ? $courses->whereIn('product_id', $viewer->products()->pluck('products.id'))
            : $courses);
    }

    /** Problems in one course, whoever is asking. */
    public function forCourse(Course $course): Collection
    {
        return $this->find(fn (Builder $courses): Builder => $courses->whereKey($course->getKey()));
    }

    /** Problems in one lesson: its own, and its questions'. */
    public function forLesson(Lesson $lesson): Collection
    {
        $courseIds = $lesson->courses()->pluck('courses.id')->all();

        if ($courseIds === []) {
            return collect();
        }

        return $this->find(fn (Builder $courses): Builder => $courses->whereIn('id', $courseIds))
            ->filter(fn (array $problem): bool => $problem['lesson_id'] === $lesson->getKey())
            ->values();
    }

    /**
     * One line for an edit page's subheading: what students hit here, and a way
     * to the full list. Null when there is nothing to say.
     */
    public static function summary(Collection $problems): ?Htmlable
    {
        if ($problems->isEmpty()) {
            return null;
        }

        $count = $problems->count();
        $items = $problems
            ->map(fn (array $problem): string => e($problem['what']).' — '.e($problem['name']))
            ->implode('; ');

        return new HtmlString(
            '<span class="font-semibold text-danger-600 dark:text-danger-400">'
            .e("Students hit {$count} ".Str::plural('problem', $count).' here right now:')
            .'</span> '.$items.'.'
        );
    }

    /** The problems as plain text, for a notification body. */
    public static function plainList(Collection $problems): string
    {
        return $problems
            ->map(fn (array $problem): string => $problem['what'].' — '.$problem['name'])
            ->implode('; ');
    }

    /** @param  Closure(Builder): Builder  $scope  Narrows the courses looked at. */
    private function find(Closure $scope): Collection
    {
        $courseIds = $scope(Course::query())->pluck('id')->all();

        if ($courseIds === []) {
            return collect();
        }

        return collect([
            ...$this->emptyPublishedCourses($courseIds),
            ...$this->finalQuizzesWithoutQuestions($courseIds),
            ...$this->lessonsWithoutQuestions($courseIds),
            ...$this->unpassableQuestions($courseIds),
            ...$this->unplayableYoutubeLinks($courseIds),
        ]);
    }

    /** Live, but a student opening it finds nothing to do. */
    private function emptyPublishedCourses(array $courseIds): array
    {
        return Course::query()->publishedButEmpty()->whereIn('id', $courseIds)
            ->get(['id', 'title'])
            ->map(fn (Course $course): array => [
                'key' => "course-{$course->id}-empty",
                'severity' => 'danger',
                'what' => 'Published course with no published lessons',
                'name' => $course->title,
                'fix' => 'Publish a lesson, or unpublish the course.',
                'url' => CourseResource::getUrl('edit', ['record' => $course]),
                'course_id' => $course->id,
                'course_ids' => [$course->id],
                'lesson_id' => null,
            ])->all();
    }

    /** The final quiz button is there; pressing it leads nowhere. */
    private function finalQuizzesWithoutQuestions(array $courseIds): array
    {
        return Course::query()->finalQuizWithoutQuestions()->whereIn('id', $courseIds)
            ->get(['id', 'title'])
            ->map(fn (Course $course): array => [
                'key' => "course-{$course->id}-final-quiz-empty",
                'severity' => 'danger',
                'what' => 'Final quiz is on but its question bank is empty',
                'name' => $course->title,
                'fix' => 'Open the course, then Final questions → Add all lesson questions.',
                'url' => CourseResource::getUrl('edit', ['record' => $course]),
                'course_id' => $course->id,
                'course_ids' => [$course->id],
                'lesson_id' => null,
            ])->all();
    }

    /**
     * Lessons in any of these courses, each carrying the ones among them it is
     * in. A lesson shared by two courses is one problem, not two.
     */
    private function lessonsIn(array $courseIds, Builder $lessons): Collection
    {
        return $lessons
            ->whereHas('courses', fn (Builder $courses) => $courses->whereIn('courses.id', $courseIds))
            ->with(['courses' => fn ($courses) => $courses->whereIn('courses.id', $courseIds)])
            ->get();
    }

    /** Where a lesson problem belongs: its home course if in view, else the first it is in. */
    private function placeOf(Lesson $lesson): array
    {
        $ids = $lesson->courses->pluck('id')->map(fn ($id): int => (int) $id)->all();

        return [
            'course_id' => in_array((int) $lesson->course_id, $ids, true) ? (int) $lesson->course_id : ($ids[0] ?? null),
            'course_ids' => $ids,
            'courses' => $lesson->courses->pluck('title')->implode(', ') ?: 'no course',
        ];
    }

    /** No knowledge check means the lesson can never be marked finished. */
    private function lessonsWithoutQuestions(array $courseIds): array
    {
        return $this->lessonsIn($courseIds, Lesson::query()->published()->withoutQuestions())
            ->map(function (Lesson $lesson): array {
                $place = $this->placeOf($lesson);

                return [
                    'key' => "lesson-{$lesson->id}-no-questions",
                    'severity' => 'warning',
                    'what' => 'Published lesson with no quiz questions',
                    'name' => $lesson->title.' — '.$place['courses'],
                    'fix' => 'Add at least one question, or unpublish the lesson.',
                    'url' => LessonResource::getUrl('edit', ['record' => $lesson]),
                    'course_id' => $place['course_id'],
                    'course_ids' => $place['course_ids'],
                    'lesson_id' => $lesson->id,
                ];
            })->all();
    }

    /** The one that traps people: no right answer, so nobody can ever pass. */
    private function unpassableQuestions(array $courseIds): array
    {
        return Question::query()
            ->withoutCorrectAnswer()
            ->whereHas('lesson.courses', fn (Builder $courses) => $courses->whereIn('courses.id', $courseIds))
            ->with(['lesson.courses' => fn ($courses) => $courses->whereIn('courses.id', $courseIds)])
            ->get(['id', 'lesson_id', 'prompt'])
            ->map(function (Question $question): array {
                $place = $this->placeOf($question->lesson);

                return [
                    'key' => "question-{$question->id}-no-correct-answer",
                    'severity' => 'danger',
                    'what' => 'Question with no correct answer — impossible to pass',
                    'name' => Str::limit($question->prompt, 70).' — '.$question->lesson->title,
                    'fix' => 'Open the lesson and tick the right answer.',
                    'url' => LessonResource::getUrl('edit', ['record' => $question->lesson]),
                    'course_id' => $place['course_id'],
                    'course_ids' => $place['course_ids'],
                    'lesson_id' => $question->lesson_id,
                ];
            })->all();
    }

    /**
     * Saved before the lesson form checked YouTube links: a playlist, channel
     * or other page the lesson cannot embed, so the student gets no video. An
     * uploaded file plays instead of the link, so those are left alone.
     */
    private function unplayableYoutubeLinks(array $courseIds): array
    {
        $lessons = Lesson::query()->published()
            ->whereNotNull('youtube_url')->where('youtube_url', '!=', '')
            ->where(fn (Builder $query) => $query->whereNull('video_path')->orWhere('video_path', ''));

        return $this->lessonsIn($courseIds, $lessons)
            ->filter(fn (Lesson $lesson): bool => Lesson::youtubeIdFrom($lesson->youtube_url) === null)
            ->map(function (Lesson $lesson): array {
                $place = $this->placeOf($lesson);

                return [
                    'key' => "lesson-{$lesson->id}-unplayable-youtube",
                    'severity' => 'danger',
                    'what' => 'YouTube link that is not a playable video',
                    'name' => $lesson->title.' — '.$place['courses'],
                    'fix' => 'Open the lesson and paste the address of the video itself, not a playlist or channel.',
                    'url' => LessonResource::getUrl('edit', ['record' => $lesson]),
                    'course_id' => $place['course_id'],
                    'course_ids' => $place['course_ids'],
                    'lesson_id' => $lesson->id,
                ];
            })->values()->all();
    }
}
