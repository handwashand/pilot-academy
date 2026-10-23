<?php

namespace App\Filament\Resources\Courses\Pages;

use App\Actions\FindContentProblems;
use App\Filament\Actions\TranslateCourseAction;
use App\Filament\Actions\TranslateVideoWithDescriptAction;
use App\Filament\Resources\Concerns\EditsInReadingLanguage;
use App\Filament\Resources\Courses\CourseResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Contracts\Support\Htmlable;

class EditCourse extends EditRecord
{
    use EditsInReadingLanguage;

    protected static string $resource = CourseResource::class;

    /** What students hit in this course right now, where it gets fixed. */
    public function getSubheading(): string|Htmlable|null
    {
        return $this->withReadingNotice(FindContentProblems::summary(app(FindContentProblems::class)->forCourse($this->getRecord())));
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        return $this->fillInReadingLanguage($data);
    }

    protected function getHeaderActions(): array
    {
        return [
            // One Translate: the course's own text or the whole course, one
            // language or all of them — chosen in its first step.
            TranslateCourseAction::make(),
            TranslateVideoWithDescriptAction::course(),
            TranslateVideoWithDescriptAction::checkCourse(),
            DeleteAction::make(),
        ];
    }
}
