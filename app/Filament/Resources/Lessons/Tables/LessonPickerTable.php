<?php

namespace App\Filament\Resources\Lessons\Tables;

use App\Models\Lesson;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/**
 * The table you tick lessons from when putting them in a course: the course
 * form's Lessons box and the Lessons tab's Browse lessons. Same search and
 * filters as the Lessons list.
 *
 * Argument exclude_course leaves out the lessons already in that course.
 */
class LessonPickerTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->query(function () use ($table): Builder {
                $user = auth()->user();
                $exclude = $table->getArguments()['exclude_course'] ?? null;

                return Lesson::query()
                    ->with(['contentTranslations', 'courses.contentTranslations', 'creator'])
                    // Same scoping as the Lessons list: a creator picks only
                    // from lessons they may edit.
                    ->when($user?->isCreator(), fn (Builder $query): Builder => $query->manageableBy($user))
                    ->when($exclude, fn (Builder $query): Builder => $query->whereDoesntHave('courses', fn (Builder $courses): Builder => $courses->whereKey($exclude)));
            })
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('title')
                    ->label(__t('admin_common.title'))
                    ->formatStateUsing(fn (Lesson $record): string => (string) $record->translated('title'))
                    ->description(fn (Lesson $record): ?string => filled($record->tags) ? implode(' · ', $record->tags) : null)
                    ->searchable(query: fn (Builder $query, string $search): Builder => $query->matching($search))
                    ->sortable()
                    ->weight('bold')
                    ->wrap(),

                TextColumn::make('status')
                    ->label(__t('admin_common.status'))
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => Lesson::statusLabels()[$state] ?? $state)
                    ->color(fn (string $state): string => match ($state) {
                        Lesson::STATUS_PUBLISHED => 'success',
                        Lesson::STATUS_ARCHIVED => 'gray',
                        default => 'warning',
                    }),

                TextColumn::make('courses.title')
                    ->label(__t('admin_common.courses'))
                    ->state(fn (Lesson $record): array => $record->courses->map(fn ($course): string => (string) $course->translated('title'))->all())
                    ->badge()
                    ->color('gray')
                    ->placeholder(__t('admin_lessons.table.no_course')),

                TextColumn::make('duration_minutes')
                    ->label(__t('admin_courses.lessons_tab.length'))
                    ->formatStateUsing(fn (?int $state): string => Lesson::formatMinutes($state) ?? '—'),

                LessonsTable::creatorColumn()->toggleable(isToggledHiddenByDefault: false),
            ])
            ->filters([
                LessonsTable::statusFilter(),
                LessonsTable::tagFilter(),
                LessonsTable::courseFilter(),
                LessonsTable::creatorFilter(),
                LessonsTable::noCourseFilter(),
                LessonsTable::addedFilter(),
            ]);
    }
}
