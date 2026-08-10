<?php

namespace App\Filament\Widgets;

use App\Models\Lesson;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;

class StudentProgressOverview extends StatsOverviewWidget
{
    /** Learner data — creators have no business seeing it. */
    public static function canView(): bool
    {
        return (bool) auth()->user()?->isAdmin();
    }

    protected function getStats(): array
    {
        // Reports are about learners: admins and creators never count.
        $students = User::learners()->count();
        $active = User::learners()
            ->whereHas('completedLessons')
            ->count();
        // Staff complete lessons too (previewing, testing) — those completions
        // are not learner progress and must not inflate the total.
        $completions = DB::table('lesson_user')
            ->join('users', 'users.id', '=', 'lesson_user.user_id')
            ->where('users.role', User::ROLE_LEARNER)
            ->count();
        $publishedLessons = Lesson::published()->count();

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
