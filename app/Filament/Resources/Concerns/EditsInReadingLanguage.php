<?php

namespace App\Filament\Resources\Concerns;

use App\Services\Translator;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\HtmlString;

/**
 * An edit page that shows its record in the admin's panel language: an
 * admin reviewing in Spanish reads, and corrects, the Spanish translation;
 * switching the panel to the language it was written in shows the original.
 * The translatable fields are saved as that translation, never over the
 * original (HasContentTranslations::saveReadingValues).
 */
trait EditsInReadingLanguage
{
    /** @param  array<string, mixed>  $data */
    protected function fillInReadingLanguage(array $data): array
    {
        $record = $this->getRecord();

        return $record->readingLanguage() ? [...$data, ...$record->readingValues()] : $data;
    }

    /** The page heading and breadcrumb name it in the reader's language too. */
    public function getRecordTitle(): string|Htmlable
    {
        return (string) $this->getRecord()->translated('title');
    }

    /** @param  array<string, mixed>  $data */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        return $this->getRecord()->saveReadingValues($data);
    }

    /** Said at the top of the page while a translation is shown, so nobody mistakes it for the original. */
    protected function readingNotice(): ?Htmlable
    {
        $record = $this->getRecord();
        $language = $record->readingLanguage();

        if (! $language) {
            return null;
        }

        $original = app(Translator::class)->activeLanguage($record->contentLanguageCode())?->native_name
            ?? strtoupper($record->contentLanguageCode());

        return new HtmlString('<span class="font-semibold">'.e(__t('admin_common.reading.notice', [
            'language' => $language->native_name,
            'original' => $original,
        ])).'</span>');
    }

    /** The reading notice above whatever else the page says under its title. */
    protected function withReadingNotice(?Htmlable $subheading): ?Htmlable
    {
        $notice = $this->readingNotice();

        if (! $notice || ! $subheading) {
            return $notice ?? $subheading;
        }

        return new HtmlString($notice->toHtml().'<br>'.$subheading->toHtml());
    }
}
