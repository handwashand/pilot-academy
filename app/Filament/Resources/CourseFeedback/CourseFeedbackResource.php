<?php

namespace App\Filament\Resources\CourseFeedback;

use App\Filament\Resources\CourseFeedback\Pages\ListCourseFeedback;
use App\Filament\Resources\CourseFeedback\Tables\CourseFeedbackTable;
use App\Models\CourseFeedback;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

/**
 * Every course's student feedback in one list.
 *
 * It was already recorded and shown, but only inside each course's edit page —
 * so a complaint that ran across several courses, or everything one partner
 * said, could not be seen in one place. Read-only: students write it, staff
 * read it.
 */
class CourseFeedbackResource extends Resource
{
    protected static ?string $model = CourseFeedback::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChatBubbleLeftRight;

    protected static ?int $navigationSort = 4;

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return __t('admin_nav.groups.results');
    }

    public static function getNavigationLabel(): string
    {
        return __t('admin_nav.feedback.nav');
    }

    public static function getModelLabel(): string
    {
        return __t('admin_nav.feedback.one');
    }

    public static function getPluralModelLabel(): string
    {
        return __t('admin_nav.feedback.many');
    }

    /**
     * Across every product, so admins only. Creators still read their own
     * courses' feedback on the course's Student feedback tab — checked here
     * rather than in a model policy so that tab keeps working for them.
     */
    public static function canViewAny(): bool
    {
        return (bool) auth()->user()?->isAdmin();
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['user.company', 'course']);
    }

    public static function table(Table $table): Table
    {
        return CourseFeedbackTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCourseFeedback::route('/'),
        ];
    }
}
