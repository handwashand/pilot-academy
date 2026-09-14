<?php

namespace App\Filament\Resources\Translations\Pages;

use App\Actions\SyncShippedTranslations;
use App\Filament\Resources\Translations\TranslationResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListTranslations extends ListRecords
{
    protected static string $resource = TranslationResource::class;

    public function mount(): void
    {
        // Text shipped since the page last opened shows up here to correct.
        app(SyncShippedTranslations::class)->handle();

        parent::mount();
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
