<?php

namespace App\Filament\Resources\CourseFeedback\Tables;

use App\Models\Company;
use App\Models\CourseFeedback;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class CourseFeedbackTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                IconColumn::make('is_positive')
                    ->label(__t('admin_courses.feedback_tab.verdict'))
                    ->boolean()
                    ->trueIcon('heroicon-o-hand-thumb-up')
                    ->falseIcon('heroicon-o-hand-thumb-down')
                    ->trueColor('success')
                    ->falseColor('danger'),

                TextColumn::make('course.title')
                    ->label(__t('admin_common.course'))
                    ->searchable()
                    ->sortable()
                    ->wrap(),

                TextColumn::make('user.name')
                    ->label(__t('admin_common.student'))
                    ->description(fn (CourseFeedback $record): ?string => $record->user?->email)
                    ->searchable(),

                TextColumn::make('user.company.name')
                    ->label(__t('admin_common.partner'))
                    ->badge()
                    ->placeholder('—'),

                TextColumn::make('comment')
                    ->label(__t('admin_courses.feedback_tab.what_they_said'))
                    ->wrap()
                    ->placeholder(__t('admin_courses.feedback_tab.no_comment'))
                    ->searchable(),

                TextColumn::make('created_at')
                    ->label(__t('admin_common.when'))
                    ->since()
                    ->sortable(),
            ])
            ->filters([
                TernaryFilter::make('is_positive')
                    ->label(__t('admin_courses.feedback_tab.verdict'))
                    ->placeholder(__t('admin_common.all'))
                    ->trueLabel(__t('admin_courses.feedback_tab.useful'))
                    ->falseLabel(__t('admin_courses.feedback_tab.not_useful')),

                SelectFilter::make('course')
                    ->label(__t('admin_common.course'))
                    ->relationship('course', 'title')
                    ->searchable()
                    ->preload(),

                SelectFilter::make('partner')
                    ->label(__t('admin_common.partner'))
                    ->options(fn (): array => Company::query()->orderBy('name')->pluck('name', 'id')->all())
                    ->query(fn (Builder $query, array $data): Builder => $query->when(
                        $data['value'] ?? null,
                        fn (Builder $query, $companyId) => $query->whereHas('user', fn (Builder $user) => $user->where('company_id', $companyId)),
                    )),
            ])
            // Students write this; staff only read it.
            ->recordActions([])
            ->toolbarActions([])
            ->emptyStateHeading(__t('admin_courses.feedback_tab.empty'))
            ->emptyStateDescription(__t('admin_results.feedback.empty_description'));
    }
}
