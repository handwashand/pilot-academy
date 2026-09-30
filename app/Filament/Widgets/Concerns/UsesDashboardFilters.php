<?php

namespace App\Filament\Widgets\Concerns;

use Carbon\CarbonImmutable;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Illuminate\Database\Eloquent\Builder;

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
