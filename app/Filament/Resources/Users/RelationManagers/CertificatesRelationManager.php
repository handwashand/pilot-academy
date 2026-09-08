<?php

namespace App\Filament\Resources\Users\RelationManagers;

use App\Models\Certificate;
use Filament\Actions\Action;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Storage;

class CertificatesRelationManager extends RelationManager
{
    protected static string $relationship = 'certificates';

    protected static ?string $title = 'Certificates';

    public function table(Table $table): Table
    {
        return $table
            ->defaultSort('issued_at', 'desc')
            ->columns([
                TextColumn::make('course.title')
                    ->label('Course')
                    ->sortable(),

                TextColumn::make('number')
                    ->label('Number')
                    ->copyable()
                    ->searchable(),

                TextColumn::make('score_percent')
                    ->label('Score')
                    ->formatStateUsing(fn ($state): string => "{$state}%"),

                TextColumn::make('issued_at')
                    ->label('Issued')
                    ->date('d M Y')
                    ->sortable(),

                TextColumn::make('status')
                    ->badge()
                    ->getStateUsing(fn (Certificate $record): string => $record->statusLabel())
                    ->color(fn (Certificate $record): string => $record->isValid() ? 'success' : 'danger'),
            ])
            ->recordActions([
                Action::make('download')
                    ->label('Download')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->visible(fn (Certificate $record): bool => $record->pdf_path && Storage::disk('public')->exists($record->pdf_path))
                    ->action(fn (Certificate $record) => Storage::disk('public')->download(
                        $record->pdf_path,
                        'certificate-'.$record->number.'.pdf'
                    )),
            ]);
    }
}
