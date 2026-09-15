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
                    ->label(__t('admin_library.products.name'))
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                TextColumn::make('courses_count')
                    ->label(__t('admin_library.products.courses'))
                    ->counts('courses')
                    ->badge(),

                TextColumn::make('creators.name')
                    ->label(__t('admin_library.products.creators'))
                    ->badge()
                    ->color('info')
                    ->placeholder(__t('admin_library.products.none_assigned')),

                TextColumn::make('description')
                    ->label(__t('admin_common.description'))
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
