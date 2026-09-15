<?php

namespace App\Filament\Resources\MediaItems\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class MediaItemsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                ImageColumn::make('path')
                    ->label(__t('admin_library.media.image'))
                    ->disk('public')
                    ->height(48),

                TextColumn::make('name')
                    ->label(__t('admin_common.name'))
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                TextColumn::make('lessons_count')
                    ->label(__t('admin_library.media.used_by'))
                    ->counts('lessons')
                    ->badge()
                    ->formatStateUsing(fn ($state): string => __tc('admin_library.media.lessons', (int) $state)),

                TextColumn::make('created_at')
                    ->label(__t('admin_common.added'))
                    ->since()
                    ->sortable(),
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
