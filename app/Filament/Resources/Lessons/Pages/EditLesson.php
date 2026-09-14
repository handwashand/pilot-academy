<?php

namespace App\Filament\Resources\Lessons\Pages;

use App\Actions\FindContentProblems;
use App\Filament\Resources\Lessons\LessonResource;
use App\Models\Course;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Model;

class EditLesson extends EditRecord
{
    protected static string $resource = LessonResource::class;

    /** What students hit in this lesson right now, where it gets fixed. */
    public function getSubheading(): string|Htmlable|null
    {
        return FindContentProblems::summary(app(FindContentProblems::class)->forLesson($this->getRecord()));
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    /**
     * Saves the lesson, then which courses it is in. Taken out of its home
     * course, the first course left owns it.
     */
    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        $courseIds = array_values(array_map('intval', (array) ($data['course_ids'] ?? [])));
        unset($data['course_ids']);

        if ($courseIds !== [] && ! in_array((int) $record->course_id, $courseIds, true)) {
            $data['course_id'] = $courseIds[0];
        }

        $record->update($data);

        // A creator's save never drops a course they cannot manage.
        $user = auth()->user();
        $keep = $user?->isAdmin()
            ? []
            : $record->courses()->get()
                ->reject(fn (Course $course): bool => (bool) $user?->canManageCourse($course))
                ->pluck('id')
                ->map(fn ($id): int => (int) $id)
                ->all();

        if ($courseIds !== []) {
            $record->courses()->sync(array_values(array_unique([...$courseIds, ...$keep])));
        }

        return $record;
    }
}
