<?php

namespace App\Filament\Resources\Courses\Pages;

use App\Actions\AddLessonsToCourse;
use App\Filament\Resources\Courses\CourseResource;
use App\Filament\Resources\Lessons\Tables\LessonsTable;
use Filament\Resources\Pages\CreateRecord;

class CreateCourse extends CreateRecord
{
    protected static string $resource = CourseResource::class;

    /** Lessons ticked on the form; they join the course once it exists. */
    protected array $lessonIds = [];

    /** Who wrote the course — read-only audit information on the edit page. */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['created_by'] = auth()->id();

        $this->lessonIds = array_values((array) ($data['lesson_ids'] ?? []));
        unset($data['lesson_ids']);

        return $data;
    }

    protected function afterCreate(): void
    {
        if ($this->lessonIds === []) {
            return;
        }

        LessonsTable::reportAdded(
            app(AddLessonsToCourse::class)->handle($this->record, $this->lessonIds, auth()->user()),
            $this->record,
        );
    }
}
