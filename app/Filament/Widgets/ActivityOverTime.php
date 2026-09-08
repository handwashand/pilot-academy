<?php

namespace App\Filament\Widgets;

use App\Filament\Widgets\Concerns\ReportsOnLearners;
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

    protected static ?int $sort = 5;

    protected ?string $heading = 'Student activity';

    protected ?string $description = 'Lessons finished and sign-ins per day, students only.';

    protected int|string|array $columnSpan = 'full';

    private const DAYS = 30;

    protected function getType(): string
    {
        return 'line';
    }

    protected function getData(): array
    {
        $since = now()->subDays(self::DAYS - 1)->startOfDay();

        // Grouped in PHP rather than SQL: date functions differ between SQLite
        // and Postgres, and a month of events is a small enough set to fold
        // here. Keeps the query portable, which the rest of the app relies on.
        $events = $this->scopeToLearners(
            ActivityEvent::query()
                ->whereIn('type', [ActivityEvent::TYPE_LESSON_COMPLETED, ActivityEvent::TYPE_LOGIN])
                ->where('created_at', '>=', $since),
        )->get(['type', 'created_at']);

        $completions = [];
        $logins = [];
        $labels = [];

        for ($day = 0; $day < self::DAYS; $day++) {
            $date = $since->copy()->addDays($day);
            $key = $date->toDateString();

            $onThisDay = $events->filter(fn (ActivityEvent $event): bool => $event->created_at->toDateString() === $key);

            $labels[] = $date->format('j M');
            $completions[] = $onThisDay->where('type', ActivityEvent::TYPE_LESSON_COMPLETED)->count();
            $logins[] = $onThisDay->where('type', ActivityEvent::TYPE_LOGIN)->count();
        }

        return [
            'datasets' => [
                [
                    'label' => 'Lessons finished',
                    'data' => $completions,
                    'borderColor' => '#16a34a',
                    'backgroundColor' => 'rgba(22, 163, 74, 0.1)',
                    'fill' => true,
                    'tension' => 0.3,
                ],
                [
                    'label' => 'Sign-ins',
                    'data' => $logins,
                    'borderColor' => '#2563eb',
                    'backgroundColor' => 'rgba(37, 99, 235, 0.1)',
                    'fill' => true,
                    'tension' => 0.3,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getOptions(): array
    {
        return [
            'scales' => [
                // Counts are whole lessons and whole sign-ins; half a step on
                // the axis would be meaningless.
                'y' => ['beginAtZero' => true, 'ticks' => ['precision' => 0]],
            ],
        ];
    }
}
