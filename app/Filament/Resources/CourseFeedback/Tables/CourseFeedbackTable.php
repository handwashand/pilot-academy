<?php

namespace App\Filament\Resources\CourseFeedback\Tables;

use App\Models\Company;
use App\Models\CourseFeedback;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class CourseFeedbackTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                IconColumn::make('is_positive')
                    ->label('Verdict')
                    ->boolean()
                    ->trueIcon('heroicon-o-hand-thumb-up')
                    ->falseIcon('heroicon-o-hand-thumb-down')
                    ->trueColor('success')
                    ->falseColor('danger'),

                TextColumn::make('course.title')
                    ->label('Course')
                    ->searchable()
                    ->sortable()
                    ->wrap(),

                TextColumn::make('user.name')
                    ->label('Student')
                    ->description(fn (CourseFeedback $record): ?string => $record->user?->email)
                    ->searchable(),

                TextColumn::make('user.company.name')
                    ->label('Partner')
                    ->badge()
                    ->placeholder('—'),

                TextColumn::make('comment')
                    ->label('What they said')
                    ->wrap()
                    ->placeholder('No comment')
                    ->searchable(),

                TextColumn::make('created_at')
                    ->label('When')
                    ->since()
                    ->sortable(),
            ])
            ->filters([
                TernaryFilter::make('is_positive')
                    ->label('Verdict')
                    ->placeholder('All')
                    ->trueLabel('Useful')
                    ->falseLabel('Not useful'),

                SelectFilter::make('course')
                    ->relationship('course', 'title')
                    ->searchable()
                    ->preload(),

                SelectFilter::make('partner')
                    ->label('Partner')
                    ->options(fn (): array => Company::query()->orderBy('name')->pluck('name', 'id')->all())
                    ->query(fn (Builder $query, array $data): Builder => $query->when(
                        $data['value'] ?? null,
                        fn (Builder $query, $companyId) => $query->whereHas('user', fn (Builder $user) => $user->where('company_id', $companyId)),
                    )),
            ])
            // Students write this; staff only read it.
            ->recordActions([])
            ->toolbarActions([])
            ->emptyStateHeading('No feedback yet')
            ->emptyStateDescription('Students are asked what they thought once they finish a course.');
    }
}
