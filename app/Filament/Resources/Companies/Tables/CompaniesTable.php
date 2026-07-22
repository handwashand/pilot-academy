<?php

namespace App\Filament\Resources\Companies\Tables;

use App\Models\Company;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class CompaniesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('name')
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                TextColumn::make('region')
                    ->badge()
                    ->placeholder('—'),

                TextColumn::make('industry')
                    ->placeholder('—'),

                TextColumn::make('students_count')
                    ->label('Members')
                    ->counts('students')
                    ->badge(),

                TextColumn::make('certified')
                    ->label('Certified')
                    ->badge()
                    ->color('success')
                    ->tooltip('Students with at least one valid certificate, out of total members.')
                    ->getStateUsing(function (Company $record): string {
                        $certified = $record->students()
                            ->whereHas('certificates', fn ($q) => $q->whereNull('revoked_at'))
                            ->count();

                        return $certified.' / '.$record->students()->count();
                    }),
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
