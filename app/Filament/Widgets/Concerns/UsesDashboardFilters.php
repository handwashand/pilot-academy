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
            // Deleted lessons and courses are only hidden, so these raw tables
            // still hold them; the models would leave them out.
            ->join('lessons', 'lessons.id', '=', 'lesson_user.lesson_id')
            ->whereNull('lessons.deleted_at')
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
                ->whereNull('courses.deleted_at')
                ->when($this->dashboardProductId(), fn ($query, int $productId) => $query->where('courses.product_id', $productId))
                ->when($this->dashboardCourseId(), fn ($query, int $courseId) => $query->where('courses.id', $courseId)));
        }

        return $rows;
    }

    /**
     * A small trend for a stat card: counts per step across the filtered
     * period, at most 30 steps (a long period is grouped into longer steps).
     * Each item is [date, key]; a key counted once per step, so passing a user
     * id gives distinct people. Folded in PHP to stay portable across SQLite
     * and PostgreSQL, like ActivityOverTime.
     *
     * @param  iterable<array{0: \DateTimeInterface|string|null, 1: int|string}>  $items
     * @return list<int>
     */
    protected function trendOf(iterable $items): array
    {
        $start = $this->dashboardStart()->startOfDay();
        $days = max(1, (int) $start->diffInDays($this->dashboardEnd()->startOfDay()) + 1);
        $steps = min($days, 30);
        $perStep = $days / $steps;

        $buckets = array_fill(0, $steps, []);

        foreach ($items as [$date, $key]) {
            if ($date === null) {
                continue;
            }

            $day = (int) $start->diffInDays(CarbonImmutable::parse($date)->startOfDay(), false);

            if ($day < 0 || $day >= $days) {
                continue;
            }

            $buckets[min($steps - 1, (int) floor($day / $perStep))][$key] = true;
        }

        return array_map('count', $buckets);
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
