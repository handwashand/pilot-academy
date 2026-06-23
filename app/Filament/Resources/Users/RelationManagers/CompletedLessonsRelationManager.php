<?php

namespace App\Filament\Resources\Users\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class CompletedLessonsRelationManager extends RelationManager
{
    protected static string $relationship = 'completedLessons';

    protected static ?string $title = 'Completed lessons';

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('course.title')
                    ->label('Course')
                    ->badge()
                    ->sortable(),

                TextColumn::make('title')
                    ->label('Lesson')
                    ->weight('bold'),

                TextColumn::make('pivot.completed_at')
                    ->label('Completed')
                    ->dateTime('d M Y, H:i')
                    ->sortable(),
            ]);
    }
}
