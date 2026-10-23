<?php

namespace App\Filament\Resources\Lessons\Pages;

use App\Filament\Resources\Courses\CourseResource;
use App\Filament\Resources\Lessons\LessonResource;
use App\Models\Course;
use App\Models\Lesson;
use App\Services\Translator;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateLesson extends CreateRecord
{
    protected static string $resource = LessonResource::class;

    /** The course this page was opened from (?course=), if the user may add to it. */
    public ?int $fromCourse = null;

    /**
     * Opened from a course's Lessons tab: start in that course, written in its
     * language. The id comes from the address bar, so it is checked here.
     */
    protected function afterFill(): void
    {
        $course = Course::find(request()->integer('course'));

        if (! $course || ! auth()->user()?->canManageCourse($course)) {
            return;
        }

        $this->fromCourse = $course->id;
        $this->data['course_ids'] = [$course->id];
        $this->data['language'] = $course->language ?: app(Translator::class)->defaultCode();
    }

    /** Back to the course it was written for; otherwise Filament's own default. */
    protected function getRedirectUrl(): string
    {
        $course = $this->fromCourse ? Course::find($this->fromCourse) : null;

        return $course && in_array($course->id, $this->getRecord()->courses()->pluck('courses.id')->map(fn ($id): int => (int) $id)->all(), true)
            ? CourseResource::getUrl('edit', ['record' => $course])
            : parent::getRedirectUrl();
    }

    /**
     * The first course chosen is the lesson's home (Lesson::saved() files it
     * there); the lesson then joins the others, at the end of each.
     */
    protected function handleRecordCreation(array $data): Model
    {
        $courseIds = array_values(array_map('intval', (array) ($data['course_ids'] ?? [])));
        unset($data['course_ids']);

        $lesson = new Lesson(['course_id' => $courseIds[0] ?? null, ...$data]);
        $lesson->save();

        $lesson->courses()->syncWithoutDetaching($courseIds);

        return $lesson;
    }
}
