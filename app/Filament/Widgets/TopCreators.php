<?php

namespace App\Filament\Widgets;

use App\Filament\Widgets\Concerns\ReportsOnLearners;
use App\Models\User;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

/**
 * Who is writing the lessons: creators ranked by the lessons they wrote.
 *
 * Lessons are the only unit counted. A creator's output is staff information,
 * so only the admin gate of ReportsOnLearners is borrowed here — this widget
 * reports on creators, not on learners, and never touches learners().
 */
class TopCreators extends TableWidget
{
    use ReportsOnLearners;

    protected static ?int $sort = 9;

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->heading(__t('admin_widgets.creators.heading'))
            ->description(__t('admin_widgets.creators.description'))
            // Soft-deleted lessons drop out on their own: Lesson is soft-deleted.
            ->query(fn (): Builder => User::query()
                ->where('role', User::ROLE_CREATOR)
                ->withCount('createdLessons')
                ->limit(10))
            ->defaultSort('created_lessons_count', 'desc')
            ->columns([
                TextColumn::make('name')
                    ->label(__t('admin_common.name'))
                    ->weight('bold'),

                TextColumn::make('created_lessons_count')
                    ->label(__t('admin_widgets.creators.lessons'))
                    ->badge()
                    ->sortable(),
            ])
            // Paginated, the table would replace the query's limit with its own
            // page size and show every creator; unpaginated, get() keeps it.
            ->paginated(false)
            ->emptyStateHeading(__t('admin_widgets.creators.empty'))
            ->emptyStateDescription(__t('admin_widgets.creators.empty_description'));
    }
}
