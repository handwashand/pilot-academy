<?php

namespace App\Filament\Resources\CaseStudies\Tables;

use App\Models\CaseStudy;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Collection;

class CaseStudiesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('sort_order')
            ->columns([
                TextColumn::make('sort_order')
                    ->label('#')
                    ->sortable(),

                TextColumn::make('title')
                    ->label(__t('admin_common.title'))
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                TextColumn::make('product.name')
                    ->label(__t('admin_common.product'))
                    ->badge()
                    ->color('info')
                    ->placeholder('-'),

                TextColumn::make('industry')
                    ->label('Industry')
                    ->searchable()
                    ->badge(),

                TextColumn::make('difficulty')
                    ->label('Difficulty')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => CaseStudy::difficultyLabels()[$state] ?? $state)
                    ->color(fn (string $state): string => match ($state) {
                        CaseStudy::DIFFICULTY_BEGINNER => 'success',
                        CaseStudy::DIFFICULTY_ADVANCED => 'danger',
                        default => 'warning',
                    }),

                IconColumn::make('is_anonymized')
                    ->label('Anonymized')
                    ->boolean(),

                IconColumn::make('is_customer_approved')
                    ->label('Approved')
                    ->boolean(),

                TextColumn::make('status')
                    ->label(__t('admin_common.status'))
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => CaseStudy::statusLabels()[$state] ?? $state)
                    ->color(fn (string $state): string => match ($state) {
                        CaseStudy::STATUS_PUBLISHED => 'success',
                        CaseStudy::STATUS_ARCHIVED => 'gray',
                        default => 'warning',
                    })
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label(__t('admin_common.status'))
                    ->options(CaseStudy::statusLabels()),

                SelectFilter::make('product')
                    ->label(__t('admin_common.product'))
                    ->relationship('product', 'name')
                    ->searchable()
                    ->preload(),

                SelectFilter::make('difficulty')
                    ->label('Difficulty')
                    ->options(CaseStudy::difficultyLabels()),
            ])
            ->recordActions([
                Action::make('preview')
                    ->label('Preview')
                    ->icon('heroicon-o-eye')
                    ->url(fn (CaseStudy $record): string => route('academy.case-studies.show', $record))
                    ->openUrlInNewTab(),

                Action::make('publish')
                    ->label(__t('admin_common.publish'))
                    ->icon('heroicon-o-rocket-launch')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn (CaseStudy $record): bool => $record->status === CaseStudy::STATUS_DRAFT)
                    ->authorize(fn (CaseStudy $record): bool => auth()->user()->canManageCaseStudy($record))
                    ->action(function (CaseStudy $record): void {
                        if (! $record->canBePublished()) {
                            Notification::make()
                                ->title('Finish the required sections first')
                                ->body('Title, summary, scenario, desired outcome, configuration, verification, and source note are required before publishing.')
                                ->danger()
                                ->persistent()
                                ->send();

                            return;
                        }

                        $record->publish();

                        Notification::make()->title('Case study published')->body(__t('admin_common.visible_now'))->success()->send();
                    }),

                Action::make('unpublish')
                    ->label(__t('admin_common.unpublish'))
                    ->icon('heroicon-o-eye-slash')
                    ->color('gray')
                    ->requiresConfirmation()
                    ->visible(fn (CaseStudy $record): bool => $record->isPublished())
                    ->authorize(fn (CaseStudy $record): bool => auth()->user()->canManageCaseStudy($record))
                    ->action(function (CaseStudy $record): void {
                        $record->unpublish();

                        Notification::make()->title('Case study returned to draft')->body(__t('admin_common.draft_again'))->warning()->send();
                    }),

                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('publish')
                        ->label(__t('admin_common.publish'))
                        ->icon('heroicon-o-rocket-launch')
                        ->color('success')
                        ->requiresConfirmation()
                        ->deselectRecordsAfterCompletion()
                        ->action(fn (Collection $records) => static::publishAll($records)),

                    BulkAction::make('unpublish')
                        ->label(__t('admin_common.unpublish'))
                        ->icon('heroicon-o-eye-slash')
                        ->color('gray')
                        ->requiresConfirmation()
                        ->deselectRecordsAfterCompletion()
                        ->action(function (Collection $records): void {
                            $records->each->unpublish();

                            Notification::make()->title('Case studies returned to draft')->warning()->send();
                        }),

                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    protected static function publishAll(Collection $records): void
    {
        $ready = $records->filter->canBePublished();
        $skipped = $records->reject->canBePublished();

        $ready->each->publish();

        if ($ready->isNotEmpty()) {
            Notification::make()->title($ready->count().' case studies published')->success()->send();
        }

        if ($skipped->isNotEmpty()) {
            Notification::make()
                ->title($skipped->count().' case studies skipped')
                ->body('Skipped records are missing required sections or source notes.')
                ->danger()
                ->persistent()
                ->send();
        }
    }
}
