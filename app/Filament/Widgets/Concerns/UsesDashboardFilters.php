<?php

namespace App\Filament\Widgets\Concerns;

use Carbon\CarbonImmutable;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Facades\DB;

trait UsesDashboardFilters
{
    use InteractsWithPageFilters;

    protected function filterActivity(Builder $query): Builder
    {
        return $query
            ->whereBetween('created_at', [$this->dashboardStart(), $this->dashboardEnd()])
            ->when($this->dashboardCompanyId(), fn (Builder $events, int $companyId): Builder => $events
                ->whereHas('user', fn (Builder $users): Builder => $users->where('company_id', $companyId)))
            ->when($this->dashboardProductId(), fn (Builder $events, int $productId): Builder => $events
                ->where('product_id', $productId))
            ->when($this->dashboardCourseId(), fn (Builder $events, int $courseId): Builder => $events
                ->where('course_id', $courseId));
    }

    /**
     * Finished lessons as the pivot records them, filtered like everything else
     * on the page.
     *
     * Activity events only go back to the day the academy started recording
     * them; lesson_user goes back to the first lesson anybody finished. Figures
     * about finishing read this, so they do not fall to zero for an academy
     * that was busy before tracking existed.
     */
    protected function completedLessonRows(): QueryBuilder
    {
        $rows = DB::table('lesson_user')
            ->join('users', 'users.id', '=', 'lesson_user.user_id')
            ->whereNotNull('lesson_user.completed_at')
            ->whereBetween('lesson_user.completed_at', [$this->dashboardStart(), $this->dashboardEnd()])
            ->when($this->dashboardCompanyId(), fn (QueryBuilder $query, int $companyId): QueryBuilder => $query
                ->where('users.company_id', $companyId));

        // Only join the course side when a filter needs it: a lesson can sit in
        // several courses, and the join would otherwise count it once each.
        if ($this->dashboardProductId() || $this->dashboardCourseId()) {
            $rows->whereExists(fn ($exists) => $exists
                ->from('course_lesson')
                ->join('courses', 'courses.id', '=', 'course_lesson.course_id')
                ->whereColumn('course_lesson.lesson_id', 'lesson_user.lesson_id')
                ->when($this->dashboardProductId(), fn ($query, int $productId) => $query->where('courses.product_id', $productId))
                ->when($this->dashboardCourseId(), fn ($query, int $courseId) => $query->where('courses.id', $courseId)));
        }

        return $rows;
    }

    protected function dashboardStart(): CarbonImmutable
    {
        return $this->filterDate('start_date', now()->subDays(29)->startOfDay(), false);
    }

    protected function dashboardEnd(): CarbonImmutable
    {
        return $this->filterDate('end_date', now()->endOfDay(), true);
    }

    protected function dashboardCompanyId(): ?int
    {
        return $this->filterId('company_id');
    }

    protected function dashboardProductId(): ?int
    {
        return $this->filterId('product_id');
    }

    protected function dashboardCourseId(): ?int
    {
        return $this->filterId('course_id');
    }

    private function filterDate(string $key, \DateTimeInterface $fallback, bool $endOfDay): CarbonImmutable
    {
        try {
            $date = filled($this->pageFilters[$key] ?? null)
                ? CarbonImmutable::parse($this->pageFilters[$key])
                : CarbonImmutable::instance($fallback);
        } catch (\Throwable) {
            $date = CarbonImmutable::instance($fallback);
        }

        return $endOfDay ? $date->endOfDay() : $date->startOfDay();
    }

    private function filterId(string $key): ?int
    {
        $value = (int) ($this->pageFilters[$key] ?? 0);

        return $value > 0 ? $value : null;
    }
}
