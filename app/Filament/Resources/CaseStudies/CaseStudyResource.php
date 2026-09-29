<?php

namespace App\Filament\Resources\CaseStudies;

use App\Filament\Resources\CaseStudies\Pages\CreateCaseStudy;
use App\Filament\Resources\CaseStudies\Pages\EditCaseStudy;
use App\Filament\Resources\CaseStudies\Pages\ListCaseStudies;
use App\Filament\Resources\CaseStudies\Schemas\CaseStudyForm;
use App\Filament\Resources\CaseStudies\Tables\CaseStudiesTable;
use App\Filament\Resources\Concerns\HasSentenceCaseLabels;
use App\Models\CaseStudy;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class CaseStudyResource extends Resource
{
    use HasSentenceCaseLabels;

    protected static ?string $model = CaseStudy::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBriefcase;

    protected static ?int $navigationSort = 3;

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return __t('admin_nav.groups.content');
    }

    public static function getNavigationLabel(): string
    {
        return 'Case Studies';
    }

    public static function getModelLabel(): string
    {
        return 'case study';
    }

    public static function getPluralModelLabel(): string
    {
        return 'case studies';
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $user = auth()->user();

        if ($user && $user->isCreator()) {
            $query->whereIn('product_id', $user->products()->pluck('products.id'));
        }

        return $query;
    }

    public static function getNavigationBadge(): ?string
    {
        $drafts = static::getEloquentQuery()->where('status', CaseStudy::STATUS_DRAFT)->count();

        return $drafts > 0 ? (string) $drafts : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    protected static ?string $recordTitleAttribute = 'title';

    public static function getGloballySearchableAttributes(): array
    {
        return ['title', 'slug', 'short_problem', 'industry'];
    }

    public static function getGlobalSearchResultDetails(mixed $record): array
    {
        return [
            'Status' => $record->statusLabel(),
            'Industry' => $record->industry,
        ];
    }

    public static function form(Schema $schema): Schema
    {
        return CaseStudyForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CaseStudiesTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCaseStudies::route('/'),
            'create' => CreateCaseStudy::route('/create'),
            'edit' => EditCaseStudy::route('/{record}/edit'),
        ];
    }
}
