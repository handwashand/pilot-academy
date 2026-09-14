<?php

namespace App\Filament\Resources\Courses\RelationManagers;

use App\Filament\Resources\Lessons\LessonResource;
use App\Models\Lesson;
use Filament\Actions\AttachAction;
use Filament\Actions\DetachAction;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class LessonsRelationManager extends RelationManager
{
    protected static string $relationship = 'lessons';

    protected static ?string $title = 'Lessons';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('title')
            // Drag the rows to set the order students see in this course. Each
            // course keeps its own order, so a shared lesson can sit first in
            // one and last in another.
            ->reorderable('course_lesson.sort_order')
            ->columns([
                TextColumn::make('title')
                    ->label('Lesson')
                    ->wrap()
                    ->searchable()
                    ->description(fn (Lesson $record): ?string => $this->alsoIn($record)),

                TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => Lesson::STATUS_LABELS[$state] ?? $state)
                    ->color(fn (string $state): string => match ($state) {
                        Lesson::STATUS_PUBLISHED => 'success',
                        Lesson::STATUS_ARCHIVED => 'gray',
                        default => 'warning',
                    }),

                TextColumn::make('duration_minutes')
                    ->label('Length')
                    ->formatStateUsing(fn (?int $state): string => Lesson::formatMinutes($state) ?? '—')
                    ->tooltip('Students see this. Set it on the lesson.'),

                TextColumn::make('questions_count')
                    ->label('Questions')
                    ->counts('questions')
                    ->badge()
                    ->color(fn (int $state): string => $state > 0 ? 'gray' : 'danger')
                    ->tooltip(fn (int $state): ?string => $state > 0
                        ? null
                        : 'A lesson with no quiz can never be marked finished.'),
            ])
            ->headerActions([
                AttachAction::make()
                    ->label('Add existing lesson')
                    ->modalHeading('Add an existing lesson to this course')
                    ->modalDescription('The lesson is shared, not copied: it stays in the courses it is already in, and a change to it shows in all of them. Students who already finished it have it finished here too.')
                    ->modalSubmitActionLabel('Add to this course')
                    ->multiple()
                    ->recordSelectSearchColumns(['title'])
                    ->recordTitle(fn (Lesson $record): string => $record->course
                        ? "{$record->title}  ·  from {$record->course->title}"
                        : $record->title)
                    ->recordSelectOptionsQuery(function (Builder $query): Builder {
                        $user = auth()->user();

                        // Same scoping as the Lessons list: a creator can only
                        // bring in lessons from courses in their own products.
                        if ($user?->isCreator()) {
                            $productIds = $user->products()->pluck('products.id');

                            $query->whereHas('course', fn (Builder $course) => $course->whereIn('product_id', $productIds));
                        }

                        return $query->with('course')->orderBy('lessons.title');
                    })
                    // The student URL finds a lesson by its slug inside the
                    // course, so two lessons with one slug cannot share a course.
                    ->before(function (array $data, AttachAction $action): void {
                        $clashes = Lesson::query()
                            ->whereIn('id', (array) ($data['recordId'] ?? []))
                            ->whereIn('slug', $this->getOwnerRecord()->lessons()->pluck('lessons.slug'))
                            ->pluck('title');

                        if ($clashes->isNotEmpty()) {
                            Notification::make()
                                ->title('A lesson in this course already uses the same web address')
                                ->body('Change the slug of '.$clashes->implode(', ').' first, then add it.')
                                ->danger()
                                ->persistent()
                                ->send();

                            $action->halt();
                        }
                    }),
            ])
            ->recordActions([
                EditAction::make()
                    ->url(fn (Lesson $record): string => LessonResource::getUrl('edit', ['record' => $record])),

                DetachAction::make()
                    ->label('Remove from course')
                    ->modalHeading('Remove this lesson from the course')
                    ->modalDescription('The lesson stays in its other courses, with its questions and student progress. Only this course stops showing it.')
                    // Its last course: removing it would leave the lesson in
                    // no course at all. Delete it from Lessons instead.
                    ->visible(fn (Lesson $record): bool => $record->courses()->count() > 1),
            ])
            ->emptyStateHeading('No lessons yet')
            ->emptyStateDescription('Add lessons under Lessons in the menu, or add an existing one here.');
    }

    /** "Also in: X, Y" for a lesson shared with other courses. */
    private function alsoIn(Lesson $record): ?string
    {
        $others = $record->courses()
            ->whereKeyNot($this->getOwnerRecord()->getKey())
            ->pluck('title');

        return $others->isNotEmpty() ? 'Also in: '.$others->implode(', ') : null;
    }
}
