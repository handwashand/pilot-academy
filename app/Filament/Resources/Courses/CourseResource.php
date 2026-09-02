<?php

namespace App\Filament\Resources\Courses;

use App\Filament\Resources\Courses\Pages\CreateCourse;
use App\Filament\Resources\Courses\Pages\EditCourse;
use App\Filament\Resources\Courses\Pages\ListCourses;
use App\Filament\Resources\Courses\Schemas\CourseForm;
use App\Filament\Resources\Courses\Tables\CoursesTable;
use App\Models\Course;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class CourseResource extends Resource
{
    protected static ?string $model = Course::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    /**
     * Creators only ever see their own products' courses — enforced here, on
     * the query, so it holds for the list, the edit page and every action
     * rather than only for what the table happens to render.
     */
    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $user = auth()->user();

        if ($user && $user->isCreator()) {
            $query->whereIn('product_id', $user->products()->pluck('products.id'));
        }

        return $query;
    }

    /** Drafts are work someone still has to finish and publish. */
    public static function getNavigationBadge(): ?string
    {
        // getEloquentQuery() already limits a creator to their own products,
        // so the badge cannot leak the size of anyone else's backlog.
        $drafts = static::getEloquentQuery()->where('status', Course::STATUS_DRAFT)->count();

        return $drafts > 0 ? (string) $drafts : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public static function getNavigationBadgeTooltip(): ?string
    {
        return 'Courses still in draft — students cannot see them yet';
    }

    protected static ?string $recordTitleAttribute = 'title';

    /** @return array<int, string> */
    public static function getGloballySearchableAttributes(): array
    {
        return ['title', 'slug'];
    }

    /** @return array<string, string|null> */
    public static function getGlobalSearchResultDetails(mixed $record): array
    {
        return [
            'Status' => $record->statusLabel(),
            'Product' => $record->product?->name ?? '—',
        ];
    }

    public static function form(Schema $schema): Schema
    {
        return CourseForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CoursesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\LessonsRelationManager::class,
            RelationManagers\FinalQuestionsRelationManager::class,
            RelationManagers\FeedbackRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCourses::route('/'),
            'create' => CreateCourse::route('/create'),
            'edit' => EditCourse::route('/{record}/edit'),
        ];
    }
}
