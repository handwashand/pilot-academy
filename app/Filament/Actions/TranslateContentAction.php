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
 * "Translate" on a course's or lesson's edit page: the same content in the
 * other languages, one tab per language. A course can be written in any
 * language — a Russian trainer's course gets English, French, … tabs, never a
 * Russian one. An empty box shows students the original.
 *
 * Saved through HasContentTranslations::setTranslation(), so an emptied box
 * deletes its translation rather than storing a blank.
 */
class TranslateContentAction
{
    public static function make(): Action
    {
        return Action::make('translateContent')
            ->label(fn (): string => __t('admin_common.translate.button'))
            ->icon(Heroicon::OutlinedLanguage)
            ->color('gray')
            ->modalHeading(fn (): string => __t('admin_common.translate.heading'))
            ->modalDescription(fn (Model $record): string => __t('admin_common.translate.description', ['language' => static::nameOf($record->contentLanguageCode())]))
            ->modalWidth('5xl')
            ->modalSubmitActionLabel(fn (): string => __t('admin_common.translate.submit'))
            ->visible(fn (Model $record): bool => static::targets($record)->isNotEmpty())
            ->fillForm(fn (Model $record): array => static::current($record))
            ->schema(fn (Model $record): array => [
                Tabs::make('languages')->tabs(
                    static::targets($record)
                        ->map(fn (Language $language): Tab => Tab::make($language->native_name)->schema(
                            collect($record->translatableFields())
                                ->map(fn (string $field) => static::input($record, $language->code, $field))
                                ->all(),
                        ))
                        ->all(),
                ),
            ])
            ->action(function (Model $record, array $data): void {
                foreach (static::targets($record) as $language) {
                    foreach ($record->translatableFields() as $field) {
                        $record->setTranslation($field, $language->code, $data[$language->code][$field] ?? null);
                    }
                }

                Notification::make()->title(__t('admin_common.translate.saved'))->success()->send();
            });
    }

    /** Every active language except the one the record is written in. */
    private static function targets(Model $record): Collection
    {
        return app(Translator::class)->activeLanguages()
            ->reject(fn (Language $language): bool => $language->code === $record->contentLanguageCode())
            ->values();
    }

    private static function nameOf(string $code): string
    {
        return app(Translator::class)->activeLanguage($code)?->native_name ?? strtoupper($code);
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
        // A field without a shipped name reads as its own name, made readable.
        $label = __t("admin_common.translate.fields.{$field}");
        // The original as the placeholder, so a translator sees what they are translating.
        $original = Str::limit(trim(strip_tags((string) $record->getAttribute($field))), 150);

        return match ($field) {
            'content' => RichEditor::make("{$code}.{$field}")->label($label),
            'title' => TextInput::make("{$code}.{$field}")->label($label)->maxLength(255)->placeholder($original),
            default => Textarea::make("{$code}.{$field}")->label($label)->rows($field === 'transcript' ? 6 : 3)->placeholder($original),
        };
    }
}
