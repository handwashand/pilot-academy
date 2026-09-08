<?php

namespace App\Filament\Resources\Users\RelationManagers;

use App\Models\ActivityEvent;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class ActivitiesRelationManager extends RelationManager
{
    protected static string $relationship = 'activities';

    protected static ?string $title = 'Activity';

    public function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('created_at')
                    ->label('When')
                    ->dateTime('d M Y, H:i')
                    ->sortable(),

                TextColumn::make('type')
                    ->label('Action')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => ActivityEvent::TYPE_LABELS[$state] ?? $state)
                    ->color(fn (string $state): string => match ($state) {
                        ActivityEvent::TYPE_LOGIN => 'gray',
                        ActivityEvent::TYPE_COURSE_OPENED => 'info',
                        ActivityEvent::TYPE_LESSON_OPENED => 'info',
                        ActivityEvent::TYPE_LESSON_COMPLETED => 'success',
                        ActivityEvent::TYPE_COURSE_COMPLETED => 'success',
                        default => 'gray',
                    }),

                TextColumn::make('label')
                    ->label('Details')
                    ->placeholder('—')
                    ->wrap(),
            ])
            ->filters([
                SelectFilter::make('type')
                    ->label('Action')
                    ->options(ActivityEvent::TYPE_LABELS),
            ]);
    }
}
