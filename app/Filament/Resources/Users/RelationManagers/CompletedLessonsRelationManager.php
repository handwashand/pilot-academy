<?php

namespace App\Filament\Resources\Users\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class CompletedLessonsRelationManager extends RelationManager
{
    protected static string $relationship = 'completedLessons';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __t('admin_nav.tabs.completed_lessons');
    }

    protected static function getModelLabel(): ?string
    {
        return __t('admin_nav.lessons.one');
    }

    protected static function getPluralModelLabel(): ?string
    {
        return __t('admin_nav.lessons.many');
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('course.title')
                    ->label(__t('admin_common.course'))
                    ->badge()
                    ->sortable(),

                TextColumn::make('title')
                    ->label(__t('admin_common.lesson'))
                    ->weight('bold'),

                TextColumn::make('pivot.completed_at')
                    ->label(__t('admin_people.tabs.completed'))
                    ->dateTime('d M Y, H:i')
                    ->sortable(),
            ]);
    }
}
