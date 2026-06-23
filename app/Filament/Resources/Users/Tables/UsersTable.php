<?php

namespace App\Filament\Resources\Users\Tables;

use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class UsersTable
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

                TextColumn::make('email')
                    ->searchable()
                    ->copyable(),

                TextColumn::make('company.name')
                    ->label('Company')
                    ->badge()
                    ->placeholder('—')
                    ->sortable(),

                TextColumn::make('completed_lessons_count')
                    ->label('Lessons done')
                    ->badge()
                    ->color('success')
                    ->sortable(),

                TextColumn::make('last_login_at')
                    ->label('Last login')
                    ->dateTime('d M Y, H:i')
                    ->since()
                    ->placeholder('never')
                    ->sortable(),

                IconColumn::make('is_admin')
                    ->label('Admin')
                    ->boolean(),

                TextColumn::make('created_at')
                    ->label('Added')
                    ->since()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('company_id')
                    ->label('Company')
                    ->relationship('company', 'name')
                    ->searchable()
                    ->preload(),

                TernaryFilter::make('is_admin')
                    ->label('Admins only'),
            ])
            ->recordActions([
                Action::make('accessLink')
                    ->label('Access link')
                    ->icon('heroicon-o-link')
                    ->color('gray')
                    ->visible(fn (User $record): bool => ! $record->is_admin)
                    ->modalHeading('Personal access link')
                    ->modalContent(fn (User $record) => view('filament.access-link', [
                        'url' => $record->accessUrl(),
                    ]))
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Close'),

                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
