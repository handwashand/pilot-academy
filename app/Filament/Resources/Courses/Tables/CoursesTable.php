<?php

namespace App\Filament\Resources\Courses\Tables;

use App\Models\Course;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Collection;

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
                    ->modalDescription(fn (Course $record): string => "\"{$record->title}\" becomes visible to students straight away.")
                    ->visible(fn (Course $record): bool => $record->status === Course::STATUS_DRAFT)
                    ->authorize(fn (Course $record): bool => auth()->user()->canManageCourse($record))
                    ->action(function (Course $record): void {
                        if (! $record->canBePublished()) {
                            Notification::make()
                                ->title('Add a lesson first')
                                ->body('A course needs at least one published lesson before students can open it.')
                                ->danger()
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

                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('publish')
                        ->label('Publish')
                        ->icon('heroicon-o-rocket-launch')
                        ->color('success')
                        ->requiresConfirmation()
                        ->modalDescription('Only courses with at least one published lesson go live; the rest are skipped and listed back to you.')
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
     * Publishing in bulk still respects the one rule that protects students:
     * an empty course never goes live. Skipped ones are named, because a bulk
     * action that silently does less than you asked is worse than one that
     * refuses.
     */
    protected static function publishAll(Collection $records): void
    {
        [$ready, $empty] = $records->partition(fn (Course $course): bool => $course->canBePublished());

        $ready->each->publish();

        if ($ready->isNotEmpty()) {
            Notification::make()->title($ready->count().' course(s) published')->success()->send();
        }

        if ($empty->isNotEmpty()) {
            Notification::make()
                ->title($empty->count().' course(s) skipped')
                ->body('No published lesson yet: '.$empty->pluck('title')->join(', '))
                ->danger()
                ->persistent()
                ->send();
        }
    }
}
