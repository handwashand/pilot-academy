<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\Companies\CompanyResource;
use App\Filament\Widgets\Concerns\ReportsOnLearners;
use App\Filament\Widgets\Concerns\UsesDashboardFilters;
use App\Models\ActivityEvent;
use App\Models\Company;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

/** Actionable partner engagement for the dashboard's selected period. */
class CompletionsByCompany extends TableWidget
{
    use ReportsOnLearners;
    use UsesDashboardFilters;

    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->heading(__t('admin_widgets.companies.heading'))
            ->description(__t('admin_widgets.companies.description'))
            ->query(Company::query()
                ->when($this->dashboardCompanyId(), fn ($query, int $companyId) => $query->whereKey($companyId))
                ->withCount(['students as learners_count'])
                ->addSelect([
                    'active_learners_count' => $this->activityAggregate('count(distinct activity_events.user_id)'),
                    'completions_count' => $this->activityAggregate('count(*)', ActivityEvent::TYPE_LESSON_COMPLETED),
                    'last_activity_at' => $this->activityAggregate('max(activity_events.created_at)'),
                    'certificates_count' => $this->certificateAggregate(),
                ]))
            ->defaultSort('active_learners_count', 'desc')
            ->columns([
                TextColumn::make('name')
                    ->label(__t('admin_common.partner'))
                    ->weight('bold')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('learners_count')
                    ->label(__t('admin_widgets.companies.learners'))
                    ->badge()
                    ->sortable(),
                TextColumn::make('active_learners_count')
                    ->label(__t('admin_widgets.companies.active'))
                    ->badge()
                    ->color('info')
                    ->sortable(),
                TextColumn::make('completions_count')
                    ->label(__t('admin_widgets.companies.completions'))
                    ->badge()
                    ->color('success')
                    ->sortable(),
                TextColumn::make('certificates_count')
                    ->label(__t('admin_widgets.companies.certificates'))
                    ->badge()
                    ->color('success')
                    ->sortable(),
                TextColumn::make('last_activity_at')
                    ->label(__t('admin_widgets.companies.last_activity'))
                    ->dateTime('d M Y, H:i')
                    ->since()
                    ->placeholder(__t('admin_common.never'))
                    ->sortable(),
            ])
            ->recordActions([
                Action::make('open')
                    ->label(__t('admin_widgets.companies.open'))
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->url(fn (Company $record): string => CompanyResource::getUrl('edit', ['record' => $record])),
            ])
            ->paginated([5, 10, 25])
            ->defaultPaginationPageOption(5)
            ->emptyStateHeading(__t('admin_widgets.companies.empty'));
    }

    private function activityAggregate(string $aggregate, ?string $type = null): Builder
    {
        return DB::table('activity_events')
            ->join('users', 'users.id', '=', 'activity_events.user_id')
            ->whereColumn('users.company_id', 'companies.id')
            ->where('users.role', User::ROLE_LEARNER)
            ->whereBetween('activity_events.created_at', [$this->dashboardStart(), $this->dashboardEnd()])
            ->when($type, fn (Builder $query, string $eventType): Builder => $query->where('activity_events.type', $eventType))
            ->when($this->dashboardProductId(), fn (Builder $query, int $productId): Builder => $query->where('activity_events.product_id', $productId))
            ->when($this->dashboardCourseId(), fn (Builder $query, int $courseId): Builder => $query->where('activity_events.course_id', $courseId))
            ->selectRaw($aggregate);
    }

    private function certificateAggregate(): Builder
    {
        return DB::table('certificates')
            ->join('users', 'users.id', '=', 'certificates.user_id')
            ->join('courses', 'courses.id', '=', 'certificates.course_id')
            ->whereColumn('users.company_id', 'companies.id')
            ->where('users.role', User::ROLE_LEARNER)
            ->whereNull('certificates.revoked_at')
            ->whereBetween('certificates.issued_at', [$this->dashboardStart(), $this->dashboardEnd()])
            ->when($this->dashboardProductId(), fn (Builder $query, int $productId): Builder => $query->where('courses.product_id', $productId))
            ->when($this->dashboardCourseId(), fn (Builder $query, int $courseId): Builder => $query->where('certificates.course_id', $courseId))
            ->selectRaw('count(*)');
    }
}
