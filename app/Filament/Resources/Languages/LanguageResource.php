<?php

namespace App\Filament\Resources\Languages;

use App\Filament\Resources\Languages\Pages\CreateLanguage;
use App\Filament\Resources\Languages\Pages\EditLanguage;
use App\Filament\Resources\Languages\Pages\ListLanguages;
use App\Models\Language;
use App\Models\Translation;
use App\Models\User;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\App;
use UnitEnum;

class LanguageResource extends Resource
{
    protected static ?string $model = Language::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedLanguage;

    protected static ?int $navigationSort = 10;

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return __t('admin_nav.groups.settings');
    }

    public static function shouldRegisterNavigation(): bool
    {
        return (bool) auth()->user()?->hasPermission(User::PERMISSION_LANGUAGES_MANAGE);
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
        return __t('admin.languages');
    }

    public static function getModelLabel(): string
    {
        return __t('admin_nav.languages.one');
    }

    public static function getPluralModelLabel(): string
    {
        return __t('admin_nav.languages.many');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('code')
                ->label(__t('admin_settings.languages.code'))
                ->required()
                ->length(2)
                ->unique(ignoreRecord: true),
            TextInput::make('name')->label(__t('admin_common.name'))->required()->maxLength(255),
            TextInput::make('native_name')->label(__t('admin_settings.languages.native_name'))->required()->maxLength(255),
            TextInput::make('direction')->label(__t('admin_settings.languages.direction'))->required()->default('ltr')->maxLength(3),
            TextInput::make('position')->label(__t('admin_settings.languages.position'))->numeric()->default(0),
            Toggle::make('is_active')->label(__t('admin_settings.languages.active'))->default(true),
            Toggle::make('is_default')
                ->label(__t('admin_settings.languages.default'))
                ->helperText(__t('admin_settings.languages.default_help')),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('position')
            ->columns([
                TextColumn::make('native_name')->label(__t('admin_settings.languages.language'))->searchable()->sortable(),
                TextColumn::make('code')->label(__t('admin_settings.languages.code_column'))->badge(),
                IconColumn::make('is_active')->label(__t('admin_settings.languages.active'))->boolean(),
                IconColumn::make('is_default')->label(__t('admin_settings.languages.default_column'))->boolean(),
                TextColumn::make('coverage')->label(__t('admin_settings.languages.coverage'))->state(fn (Language $record): string => self::coverage($record)),
            ])
            ->recordActions([
                Action::make('use')
                    ->label(__t('admin_settings.languages.use'))
                    ->action(function (Language $record): void {
                        session()->put('locale', $record->code);
                        auth()->user()?->forceFill(['locale' => $record->code])->save();
                        App::setLocale($record->code);
                    })
                    ->visible(fn (Language $record): bool => $record->is_active),
                EditAction::make(),
            ]);
    }

    private static function coverage(Language $language): string
    {
        $totalKeys = Translation::query()->distinct('key')->count('key');

        if ($totalKeys === 0) {
            return '0%';
        }

        $translated = Translation::query()
            ->where('language_id', $language->id)
            ->whereNotNull('value')
            ->where('value', '!=', '')
            ->count();

        return round($translated / $totalKeys * 100).'%';
    }

    public static function getPages(): array
    {
        return [
            'index' => ListLanguages::route('/'),
            'create' => CreateLanguage::route('/create'),
            'edit' => EditLanguage::route('/{record}/edit'),
        ];
    }
}
