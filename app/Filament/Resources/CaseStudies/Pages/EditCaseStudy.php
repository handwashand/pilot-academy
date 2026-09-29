<?php

namespace App\Filament\Resources\CaseStudies\Pages;

use App\Filament\Actions\TranslateContentAction;
use App\Filament\Resources\CaseStudies\CaseStudyResource;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditCaseStudy extends EditRecord
{
    protected static string $resource = CaseStudyResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('preview')
                ->label(__t('admin_case_studies.table.preview'))
                ->icon('heroicon-o-eye')
                ->url(fn (): string => route('academy.case-studies.show', $this->getRecord()))
                ->openUrlInNewTab(),
            TranslateContentAction::make(),
            DeleteAction::make(),
        ];
    }
}
