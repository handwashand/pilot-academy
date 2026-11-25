<?php

namespace App\Filament\Resources\Webinars\Pages;

use App\Filament\Actions\TranslateContentAction;
use App\Filament\Resources\Concerns\EditsInReadingLanguage;
use App\Filament\Resources\Webinars\WebinarResource;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Contracts\Support\Htmlable;

class EditWebinar extends EditRecord
{
    use EditsInReadingLanguage;

    protected static string $resource = WebinarResource::class;

    /** Shown and saved in the panel language, as a course or lesson is. */
    public function getSubheading(): string|Htmlable|null
    {
        return $this->readingNotice();
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        return $this->fillInReadingLanguage($data);
    }

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
