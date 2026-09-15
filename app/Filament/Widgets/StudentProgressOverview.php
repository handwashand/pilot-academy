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
            Stat::make(__t('admin_widgets.overview.students'), $students)
                ->description(__t('admin_widgets.overview.students_help'))
                ->descriptionIcon('heroicon-m-user-group')
                ->color('primary'),

            Stat::make(__t('admin_widgets.overview.active'), $active)
                ->description(__t('admin_widgets.overview.active_help', ['percent' => $engagement]))
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color($this->band($engagement)),

            Stat::make(__t('admin_widgets.overview.completions'), $completions)
                ->description(__t('admin_widgets.overview.completions_help'))
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('info'),

            Stat::make(__t('admin_widgets.overview.published_courses'), $publishedCourses)
                ->description(__t('admin_widgets.overview.published_courses_help', ['count' => $totalCourses]))
                ->descriptionIcon('heroicon-m-rectangle-stack')
                ->color('success'),

            Stat::make(__t('admin_widgets.overview.published_lessons'), Lesson::published()->count())
                ->description(__t('admin_widgets.overview.published_lessons_help'))
                ->descriptionIcon('heroicon-m-book-open')
                ->color('gray'),

            Stat::make(__t('admin_widgets.overview.certificates'), $certificates)
                // Staff pick up real certificates when previewing a final quiz,
                // so this counts learners only — see ReportsOnLearners.
                ->description($averageScore === null
                    ? __t('admin_widgets.overview.no_passes')
                    : __t('admin_widgets.overview.average_score', ['score' => round((float) $averageScore)]))
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
