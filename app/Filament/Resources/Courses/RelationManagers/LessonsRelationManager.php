<?php

namespace App\Filament\Resources\Courses\RelationManagers;

use App\Filament\Resources\Lessons\LessonResource;
use App\Models\Lesson;
use Filament\Actions\AssociateAction;
use Filament\Actions\EditAction;
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
            // Drag the rows to set the order students see. sort_order is what
            // every student-facing query already orders by.
            ->reorderable('sort_order')
            ->defaultSort('sort_order')
            ->columns([
                TextColumn::make('title')
                    ->label('Lesson')
                    ->wrap()
                    ->searchable(),

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
                AssociateAction::make()
                    ->label('Add existing lesson')
                    ->modalHeading('Add an existing lesson to this course')
                    // A lesson belongs to exactly one course, so this is a move,
                    // not a copy — it leaves whichever course it is in now.
                    ->modalDescription('A lesson can only live in one course, so adding it here removes it from the course it is in now. Its text, video, questions and student progress all move with it.')
                    ->modalSubmitActionLabel('Move it here')
                    ->multiple()
                    ->recordSelectSearchColumns(['title'])
                    ->recordTitle(fn (Lesson $record): string => $record->course
                        ? "{$record->title}  ·  currently in {$record->course->title}"
                        : $record->title)
                    ->recordSelectOptionsQuery(function (Builder $query): Builder {
                        $user = auth()->user();

                        // Same scoping as the Lessons list: a creator must not be
                        // able to pull a lesson out of a product they do not own.
                        if ($user?->isCreator()) {
                            $productIds = $user->products()->pluck('products.id');

                            $query->whereHas('course', fn (Builder $course) => $course->whereIn('product_id', $productIds));
                        }

                        return $query->with('course')->orderBy('title');
                    }),
            ])
            ->recordActions([
                EditAction::make()
                    ->url(fn (Lesson $record): string => LessonResource::getUrl('edit', ['record' => $record])),
            ])
            ->emptyStateHeading('No lessons yet')
            ->emptyStateDescription('Add lessons under Lessons in the menu, or move an existing one here.');
    }
}
