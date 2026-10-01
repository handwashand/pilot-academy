<?php

namespace App\Filament\Resources\Webinars;

use App\Filament\Resources\Concerns\HasSentenceCaseLabels;
use App\Filament\Resources\Webinars\Pages\CreateWebinar;
use App\Filament\Resources\Webinars\Pages\EditWebinar;
use App\Filament\Resources\Webinars\Pages\ListWebinars;
use App\Filament\Resources\Webinars\Schemas\WebinarForm;
use App\Filament\Resources\Webinars\Tables\WebinarsTable;
use App\Models\Webinar;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class WebinarResource extends Resource
{
    use HasSentenceCaseLabels;

    protected static ?string $model = Webinar::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedVideoCamera;

    protected static ?int $navigationSort = 4;

    protected static ?string $recordTitleAttribute = 'title';

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return __t('admin_nav.groups.content');
    }

    public static function getNavigationLabel(): string
    {
        return __t('admin_nav.webinars.nav');
    }

    public static function getModelLabel(): string
    {
        return __t('admin_nav.webinars.one');
    }

    public static function getPluralModelLabel(): string
    {
        return __t('admin_nav.webinars.many');
    }

    /** A creator sees their own products' sessions, and nobody else's. */
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
        $drafts = static::getEloquentQuery()->where('status', Webinar::STATUS_DRAFT)->count();

        return $drafts > 0 ? (string) $drafts : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public static function getNavigationBadgeTooltip(): ?string
    {
        return __t('admin_nav.webinars.badge');
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['title', 'slug', 'summary', 'presenter'];
    }

    public static function getGlobalSearchResultDetails(mixed $record): array
    {
        return [
            __t('admin_common.status') => $record->statusLabel(),
            __t('admin_webinars.form.starts_at') => $record->whenLabel(),
        ];
    }

    public static function form(Schema $schema): Schema
    {
        return WebinarForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return WebinarsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListWebinars::route('/'),
            'create' => CreateWebinar::route('/create'),
            'edit' => EditWebinar::route('/{record}/edit'),
        ];
    }
}
