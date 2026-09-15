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
                    ->label(__t('admin_common.name'))
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                TextColumn::make('region')
                    ->label(__t('admin_people.companies.region'))
                    ->badge()
                    ->placeholder('—'),

                TextColumn::make('industry')
                    ->label(__t('admin_people.companies.industry'))
                    ->placeholder('—'),

                TextColumn::make('students_count')
                    ->label(__t('admin_people.companies.members'))
                    ->counts('students')
                    ->badge(),

                TextColumn::make('certified')
                    ->label(__t('admin_people.companies.certified'))
                    ->badge()
                    ->color('success')
                    ->tooltip(__t('admin_people.companies.certified_tip'))
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
