<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\Users\UserResource;
use App\Filament\Widgets\Concerns\ReportsOnLearners;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

/**
 * Students who started something and then stopped: at least one finished
 * lesson, nothing in the last fortnight, and no certificate to show for it.
 *
 * The academy has no deadlines, so nobody can be "overdue" — going quiet is the
 * nearest thing to an outstanding item, and it is the one list here worth
 * acting on. Whoever manages the partner can follow it up.
 */
class StalledLearners extends TableWidget
{
    use ReportsOnLearners;

    protected static ?int $sort = 3;

    protected int|string|array $columnSpan = 'full';

    /** How long without a completed lesson counts as having gone quiet. */
    private const QUIET_DAYS = 14;

    public function table(Table $table): Table
    {
        return $table
            ->heading('Students who have gone quiet')
            ->description('Started a course, nothing completed in the last '.self::QUIET_DAYS.' days, no certificate yet.')
            ->query($this->stalledLearners())
            ->defaultSort('last_completed_at')
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->weight('bold')
                    ->description(fn (User $record): ?string => $record->email),

                TextColumn::make('company.name')
                    ->label('Partner')
                    ->badge()
                    ->placeholder('—'),

                TextColumn::make('completed_lessons_count')
                    ->label('Lessons done')
                    ->badge()
                    ->color('success'),

                TextColumn::make('last_completed_at')
                    ->label('Last activity')
                    ->dateTime('d M Y')
                    ->since()
                    ->placeholder('—')
                    ->sortable(),
            ])
            ->recordActions([
                Action::make('open')
                    ->label('Open')
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->url(fn (User $record): string => UserResource::getUrl('edit', ['record' => $record])),
            ])
            // A dashboard panel, not a report — keep it glanceable.
            ->paginated([5, 10, 25])
            ->defaultPaginationPageOption(5)
            ->emptyStateHeading('Nobody has gone quiet')
            ->emptyStateDescription('Every student who started a course is either still working through it or has finished.');
    }

    private function stalledLearners()
    {
        return $this->learners()
            ->with('company')
            ->withCount('completedLessons')
            ->addSelect(['last_completed_at' => DB::table('lesson_user')
                ->selectRaw('max(completed_at)')
                ->whereColumn('lesson_user.user_id', 'users.id'),
            ])
            // Started something…
            ->whereExists(fn (Builder $query) => $query
                ->from('lesson_user')
                ->whereColumn('lesson_user.user_id', 'users.id'))
            // …but nothing recently.
            ->whereNotExists(fn (Builder $query) => $query
                ->from('lesson_user')
                ->whereColumn('lesson_user.user_id', 'users.id')
                ->where('lesson_user.completed_at', '>=', now()->subDays(self::QUIET_DAYS)))
            // Finishing late still counts as finished, so anyone holding a
            // certificate is not outstanding and drops off the list.
            ->whereNotExists(fn (Builder $query) => $query
                ->from('certificates')
                ->whereColumn('certificates.user_id', 'users.id')
                ->whereNull('certificates.revoked_at'));
    }
}
