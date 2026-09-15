<?php

namespace App\Filament\Resources\Courses\RelationManagers;

use App\Models\CourseFeedback;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

/**
 * What students said about this course. Read-only: students write it, staff
 * read it. Nobody in the panel should be editing someone else's verdict.
 */
class FeedbackRelationManager extends RelationManager
{
    protected static string $relationship = 'feedback';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __t('admin_nav.tabs.feedback');
    }

    public static function getBadge(Model $ownerRecord, string $pageClass): ?string
    {
        $count = $ownerRecord->feedback()->count();

        return $count > 0 ? (string) $count : null;
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('comment')
            ->defaultSort('created_at', 'desc')
            ->columns([
                IconColumn::make('is_positive')
                    ->label(__t('admin_courses.feedback_tab.verdict'))
                    ->boolean()
                    ->trueIcon('heroicon-o-hand-thumb-up')
                    ->falseIcon('heroicon-o-hand-thumb-down')
                    ->trueColor('success')
                    ->falseColor('danger'),

                TextColumn::make('user.name')
                    ->label(__t('admin_common.student'))
                    ->searchable(),

                TextColumn::make('user.company.name')
                    ->label(__t('admin_common.partner'))
                    ->badge()
                    ->default('—'),

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
            ])
            ->emptyStateHeading(__t('admin_courses.feedback_tab.empty'))
            ->emptyStateDescription(__t('admin_courses.feedback_tab.empty_description'))
            // Students write this, staff only read it.
            ->headerActions([])
            ->recordActions([])
            ->toolbarActions([]);
    }

    public function isReadOnly(): bool
    {
        return true;
    }

    /** @return class-string */
    protected function getModel(): string
    {
        return CourseFeedback::class;
    }
}
