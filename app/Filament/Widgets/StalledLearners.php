<?php

namespace App\Filament\Widgets;

use App\Actions\RemindStudent;
use App\Filament\Resources\Users\UserResource;
use App\Filament\Widgets\Concerns\ReportsOnLearners;
use App\Filament\Widgets\Concerns\UsesDashboardFilters;
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
    use UsesDashboardFilters;

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

                // Grey has to be said out loud: a badge with no colour falls
                // back to primary, which is Amber in this panel, and amber here
                // means "look at this". The partner and the course are context.
                TextColumn::make('company.name')
                    ->label(__t('admin_common.partner'))
                    ->badge()
                    ->color('gray')
                    ->placeholder('—'),

                TextColumn::make('stalled_course_title')
                    ->label(__t('admin_common.course'))
                    ->badge()
                    ->color('gray'),

                TextColumn::make('completed_lessons_count')
                    ->label(__t('admin_widgets.stalled.lessons_done'))
                    ->badge()
                    ->color('info'),

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
            ->when($this->dashboardCompanyId(), fn ($query, int $companyId) => $query->where('company_id', $companyId))
            ->with('company')
            ->addSelect(['stalled_course_title' => $this->stalledCoursesForUser()->select('stalled_courses.title')->limit(1)])
            ->addSelect(['completed_lessons_count' => $this->stalledCoursesForUser()
                ->selectRaw('count(distinct stalled_lu.lesson_id)')
                ->limit(1)])
            ->addSelect(['last_completed_at' => $this->stalledCoursesForUser()
                ->selectRaw('max(stalled_lu.completed_at)')
                ->limit(1)])
            // Who has already been chased, so nobody gets the same nudge twice.
            ->addSelect(['last_reminded_at' => DB::table('activity_events')
                ->selectRaw('max(created_at)')
                ->whereColumn('activity_events.user_id', 'users.id')
                ->where('activity_events.type', ActivityEvent::TYPE_REMINDER_SENT),
            ])
            ->whereExists($this->stalledCoursesForUser()->selectRaw('1'));
    }

    /** A course this learner started, went quiet in, and has not certified in. */
    private function stalledCoursesForUser(): Builder
    {
        return DB::table('courses as stalled_courses')
            ->join('course_lesson as stalled_cl', 'stalled_cl.course_id', '=', 'stalled_courses.id')
            ->join('lesson_user as stalled_lu', 'stalled_lu.lesson_id', '=', 'stalled_cl.lesson_id')
            ->whereColumn('stalled_lu.user_id', 'users.id')
            ->where('stalled_lu.completed_at', '<', now()->subDays(self::QUIET_DAYS))
            ->when($this->dashboardProductId(), fn (Builder $query, int $productId): Builder => $query
                ->where('stalled_courses.product_id', $productId))
            ->when($this->dashboardCourseId(), fn (Builder $query, int $courseId): Builder => $query
                ->where('stalled_courses.id', $courseId))
            ->whereNotExists(fn (Builder $recent) => $recent
                ->from('course_lesson as recent_cl')
                ->join('lesson_user as recent_lu', 'recent_lu.lesson_id', '=', 'recent_cl.lesson_id')
                ->whereColumn('recent_cl.course_id', 'stalled_courses.id')
                ->whereColumn('recent_lu.user_id', 'users.id')
                ->where('recent_lu.completed_at', '>=', now()->subDays(self::QUIET_DAYS)))
            ->whereNotExists(fn (Builder $certificates) => $certificates
                ->from('certificates')
                ->whereColumn('certificates.course_id', 'stalled_courses.id')
                ->whereColumn('certificates.user_id', 'users.id')
                ->whereNull('certificates.revoked_at'))
            ->groupBy('stalled_courses.id', 'stalled_courses.title')
            ->orderByRaw('max(stalled_lu.completed_at) asc')
            ->orderBy('stalled_courses.id');
    }
}
