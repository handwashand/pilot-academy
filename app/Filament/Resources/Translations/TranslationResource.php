<?php

namespace App\Filament\Resources\Translations;

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
    protected static ?string $model = Translation::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedLanguage;

    protected static string|UnitEnum|null $navigationGroup = 'Settings';

    protected static ?int $navigationSort = 11;

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

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('key')
                ->required()
                ->maxLength(255)
                ->disabledOn('edit'),

            Select::make('language_id')
                ->label('Language')
                ->relationship('language', 'native_name')
                ->required()
                ->preload()
                ->disabledOn('edit'),

            // For reference only: what the English site says, and what this
            // language says when nobody has corrected it.
            Textarea::make('english')
                ->label('English')
                ->rows(3)
                ->disabled()
                ->dehydrated(false)
                ->visibleOn('edit')
                ->columnSpanFull()
                ->afterStateHydrated(fn (Textarea $component, ?Translation $record) => $component->state(
                    $record ? __t($record->key, [], 'en') : null,
                )),

            Textarea::make('shipped')
                ->label('Shipped text')
                ->helperText('What students see in this language when the box below is empty.')
                ->rows(3)
                ->disabled()
                ->dehydrated(false)
                ->visibleOn('edit')
                ->columnSpanFull()
                ->afterStateHydrated(fn (Textarea $component, ?Translation $record) => $component->state(
                    $record ? (static::shippedLine($record) ?? '— none shipped —') : null,
                )),

            Textarea::make('value')
                ->label('Correction')
                ->rows(4)
                ->columnSpanFull()
                ->helperText('Leave empty to use the shipped text. Keep words that start with a colon, such as :name, exactly as they are, and keep the | between the forms of a count ("1 lesson|2 lessons").')
                ->dehydrateStateUsing(fn ($state) => filled($state) ? $state : null),

            Textarea::make('notes')->rows(2)->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('key')
            ->modifyQueryUsing(fn (Builder $query) => $query->with('language'))
            ->columns([
                TextColumn::make('language.native_name')
                    ->label('Language')
                    ->sortable(),

                TextColumn::make('text')
                    ->label('Text students see')
                    ->state(fn (Translation $record): string => filled($record->value) ? $record->value : (static::shippedLine($record) ?? ''))
                    ->description(fn (Translation $record): ?string => $record->language?->code === 'en' ? null : __t($record->key, [], 'en'))
                    ->limit(90)
                    ->wrap()
                    ->placeholder('missing'),

                TextColumn::make('status')
                    ->state(fn (Translation $record): string => match (true) {
                        filled($record->value) => 'Corrected',
                        static::shippedLine($record) !== null => 'Shipped',
                        default => 'Missing',
                    })
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Corrected' => 'success',
                        'Shipped' => 'gray',
                        default => 'danger',
                    }),

                TextColumn::make('key')
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
                SelectFilter::make('language_id')->relationship('language', 'native_name')->label('Language'),
                SelectFilter::make('module')->options(fn (): array => Translation::query()->distinct()->pluck('module', 'module')->all()),
                TernaryFilter::make('corrected')
                    ->label('Corrected')
                    ->queries(
                        true: fn (Builder $query) => $query->whereNotNull('value')->where('value', '!=', ''),
                        false: fn (Builder $query) => $query->where(fn (Builder $rows) => $rows->whereNull('value')->orWhere('value', '')),
                    ),
            ])
            ->recordActions([
                EditAction::make()->label('Correct'),
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
