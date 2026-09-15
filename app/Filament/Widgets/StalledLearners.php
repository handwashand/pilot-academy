<?php

namespace App\Filament\Widgets;

use App\Actions\RemindStudent;
use App\Filament\Resources\Users\UserResource;
use App\Filament\Widgets\Concerns\ReportsOnLearners;
use App\Models\ActivityEvent;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
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
            ->heading(__t('admin_widgets.stalled.heading'))
            ->description(__t('admin_widgets.stalled.description', ['days' => self::QUIET_DAYS]))
            ->query($this->stalledLearners())
            ->defaultSort('last_completed_at')
            ->columns([
                TextColumn::make('name')
                    ->label(__t('admin_common.name'))
                    ->searchable()
                    ->weight('bold')
                    ->description(fn (User $record): ?string => $record->email),

                TextColumn::make('company.name')
                    ->label(__t('admin_common.partner'))
                    ->badge()
                    ->placeholder('—'),

                TextColumn::make('completed_lessons_count')
                    ->label(__t('admin_widgets.stalled.lessons_done'))
                    ->badge()
                    ->color('success'),

                TextColumn::make('last_completed_at')
                    ->label(__t('admin_widgets.stalled.last_activity'))
                    ->dateTime('d M Y')
                    ->since()
                    ->placeholder('—')
                    ->sortable(),

                TextColumn::make('last_reminded_at')
                    ->label(__t('admin_widgets.stalled.reminded'))
                    ->dateTime('d M Y')
                    ->since()
                    ->placeholder(__t('admin_common.never'))
                    ->color(fn ($state): string => $state ? 'gray' : 'warning')
                    ->sortable(),
            ])
            ->recordActions([
                Action::make('remind')
                    ->label(__t('admin_widgets.stalled.remind'))
                    ->icon('heroicon-o-envelope')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->modalHeading(__t('admin_widgets.stalled.remind_heading'))
                    ->modalDescription(fn (User $record): string => __t('admin_widgets.stalled.remind_description', ['email' => $record->email]))
                    ->modalSubmitActionLabel(__t('admin_widgets.stalled.send_it'))
                    // Hidden rather than disabled once sent: a greyed-out button
                    // invites clicking, and there is nothing to click for a week.
                    ->visible(fn (User $record): bool => app(RemindStudent::class)->canRemind($record))
                    ->action(function (User $record, RemindStudent $remind): void {
                        $remind->handle($record)
                            ? Notification::make()->title(__t('admin_widgets.stalled.sent', ['name' => $record->name]))->success()->send()
                            : Notification::make()->title(__t('admin_widgets.stalled.not_sent'))->body(__t('admin_widgets.stalled.cooldown', ['days' => RemindStudent::COOLDOWN_DAYS]))->warning()->send();
                    }),

                Action::make('open')
                    ->label(__t('admin_widgets.stalled.open'))
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->url(fn (User $record): string => UserResource::getUrl('edit', ['record' => $record])),
            ])
            ->toolbarActions([
                BulkAction::make('remind')
                    ->label(__t('admin_widgets.stalled.remind_all'))
                    ->icon('heroicon-o-envelope')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->modalDescription(__t('admin_widgets.stalled.remind_all_description', ['days' => RemindStudent::COOLDOWN_DAYS]))
                    ->deselectRecordsAfterCompletion()
                    ->action(function (Collection $records, RemindStudent $remind): void {
                        $sent = $records->filter(fn (User $student): bool => $remind->handle($student));
                        $skipped = $records->count() - $sent->count();

                        if ($sent->isNotEmpty()) {
                            Notification::make()->title(__tc('admin_widgets.stalled.sent_count', $sent->count()))->success()->send();
                        }

                        // Say who was left out rather than quietly doing less.
                        if ($skipped > 0) {
                            Notification::make()
                                ->title(__t('admin_widgets.stalled.skipped_count', ['count' => $skipped]))
                                ->body(__t('admin_widgets.stalled.cooldown', ['days' => RemindStudent::COOLDOWN_DAYS]))
                                ->warning()
                                ->send();
                        }
                    }),
            ])
            // A dashboard panel, not a report — keep it glanceable.
            ->paginated([5, 10, 25])
            ->defaultPaginationPageOption(5)
            ->emptyStateHeading(__t('admin_widgets.stalled.empty'))
            ->emptyStateDescription(__t('admin_widgets.stalled.empty_description'));
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
            // Who has already been chased, so nobody gets the same nudge twice.
            ->addSelect(['last_reminded_at' => DB::table('activity_events')
                ->selectRaw('max(created_at)')
                ->whereColumn('activity_events.user_id', 'users.id')
                ->where('activity_events.type', ActivityEvent::TYPE_REMINDER_SENT),
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
