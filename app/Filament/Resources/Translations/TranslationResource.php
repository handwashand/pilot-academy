<?php

namespace App\Filament\Resources\Translations;

use App\Filament\Resources\Concerns\HasSentenceCaseLabels;
use App\Filament\Resources\Translations\Pages\CreateTranslation;
use App\Filament\Resources\Translations\Pages\EditTranslation;
use App\Filament\Resources\Translations\Pages\ListTranslations;
use App\Models\Language;
use App\Models\Translation;
use App\Models\User;
use App\Services\Translator;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

/**
 * Settings → Translations: where admins correct the wording students see, in
 * every language, without a deploy.
 *
 * The page lists the text shipped in lang/{code}/academy.php (synced in as empty
 * rows when it opens — see SyncShippedTranslations) alongside keys that only
 * exist in the database. A value saved here overrides the shipped line;
 * clearing it brings the shipped line back.
 */
class TranslationResource extends Resource
{
    use HasSentenceCaseLabels;

    protected static ?string $model = Translation::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedLanguage;

    protected static ?int $navigationSort = 11;

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return __t('admin_nav.groups.settings');
    }

    /**
     * Reached through the Settings overlay now, not its own sidebar link —
     * see App\Livewire\SettingsPanel. The resource and its routes stay, so a
     * direct link (and this class's own tests) keep working.
     */
    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }

    /** Every admin can correct wording; anyone else needs the permission. */
    public static function canAccess(): bool
    {
        $user = auth()->user();

        return (bool) ($user?->isAdmin() || $user?->hasPermission(User::PERMISSION_TRANSLATIONS_MANAGE));
    }

    public static function canViewAny(): bool
    {
        return static::canAccess();
    }

    public static function canCreate(): bool
    {
        return static::canAccess();
    }

    public static function canEdit(Model $record): bool
    {
        return static::canAccess();
    }

    public static function getNavigationLabel(): string
    {
        return __t('admin.translations');
    }

    public static function getModelLabel(): string
    {
        return __t('admin_nav.translations.one');
    }

    public static function getPluralModelLabel(): string
    {
        return __t('admin_nav.translations.many');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('key')
                ->label(__t('admin_settings.translations.key'))
                ->required()
                ->maxLength(255)
                ->disabledOn('edit'),

            Select::make('language_id')
                ->label(__t('admin_settings.translations.language'))
                ->relationship('language', 'native_name')
                ->required()
                ->preload()
                ->disabledOn('edit'),

            // For reference only: what the English site says, and what this
            // language says when nobody has corrected it.
            Textarea::make('english')
                ->label(__t('admin_settings.translations.english'))
                ->rows(3)
                ->disabled()
                ->dehydrated(false)
                ->visibleOn('edit')
                ->columnSpanFull()
                ->afterStateHydrated(fn (Textarea $component, ?Translation $record) => $component->state(
                    $record ? __t($record->key, [], 'en') : null,
                )),

            Textarea::make('shipped')
                ->label(__t('admin_settings.translations.shipped'))
                ->helperText(__t('admin_settings.translations.shipped_help'))
                ->rows(3)
                ->disabled()
                ->dehydrated(false)
                ->visibleOn('edit')
                ->columnSpanFull()
                ->afterStateHydrated(fn (Textarea $component, ?Translation $record) => $component->state(
                    $record ? (static::shippedLine($record) ?? __t('admin_settings.translations.none_shipped')) : null,
                )),

            Textarea::make('value')
                ->label(__t('admin_settings.translations.correction'))
                ->rows(4)
                ->columnSpanFull()
                ->helperText(__t('admin_settings.translations.correction_help'))
                ->dehydrateStateUsing(fn ($state) => filled($state) ? $state : null),

            Textarea::make('notes')->label(__t('admin_settings.translations.notes'))->rows(2)->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('key')
            // One row per key, whichever language it was first written in, with
            // every language beside it — so a line reads across, not down.
            ->modifyQueryUsing(fn (Builder $query) => $query
                ->whereIn('translations.id', Translation::query()->selectRaw('MIN(id)')->groupBy('key'))
                ->with(['language', 'siblings.language']))
            ->columns([
                ...static::languageColumns(),

                TextColumn::make('key')
                    ->label(__t('admin_settings.translations.key'))
                    ->size('xs')
                    ->color('gray')
                    ->copyable()
                    ->sortable()
                    ->toggleable()
                    // Finds a row by its key, a correction, or any language's
                    // shipped text — admins search for the words they saw.
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        $needle = mb_strtolower($search);
                        $translator = app(Translator::class);

                        $matchingKeys = Language::query()->pluck('code')
                            ->flatMap(fn (string $code): array => array_keys(array_filter(
                                $translator->shipped($code),
                                fn (string $line): bool => str_contains(mb_strtolower($line), $needle),
                            )))
                            ->unique()
                            ->values()
                            ->all();

                        // The row shown is one language; a correction may
                        // have been written in any of them.
                        $correctedKeys = Translation::query()
                            ->whereRaw('LOWER(value) LIKE ?', ["%{$needle}%"])
                            ->pluck('key')
                            ->all();

                        return $query->where(fn (Builder $rows) => $rows
                            ->whereRaw('LOWER(translations.key) LIKE ?', ["%{$needle}%"])
                            ->orWhereIn('translations.key', $correctedKeys)
                            ->orWhereIn('translations.key', $matchingKeys));
                    }),
            ])
            ->filters([
                SelectFilter::make('module')->label(__t('admin_settings.translations.module'))->options(fn (): array => Translation::query()->distinct()->pluck('module', 'module')->all()),
                // A key counts as corrected when any language has been corrected.
                TernaryFilter::make('corrected')
                    ->label(__t('admin_settings.translations.corrected'))
                    ->queries(
                        true: fn (Builder $query) => $query->whereHas('siblings', fn (Builder $rows) => $rows->whereNotNull('value')->where('value', '!=', '')),
                        false: fn (Builder $query) => $query->whereDoesntHave('siblings', fn (Builder $rows) => $rows->whereNotNull('value')->where('value', '!=', '')),
                    ),
            ]);
    }

    /**
     * A column per language: the correction where someone wrote one, the
     * shipped line otherwise, and empty where the key is missing from that
     * language. Clicking a cell corrects that language.
     *
     * @return array<int, TextColumn>
     */
    protected static function languageColumns(): array
    {
        return app(Translator::class)->activeLanguages()
            ->map(fn (Language $language): TextColumn => TextColumn::make("language_{$language->code}")
                ->label($language->native_name)
                ->state(fn (Translation $record): ?string => static::lineFor($record, $language->code))
                ->placeholder(__t('admin_settings.translations.missing'))
                ->color(fn (Translation $record): string => match (true) {
                    filled(static::rowFor($record, $language->code)?->value) => 'success',
                    static::lineFor($record, $language->code) !== null => 'gray',
                    default => 'danger',
                })
                ->tooltip(fn (Translation $record): ?string => static::lineFor($record, $language->code))
                ->limit(60)
                ->wrap()
                ->action(static::correctionAction($language)))
            ->all();
    }

    /** The correction box behind a cell, for one language. */
    protected static function correctionAction(Language $language): Action
    {
        $code = $language->code;

        return Action::make("correct_{$code}")
            ->modalHeading(fn (Translation $record): string => __t('admin_settings.translations.correct_in', ['language' => $language->native_name]))
            ->modalSubmitActionLabel(__t('admin_settings.translations.save_correction'))
            ->fillForm(fn (Translation $record): array => [
                'english' => __t($record->key, [], 'en'),
                'shipped' => app(Translator::class)->shipped($code)[$record->key] ?? __t('admin_settings.translations.none_shipped'),
                'value' => static::rowFor($record, $code)?->value,
            ])
            ->schema([
                Textarea::make('english')
                    ->label(__t('admin_settings.translations.english'))
                    ->rows(2)
                    ->disabled()
                    ->dehydrated(false),

                Textarea::make('shipped')
                    ->label(__t('admin_settings.translations.shipped'))
                    ->helperText(__t('admin_settings.translations.shipped_help'))
                    ->rows(2)
                    ->disabled()
                    ->dehydrated(false),

                Textarea::make('value')
                    ->label(__t('admin_settings.translations.correction'))
                    ->helperText(__t('admin_settings.translations.correction_help'))
                    ->rows(4),
            ])
            ->action(function (Translation $record, array $data) use ($language): void {
                // The row for this language may not exist yet: a key written in
                // one language only still gets corrected in the others.
                $row = Translation::firstOrNew([
                    'key' => $record->key,
                    'language_id' => $language->id,
                ]);

                $row->value = filled($data['value']) ? $data['value'] : null;
                $row->updated_by = auth()->id();
                $row->created_by ??= auth()->id();
                $row->save();
            });
    }

    protected static function rowFor(Translation $record, string $code): ?Translation
    {
        return $record->siblings->first(fn (Translation $row): bool => $row->language?->code === $code);
    }

    /** What this language shows today: the correction, or the shipped line. */
    protected static function lineFor(Translation $record, string $code): ?string
    {
        $value = static::rowFor($record, $code)?->value;

        return filled($value)
            ? $value
            : (app(Translator::class)->shipped($code)[$record->key] ?? null);
    }

    public static function shippedLine(Translation $record): ?string
    {
        $code = $record->language?->code;

        return $code ? (app(Translator::class)->shipped($code)[$record->key] ?? null) : null;
    }

    public static function getPages(): array
    {
        return [
            'index' => ListTranslations::route('/'),
            'create' => CreateTranslation::route('/create'),
            'edit' => EditTranslation::route('/{record}/edit'),
        ];
    }
}
