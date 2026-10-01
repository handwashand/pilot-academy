<?php

namespace App\Filament\Widgets;

use App\Filament\Widgets\Concerns\ReportsOnLearners;
use App\Filament\Widgets\Concerns\UsesDashboardFilters;
use App\Models\ActivityEvent;
use App\Models\Certificate;
use App\Models\Course;
use App\Models\Lesson;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Database\Eloquent\Builder;

class StudentProgressOverview extends StatsOverviewWidget
{
    use ReportsOnLearners;
    use UsesDashboardFilters;

    protected static ?int $sort = 1;

    protected int|string|array $columnSpan = 'full';

    protected function getColumns(): int
    {
        return 3;
    }

    protected function getStats(): array
    {
        $learners = $this->learners()
            ->when($this->dashboardCompanyId(), fn (Builder $query, int $companyId): Builder => $query->where('company_id', $companyId));
        $students = (clone $learners)->count();
        $active = (clone $learners)
            ->whereHas('activities', fn (Builder $query): Builder => $this->filterActivity($query)
                ->whereIn('type', ActivityEvent::LEARNER_ACTIVITY_TYPES))
            ->count();
        $engagement = $students > 0 ? (int) round($active / $students * 100) : 0;

        // From the pivot, not from activity events: an academy that was busy
        // before tracking began still has its finished lessons counted here,
        // and this card no longer reads zero beside a funnel showing
        // certificates. See UsesDashboardFilters::completedLessonRows().
        $completions = $this->scopeToLearners($this->completedLessonRows(), 'lesson_user.user_id')->count();

        $publishedCourses = Course::published()
            ->when($this->dashboardProductId(), fn (Builder $query, int $productId): Builder => $query->where('product_id', $productId))
            ->when($this->dashboardCourseId(), fn (Builder $query, int $courseId): Builder => $query->whereKey($courseId))
            ->count();
        $totalCourses = Course::query()
            ->when($this->dashboardProductId(), fn (Builder $query, int $productId): Builder => $query->where('product_id', $productId))
            ->when($this->dashboardCourseId(), fn (Builder $query, int $courseId): Builder => $query->whereKey($courseId))
            ->count();

        $certificates = $this->scopeToLearners(
            $this->filteredCertificates(),
        )->count();

        $averageScore = $this->scopeToLearners(
            $this->filteredCertificates(),
        )->avg('score_percent');

        // The line under each figure is left its default grey, on purpose. A
        // stat's colour reaches only that line and its icon, so colouring each
        // one differently gave the row six inks and no rhythm. Where the colour
        // carried a meaning, the words still do: the engagement line prints its
        // own percentage, and the certificates line prints the average score.
        // The dashboard has two colours — blue for ordinary numbers, amber for
        // what wants attention. Grey is not a third; it is plain text.
        return [
            Stat::make(__t('admin_widgets.overview.students'), $students)
                ->description(__t('admin_widgets.overview.students_help'))
                ->descriptionIcon('heroicon-m-user-group'),

            Stat::make(__t('admin_widgets.overview.active'), $active)
                ->description(__t('admin_widgets.overview.active_help', [
                    'percent' => $engagement,
                ]))
                ->descriptionIcon('heroicon-m-arrow-trending-up'),

            Stat::make(__t('admin_widgets.overview.completions'), $completions)
                ->description(__t('admin_widgets.overview.completions_help'))
                ->descriptionIcon('heroicon-m-check-circle'),

            Stat::make(__t('admin_widgets.overview.published_courses'), $publishedCourses)
                ->description(__t('admin_widgets.overview.published_courses_help', ['count' => $totalCourses]))
                ->descriptionIcon('heroicon-m-rectangle-stack'),

            Stat::make(__t('admin_widgets.overview.published_lessons'), Lesson::availableToLearners()
                ->when($this->dashboardProductId(), fn (Builder $query, int $productId): Builder => $query
                    ->whereHas('courses', fn (Builder $courses): Builder => $courses->where('product_id', $productId)))
                ->when($this->dashboardCourseId(), fn (Builder $query, int $courseId): Builder => $query
                    ->whereHas('courses', fn (Builder $courses): Builder => $courses->whereKey($courseId)))
                ->count())
                ->description(__t('admin_widgets.overview.published_lessons_help'))
                ->descriptionIcon('heroicon-m-book-open'),

            Stat::make(__t('admin_widgets.overview.certificates'), $certificates)
                // Staff pick up real certificates when previewing a final quiz,
                // so this counts learners only — see ReportsOnLearners.
                ->description($averageScore === null
                    ? __t('admin_widgets.overview.no_passes')
                    : __t('admin_widgets.overview.average_score', ['score' => round((float) $averageScore)]))
                ->descriptionIcon('heroicon-m-academic-cap'),
        ];
    }

    private function filteredCertificates(): Builder
    {
        return Certificate::query()
            ->whereNull('revoked_at')
            ->whereBetween('issued_at', [$this->dashboardStart(), $this->dashboardEnd()])
            ->when($this->dashboardCompanyId(), fn (Builder $query, int $companyId): Builder => $query
                ->whereHas('user', fn (Builder $user): Builder => $user->where('company_id', $companyId)))
            ->when($this->dashboardProductId(), fn (Builder $query, int $productId): Builder => $query
                ->whereHas('course', fn (Builder $course): Builder => $course->where('product_id', $productId)))
            ->when($this->dashboardCourseId(), fn (Builder $query, int $courseId): Builder => $query->where('course_id', $courseId));
    }
}
