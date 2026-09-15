<?php

namespace App\Filament\Resources\Users\RelationManagers;

use App\Models\ActivityEvent;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class ActivitiesRelationManager extends RelationManager
{
    protected static string $relationship = 'activities';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __t('admin_nav.tabs.activity');
    }

    public function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('created_at')
                    ->label(__t('admin_common.when'))
                    ->dateTime('d M Y, H:i')
                    ->sortable(),

                TextColumn::make('type')
                    ->label(__t('admin_people.tabs.action'))
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => ActivityEvent::typeLabels()[$state] ?? $state)
                    ->color(fn (string $state): string => match ($state) {
                        ActivityEvent::TYPE_LOGIN => 'gray',
                        ActivityEvent::TYPE_COURSE_OPENED => 'info',
                        ActivityEvent::TYPE_LESSON_OPENED => 'info',
                        ActivityEvent::TYPE_LESSON_COMPLETED => 'success',
                        ActivityEvent::TYPE_COURSE_COMPLETED => 'success',
                        default => 'gray',
                    }),

                TextColumn::make('label')
                    ->label(__t('admin_people.tabs.details'))
                    ->placeholder('—')
                    ->wrap(),
            ])
            ->filters([
                SelectFilter::make('type')
                    ->label(__t('admin_people.tabs.action'))
                    ->options(ActivityEvent::typeLabels()),
            ]);
    }
}
