<?php

namespace App\Filament\Resources\Webinars\Tables;

use App\Models\Webinar;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class WebinarsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('starts_at', 'desc')
            ->columns([
                TextColumn::make('title')
                    ->label(__t('admin_common.title'))
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                TextColumn::make('starts_at')
                    ->label(__t('admin_webinars.form.starts_at'))
                    ->dateTime('d M Y H:i')
                    ->description(fn (Webinar $record): string => $record->isUpcoming()
                        ? __t('admin_webinars.table.upcoming')
                        : __t('admin_webinars.table.past'))
                    ->sortable(),

                TextColumn::make('presenter')
                    ->label(__t('admin_webinars.form.presenter'))
                    ->placeholder('-'),

                TextColumn::make('product.name')
                    ->label(__t('admin_common.product'))
                    ->badge()
                    ->color('info')
                    ->placeholder('-'),

                IconColumn::make('recording_url')
                    ->label(__t('admin_webinars.table.recording'))
                    ->boolean()
                    ->state(fn (Webinar $record): bool => $record->hasRecording()),

                TextColumn::make('status')
                    ->label(__t('admin_common.status'))
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => Webinar::statusLabels()[$state] ?? $state)
                    ->color(fn (string $state): string => match ($state) {
                        Webinar::STATUS_PUBLISHED => 'success',
                        Webinar::STATUS_ARCHIVED => 'gray',
                        default => 'warning',
                    })
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label(__t('admin_common.status'))
                    ->options(Webinar::statusLabels()),

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
                    ->url(fn (Webinar $record): string => route('academy.webinar', $record))
                    ->openUrlInNewTab(),

                Action::make('publish')
                    ->label(__t('admin_common.publish'))
                    ->icon('heroicon-o-rocket-launch')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn (Webinar $record): bool => $record->status === Webinar::STATUS_DRAFT)
                    ->authorize(fn (Webinar $record): bool => auth()->user()->canManageWebinar($record))
                    ->action(function (Webinar $record): void {
                        if (! $record->canBePublished()) {
                            Notification::make()
                                ->title(__t('admin_webinars.notify.incomplete'))
                                ->body(__t('admin_webinars.notify.incomplete_body'))
                                ->danger()
                                ->persistent()
                                ->send();

                            return;
                        }

                        $record->publish();

                        Notification::make()->title(__t('admin_webinars.notify.published'))->body(__t('admin_common.visible_now'))->success()->send();
                    }),

                Action::make('unpublish')
                    ->label(__t('admin_common.unpublish'))
                    ->icon('heroicon-o-eye-slash')
                    ->color('gray')
                    ->requiresConfirmation()
                    ->visible(fn (Webinar $record): bool => $record->isPublished())
                    ->authorize(fn (Webinar $record): bool => auth()->user()->canManageWebinar($record))
                    ->action(function (Webinar $record): void {
                        $record->unpublish();

                        Notification::make()->title(__t('admin_webinars.notify.drafted'))->body(__t('admin_common.draft_again'))->warning()->send();
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
