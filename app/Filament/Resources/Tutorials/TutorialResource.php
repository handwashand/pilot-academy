<?php

namespace App\Filament\Resources\Tutorials;

use App\Filament\Resources\Concerns\HasSentenceCaseLabels;
use App\Filament\Resources\Tutorials\Pages\CreateTutorial;
use App\Filament\Resources\Tutorials\Pages\EditTutorial;
use App\Filament\Resources\Tutorials\Pages\ListTutorials;
use App\Filament\Resources\Tutorials\Schemas\TutorialForm;
use App\Filament\Resources\Tutorials\Tables\TutorialsTable;
use App\Models\Tutorial;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class TutorialResource extends Resource
{
    use HasSentenceCaseLabels;

    protected static ?string $model = Tutorial::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPlayCircle;

    protected static ?int $navigationSort = 5;

    protected static ?string $recordTitleAttribute = 'title';

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return __t('admin_nav.groups.content');
    }

    public static function getNavigationLabel(): string
    {
        return __t('admin_nav.tutorials.nav');
    }

    public static function getModelLabel(): string
    {
        return __t('admin_nav.tutorials.one');
    }

    public static function getPluralModelLabel(): string
    {
        return __t('admin_nav.tutorials.many');
    }

    /** A creator sees their own products' tutorials, and nobody else's. */
    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $user = auth()->user();

        if ($user?->isCreator()) {
            $query->whereIn('product_id', $user->products()->pluck('products.id'));
        }

        return $query;
    }

    public static function getNavigationBadge(): ?string
    {
        $drafts = static::getEloquentQuery()->where('status', Tutorial::STATUS_DRAFT)->count();

        return $drafts > 0 ? (string) $drafts : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public static function getNavigationBadgeTooltip(): ?string
    {
        return __t('admin_nav.tutorials.badge');
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['title', 'slug', 'summary'];
    }

    public static function getGlobalSearchResultDetails(mixed $record): array
    {
        return [__t('admin_common.status') => $record->statusLabel()];
    }

    public static function form(Schema $schema): Schema
    {
        return TutorialForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return TutorialsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListTutorials::route('/'),
            'create' => CreateTutorial::route('/create'),
            'edit' => EditTutorial::route('/{record}/edit'),
        ];
    }
}
