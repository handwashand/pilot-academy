<?php

namespace App\Filament\Widgets;

use App\Filament\Widgets\Concerns\ReportsOnLearners;
use App\Filament\Widgets\Concerns\UsesDashboardFilters;
use App\Models\ActivityEvent;
use Filament\Widgets\ChartWidget;

/**
 * Is the academy being used more or less than it was?
 *
 * Activity has been recorded since the event log shipped and never shown in
 * aggregate — the panel could only tell you totals, never a direction.
 */
class ActivityOverTime extends ChartWidget
{
    use ReportsOnLearners;
    use UsesDashboardFilters;

    protected static ?int $sort = 6;

    public function getHeading(): ?string
    {
        return __t('admin_widgets.activity.heading');
    }

    public function getDescription(): ?string
    {
        return __t('admin_widgets.activity.description');
    }

    protected int|string|array $columnSpan = 'full';

    protected ?string $maxHeight = '320px';

    protected function getType(): string
    {
        return 'line';
    }

    protected function getData(): array
    {
        $since = $this->dashboardStart();
        $until = $this->dashboardEnd();

        if ($until->lt($since)) {
            [$since, $until] = [$until->startOfDay(), $since->endOfDay()];
        }

        $days = min((int) $since->startOfDay()->diffInDays($until->startOfDay()) + 1, 366);

        // Grouped in PHP rather than SQL: date functions differ between SQLite
        // and Postgres, and a month of events is a small enough set to fold
        // here. Keeps the query portable, which the rest of the app relies on.
        $events = $this->scopeToLearners(
            $this->filterActivity(ActivityEvent::query())
                ->whereIn('type', ActivityEvent::LEARNER_ACTIVITY_TYPES),
        )->get(['user_id', 'type', 'created_at']);

        $daily = [];

        foreach ($events as $event) {
            $key = $event->created_at->toDateString();
            $daily[$key] ??= ['learners' => [], 'completions' => 0];
            $daily[$key]['learners'][$event->user_id] = true;

            if ($event->type === ActivityEvent::TYPE_LESSON_COMPLETED) {
                $daily[$key]['completions']++;
            }
        }

        $activeLearners = [];
        $completions = [];
        $labels = [];

        for ($day = 0; $day < $days; $day++) {
            $date = $since->copy()->addDays($day);
            $key = $date->toDateString();

            $labels[] = $date->locale(app()->getLocale())->translatedFormat('j M');
            $activeLearners[] = count($daily[$key]['learners'] ?? []);
            $completions[] = $daily[$key]['completions'] ?? 0;
        }

        return [
            'datasets' => [
                [
                    'label' => __t('admin_widgets.activity.active_learners'),
                    'data' => $activeLearners,
                    'borderColor' => '#2563eb',
                    'backgroundColor' => '#2563eb',
                    'borderWidth' => 2,
                    'fill' => false,
                    'pointRadius' => 0,
                    'pointHoverRadius' => 4,
                    'tension' => 0.25,
                    'order' => 0,
                ],
                [
                    'type' => 'bar',
                    'label' => __t('admin_widgets.activity.lessons_finished'),
                    'data' => $completions,
                    // Amber, the dashboard's one accent beside blue — see the
                    // palette note in StudentProgressOverview.
                    'borderColor' => '#b45309',
                    'backgroundColor' => 'rgba(217, 119, 6, 0.55)',
                    'borderWidth' => 1,
                    'borderRadius' => 3,
                    'maxBarThickness' => 20,
                    'order' => 1,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getOptions(): array
    {
        return [
            'interaction' => [
                'mode' => 'index',
                'intersect' => false,
            ],
            'plugins' => [
                'legend' => [
                    'position' => 'bottom',
                    'labels' => ['usePointStyle' => true, 'boxWidth' => 8],
                ],
                'tooltip' => ['mode' => 'index', 'intersect' => false],
            ],
            'scales' => [
                'x' => [
                    'grid' => ['display' => false],
                    'ticks' => ['autoSkip' => true, 'maxTicksLimit' => 10],
                ],
                // Counts are whole learners and whole lessons; half a step on
                // the axis would be meaningless.
                'y' => ['beginAtZero' => true, 'ticks' => ['precision' => 0]],
            ],
        ];
    }
}
