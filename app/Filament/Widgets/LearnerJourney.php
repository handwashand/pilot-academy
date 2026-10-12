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

    /**
     * A funnel that cannot contradict itself.
     *
     * Opens are known only from activity events, which start when the academy
     * began recording them; finishing is also written in lesson_user and
     * certificates, which go back further. Counting each stage from its own
     * source alone produced nonsense on real data — no opens, no completions,
     * eleven certificates — because a certificate earned before tracking has
     * no matching events.
     *
     * So each stage also counts the learners the later stages prove were
     * there: earning a certificate means finishing the course, which means
     * finishing its lessons, which means opening them. Every bar is therefore
     * at least as tall as the one after it, and none of them overstates what
     * happened — the evidence is simply read forwards.
     */
    protected function getData(): array
    {
        $eventLearners = $this->scopeToLearners(
            $this->filterActivity(ActivityEvent::query())->whereIn('type', [
                ActivityEvent::TYPE_COURSE_OPENED,
                ActivityEvent::TYPE_LESSON_OPENED,
                ActivityEvent::TYPE_LESSON_COMPLETED,
                ActivityEvent::TYPE_COURSE_COMPLETED,
            ]),
        )
            ->select(['type', 'user_id'])
            ->get()
            ->groupBy('type')
            ->map(fn ($rows) => $rows->pluck('user_id')->unique()->all());

        $certified = $this->scopeToLearners(
            Certificate::query()
                ->whereNull('revoked_at')
                ->whereBetween('issued_at', [$this->dashboardStart(), $this->dashboardEnd()])
                ->when($this->dashboardCompanyId(), fn (Builder $query, int $companyId): Builder => $query
                    ->whereHas('user', fn (Builder $user): Builder => $user->where('company_id', $companyId)))
                ->when($this->dashboardProductId(), fn (Builder $query, int $productId): Builder => $query
                    ->whereHas('course', fn (Builder $course): Builder => $course->where('product_id', $productId)))
                ->when($this->dashboardCourseId(), fn (Builder $query, int $courseId): Builder => $query
                    ->where('course_id', $courseId)),
        )->pluck('user_id')->unique()->all();

        // Read backwards from the strongest evidence, so each stage carries
        // everyone the stages after it imply.
        $courseFinished = array_unique([...$eventLearners[ActivityEvent::TYPE_COURSE_COMPLETED] ?? [], ...$certified]);
        $lessonFinished = array_unique([...$eventLearners[ActivityEvent::TYPE_LESSON_COMPLETED] ?? [], ...$this->finishedALesson(), ...$courseFinished]);
        $lessonOpened = array_unique([...$eventLearners[ActivityEvent::TYPE_LESSON_OPENED] ?? [], ...$lessonFinished]);
        $courseOpened = array_unique([...$eventLearners[ActivityEvent::TYPE_COURSE_OPENED] ?? [], ...$lessonOpened]);

        return $this->chart([
            count($courseOpened),
            count($lessonOpened),
            count($lessonFinished),
            count($courseFinished),
            count($certified),
        ]);
    }

    /** Learners with a lesson finished in the period, from the pivot itself. */
    private function finishedALesson(): array
    {
        return $this->scopeToLearners($this->completedLessonRows(), 'lesson_user.user_id')
            ->distinct()
            ->pluck('lesson_user.user_id')
            ->all();
    }

    /** @param  array<int, int>  $data */
    private function chart(array $data): array
    {
        return [
            'datasets' => [[
                'label' => __t('admin_widgets.journey.learners'),
                'data' => $data,
                // One colour, darkest first: the bars already fall from left to
                // right, and five hues made five unrelated things out of one
                // journey. Blue and amber are the dashboard's only two colours.
                'backgroundColor' => ['#1e3a8a', '#1d4ed8', '#2563eb', '#60a5fa', '#93c5fd'],
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
