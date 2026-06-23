<?php

namespace App\Filament\Widgets;

use App\Models\Lesson;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;

class StudentProgressOverview extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        $students = User::where('is_admin', false)->count();
        $active = User::where('is_admin', false)
            ->whereHas('completedLessons')
            ->count();
        $completions = DB::table('lesson_user')->count();
        $publishedLessons = Lesson::where('is_published', true)->count();

        $engagement = $students > 0 ? round($active / $students * 100) : 0;

        return [
            Stat::make('Students', $students)
                ->description('Partner accounts')
                ->color('primary'),

            Stat::make('Active students', $active)
                ->description($engagement.'% started at least one lesson')
                ->color('success'),

            Stat::make('Lesson completions', $completions)
                ->description('Across all students')
                ->color('info'),

            Stat::make('Published lessons', $publishedLessons)
                ->description('Available to learn')
                ->color('gray'),
        ];
    }
}
