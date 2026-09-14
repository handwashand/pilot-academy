<?php

namespace App\Filament\Resources\Courses\Tables;

use App\Actions\DuplicateCourse;
use App\Actions\FindContentProblems;
use App\Models\Course;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class CoursesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('sort_order')
            ->reorderable('sort_order')
            ->columns([
                TextColumn::make('title')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                // Flagged where the work happens, not only on Content health.
                TextColumn::make('attention')
                    ->label('Attention')
                    ->state(function (Course $record): ?string {
                        $count = FindContentProblems::forCurrentUser()->where('course_id', $record->id)->count();

                        return $count > 0 ? $count.' '.Str::plural('problem', $count) : null;
                    })
                    ->badge()
                    ->color('danger')
                    ->icon('heroicon-m-exclamation-triangle')
                    ->tooltip(fn (Course $record): ?string => FindContentProblems::forCurrentUser()
                        ->where('course_id', $record->id)
                        ->pluck('what')
                        ->unique()
                        ->implode(' · ') ?: null),

                TextColumn::make('product.name')
                    ->label('Product')
                    ->badge()
                    ->color('info')
                    ->placeholder('—')
                    ->sortable(),

                TextColumn::make('level')
                    ->badge()
                    ->colors([
                        'success' => 'beginner',
                        'warning' => 'intermediate',
                        'danger' => 'advanced',
                    ]),

                TextColumn::make('audience')
                    ->label('For')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): ?string => $state ? (Course::AUDIENCES[$state] ?? $state) : null)
                    ->color('info')
                    ->placeholder('—'),

                TextColumn::make('lessons_count')
                    ->label('Lessons')
                    ->counts('lessons')
                    ->badge(),

                TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => Course::STATUS_LABELS[$state] ?? $state)
                    ->color(fn (string $state): string => match ($state) {
                        Course::STATUS_PUBLISHED => 'success',
                        Course::STATUS_ARCHIVED => 'gray',
                        default => 'warning',
                    })
                    ->sortable(),

                TextColumn::make('updated_at')
                    ->label('Updated')
                    ->since()
                    ->sortable(),
            ])
            ->filters([
                Filter::make('needs_attention')
                    ->label('Needs attention')
                    ->query(fn (Builder $query): Builder => $query->whereIn(
                        'id',
                        FindContentProblems::forCurrentUser()->pluck('course_id')->unique()->values()->all(),
                    )),

                SelectFilter::make('status')
                    ->options(Course::STATUS_LABELS),

                SelectFilter::make('product')
                    ->relationship('product', 'name')
                    ->searchable()
                    ->preload(),
            ])
            ->recordActions([
                Action::make('publish')
                    ->label('Publish')
                    ->icon('heroicon-o-rocket-launch')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Publish course')
                    // Say what is wrong before anyone presses the button.
                    ->modalDescription(function (Course $record, FindContentProblems $find): string {
                        $problems = $find->forCourse($record);

                        return $problems->isEmpty()
                            ? "\"{$record->title}\" becomes visible to students straight away."
                            : 'This course cannot go live yet. Fix these first: '.FindContentProblems::plainList($problems).'.';
                    })
                    ->visible(fn (Course $record): bool => $record->status === Course::STATUS_DRAFT)
                    ->authorize(fn (Course $record): bool => auth()->user()->canManageCourse($record))
                    ->action(function (Course $record, FindContentProblems $find): void {
                        if (! $record->canBePublished()) {
                            Notification::make()
                                ->title('Add a lesson first')
                                ->body('A course needs at least one published lesson before students can open it.')
                                ->danger()
                                ->send();

                            return;
                        }

                        $problems = $find->forCourse($record);

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

                        Notification::make()->title('Course published')->body('Students can see it now.')->success()->send();
                    }),

                Action::make('unpublish')
                    ->label('Unpublish')
                    ->icon('heroicon-o-eye-slash')
                    ->color('gray')
                    ->requiresConfirmation()
                    ->modalHeading('Unpublish course')
                    ->modalDescription('The course goes back to draft and disappears from the student site. Nothing is deleted — lessons, questions and certificates all stay.')
                    ->visible(fn (Course $record): bool => $record->isPublished())
                    ->authorize(fn (Course $record): bool => auth()->user()->canManageCourse($record))
                    ->action(function (Course $record): void {
                        $record->unpublish();

                        Notification::make()->title('Course unpublished')->body('It is a draft again and hidden from students.')->warning()->send();
                    }),

                Action::make('duplicate')
                    ->label('Duplicate')
                    ->icon('heroicon-o-document-duplicate')
                    ->color('gray')
                    ->requiresConfirmation()
                    ->modalHeading('Duplicate course')
                    ->modalDescription('Copies the course, its lessons and every quiz question as a new draft. Student progress and certificates are not copied.')
                    ->authorize(fn (Course $record): bool => auth()->user()->canManageCourse($record))
                    ->action(function (Course $record, DuplicateCourse $duplicate): void {
                        $copy = $duplicate->handle($record);

                        Notification::make()
                            ->title('Course duplicated')
                            ->body('"'.$copy->title.'" was created as a draft.')
                            ->success()
                            ->send();
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
                        ->modalDescription('Only courses students could use go live — with at least one published lesson, and nothing broken in them. The rest are skipped and listed back to you.')
                        ->deselectRecordsAfterCompletion()
                        ->action(fn (Collection $records) => static::publishAll($records)),

                    BulkAction::make('unpublish')
                        ->label('Unpublish')
                        ->icon('heroicon-o-eye-slash')
                        ->color('gray')
                        ->requiresConfirmation()
                        ->modalDescription('The selected courses go back to draft and disappear from the student site. Nothing is deleted.')
                        ->deselectRecordsAfterCompletion()
                        ->action(function (Collection $records): void {
                            $records->each->unpublish();

                            Notification::make()
                                ->title($records->count().' course(s) unpublished')
                                ->warning()
                                ->send();
                        }),

                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    /**
     * Publishing in bulk still respects the rules that protect students: an
     * empty course never goes live, and neither does one with something broken
     * in it. Skipped ones are named with the reason, because a bulk action that
     * silently does less than you asked is worse than one that refuses.
     */
    protected static function publishAll(Collection $records): void
    {
        $find = app(FindContentProblems::class);
        $skipped = [];

        $ready = $records->filter(function (Course $course) use ($find, &$skipped): bool {
            if (! $course->canBePublished()) {
                $skipped[] = "{$course->title} (no published lesson yet)";

                return false;
            }

            $problems = $find->forCourse($course);

            if ($problems->isNotEmpty()) {
                $skipped[] = "{$course->title} ({$problems->pluck('what')->unique()->implode('; ')})";

                return false;
            }

            return true;
        });

        $ready->each->publish();

        if ($ready->isNotEmpty()) {
            Notification::make()->title($ready->count().' course(s) published')->success()->send();
        }

        if ($skipped !== []) {
            Notification::make()
                ->title(count($skipped).' course(s) skipped')
                ->body(implode('; ', $skipped))
                ->danger()
                ->persistent()
                ->send();
        }
    }
}
