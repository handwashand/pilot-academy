<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\Courses\CourseResource;
use App\Filament\Resources\Lessons\LessonResource;
use App\Models\Course;
use App\Models\Lesson;
use App\Models\Question;
use Filament\Widgets\Widget;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Content that is broken for students right now, and nothing else tells anyone.
 *
 * Each of these is silent: the course looks configured in the panel, and the
 * student hits a dead end. Creators see only their own products' problems.
 */
class ContentNeedingAttention extends Widget
{
    protected string $view = 'filament.widgets.content-needing-attention';

    protected static ?int $sort = 0;

    protected int|string|array $columnSpan = 'full';

    /**
     * Anyone who can edit content should be told when it is broken — but the
     * widget stays out of the dashboard entirely when there is nothing wrong,
     * rather than occupying a grid cell with an empty card.
     */
    public static function canView(): bool
    {
        $user = auth()->user();

        if (! ($user?->isAdmin() || $user?->isCreator())) {
            return false;
        }

        return (new static)->getProblems()->isNotEmpty();
    }

    /** @return Collection<int, array<string, string|null>> */
    public function getProblems(): Collection
    {
        return collect([
            ...$this->emptyPublishedCourses(),
            ...$this->finalQuizzesWithoutQuestions(),
            ...$this->lessonsWithoutQuestions(),
            ...$this->unpassableQuestions(),
        ]);
    }

    /** Live, but a student opening it finds nothing to do. */
    private function emptyPublishedCourses(): array
    {
        return $this->scopeCourses(Course::query()->publishedButEmpty())
            ->get(['id', 'title'])
            ->map(fn (Course $course): array => [
                'severity' => 'danger',
                'what' => 'Published course with no published lessons',
                'name' => $course->title,
                'fix' => 'Publish a lesson, or unpublish the course.',
                'url' => CourseResource::getUrl('edit', ['record' => $course]),
            ])->all();
    }

    /** The final quiz button is there; pressing it leads nowhere. */
    private function finalQuizzesWithoutQuestions(): array
    {
        return $this->scopeCourses(Course::query()->finalQuizWithoutQuestions())
            ->get(['id', 'title'])
            ->map(fn (Course $course): array => [
                'severity' => 'danger',
                'what' => 'Final quiz is on but its question bank is empty',
                'name' => $course->title,
                'fix' => 'Open the course, then Final questions → Add all lesson questions.',
                'url' => CourseResource::getUrl('edit', ['record' => $course]),
            ])->all();
    }

    /** No knowledge check means the lesson can never be marked finished. */
    private function lessonsWithoutQuestions(): array
    {
        return $this->scopeLessons(Lesson::query()->published()->withoutQuestions())
            ->with('course:id,title')
            ->get(['id', 'course_id', 'title'])
            ->map(fn (Lesson $lesson): array => [
                'severity' => 'warning',
                'what' => 'Published lesson with no quiz questions',
                'name' => $lesson->title.' — '.($lesson->course?->title ?? 'no course'),
                'fix' => 'Add at least one question, or unpublish the lesson.',
                'url' => LessonResource::getUrl('edit', ['record' => $lesson]),
            ])->all();
    }

    /** The one that traps people: no right answer, so nobody can ever pass. */
    private function unpassableQuestions(): array
    {
        return Question::query()
            ->withoutCorrectAnswer()
            ->whereHas('lesson', fn ($query) => $this->scopeLessons($query))
            ->with('lesson:id,title')
            ->get(['id', 'lesson_id', 'prompt'])
            ->map(fn (Question $question): array => [
                'severity' => 'danger',
                'what' => 'Question with no correct answer — impossible to pass',
                'name' => Str::limit($question->prompt, 70).' — '.($question->lesson?->title ?? 'final quiz only'),
                'fix' => 'Open the lesson and tick the right answer.',
                'url' => $question->lesson
                    ? LessonResource::getUrl('edit', ['record' => $question->lesson])
                    : null,
            ])->all();
    }

    /** Creators are told about their own products and nobody else's. */
    private function scopeCourses($query)
    {
        $user = auth()->user();

        return $user?->isCreator()
            ? $query->whereIn('product_id', $user->products()->pluck('products.id'))
            : $query;
    }

    private function scopeLessons($query)
    {
        $user = auth()->user();

        if (! $user?->isCreator()) {
            return $query;
        }

        $productIds = $user->products()->pluck('products.id');

        return $query->whereHas('course', fn ($course) => $course->whereIn('product_id', $productIds));
    }
}
