<?php

namespace App\Filament\Widgets;

use App\Filament\Widgets\Concerns\ReportsOnLearners;
use App\Models\ActivityEvent;
use Filament\Widgets\ChartWidget;

/**
 * Which courses students actually open.
 *
 * Certificates only show what people finished; this shows what drew them in,
 * including the courses everyone starts and nobody completes.
 */
class MostOpenedCourses extends ChartWidget
{
    use ReportsOnLearners;

    protected static ?int $sort = 6;

    protected ?string $heading = 'Most opened courses';

    protected ?string $description = 'Times students opened each course in the last 90 days.';

    private const DAYS = 90;

    private const TOP = 8;

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getData(): array
    {
        // The event stores the course title as a label rather than an id, so
        // this counts by title. A renamed course starts a new bar, which is
        // honest: the old bar is what students actually opened at the time.
        $opens = $this->scopeToLearners(
            ActivityEvent::query()
                ->where('type', ActivityEvent::TYPE_COURSE_OPENED)
                ->whereNotNull('label')
                ->where('created_at', '>=', now()->subDays(self::DAYS)),
        )
            ->get(['label'])
            ->countBy('label')
            ->sortDesc()
            ->take(self::TOP);

        return [
            'datasets' => [[
                'label' => 'Times opened',
                'data' => $opens->values()->all(),
                'backgroundColor' => '#7c3aed',
            ]],
            'labels' => $opens->keys()->all(),
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
