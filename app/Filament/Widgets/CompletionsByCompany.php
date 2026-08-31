<?php

namespace App\Filament\Widgets;

use App\Filament\Widgets\Concerns\ReportsOnLearners;
use App\Models\Company;
use App\Models\Lesson;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

/**
 * How far each partner company has got through the published material.
 *
 * The academy has no enrolments or deadlines — students take what they like —
 * so "progress" here is completed lessons against everything on offer, which is
 * the only honest denominator available.
 */
class CompletionsByCompany extends ChartWidget
{
    use ReportsOnLearners;

    protected static ?int $sort = 2;

    protected ?string $heading = 'Progress by partner company';

    protected ?string $description = 'Share of all published lessons completed by each company\'s students.';

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getData(): array
    {
        $companies = Company::query()->orderBy('name')->get(['id', 'name']);

        if ($companies->isEmpty()) {
            return ['datasets' => [], 'labels' => []];
        }

        $available = Lesson::published()->count();

        // One grouped aggregate for every company at once. A query per bar
        // would scale with the partner list, and this runs on every load.
        $completed = $this->scopeToLearners(
            DB::table('lesson_user')
                ->join('users', 'users.id', '=', 'lesson_user.user_id')
                ->whereIn('lesson_user.lesson_id', Lesson::published()->select('lessons.id'))
                ->whereNotNull('users.company_id')
                ->groupBy('users.company_id')
                ->selectRaw('users.company_id, count(*) as total'),
            'lesson_user.user_id',
        )->pluck('total', 'company_id');

        $learners = $this->learners()
            ->whereNotNull('company_id')
            ->groupBy('company_id')
            ->selectRaw('company_id, count(*) as total')
            ->pluck('total', 'company_id');

        $percentages = $companies->map(function (Company $company) use ($completed, $learners, $available): int {
            $people = (int) ($learners[$company->id] ?? 0);
            $possible = $people * $available;

            // A company with no students, or an academy with no published
            // lessons, reads as 0 rather than disappearing or dividing by zero.
            if ($possible === 0) {
                return 0;
            }

            return (int) round(((int) ($completed[$company->id] ?? 0)) / $possible * 100);
        });

        return [
            'datasets' => [[
                'label' => '% of published lessons completed',
                'data' => $percentages->values()->all(),
                'backgroundColor' => '#2563eb',
            ]],
            'labels' => $companies->pluck('name')->all(),
        ];
    }

    protected function getOptions(): array
    {
        return [
            'scales' => [
                'y' => ['beginAtZero' => true, 'max' => 100, 'ticks' => ['stepSize' => 25]],
            ],
            'plugins' => ['legend' => ['display' => false]],
        ];
    }
}
