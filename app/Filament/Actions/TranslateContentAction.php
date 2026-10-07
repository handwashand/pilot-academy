<?php

namespace App\Filament\Actions;

use App\Actions\DraftTranslations;
use App\Models\AiProvider;
use App\Models\Language;
use App\Models\User;
use App\Services\DeepL\DeepLClient;
use App\Services\DeepL\DeepLException;
use App\Services\Llm\LlmException;
use App\Services\Llm\LlmTranslator;
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
                Actions::make(array_map(fn (string $name): Action => static::draftAction($record, $name), array_keys(static::ENGINES)))
                    ->key('draftActions')
                    ->visible(fn (): bool => array_filter(array_keys(static::ENGINES), fn (string $name): bool => static::engine($name) !== null) !== []),
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

    /** The engines that can draft, by action name: their label and the right that spends their credit. */
    private const ENGINES = [
        'draftWithDeepL' => ['label' => 'DeepL', 'permission' => User::PERMISSION_DEEPL_TRANSLATE, 'provider' => AiProvider::DEEPL],
        'draftWithChatgpt' => ['label' => 'ChatGPT', 'permission' => User::PERMISSION_AI_TRANSLATE, 'provider' => AiProvider::CHATGPT],
        'draftWithDeepseek' => ['label' => 'DeepSeek', 'permission' => User::PERMISSION_AI_TRANSLATE, 'provider' => AiProvider::DEEPSEEK],
    ];

    /** The connected engine for this action — only for someone holding its right. */
    private static function engine(string $name): DeepLClient|LlmTranslator|null
    {
        $config = static::ENGINES[$name];

        if (! auth()->user()?->hasPermission($config['permission'])) {
            return null;
        }

        if ($config['provider'] === AiProvider::DEEPL) {
            // The key saved under Settings → Integrations (and its switch), else the server's .env.
            $saved = AiProvider::withToken(AiProvider::DEEPL);
            $client = $saved ? new DeepLClient($saved->deeplSettings()) : app(DeepLClient::class);

            return $client->enabled() ? $client : null;
        }

        $provider = AiProvider::usable($config['provider']);

        return $provider ? new LlmTranslator($provider) : null;
    }

    /** Fills the empty boxes with drafts; never touches a box that has text. */
    private static function draftAction(Model $record, string $name): Action
    {
        $provider = ['provider' => static::ENGINES[$name]['label']];

        return Action::make($name)
            ->label(fn (): string => __t('admin_common.translate.draft.button', $provider))
            ->icon(Heroicon::OutlinedSparkles)
            ->color('gray')
            ->visible(fn (): bool => static::engine($name) !== null)
            ->authorize(fn (): bool => static::engine($name) !== null)
            ->requiresConfirmation()
            ->modalHeading(fn (): string => __t('admin_common.translate.draft.confirm_heading', $provider))
            ->modalDescription(fn (): string => __t('admin_common.translate.draft.confirm_description', $provider))
            ->modalSubmitActionLabel(fn (): string => __t('admin_common.translate.draft.confirm_submit'))
            ->action(function (Get $get, Set $set) use ($record, $name, $provider): void {
                $targets = static::targets($record);
                $current = [];

                foreach ($targets as $language) {
                    foreach ($record->translatableFields() as $field) {
                        $current[$language->code][$field] = $get("{$language->code}.{$field}");
                    }
                }

                $result = app(DraftTranslations::class)->handle($record, $targets, $current, static::engine($name));

                foreach ($result['drafts'] as $code => $fields) {
                    foreach ($fields as $field => $text) {
                        $set("{$code}.{$field}", $text);
                    }
                }

                $count = collect($result['drafts'])->flatten()->count();

                if ($result['error']) {
                    Notification::make()
                        ->title(__t('admin_common.translate.draft.failed', $provider))
                        ->body(static::failureMessage($result['error'], $provider).($count ? ' '.__t('admin_common.translate.draft.kept', ['count' => $count]) : ''))
                        ->danger()
                        ->send();

                    return;
                }

                $count
                    ? Notification::make()->title(__t('admin_common.translate.draft.drafted'))->body(__t('admin_common.translate.draft.drafted_body', ['count' => $count]))->success()->send()
                    : Notification::make()->title(__t('admin_common.translate.draft.nothing'))->body(__t('admin_common.translate.draft.nothing_body', $provider))->warning()->send();
            });
    }

    /** @param  array{provider: string}  $provider */
    private static function failureMessage(DeepLException|LlmException $exception, array $provider): string
    {
        $key = match (true) {
            $exception->isQuotaProblem() => 'quota',
            $exception->isAuthProblem() || $exception->status === 401 => 'auth',
            $exception->status === 429 => 'busy',
            $exception->isTemporary() => 'unreachable',
            $exception->errorCode === 'unsupported_language_pair' => 'unsupported',
            default => 'rejected',
        };

        return __t("admin_common.translate.draft.errors.{$key}", $provider);
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
