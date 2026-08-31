<?php

namespace App\Filament\Widgets;

use App\Filament\Widgets\Concerns\ReportsOnLearners;
use App\Models\Certificate;
use App\Models\Course;
use App\Models\Lesson;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;

class StudentProgressOverview extends StatsOverviewWidget
{
    use ReportsOnLearners;

    protected static ?int $sort = 1;

    protected int|string|array $columnSpan = 'full';

    protected function getColumns(): int
    {
        return 3;
    }

    protected function getStats(): array
    {
        $students = $this->learners()->count();
        $active = $this->learners()->whereHas('completedLessons')->count();
        $engagement = $students > 0 ? (int) round($active / $students * 100) : 0;

        $completions = $this->scopeToLearners(DB::table('lesson_user'))->count();

        $publishedCourses = Course::published()->count();
        $totalCourses = Course::count();

        $certificates = $this->scopeToLearners(
            Certificate::query()->whereNull('revoked_at'),
        )->count();

        $averageScore = $this->scopeToLearners(
            Certificate::query()->whereNull('revoked_at'),
        )->avg('score_percent');

        return [
            Stat::make('Students', $students)
                ->description('Partner accounts')
                ->descriptionIcon('heroicon-m-user-group')
                ->color('primary'),

            Stat::make('Active students', $active)
                ->description($engagement.'% started at least one lesson')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color($this->band($engagement)),

            Stat::make('Lesson completions', $completions)
                ->description('Across all students')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('info'),

            Stat::make('Published courses', $publishedCourses)
                ->description($totalCourses.' in total, drafts included')
                ->descriptionIcon('heroicon-m-rectangle-stack')
                ->color('success'),

            Stat::make('Published lessons', Lesson::published()->count())
                ->description('Available to learn')
                ->descriptionIcon('heroicon-m-book-open')
                ->color('gray'),

            Stat::make('Certificates issued', $certificates)
                // Staff pick up real certificates when previewing a final quiz,
                // so this counts learners only — see ReportsOnLearners.
                ->description($averageScore === null
                    ? 'No passes yet'
                    : 'Average score '.round((float) $averageScore).'%')
                ->descriptionIcon('heroicon-m-academic-cap')
                ->color($certificates > 0 ? 'success' : 'gray'),
        ];
    }

    /** Traffic-light banding, so a number is readable without doing the maths. */
    private function band(int $percent): string
    {
        return match (true) {
            $percent >= 66 => 'success',
            $percent >= 33 => 'warning',
            default => 'danger',
        };
    }
}
