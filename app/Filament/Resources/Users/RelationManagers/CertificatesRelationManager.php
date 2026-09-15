<?php

namespace App\Filament\Resources\Users\RelationManagers;

use App\Models\Certificate;
use Filament\Actions\Action;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class CertificatesRelationManager extends RelationManager
{
    protected static string $relationship = 'certificates';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __t('admin_nav.tabs.certificates');
    }

    public function table(Table $table): Table
    {
        return $table
            ->defaultSort('issued_at', 'desc')
            ->columns([
                TextColumn::make('course.title')
                    ->label(__t('admin_common.course'))
                    ->sortable(),

                TextColumn::make('number')
                    ->label(__t('admin_common.number'))
                    ->copyable()
                    ->searchable(),

                TextColumn::make('score_percent')
                    ->label(__t('admin_common.score'))
                    ->formatStateUsing(fn ($state): string => "{$state}%"),

                TextColumn::make('issued_at')
                    ->label(__t('admin_common.issued'))
                    ->date('d M Y')
                    ->sortable(),

                TextColumn::make('status')
                    ->label(__t('admin_common.status'))
                    ->badge()
                    ->getStateUsing(fn (Certificate $record): string => $record->statusLabel())
                    ->color(fn (Certificate $record): string => $record->isValid() ? 'success' : 'danger'),
            ])
            ->recordActions([
                Action::make('download')
                    ->label(__t('admin_common.download'))
                    ->icon('heroicon-o-arrow-down-tray')
                    ->visible(fn (Certificate $record): bool => $record->pdf_path && Storage::disk('public')->exists($record->pdf_path))
                    ->action(fn (Certificate $record) => Storage::disk('public')->download(
                        $record->pdf_path,
                        'certificate-'.$record->number.'.pdf'
                    )),
            ]);
    }
}
