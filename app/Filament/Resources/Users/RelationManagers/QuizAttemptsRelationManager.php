<?php

namespace App\Filament\Resources\Users\RelationManagers;

use App\Models\QuizAttempt;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class QuizAttemptsRelationManager extends RelationManager
{
    protected static string $relationship = 'quizAttempts';

    protected static ?string $title = 'Quiz attempts';

    public function table(Table $table): Table
    {
        return $table
            ->defaultSort('started_at', 'desc')
            ->columns([
                TextColumn::make('lesson.title')
                    ->label('Lesson')
                    ->badge()
                    ->sortable(),

                TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => QuizAttempt::STATUS_LABELS[$state] ?? $state)
                    ->color(fn (string $state): string => match ($state) {
                        QuizAttempt::STATUS_PASSED => 'success',
                        QuizAttempt::STATUS_FAILED => 'danger',
                        QuizAttempt::STATUS_EXPIRED => 'warning',
                        default => 'gray',
                    }),

                TextColumn::make('score')
                    ->label('Score')
                    ->formatStateUsing(fn ($state, $record): string => $record->total ? "{$state}/{$record->total}" : (string) ($state ?? '—')),

                TextColumn::make('started_at')
                    ->label('Started')
                    ->dateTime('d M Y, H:i')
                    ->sortable(),

                TextColumn::make('submitted_at')
                    ->label('Submitted')
                    ->dateTime('d M Y, H:i')
                    ->placeholder('—'),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options(QuizAttempt::STATUS_LABELS),
            ]);
    }
}
