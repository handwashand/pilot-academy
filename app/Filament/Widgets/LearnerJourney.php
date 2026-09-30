<?php

namespace App\Filament\Widgets;

use App\Filament\Widgets\Concerns\ReportsOnLearners;
use App\Filament\Widgets\Concerns\UsesDashboardFilters;
use App\Models\ActivityEvent;
use App\Models\Certificate;
use Filament\Widgets\ChartWidget;
use Illuminate\Database\Eloquent\Builder;

class LearnerJourney extends ChartWidget
{
    use ReportsOnLearners;
    use UsesDashboardFilters;

    protected static ?int $sort = 5;

    protected int|string|array $columnSpan = 'full';

    protected ?string $maxHeight = '300px';

    public function getHeading(): ?string
    {
        return __t('admin_widgets.journey.heading');
    }

    public function getDescription(): ?string
    {
        return __t('admin_widgets.journey.description');
    }

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getData(): array
    {
        $types = [
            ActivityEvent::TYPE_COURSE_OPENED,
            ActivityEvent::TYPE_LESSON_OPENED,
            ActivityEvent::TYPE_LESSON_COMPLETED,
            ActivityEvent::TYPE_COURSE_COMPLETED,
        ];

        $counts = $this->scopeToLearners(
            $this->filterActivity(ActivityEvent::query())->whereIn('type', $types),
        )
            ->selectRaw('type, count(distinct user_id) as learners')
            ->groupBy('type')
            ->pluck('learners', 'type');

        $certificates = $this->scopeToLearners(
            Certificate::query()
                ->whereNull('revoked_at')
                ->whereBetween('issued_at', [$this->dashboardStart(), $this->dashboardEnd()])
                ->when($this->dashboardCompanyId(), fn (Builder $query, int $companyId): Builder => $query
                    ->whereHas('user', fn (Builder $user): Builder => $user->where('company_id', $companyId)))
                ->when($this->dashboardProductId(), fn (Builder $query, int $productId): Builder => $query
                    ->whereHas('course', fn (Builder $course): Builder => $course->where('product_id', $productId)))
                ->when($this->dashboardCourseId(), fn (Builder $query, int $courseId): Builder => $query
                    ->where('course_id', $courseId)),
        )->distinct('user_id')->count('user_id');

        return [
            'datasets' => [[
                'label' => __t('admin_widgets.journey.learners'),
                'data' => [
                    (int) ($counts[ActivityEvent::TYPE_COURSE_OPENED] ?? 0),
                    (int) ($counts[ActivityEvent::TYPE_LESSON_OPENED] ?? 0),
                    (int) ($counts[ActivityEvent::TYPE_LESSON_COMPLETED] ?? 0),
                    (int) ($counts[ActivityEvent::TYPE_COURSE_COMPLETED] ?? 0),
                    $certificates,
                ],
                'backgroundColor' => ['#2563eb', '#0891b2', '#16a34a', '#ca8a04', '#7c3aed'],
                'borderRadius' => 3,
            ]],
            'labels' => [
                __t('admin_widgets.journey.course_opened'),
                __t('admin_widgets.journey.lesson_opened'),
                __t('admin_widgets.journey.lesson_completed'),
                __t('admin_widgets.journey.course_completed'),
                __t('admin_widgets.journey.certified'),
            ],
        ];
    }

    protected function getOptions(): array
    {
        return [
            'indexAxis' => 'y',
            'scales' => ['x' => ['beginAtZero' => true, 'ticks' => ['precision' => 0]]],
            'plugins' => ['legend' => ['display' => false]],
        ];
    }
}
