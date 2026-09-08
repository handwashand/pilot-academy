<?php

namespace App\Filament\Resources\Lessons\Tables;

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
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Collection;

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

                TextColumn::make('course.title')
                    ->label('Course')
                    ->sortable()
                    ->badge(),

                TextColumn::make('sort_order')
                    ->label('#')
                    ->sortable(),

                TextColumn::make('title')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                TextColumn::make('questions_count')
                    ->label('Quiz Qs')
                    ->counts('questions')
                    ->badge(),

                IconColumn::make('has_video')
                    ->label('Video')
                    ->boolean()
                    ->state(fn ($record) => filled($record->youtube_url) || filled($record->video_path)),

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
                SelectFilter::make('status')
                    ->options(Lesson::STATUS_LABELS),

                SelectFilter::make('course')
                    ->relationship('course', 'title', function ($query) {
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
                    ->modalDescription(fn (Lesson $record): string => "\"{$record->title}\" becomes visible to students in a published course straight away.")
                    ->visible(fn (Lesson $record): bool => $record->status === Lesson::STATUS_DRAFT)
                    ->authorize(fn (Lesson $record): bool => auth()->user()->canManageCourse($record->course))
                    ->action(function (Lesson $record): void {
                        $record->publish();

                        Notification::make()->title('Lesson published')->body('Students can see it now.')->success()->send();
                    }),

                Action::make('unpublish')
                    ->label('Unpublish')
                    ->icon('heroicon-o-eye-slash')
                    ->color('gray')
                    ->requiresConfirmation()
                    ->modalHeading('Unpublish lesson')
                    ->modalDescription('The lesson goes back to draft and disappears from the student site. Nothing is deleted — its text, video, questions and student progress all stay.')
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
                    // No prerequisite here: a lesson has nothing to be empty of,
                    // and its course still gates whether students see it.
                    BulkAction::make('publish')
                        ->label('Publish')
                        ->icon('heroicon-o-rocket-launch')
                        ->color('success')
                        ->requiresConfirmation()
                        ->deselectRecordsAfterCompletion()
                        ->action(function (Collection $records): void {
                            $records->each->publish();

                            Notification::make()->title($records->count().' lesson(s) published')->success()->send();
                        }),

                    BulkAction::make('unpublish')
                        ->label('Unpublish')
                        ->icon('heroicon-o-eye-slash')
                        ->color('gray')
                        ->requiresConfirmation()
                        ->modalDescription('The selected lessons disappear from their courses. Text, video, questions and student progress all stay.')
                        ->deselectRecordsAfterCompletion()
                        ->action(function (Collection $records): void {
                            $records->each->unpublish();

                            Notification::make()->title($records->count().' lesson(s) unpublished')->warning()->send();
                        }),

                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
