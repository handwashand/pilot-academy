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
use Filament\Actions\ActionGroup;
use Filament\Forms\Components\Checkbox;
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
 *
 * When DeepL, ChatGPT or DeepSeek is switched on (Settings → Integrations) and
 * the person holds the right to use it, Translate becomes a small menu of what
 * is available: by hand, or with each engine. Choosing an engine asks for
 * confirmation, then opens the same window with the empty boxes drafted. That
 * only fills the form — an editor reads and changes the text, and nothing is
 * stored until Save translations. Typing by hand works the same whether an
 * engine is on, off, out of quota or down.
 */
class TranslateContentAction
{
    /** The engines that can draft, by action name: their label and the right that spends their credit. */
    public const ENGINES = [
        'translateWithDeepL' => ['label' => 'DeepL', 'permission' => User::PERMISSION_DEEPL_TRANSLATE, 'provider' => AiProvider::DEEPL],
        'translateWithChatgpt' => ['label' => 'ChatGPT', 'permission' => User::PERMISSION_AI_TRANSLATE, 'provider' => AiProvider::CHATGPT],
        'translateWithDeepseek' => ['label' => 'DeepSeek', 'permission' => User::PERMISSION_AI_TRANSLATE, 'provider' => AiProvider::DEEPSEEK],
    ];

    /** The button: a plain Translate, or a menu of the ways to translate when an engine is available. */
    public static function make(): Action|ActionGroup
    {
        $engines = array_values(array_filter(array_keys(static::ENGINES), fn (string $name): bool => static::engine($name) !== null));

        if ($engines === []) {
            return static::dialog(grouped: false);
        }

        return ActionGroup::make([
            static::dialog(grouped: true),
            ...array_map(fn (string $name): Action => static::engineItem($name), $engines),
        ])
            ->label(fn (): string => __t('admin_common.translate.button'))
            ->icon(Heroicon::OutlinedLanguage)
            ->color('gray')
            ->button();
    }

    /** The Translate window itself. */
    private static function dialog(bool $grouped): Action
    {
        return Action::make('translateContent')
            ->label(fn (): string => __t($grouped ? 'admin_common.translate.by_hand' : 'admin_common.translate.button'))
            ->icon($grouped ? Heroicon::OutlinedPencilSquare : Heroicon::OutlinedLanguage)
            ->color('gray')
            ->modalHeading(fn (): string => __t('admin_common.translate.heading'))
            ->modalDescription(fn (Model $record): string => __t('admin_common.translate.description', ['language' => static::nameOf($record->contentLanguageCode())]))
            ->modalWidth('5xl')
            ->modalSubmitActionLabel(fn (): string => __t('admin_common.translate.submit'))
            ->visible(fn (Model $record): bool => static::targets($record)->isNotEmpty())
            ->fillForm(fn (Model $record, array $arguments): array => static::initial($record, $arguments['engine'] ?? null, (bool) ($arguments['overwrite'] ?? false)))
            ->schema(fn (Model $record): array => [
                Tabs::make('languages')->tabs(
                    static::targets($record)
                        ->map(fn (Language $language): Tab => Tab::make($language->native_name)->schema(
                            collect($record->translatableFields())
                                ->map(fn (string $field) => static::input($record, "{$language->code}.{$field}", $field))
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

    /** "Translate with X": confirm the paid request, then open the window with drafts in it. */
    private static function engineItem(string $name): Action
    {
        $provider = ['provider' => static::ENGINES[$name]['label']];

        return Action::make($name)
            ->label(fn (): string => __t('admin_common.translate.draft.button', $provider))
            ->icon(Heroicon::OutlinedSparkles)
            ->visible(fn (): bool => static::engine($name) !== null)
            ->authorize(fn (): bool => static::engine($name) !== null)
            ->requiresConfirmation()
            ->modalHeading(fn (): string => __t('admin_common.translate.draft.confirm_heading', $provider))
            ->modalDescription(fn (Model $record): string => __t('admin_common.translate.draft.confirm_description', $provider)
                .(static::translatedInto($record) === [] ? '' : ' '.__t('admin_common.translate.draft.existing', ['languages' => implode(', ', static::translatedInto($record))])))
            ->modalSubmitActionLabel(fn (): string => __t('admin_common.translate.draft.confirm_submit'))
            // Already translated? The default is to keep it and edit; replacing is a choice.
            ->schema(fn (Model $record): array => [
                Checkbox::make('overwrite')
                    ->label(fn (): string => __t('admin_common.translate.draft.overwrite'))
                    ->visible(static::translatedInto($record) !== []),
            ])
            ->action(fn (array $data, $livewire) => $livewire->replaceMountedAction('translateContent', [
                'engine' => $name,
                'overwrite' => (bool) ($data['overwrite'] ?? false),
            ]));
    }

    /** @return array<int, string> The languages this record already has text in, by their own names. */
    private static function translatedInto(Model $record): array
    {
        $codes = array_keys(array_filter(static::current($record), fn (array $fields): bool => array_filter($fields, 'filled') !== []));

        return array_map(fn (string $code): string => static::nameOf($code), $codes);
    }

    /** The connected engine for this action — only for someone holding its right. */
    public static function engine(string $name): DeepLClient|LlmTranslator|null
    {
        $config = static::ENGINES[$name];

        // An admin is the one who adds the key, so an admin may use it. Anyone
        // else needs the right ticked on their account.
        $user = auth()->user();

        if (! $user || ! ($user->isAdmin() || $user->hasPermission($config['permission']))) {
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

    /**
     * What the window opens with: the saved translations, and — when an engine
     * was chosen — a draft in every box that is still empty. The engine is looked
     * up again here: the choice arrives from the browser.
     *
     * @return array<string, array<string, string>>
     */
    private static function initial(Model $record, ?string $engineName, bool $overwrite = false): array
    {
        $values = static::current($record);

        if ($engineName === null || ! isset(static::ENGINES[$engineName]) || ! ($engine = static::engine($engineName))) {
            return $values;
        }

        // Asked to replace: draft every box, not only the empty ones. Still only in the
        // form — the saved text is untouched until Save translations.
        $result = app(DraftTranslations::class)->handle($record, static::targets($record), $overwrite ? [] : $values, $engine);

        foreach ($result['drafts'] as $code => $fields) {
            foreach ($fields as $field => $text) {
                $values[$code][$field] = $text;
            }
        }

        static::reportDraft($result, ['provider' => static::ENGINES[$engineName]['label']]);

        return $values;
    }

    /**
     * Tell the editor how drafting went.
     *
     * @param  array{drafts: array<mixed>, error: DeepLException|LlmException|null}  $result
     * @param  array{provider: string}  $provider
     */
    public static function reportDraft(array $result, array $provider): void
    {
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

    /** One box for a translatable field, named $name in the form state. */
    public static function input(Model $record, string $name, string $field)
    {
        // A field without a shipped name reads as its own name, made readable.
        $label = $record->translatableFieldLabel($field);
        // The original as the placeholder, so a translator sees what they are translating.
        $original = Str::limit(trim(strip_tags((string) $record->getAttribute($field))), 150);

        return match ($field) {
            'content' => RichEditor::make($name)->label($label),
            'title', 'industry', 'implementation_time' => TextInput::make($name)->label($label)->maxLength(255)->placeholder($original),
            default => Textarea::make($name)->label($label)->rows($field === 'transcript' ? 6 : 3)->placeholder($original),
        };
    }
}
