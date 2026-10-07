<?php

namespace App\Filament\Actions;

use App\Actions\DraftTranslationsWithDeepL;
use App\Models\Language;
use App\Models\User;
use App\Services\DeepL\DeepLClient;
use App\Services\DeepL\DeepLException;
use App\Services\Translator;
use Filament\Actions\Action;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
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
 *
 * With DeepL connected, someone who has been given the right can also draft the
 * empty boxes. That only fills the form: an editor reads and changes the text,
 * and nothing is stored until Save translations. Typing by hand works the same
 * whether DeepL is on, off, out of quota or down.
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
                Actions::make([static::draftWithDeepL($record)])
                    ->key('deeplActions')
                    ->visible(fn (): bool => static::canDraftWithDeepL()),
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

    private static function canDraftWithDeepL(): bool
    {
        return app(DeepLClient::class)->enabled()
            && (bool) auth()->user()?->hasPermission(User::PERMISSION_DEEPL_TRANSLATE);
    }

    /** Fills the empty boxes with DeepL drafts; never touches a box that has text. */
    private static function draftWithDeepL(Model $record): Action
    {
        return Action::make('draftWithDeepL')
            ->label(fn (): string => __t('admin_common.translate.deepl.button'))
            ->icon(Heroicon::OutlinedSparkles)
            ->color('gray')
            ->authorize(fn (): bool => static::canDraftWithDeepL())
            ->requiresConfirmation()
            ->modalHeading(fn (): string => __t('admin_common.translate.deepl.confirm_heading'))
            ->modalDescription(fn (): string => __t('admin_common.translate.deepl.confirm_description'))
            ->modalSubmitActionLabel(fn (): string => __t('admin_common.translate.deepl.confirm_submit'))
            ->action(function (Get $get, Set $set) use ($record): void {
                $targets = static::targets($record);
                $current = [];

                foreach ($targets as $language) {
                    foreach ($record->translatableFields() as $field) {
                        $current[$language->code][$field] = $get("{$language->code}.{$field}");
                    }
                }

                $result = app(DraftTranslationsWithDeepL::class)->handle($record, $targets, $current);

                foreach ($result['drafts'] as $code => $fields) {
                    foreach ($fields as $field => $text) {
                        $set("{$code}.{$field}", $text);
                    }
                }

                $count = collect($result['drafts'])->flatten()->count();

                if ($result['error']) {
                    Notification::make()
                        ->title(__t('admin_common.translate.deepl.failed'))
                        ->body(static::failureMessage($result['error']).($count ? ' '.__t('admin_common.translate.deepl.kept', ['count' => $count]) : ''))
                        ->danger()
                        ->send();

                    return;
                }

                $count
                    ? Notification::make()->title(__t('admin_common.translate.deepl.drafted'))->body(__t('admin_common.translate.deepl.drafted_body', ['count' => $count]))->success()->send()
                    : Notification::make()->title(__t('admin_common.translate.deepl.nothing'))->body(__t('admin_common.translate.deepl.nothing_body'))->warning()->send();
            });
    }

    private static function failureMessage(DeepLException $exception): string
    {
        $key = match (true) {
            $exception->isQuotaProblem() => 'quota',
            $exception->isAuthProblem() || $exception->status === 401 => 'auth',
            $exception->status === 429 => 'busy',
            $exception->isTemporary() => 'unreachable',
            $exception->errorCode === 'unsupported_language_pair' => 'unsupported',
            default => 'rejected',
        };

        return __t("admin_common.translate.deepl.errors.{$key}");
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
        $label = $record->translatableFieldLabel($field);
        // The original as the placeholder, so a translator sees what they are translating.
        $original = Str::limit(trim(strip_tags((string) $record->getAttribute($field))), 150);

        return match ($field) {
            'content' => RichEditor::make("{$code}.{$field}")->label($label),
            'title' => TextInput::make("{$code}.{$field}")->label($label)->maxLength(255)->placeholder($original),
            default => Textarea::make("{$code}.{$field}")->label($label)->rows($field === 'transcript' ? 6 : 3)->placeholder($original),
        };
    }
}
