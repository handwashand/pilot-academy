<?php

namespace App\Filament\Resources\Courses\Pages;

use App\Filament\Pages\GenerateCourse;
use App\Filament\Resources\Courses\CourseResource;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListCourses extends ListRecords
{
    protected static string $resource = CourseResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // The AI course generator, deliberately low-key: a small grey icon
            // beside New course, named only in its tooltip, and only for
            // accounts with the extra right. It is not in the sidebar.
            Action::make('generateCourse')
                ->iconButton()
                ->icon('heroicon-o-sparkles')
                ->color('gray')
                ->tooltip(__t('admin_nav.course_generation.nav'))
                ->url(fn (): string => GenerateCourse::getUrl())
                ->visible(fn (): bool => GenerateCourse::canAccess()),

            CreateAction::make(),
        ];
    }
}
