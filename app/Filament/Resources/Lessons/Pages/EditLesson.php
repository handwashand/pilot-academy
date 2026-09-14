<?php

namespace App\Filament\Resources\Lessons\Pages;

use App\Actions\FindContentProblems;
use App\Filament\Resources\Lessons\LessonResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Contracts\Support\Htmlable;

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
}
