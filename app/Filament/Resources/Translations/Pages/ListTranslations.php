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

    /** The cells are the way in, and nothing else on the page says so. */
    public function getSubheading(): ?string
    {
        return __t('admin_settings.translations.click_hint');
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
