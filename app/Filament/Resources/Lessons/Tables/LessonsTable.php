<?php

namespace App\Filament\Resources\Lessons\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class LessonsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('sort_order')
            ->columns([
                ImageColumn::make('image_path')
                    ->label('Cover')
                    ->disk('public')
                    ->height(36),

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

                IconColumn::make('is_published')
                    ->label('Published')
                    ->boolean(),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
