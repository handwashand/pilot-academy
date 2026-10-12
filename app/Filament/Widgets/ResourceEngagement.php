<?php

namespace App\Filament\Widgets;

use App\Filament\Widgets\Concerns\ReportsOnLearners;
use App\Filament\Widgets\Concerns\UsesDashboardFilters;
use App\Models\ActivityEvent;
use Filament\Widgets\ChartWidget;

class ResourceEngagement extends ChartWidget
{
    use ReportsOnLearners;
    use UsesDashboardFilters;

    protected static ?int $sort = 8;

    protected int|string|array $columnSpan = 'full';

    protected ?string $maxHeight = '280px';

    public function getHeading(): ?string
    {
        return __t('admin_widgets.resources.heading');
    }

    public function getDescription(): ?string
    {
        return __t('admin_widgets.resources.description');
    }

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getData(): array
    {
        $types = [
            ActivityEvent::TYPE_CASE_STUDY_OPENED,
            ActivityEvent::TYPE_TUTORIAL_OPENED,
            ActivityEvent::TYPE_WEBINAR_OPENED,
            ActivityEvent::TYPE_WEBINAR_JOINED,
            ActivityEvent::TYPE_WEBINAR_RECORDING_OPENED,
        ];

        $counts = $this->scopeToLearners(
            $this->filterActivity(ActivityEvent::query())->whereIn('type', $types),
        )
            ->selectRaw('type, count(*) as total')
            ->groupBy('type')
            ->pluck('total', 'type');

        return [
            'datasets' => [[
                'label' => __t('admin_widgets.resources.opens'),
                'data' => collect($types)->map(fn (string $type): int => (int) ($counts[$type] ?? 0))->all(),
                // Shades of the one blue, not five hues — the bars are five of
                // the same thing, and the labels already say which is which.
                'backgroundColor' => ['#1e3a8a', '#1d4ed8', '#2563eb', '#60a5fa', '#93c5fd'],
                'borderRadius' => 3,
            ]],
            'labels' => [
                __t('admin_widgets.resources.case_studies'),
                __t('admin_widgets.resources.tutorials'),
                __t('admin_widgets.resources.webinars'),
                __t('admin_widgets.resources.joins'),
                __t('admin_widgets.resources.recordings'),
            ],
        ];
    }

    protected function getOptions(): array
    {
        return [
            'scales' => ['y' => ['beginAtZero' => true, 'ticks' => ['precision' => 0]]],
            'plugins' => ['legend' => ['display' => false]],
        ];
    }
}
