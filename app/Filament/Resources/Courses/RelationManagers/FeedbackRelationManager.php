<?php

namespace App\Filament\Resources\Courses\RelationManagers;

use App\Models\CourseFeedback;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

/**
 * What students said about this course. Read-only: students write it, staff
 * read it. Nobody in the panel should be editing someone else's verdict.
 */
class FeedbackRelationManager extends RelationManager
{
    protected static string $relationship = 'feedback';

    protected static ?string $title = 'Student feedback';

    public static function getBadge(Model $ownerRecord, string $pageClass): ?string
    {
        $count = $ownerRecord->feedback()->count();

        return $count > 0 ? (string) $count : null;
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('comment')
            ->defaultSort('created_at', 'desc')
            ->columns([
                IconColumn::make('is_positive')
                    ->label('Verdict')
                    ->boolean()
                    ->trueIcon('heroicon-o-hand-thumb-up')
                    ->falseIcon('heroicon-o-hand-thumb-down')
                    ->trueColor('success')
                    ->falseColor('danger'),

                TextColumn::make('user.name')
                    ->label('Student')
                    ->searchable(),

                TextColumn::make('user.company.name')
                    ->label('Partner')
                    ->badge()
                    ->default('—'),

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
            ])
            ->emptyStateHeading('No feedback yet')
            ->emptyStateDescription('Students are asked what they thought once they finish every lesson.')
            // Students write this, staff only read it.
            ->headerActions([])
            ->recordActions([])
            ->toolbarActions([]);
    }

    public function isReadOnly(): bool
    {
        return true;
    }

    /** @return class-string */
    protected function getModel(): string
    {
        return CourseFeedback::class;
    }
}
