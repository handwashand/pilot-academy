<?php

namespace App\Filament\Widgets;

use App\Filament\Widgets\Concerns\ReportsOnLearners;
use App\Filament\Widgets\Concerns\UsesDashboardFilters;
use App\Models\ActivityEvent;
use App\Models\Course;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Str;

/**
 * Which courses students actually open.
 *
 * Certificates only show what people finished; this shows what drew them in,
 * including the courses everyone starts and nobody completes.
 */
class MostOpenedCourses extends ChartWidget
{
    use ReportsOnLearners;
    use UsesDashboardFilters;

    protected static ?int $sort = 7;

    /** Full width like every other chart; half left dead space beside it. */
    protected int|string|array $columnSpan = 'full';

    public function getHeading(): ?string
    {
        return __t('admin_widgets.opened.heading');
    }

    public function getDescription(): ?string
    {
        return __t('admin_widgets.opened.description');
    }

    private const TOP = 8;

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getData(): array
    {
        $events = $this->scopeToLearners(
            $this->filterActivity(ActivityEvent::query())
                ->where('type', ActivityEvent::TYPE_COURSE_OPENED)
                ->where(fn ($query) => $query->whereNotNull('subject_id')->orWhereNotNull('label')),
        )
            ->get(['subject_id', 'label']);

        $courseTitles = Course::query()
            ->whereIn('id', $events->pluck('subject_id')->filter()->unique())
            ->pluck('title', 'id');

        $opens = $events
            ->countBy(fn (ActivityEvent $event): string => $event->subject_id
                ? 'course:'.$event->subject_id
                : 'legacy:'.$event->label)
            ->sortDesc()
            ->take(self::TOP);

        $labels = $opens->keys()->map(function (string $key) use ($courseTitles): string {
            if (str_starts_with($key, 'course:')) {
                return $courseTitles[(int) Str::after($key, 'course:')] ?? __t('admin_widgets.opened.removed_course');
            }

            return Str::after($key, 'legacy:');
        });

        return [
            'datasets' => [[
                'label' => __t('admin_widgets.opened.times_opened'),
                'data' => $opens->values()->all(),
                'backgroundColor' => '#2563eb',
            ]],
            'labels' => $labels->all(),
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
