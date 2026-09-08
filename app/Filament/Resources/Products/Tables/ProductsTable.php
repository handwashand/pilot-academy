<?php

namespace App\Filament\Resources\Products\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ProductsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('name')
            ->columns([
                TextColumn::make('name')
                    ->label('Product / module')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                TextColumn::make('courses_count')
                    ->label('Courses')
                    ->counts('courses')
                    ->badge(),

                TextColumn::make('creators.name')
                    ->label('Creators')
                    ->badge()
                    ->color('info')
                    ->placeholder('none assigned'),

                TextColumn::make('description')
                    ->limit(60)
                    ->placeholder('—')
                    ->toggleable(),
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
