<?php

namespace App\Filament\Resources\Users\Tables;

use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
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

                TextColumn::make('role')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => User::ROLE_LABELS[$state] ?? $state)
                    ->color(fn (string $state): string => match ($state) {
                        User::ROLE_ADMIN => 'danger',
                        User::ROLE_CREATOR => 'warning',
                        default => 'gray',
                    })
                    ->sortable(),

                TextColumn::make('products.name')
                    ->label('Products')
                    ->badge()
                    ->color('info')
                    ->placeholder('—'),

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

                SelectFilter::make('role')
                    ->label('Role')
                    ->options(User::ROLE_LABELS),

                SelectFilter::make('products')
                    ->label('Product / module')
                    ->relationship('products', 'name')
                    ->searchable()
                    ->preload(),
            ])
            ->recordActions([
                Action::make('accessLink')
                    ->label('Access link')
                    ->icon('heroicon-o-link')
                    ->color('gray')
                    ->visible(fn (User $record): bool => $record->isLearner())
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
