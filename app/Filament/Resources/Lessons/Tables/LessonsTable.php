<?php

namespace App\Filament\Resources\Lessons\Tables;

use App\Actions\FindContentProblems;
use App\Models\Course;
use App\Models\CourseLesson;
use App\Models\Lesson;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class LessonsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('sort_order')
            ->columns([
                ImageColumn::make('cover')
                    ->label(__t('admin_lessons.table.cover'))
                    ->disk('public')
                    ->height(36)
                    ->state(fn ($record) => $record->media_item_id ? $record->mediaItem?->path : $record->image_path),

                TextColumn::make('courses.title')
                    ->label(__t('admin_common.courses'))
                    ->badge(),

                TextColumn::make('sort_order')
                    ->label('#')
                    ->sortable(),

                TextColumn::make('title')
                    ->label(__t('admin_common.title'))
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                // Flagged where the work happens, not only on Content health.
                TextColumn::make('attention')
                    ->label(__t('admin_common.attention'))
                    ->state(function (Lesson $record): ?string {
                        $count = FindContentProblems::forCurrentUser()->where('lesson_id', $record->id)->count();

                        return $count > 0 ? __tc('admin_common.problems', $count) : null;
                    })
                    ->badge()
                    ->color('danger')
                    ->icon('heroicon-m-exclamation-triangle')
                    ->tooltip(fn (Lesson $record): ?string => FindContentProblems::forCurrentUser()
                        ->where('lesson_id', $record->id)
                        ->pluck('what')
                        ->unique()
                        ->implode(' · ') ?: null),

                TextColumn::make('questions_count')
                    ->label(__t('admin_lessons.table.quiz_questions'))
                    ->counts('questions')
                    ->badge(),

                IconColumn::make('has_video')
                    ->label(__t('admin_lessons.table.video'))
                    ->boolean()
                    ->state(fn ($record) => ! empty($record->videoEntries())),

                TextColumn::make('status')
                    ->label(__t('admin_common.status'))
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => Lesson::statusLabels()[$state] ?? $state)
                    ->color(fn (string $state): string => match ($state) {
                        Lesson::STATUS_PUBLISHED => 'success',
                        Lesson::STATUS_ARCHIVED => 'gray',
                        default => 'warning',
                    })
                    ->sortable(),
            ])
            ->filters([
                Filter::make('needs_attention')
                    ->label(__t('admin_common.needs_attention'))
                    ->query(fn (Builder $query): Builder => $query->whereIn(
                        'id',
                        FindContentProblems::forCurrentUser()->pluck('lesson_id')->filter()->unique()->values()->all(),
                    )),

                SelectFilter::make('status')
                    ->label(__t('admin_common.status'))
                    ->options(Lesson::statusLabels()),

                SelectFilter::make('course')
                    ->label(__t('admin_common.course'))
                    ->relationship('courses', 'title', function ($query) {
                        $user = auth()->user();

                        return $user?->isCreator()
                            ? $query->whereIn('product_id', $user->products()->pluck('products.id'))
                            : $query;
                    })
                    ->searchable()
                    ->preload(),
            ])
            ->recordActions([
                Action::make('publish')
                    ->label(__t('admin_common.publish'))
                    ->icon('heroicon-o-rocket-launch')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading(__t('admin_lessons.table.publish_heading'))
                    // Say what is wrong before anyone presses the button.
                    ->modalDescription(function (Lesson $record): string {
                        $problems = static::problemsIfPublished($record);

                        return $problems->isEmpty()
                            ? __t('admin_lessons.table.publish_ready', ['title' => $record->title])
                            : __t('admin_lessons.table.publish_blocked', ['problems' => FindContentProblems::plainList($problems)]);
                    })
                    ->visible(fn (Lesson $record): bool => $record->status === Lesson::STATUS_DRAFT)
                    ->authorize(fn (Lesson $record): bool => auth()->user()->canManageCourse($record->course))
                    ->action(function (Lesson $record): void {
                        $problems = static::problemsIfPublished($record);

                        if ($problems->isNotEmpty()) {
                            Notification::make()
                                ->title(__t('admin_common.fix_before_publishing'))
                                ->body(FindContentProblems::plainList($problems))
                                ->danger()
                                ->persistent()
                                ->send();

                            return;
                        }

                        $record->publish();

                        Notification::make()->title(__t('admin_lessons.table.published'))->body(__t('admin_common.visible_now'))->success()->send();
                    }),

                Action::make('unpublish')
                    ->label(__t('admin_common.unpublish'))
                    ->icon('heroicon-o-eye-slash')
                    ->color('gray')
                    ->requiresConfirmation()
                    ->modalHeading(__t('admin_lessons.table.unpublish_heading'))
                    ->modalDescription(function (Lesson $record): string {
                        $description = __t('admin_lessons.table.unpublish_description');

                        $emptied = static::liveCoursesItIsLastIn($record);

                        return $emptied->isNotEmpty()
                            ? __tc('admin_lessons.table.only_lesson_in', $emptied->count(), ['courses' => '"'.$emptied->implode('", "').'"']).' '.$description
                            : $description;
                    })
                    ->visible(fn (Lesson $record): bool => $record->isPublished())
                    ->authorize(fn (Lesson $record): bool => auth()->user()->canManageCourse($record->course))
                    ->action(function (Lesson $record): void {
                        $record->unpublish();

                        Notification::make()->title(__t('admin_lessons.table.unpublished'))->body(__t('admin_common.draft_again'))->warning()->send();
                    }),

                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('publish')
                        ->label(__t('admin_common.publish'))
                        ->icon('heroicon-o-rocket-launch')
                        ->color('success')
                        ->requiresConfirmation()
                        ->modalDescription(__t('admin_lessons.table.bulk_publish_description'))
                        ->deselectRecordsAfterCompletion()
                        ->action(function (Collection $records): void {
                            $skipped = [];

                            $ready = $records->filter(function (Lesson $lesson) use (&$skipped): bool {
                                $problems = static::problemsIfPublished($lesson);

                                if ($problems->isNotEmpty()) {
                                    $skipped[] = "{$lesson->title} ({$problems->pluck('what')->implode('; ')})";

                                    return false;
                                }

                                return true;
                            });

                            $ready->each->publish();

                            if ($ready->isNotEmpty()) {
                                Notification::make()->title(__tc('admin_lessons.table.bulk_published', $ready->count()))->success()->send();
                            }

                            if ($skipped !== []) {
                                Notification::make()
                                    ->title(__tc('admin_lessons.table.bulk_skipped', count($skipped)))
                                    ->body(implode('; ', $skipped))
                                    ->danger()
                                    ->persistent()
                                    ->send();
                            }
                        }),

                    BulkAction::make('unpublish')
                        ->label(__t('admin_common.unpublish'))
                        ->icon('heroicon-o-eye-slash')
                        ->color('gray')
                        ->requiresConfirmation()
                        ->modalDescription(__t('admin_lessons.table.bulk_unpublish_description'))
                        ->deselectRecordsAfterCompletion()
                        ->action(function (Collection $records): void {
                            $records->each->unpublish();

                            Notification::make()->title(__tc('admin_lessons.table.bulk_unpublished', $records->count()))->warning()->send();

                            $emptied = Course::query()
                                ->publishedButEmpty()
                                ->whereIn('id', CourseLesson::query()->whereIn('lesson_id', $records->pluck('id'))->pluck('course_id')->unique())
                                ->pluck('title');

                            if ($emptied->isNotEmpty()) {
                                Notification::make()
                                    ->title(__t('admin_lessons.table.empty_course'))
                                    ->body(__t('admin_lessons.table.empty_course_body', ['courses' => $emptied->implode(', ')]))
                                    ->danger()
                                    ->persistent()
                                    ->send();
                            }
                        }),

                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    /**
     * What a student would hit if this lesson went live now. Only a live course
     * matters — in a draft course nobody sees the lesson yet, and it stays
     * editable in peace.
     */
    protected static function problemsIfPublished(Lesson $lesson): Collection
    {
        if (! $lesson->courses()->published()->exists()) {
            return collect();
        }

        $problems = collect();

        if (! $lesson->questions()->exists()) {
            $problems->push(['what' => __t('admin_lessons.table.no_questions'), 'name' => $lesson->title]);
        }

        if ($lesson->questions()->withoutCorrectAnswer()->exists()) {
            $problems->push(['what' => __t('admin_lessons.table.no_correct_answer'), 'name' => $lesson->title]);
        }

        if ($lesson->hasUnplayableYoutubeLink()) {
            $problems->push(['what' => __t('admin_lessons.table.bad_youtube'), 'name' => $lesson->title]);
        }

        return $problems;
    }

    /** Titles of the live courses this lesson is the only published lesson in. */
    protected static function liveCoursesItIsLastIn(Lesson $lesson): Collection
    {
        if (! $lesson->isPublished()) {
            return collect();
        }

        return $lesson->courses()->published()->withCount('publishedLessons')->get()
            ->filter(fn (Course $course): bool => $course->published_lessons_count === 1)
            ->pluck('title');
    }
}
