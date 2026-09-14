<?php

namespace App\Filament\Resources\Courses\Pages;

use App\Actions\FindContentProblems;
use App\Filament\Actions\TranslateContentAction;
use App\Filament\Resources\Courses\CourseResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Contracts\Support\Htmlable;

class EditCourse extends EditRecord
{
    protected static string $resource = CourseResource::class;

    /** What students hit in this course right now, where it gets fixed. */
    public function getSubheading(): string|Htmlable|null
    {
        return FindContentProblems::summary(app(FindContentProblems::class)->forCourse($this->getRecord()));
    }

    protected function getHeaderActions(): array
    {
        return [
            TranslateContentAction::make(),
            DeleteAction::make(),
        ];
    }
}
