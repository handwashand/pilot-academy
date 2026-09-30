<?php

namespace App\Filament\Resources\Tutorials\Pages;

use App\Filament\Actions\TranslateContentAction;
use App\Filament\Resources\Tutorials\TutorialResource;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditTutorial extends EditRecord
{
    protected static string $resource = TutorialResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('preview')
                ->label(__t('admin_case_studies.table.preview'))
                ->icon('heroicon-o-eye')
                ->url(fn (): string => route('academy.tutorial', $this->getRecord()))
                ->openUrlInNewTab(),
            TranslateContentAction::make(),
            DeleteAction::make(),
        ];
    }
}
