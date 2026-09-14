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
use Illuminate\Support\Str;

class LessonsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('sort_order')
            ->columns([
                ImageColumn::make('cover')
                    ->label('Cover')
                    ->disk('public')
                    ->height(36)
                    ->state(fn ($record) => $record->media_item_id ? $record->mediaItem?->path : $record->image_path),

                TextColumn::make('courses.title')
                    ->label('Courses')
                    ->badge(),

                TextColumn::make('sort_order')
                    ->label('#')
                    ->sortable(),

                TextColumn::make('title')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                // Flagged where the work happens, not only on Content health.
                TextColumn::make('attention')
                    ->label('Attention')
                    ->state(function (Lesson $record): ?string {
                        $count = FindContentProblems::forCurrentUser()->where('lesson_id', $record->id)->count();

                        return $count > 0 ? $count.' '.Str::plural('problem', $count) : null;
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
                    ->label('Quiz Qs')
                    ->counts('questions')
                    ->badge(),

                IconColumn::make('has_video')
                    ->label('Video')
                    ->boolean()
                    ->state(fn ($record) => ! empty($record->videoEntries())),

                TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => Lesson::STATUS_LABELS[$state] ?? $state)
                    ->color(fn (string $state): string => match ($state) {
                        Lesson::STATUS_PUBLISHED => 'success',
                        Lesson::STATUS_ARCHIVED => 'gray',
                        default => 'warning',
                    })
                    ->sortable(),
            ])
            ->filters([
                Filter::make('needs_attention')
                    ->label('Needs attention')
                    ->query(fn (Builder $query): Builder => $query->whereIn(
                        'id',
                        FindContentProblems::forCurrentUser()->pluck('lesson_id')->filter()->unique()->values()->all(),
                    )),

                SelectFilter::make('status')
                    ->options(Lesson::STATUS_LABELS),

                SelectFilter::make('course')
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
                    ->label('Publish')
                    ->icon('heroicon-o-rocket-launch')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Publish lesson')
                    // Say what is wrong before anyone presses the button.
                    ->modalDescription(function (Lesson $record): string {
                        $problems = static::problemsIfPublished($record);

                        return $problems->isEmpty()
                            ? "\"{$record->title}\" becomes visible to students in a published course straight away."
                            : 'Students would hit this straight away, so it cannot go live yet: '.FindContentProblems::plainList($problems).'.';
                    })
                    ->visible(fn (Lesson $record): bool => $record->status === Lesson::STATUS_DRAFT)
                    ->authorize(fn (Lesson $record): bool => auth()->user()->canManageCourse($record->course))
                    ->action(function (Lesson $record): void {
                        $problems = static::problemsIfPublished($record);

                        if ($problems->isNotEmpty()) {
                            Notification::make()
                                ->title('Fix these before publishing')
                                ->body(FindContentProblems::plainList($problems))
                                ->danger()
                                ->persistent()
                                ->send();

                            return;
                        }

                        $record->publish();

                        Notification::make()->title('Lesson published')->body('Students can see it now.')->success()->send();
                    }),

                Action::make('unpublish')
                    ->label('Unpublish')
                    ->icon('heroicon-o-eye-slash')
                    ->color('gray')
                    ->requiresConfirmation()
                    ->modalHeading('Unpublish lesson')
                    ->modalDescription(function (Lesson $record): string {
                        $description = 'The lesson goes back to draft and disappears from the student site. Nothing is deleted — its text, video, questions and student progress all stay.';

                        $emptied = static::liveCoursesItIsLastIn($record);

                        return $emptied->isNotEmpty()
                            ? 'This is the only published lesson in "'.$emptied->implode('", "').'", which '.($emptied->count() === 1 ? 'is' : 'are')." live — unpublishing it leaves students an empty course. Publish another lesson first, or unpublish the course too. {$description}"
                            : $description;
                    })
                    ->visible(fn (Lesson $record): bool => $record->isPublished())
                    ->authorize(fn (Lesson $record): bool => auth()->user()->canManageCourse($record->course))
                    ->action(function (Lesson $record): void {
                        $record->unpublish();

                        Notification::make()->title('Lesson unpublished')->body('It is a draft again and hidden from students.')->warning()->send();
                    }),

                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('publish')
                        ->label('Publish')
                        ->icon('heroicon-o-rocket-launch')
                        ->color('success')
                        ->requiresConfirmation()
                        ->modalDescription('Lessons students could not finish — no questions, a question with no right answer, a YouTube link that will not play — are skipped when their course is live, and listed back to you.')
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
                                Notification::make()->title($ready->count().' lesson(s) published')->success()->send();
                            }

                            if ($skipped !== []) {
                                Notification::make()
                                    ->title(count($skipped).' lesson(s) skipped')
                                    ->body(implode('; ', $skipped))
                                    ->danger()
                                    ->persistent()
                                    ->send();
                            }
                        }),

                    BulkAction::make('unpublish')
                        ->label('Unpublish')
                        ->icon('heroicon-o-eye-slash')
                        ->color('gray')
                        ->requiresConfirmation()
                        ->modalDescription('The selected lessons disappear from their courses. Text, video, questions and student progress all stay. A live course left with no published lessons is named afterwards.')
                        ->deselectRecordsAfterCompletion()
                        ->action(function (Collection $records): void {
                            $records->each->unpublish();

                            Notification::make()->title($records->count().' lesson(s) unpublished')->warning()->send();

                            $emptied = Course::query()
                                ->publishedButEmpty()
                                ->whereIn('id', CourseLesson::query()->whereIn('lesson_id', $records->pluck('id'))->pluck('course_id')->unique())
                                ->pluck('title');

                            if ($emptied->isNotEmpty()) {
                                Notification::make()
                                    ->title('Students now see an empty course')
                                    ->body('No published lessons left in: '.$emptied->implode(', ').'. Publish a lesson, or unpublish the course.')
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
            $problems->push(['what' => 'No quiz questions, so students could never finish it', 'name' => $lesson->title]);
        }

        if ($lesson->questions()->withoutCorrectAnswer()->exists()) {
            $problems->push(['what' => 'A question with no correct answer', 'name' => $lesson->title]);
        }

        if (filled($lesson->youtube_url) && blank($lesson->video_path) && Lesson::youtubeIdFrom($lesson->youtube_url) === null) {
            $problems->push(['what' => 'A YouTube link that is not a playable video', 'name' => $lesson->title]);
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
