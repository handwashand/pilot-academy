<?php

namespace App\Filament\Resources\Courses\Tables;

use App\Actions\DuplicateCourse;
use App\Actions\FindContentProblems;
use App\Models\Course;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class CoursesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('sort_order')
            ->reorderable('sort_order')
            ->columns([
                TextColumn::make('title')
                    ->label(__t('admin_common.title'))
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                // Flagged where the work happens, not only on Content health.
                TextColumn::make('attention')
                    ->label(__t('admin_common.attention'))
                    ->state(function (Course $record): ?string {
                        // course_ids: a problem in a shared lesson flags every course it is in.
                        $count = FindContentProblems::forCurrentUser()
                            ->filter(fn (array $problem): bool => in_array($record->id, $problem['course_ids'], true))
                            ->count();

                        return $count > 0 ? __tc('admin_common.problems', $count) : null;
                    })
                    ->badge()
                    ->color('danger')
                    ->icon('heroicon-m-exclamation-triangle')
                    ->tooltip(fn (Course $record): ?string => FindContentProblems::forCurrentUser()
                        ->filter(fn (array $problem): bool => in_array($record->id, $problem['course_ids'], true))
                        ->pluck('what')
                        ->unique()
                        ->implode(' · ') ?: null),

                TextColumn::make('product.name')
                    ->label(__t('admin_common.product'))
                    ->badge()
                    ->color('info')
                    ->placeholder('—')
                    ->sortable(),

                TextColumn::make('level')
                    ->label(__t('admin_common.level'))
                    ->badge()
                    ->formatStateUsing(fn (?string $state): ?string => $state ? (Course::levelLabels()[$state] ?? $state) : null)
                    ->colors([
                        'success' => 'beginner',
                        'warning' => 'intermediate',
                        'danger' => 'advanced',
                    ]),

                TextColumn::make('audience')
                    ->label(__t('admin_courses.table.for'))
                    ->badge()
                    ->formatStateUsing(fn (?string $state): ?string => $state ? (Course::audienceLabels()[$state] ?? $state) : null)
                    ->color('info')
                    ->placeholder('—'),

                TextColumn::make('lessons_count')
                    ->label(__t('admin_courses.table.lessons'))
                    ->counts('lessons')
                    ->badge(),

                TextColumn::make('status')
                    ->label(__t('admin_common.status'))
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => Course::statusLabels()[$state] ?? $state)
                    ->color(fn (string $state): string => match ($state) {
                        Course::STATUS_PUBLISHED => 'success',
                        Course::STATUS_ARCHIVED => 'gray',
                        default => 'warning',
                    })
                    ->sortable(),

                TextColumn::make('updated_at')
                    ->label(__t('admin_common.updated'))
                    ->since()
                    ->sortable(),
            ])
            ->filters([
                Filter::make('needs_attention')
                    ->label(__t('admin_common.needs_attention'))
                    ->query(fn (Builder $query): Builder => $query->whereIn(
                        'id',
                        FindContentProblems::forCurrentUser()->pluck('course_ids')->flatten()->unique()->values()->all(),
                    )),

                SelectFilter::make('status')
                    ->label(__t('admin_common.status'))
                    ->options(Course::statusLabels()),

                SelectFilter::make('product')
                    ->label(__t('admin_common.product'))
                    ->relationship('product', 'name')
                    ->searchable()
                    ->preload(),
            ])
            ->recordActions([
                Action::make('publish')
                    ->label(__t('admin_common.publish'))
                    ->icon('heroicon-o-rocket-launch')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading(__t('admin_courses.table.publish_heading'))
                    // Say what is wrong before anyone presses the button.
                    ->modalDescription(function (Course $record, FindContentProblems $find): string {
                        $problems = $find->forCourse($record);

                        return $problems->isEmpty()
                            ? __t('admin_courses.table.publish_ready', ['title' => $record->title])
                            : __t('admin_courses.table.publish_blocked', ['problems' => FindContentProblems::plainList($problems)]);
                    })
                    ->visible(fn (Course $record): bool => $record->status === Course::STATUS_DRAFT)
                    ->authorize(fn (Course $record): bool => auth()->user()->canManageCourse($record))
                    ->action(function (Course $record, FindContentProblems $find): void {
                        if (! $record->canBePublished()) {
                            Notification::make()
                                ->title(__t('admin_courses.table.add_lesson_first'))
                                ->body(__t('admin_courses.table.add_lesson_first_body'))
                                ->danger()
                                ->send();

                            return;
                        }

                        $problems = $find->forCourse($record);

                        if ($problems->isNotEmpty()) {
                            Notification::make()
                                ->title(__t('admin_common.fix_before_publishing'))
                                ->body(FindContentProblems::plainList($problems))
                                ->danger()
                                ->persistent()
                                ->send();

                            return;
                        }

                        $record->publish();

                        Notification::make()->title(__t('admin_courses.table.published'))->body(__t('admin_common.visible_now'))->success()->send();
                    }),

                Action::make('unpublish')
                    ->label(__t('admin_common.unpublish'))
                    ->icon('heroicon-o-eye-slash')
                    ->color('gray')
                    ->requiresConfirmation()
                    ->modalHeading(__t('admin_courses.table.unpublish_heading'))
                    ->modalDescription(__t('admin_courses.table.unpublish_description'))
                    ->visible(fn (Course $record): bool => $record->isPublished())
                    ->authorize(fn (Course $record): bool => auth()->user()->canManageCourse($record))
                    ->action(function (Course $record): void {
                        $record->unpublish();

                        Notification::make()->title(__t('admin_courses.table.unpublished'))->body(__t('admin_common.draft_again'))->warning()->send();
                    }),

                Action::make('duplicate')
                    ->label(__t('admin_courses.table.duplicate'))
                    ->icon('heroicon-o-document-duplicate')
                    ->color('gray')
                    ->requiresConfirmation()
                    ->modalHeading(__t('admin_courses.table.duplicate_heading'))
                    ->modalDescription(__t('admin_courses.table.duplicate_description'))
                    ->authorize(fn (Course $record): bool => auth()->user()->canManageCourse($record))
                    ->action(function (Course $record, DuplicateCourse $duplicate): void {
                        $copy = $duplicate->handle($record);

                        Notification::make()
                            ->title(__t('admin_courses.table.duplicated'))
                            ->body(__t('admin_courses.table.duplicated_body', ['title' => $copy->title]))
                            ->success()
                            ->send();
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
                        ->modalDescription(__t('admin_courses.table.bulk_publish_description'))
                        ->deselectRecordsAfterCompletion()
                        ->action(fn (Collection $records) => static::publishAll($records)),

                    BulkAction::make('unpublish')
                        ->label(__t('admin_common.unpublish'))
                        ->icon('heroicon-o-eye-slash')
                        ->color('gray')
                        ->requiresConfirmation()
                        ->modalDescription(__t('admin_courses.table.bulk_unpublish_description'))
                        ->deselectRecordsAfterCompletion()
                        ->action(function (Collection $records): void {
                            $records->each->unpublish();

                            Notification::make()
                                ->title(__tc('admin_courses.table.bulk_unpublished', $records->count()))
                                ->warning()
                                ->send();
                        }),

                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    /**
     * Publishing in bulk still respects the rules that protect students: an
     * empty course never goes live, and neither does one with something broken
     * in it. Skipped ones are named with the reason, because a bulk action that
     * silently does less than you asked is worse than one that refuses.
     */
    protected static function publishAll(Collection $records): void
    {
        $find = app(FindContentProblems::class);
        $skipped = [];

        $ready = $records->filter(function (Course $course) use ($find, &$skipped): bool {
            if (! $course->canBePublished()) {
                $skipped[] = __t('admin_courses.table.no_published_lesson', ['title' => $course->title]);

                return false;
            }

            $problems = $find->forCourse($course);

            if ($problems->isNotEmpty()) {
                $skipped[] = "{$course->title} ({$problems->pluck('what')->unique()->implode('; ')})";

                return false;
            }

            return true;
        });

        $ready->each->publish();

        if ($ready->isNotEmpty()) {
            Notification::make()->title(__tc('admin_courses.table.bulk_published', $ready->count()))->success()->send();
        }

        if ($skipped !== []) {
            Notification::make()
                ->title(__tc('admin_courses.table.bulk_skipped', count($skipped)))
                ->body(implode('; ', $skipped))
                ->danger()
                ->persistent()
                ->send();
        }
    }
}
