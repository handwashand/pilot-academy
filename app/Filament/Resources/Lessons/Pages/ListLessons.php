<?php

namespace App\Filament\Resources\Lessons\Pages;

use App\Filament\Resources\Courses\CourseResource;
use App\Filament\Resources\Lessons\LessonResource;
use App\Models\Lesson;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class ListLessons extends ListRecords
{
    protected static string $resource = LessonResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }

    /**
     * One click to the lessons that usually matter: all, drafts, lessons in no
     * course, then one tab per course in the course order. Counts go through
     * LessonResource::getEloquentQuery() and the courses through
     * CourseResource::getEloquentQuery(), so a creator sees and counts only
     * their own. Course counts come from one grouped query, not one per tab.
     */
    public function getTabs(): array
    {
        $visible = fn (): Builder => LessonResource::getEloquentQuery();

        // The count is named: PostgreSQL calls an unnamed count(*) "count",
        // SQLite "count(*)", so plucking the raw expression broke the whole
        // page in production while every test passed.
        $perCourse = DB::table('course_lesson')
            ->whereIn('lesson_id', $visible()->select('lessons.id'))
            ->groupBy('course_id')
            ->select('course_id', DB::raw('count(*) as lessons_count'))
            ->pluck('lessons_count', 'course_id');

        $tabs = [
            'all' => Tab::make(__t('admin_lessons.tabs.all'))
                ->badge(fn (): int => $visible()->count()),

            'drafts' => Tab::make(__t('admin_lessons.tabs.drafts'))
                ->badge(fn (): int => $visible()->where('status', Lesson::STATUS_DRAFT)->count())
                ->modifyQueryUsing(fn (Builder $query): Builder => $query->where('status', Lesson::STATUS_DRAFT)),

            'no_course' => Tab::make(__t('admin_lessons.table.no_course'))
                ->badge(fn (): int => $visible()->whereDoesntHave('courses')->count())
                ->modifyQueryUsing(fn (Builder $query): Builder => $query->whereDoesntHave('courses')),
        ];

        $courses = CourseResource::getEloquentQuery()
            ->with('contentTranslations')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get(['courses.id', 'courses.title', 'courses.language']);

        foreach ($courses as $course) {
            $tabs['course-'.$course->id] = Tab::make($course->translated('title'))
                ->badge((int) ($perCourse[$course->id] ?? 0))
                ->modifyQueryUsing(fn (Builder $query): Builder => $query->whereHas('courses', fn (Builder $q): Builder => $q->whereKey($course->id)));
        }

        return $tabs;
    }
}
