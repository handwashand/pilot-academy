<?php

namespace App\Filament\Resources\Webinars\Pages;

use App\Filament\Actions\TranslateContentAction;
use App\Filament\Resources\Webinars\WebinarResource;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditWebinar extends EditRecord
{
    protected static string $resource = WebinarResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('preview')
                ->label(__t('admin_case_studies.table.preview'))
                ->icon('heroicon-o-eye')
                ->url(fn (): string => route('academy.webinar', $this->getRecord()))
                ->openUrlInNewTab(),
            TranslateContentAction::make(),
            DeleteAction::make(),
        ];
    }
}
