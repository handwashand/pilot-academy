<?php

namespace App\Filament\Actions;

use App\Models\Language;
use App\Services\Translator;
use Filament\Actions\Action;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * "Translate" on a course's or lesson's edit page: what students see in each
 * other language, one tab per language. An empty box shows the English text.
 *
 * Saved through HasContentTranslations::setTranslation(), so an emptied box
 * deletes its translation rather than storing a blank.
 */
class TranslateContentAction
{
    private const LABELS = [
        'title' => 'Title',
        'description' => 'Description',
        'summary' => 'Short summary',
        'content' => 'Lesson text',
        'transcript' => 'Video transcript',
    ];

    public static function make(): Action
    {
        return Action::make('translateContent')
            ->label('Translate')
            ->icon(Heroicon::OutlinedLanguage)
            ->color('gray')
            ->modalHeading('Translations')
            ->modalDescription('What students see in each language. Leave a box empty to show the English text.')
            ->modalWidth('5xl')
            ->modalSubmitActionLabel('Save translations')
            ->visible(fn (): bool => static::languages()->isNotEmpty())
            ->fillForm(fn (Model $record): array => static::current($record))
            ->schema(fn (Model $record): array => [
                Tabs::make('languages')->tabs(
                    static::languages()
                        ->map(fn (Language $language): Tab => Tab::make($language->native_name)->schema(
                            collect($record->translatableFields())
                                ->map(fn (string $field) => static::input($record, $language->code, $field))
                                ->all(),
                        ))
                        ->all(),
                ),
            ])
            ->action(function (Model $record, array $data): void {
                foreach (static::languages() as $language) {
                    foreach ($record->translatableFields() as $field) {
                        $record->setTranslation($field, $language->code, $data[$language->code][$field] ?? null);
                    }
                }

                Notification::make()->title('Translations saved')->success()->send();
            });
    }

    /** Every active language except the default, which is the record itself. */
    private static function languages(): Collection
    {
        return app(Translator::class)->activeLanguages()
            ->reject(fn (Language $language): bool => (bool) $language->is_default)
            ->values();
    }

    /** @return array<string, array<string, string>> */
    private static function current(Model $record): array
    {
        $values = [];

        foreach ($record->contentTranslations()->with('language')->get() as $translation) {
            if ($translation->language) {
                $values[$translation->language->code][$translation->field] = $translation->value;
            }
        }

        return $values;
    }

    private static function input(Model $record, string $code, string $field)
    {
        $label = self::LABELS[$field] ?? Str::headline($field);
        // The English as the placeholder, so a translator sees what they are translating.
        $english = Str::limit(trim(strip_tags((string) $record->getAttribute($field))), 150);

        return match ($field) {
            'content' => RichEditor::make("{$code}.{$field}")->label($label),
            'title' => TextInput::make("{$code}.{$field}")->label($label)->maxLength(255)->placeholder($english),
            default => Textarea::make("{$code}.{$field}")->label($label)->rows($field === 'transcript' ? 6 : 3)->placeholder($english),
        };
    }
}
