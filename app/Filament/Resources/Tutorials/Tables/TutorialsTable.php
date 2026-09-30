<?php

namespace App\Filament\Resources\Tutorials\Tables;

use App\Models\Tutorial;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class TutorialsTable
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

                TextColumn::make('type')
                    ->label(__t('admin_lessons.form.source'))
                    ->badge()
                    ->formatStateUsing(fn (Tutorial $record): string => $record->videoType() === Tutorial::TYPE_UPLOAD
                        ? __t('admin_lessons.form.uploaded_video')
                        : __t('admin_lessons.form.youtube_video'))
                    ->color(fn (Tutorial $record): string => $record->hasVideo() ? 'info' : 'danger'),

                TextColumn::make('product.name')
                    ->label(__t('admin_common.product'))
                    ->badge()
                    ->color('info')
                    ->placeholder('-'),

                TextColumn::make('duration_minutes')
                    ->label(__t('admin_common.duration_minutes'))
                    ->placeholder('-'),

                TextColumn::make('status')
                    ->label(__t('admin_common.status'))
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => Tutorial::statusLabels()[$state] ?? $state)
                    ->color(fn (string $state): string => match ($state) {
                        Tutorial::STATUS_PUBLISHED => 'success',
                        Tutorial::STATUS_ARCHIVED => 'gray',
                        default => 'warning',
                    })
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label(__t('admin_common.status'))
                    ->options(Tutorial::statusLabels()),

                SelectFilter::make('product')
                    ->label(__t('admin_common.product'))
                    ->relationship('product', 'name')
                    ->searchable()
                    ->preload(),
            ])
            ->recordActions([
                Action::make('preview')
                    ->label(__t('admin_case_studies.table.preview'))
                    ->icon('heroicon-o-eye')
                    ->url(fn (Tutorial $record): string => route('academy.tutorial', $record))
                    ->openUrlInNewTab(),

                Action::make('publish')
                    ->label(__t('admin_common.publish'))
                    ->icon('heroicon-o-rocket-launch')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn (Tutorial $record): bool => $record->status === Tutorial::STATUS_DRAFT)
                    ->authorize(fn (Tutorial $record): bool => auth()->user()->canManageTutorial($record))
                    ->action(function (Tutorial $record): void {
                        if (! $record->canBePublished()) {
                            Notification::make()
                                ->title(__t('admin_tutorials.notify.incomplete'))
                                ->body(__t('admin_tutorials.notify.incomplete_body'))
                                ->danger()
                                ->persistent()
                                ->send();

                            return;
                        }

                        $record->publish();

                        Notification::make()->title(__t('admin_tutorials.notify.published'))->body(__t('admin_common.visible_now'))->success()->send();
                    }),

                Action::make('unpublish')
                    ->label(__t('admin_common.unpublish'))
                    ->icon('heroicon-o-eye-slash')
                    ->color('gray')
                    ->requiresConfirmation()
                    ->visible(fn (Tutorial $record): bool => $record->isPublished())
                    ->authorize(fn (Tutorial $record): bool => auth()->user()->canManageTutorial($record))
                    ->action(function (Tutorial $record): void {
                        $record->unpublish();

                        Notification::make()->title(__t('admin_tutorials.notify.drafted'))->body(__t('admin_common.draft_again'))->warning()->send();
                    }),

                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
