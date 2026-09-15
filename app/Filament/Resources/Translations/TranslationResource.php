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
use Filament\Actions\EditAction;
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

    /** Every admin can correct wording; anyone else needs the permission. */
    public static function shouldRegisterNavigation(): bool
    {
        $user = auth()->user();

        return (bool) ($user?->isAdmin() || $user?->hasPermission(User::PERMISSION_TRANSLATIONS_MANAGE));
    }

    public static function canAccess(): bool
    {
        return static::shouldRegisterNavigation();
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
            ->modifyQueryUsing(fn (Builder $query) => $query->with('language'))
            ->columns([
                TextColumn::make('language.native_name')
                    ->label(__t('admin_settings.translations.language'))
                    ->sortable(),

                TextColumn::make('text')
                    ->label(__t('admin_settings.translations.text'))
                    ->state(fn (Translation $record): string => filled($record->value) ? $record->value : (static::shippedLine($record) ?? ''))
                    ->description(fn (Translation $record): ?string => $record->language?->code === 'en' ? null : __t($record->key, [], 'en'))
                    ->limit(90)
                    ->wrap()
                    ->placeholder(__t('admin_settings.translations.missing')),

                TextColumn::make('status')
                    ->label(__t('admin_common.status'))
                    ->state(fn (Translation $record): string => match (true) {
                        filled($record->value) => 'corrected',
                        static::shippedLine($record) !== null => 'shipped',
                        default => 'missing',
                    })
                    ->formatStateUsing(fn (string $state): string => __t("admin_settings.translations.states.{$state}"))
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'corrected' => 'success',
                        'shipped' => 'gray',
                        default => 'danger',
                    }),

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

                        return $query->where(fn (Builder $rows) => $rows
                            ->whereRaw('LOWER(translations.key) LIKE ?', ["%{$needle}%"])
                            ->orWhereRaw('LOWER(translations.value) LIKE ?', ["%{$needle}%"])
                            ->orWhereIn('translations.key', $matchingKeys));
                    }),
            ])
            ->filters([
                SelectFilter::make('language_id')->relationship('language', 'native_name')->label(__t('admin_settings.translations.language')),
                SelectFilter::make('module')->label(__t('admin_settings.translations.module'))->options(fn (): array => Translation::query()->distinct()->pluck('module', 'module')->all()),
                TernaryFilter::make('corrected')
                    ->label(__t('admin_settings.translations.corrected'))
                    ->queries(
                        true: fn (Builder $query) => $query->whereNotNull('value')->where('value', '!=', ''),
                        false: fn (Builder $query) => $query->where(fn (Builder $rows) => $rows->whereNull('value')->orWhere('value', '')),
                    ),
            ])
            ->recordActions([
                EditAction::make()->label(__t('admin_settings.translations.correct')),
            ]);
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
