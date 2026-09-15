<?php

namespace App\Filament\Resources\Users\RelationManagers;

use App\Models\QuizAttempt;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class QuizAttemptsRelationManager extends RelationManager
{
    protected static string $relationship = 'quizAttempts';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __t('admin_nav.tabs.quiz_attempts');
    }

    protected static function getModelLabel(): ?string
    {
        return __t('admin_nav.quiz_attempts.one');
    }

    protected static function getPluralModelLabel(): ?string
    {
        return __t('admin_nav.quiz_attempts.many');
    }

    public function table(Table $table): Table
    {
        return $table
            ->defaultSort('started_at', 'desc')
            ->columns([
                TextColumn::make('lesson.title')
                    ->label(__t('admin_common.lesson'))
                    ->badge()
                    ->sortable(),

                TextColumn::make('status')
                    ->label(__t('admin_common.status'))
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => QuizAttempt::statusLabels()[$state] ?? $state)
                    ->color(fn (string $state): string => match ($state) {
                        QuizAttempt::STATUS_PASSED => 'success',
                        QuizAttempt::STATUS_FAILED => 'danger',
                        QuizAttempt::STATUS_EXPIRED => 'warning',
                        default => 'gray',
                    }),

                TextColumn::make('score')
                    ->label(__t('admin_common.score'))
                    ->formatStateUsing(fn ($state, $record): string => $record->total ? "{$state}/{$record->total}" : (string) ($state ?? '—')),

                TextColumn::make('started_at')
                    ->label(__t('admin_people.tabs.started'))
                    ->dateTime('d M Y, H:i')
                    ->sortable(),

                TextColumn::make('submitted_at')
                    ->label(__t('admin_common.submitted'))
                    ->dateTime('d M Y, H:i')
                    ->placeholder('—'),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label(__t('admin_common.status'))
                    ->options(QuizAttempt::statusLabels()),
            ]);
    }
}
