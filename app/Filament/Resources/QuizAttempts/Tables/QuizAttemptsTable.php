<?php

namespace App\Filament\Resources\QuizAttempts\Tables;

use App\Models\AttemptGrant;
use App\Models\Company;
use App\Models\Course;
use App\Models\QuizAttempt;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class QuizAttemptsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('started_at', 'desc')
            ->columns([
                TextColumn::make('user.name')
                    ->label(__t('admin_common.student'))
                    ->description(fn (QuizAttempt $record): ?string => $record->user?->email)
                    ->searchable(),

                TextColumn::make('user.company.name')
                    ->label(__t('admin_common.partner'))
                    ->badge()
                    ->placeholder('—'),

                TextColumn::make('quiz')
                    ->label(__t('admin_results.attempts.quiz'))
                    ->state(fn (QuizAttempt $record): string => static::quizName($record))
                    ->description(fn (QuizAttempt $record): ?string => $record->course_id
                        ? $record->course?->title
                        : $record->lesson?->course?->title),

                TextColumn::make('status')
                    ->label(__t('admin_common.status'))
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => QuizAttempt::statusLabels()[$state] ?? $state)
                    ->color(fn (string $state): string => match ($state) {
                        QuizAttempt::STATUS_PASSED => 'success',
                        QuizAttempt::STATUS_FAILED => 'danger',
                        QuizAttempt::STATUS_EXPIRED => 'warning',
                        default => 'gray',
                    }),

                TextColumn::make('score')
                    ->label(__t('admin_common.score'))
                    ->state(fn (QuizAttempt $record): ?string => $record->total
                        ? "{$record->score}/{$record->total} ({$record->scorePercent()}%)"
                        : null)
                    ->placeholder('—'),

                TextColumn::make('submitted_at')
                    ->label(__t('admin_common.submitted'))
                    ->dateTime('d M Y, H:i')
                    ->sortable()
                    ->placeholder(__t('admin_results.attempts.not_submitted')),
            ])
            ->filters([
                Filter::make('out_of_attempts')
                    ->label(__t('admin_results.attempts.out_of_attempts'))
                    ->query(fn (Builder $query): Builder => $query->where(function (Builder $where): void {
                        $stuck = QuizAttempt::stuckLearners();

                        if ($stuck->isEmpty()) {
                            $where->whereRaw('1 = 0');

                            return;
                        }

                        foreach ($stuck as $pair) {
                            $where->orWhere(fn (Builder $one) => $one
                                ->where('user_id', $pair['user_id'])
                                ->where($pair['course_id'] ? 'course_id' : 'lesson_id', $pair['course_id'] ?? $pair['lesson_id']));
                        }
                    })),

                SelectFilter::make('kind')
                    ->label(__t('admin_results.attempts.quiz_type'))
                    ->options(['final' => __t('admin_results.attempts.final_quizzes'), 'lesson' => __t('admin_results.attempts.lesson_checks')])
                    ->query(fn (Builder $query, array $data): Builder => match ($data['value'] ?? null) {
                        'final' => $query->whereNotNull('course_id'),
                        'lesson' => $query->whereNotNull('lesson_id'),
                        default => $query,
                    }),

                SelectFilter::make('course')
                    ->label(__t('admin_common.course'))
                    ->options(fn (): array => Course::query()->orderBy('title')->pluck('title', 'id')->all())
                    ->query(fn (Builder $query, array $data): Builder => $query->when(
                        $data['value'] ?? null,
                        fn (Builder $query, $courseId) => $query->where(fn (Builder $where) => $where
                            ->where('course_id', $courseId)
                            ->orWhereHas('lesson.courses', fn (Builder $courses) => $courses->whereKey($courseId))),
                    )),

                SelectFilter::make('partner')
                    ->label(__t('admin_common.partner'))
                    ->options(fn (): array => Company::query()->orderBy('name')->pluck('name', 'id')->all())
                    ->query(fn (Builder $query, array $data): Builder => $query->when(
                        $data['value'] ?? null,
                        fn (Builder $query, $companyId) => $query->whereHas('user', fn (Builder $user) => $user->where('company_id', $companyId)),
                    )),

                SelectFilter::make('status')
                    ->label(__t('admin_common.status'))
                    ->options(QuizAttempt::statusLabels()),
            ])
            ->recordActions([
                Action::make('grantAttempt')
                    ->label(__t('admin_results.attempts.grant'))
                    ->icon('heroicon-o-arrow-path')
                    ->color('warning')
                    ->visible(fn (QuizAttempt $record): bool => $record->isStuck())
                    ->modalHeading(__t('admin_results.attempts.grant'))
                    ->modalDescription(fn (QuizAttempt $record): string => __t('admin_results.attempts.grant_description', [
                        'name' => (string) $record->user?->name,
                        'quiz' => static::quizName($record),
                    ]))
                    ->schema([
                        TextInput::make('reason')
                            ->label(__t('admin_results.attempts.reason'))
                            ->placeholder(__t('admin_results.attempts.reason_placeholder'))
                            ->maxLength(255),
                    ])
                    ->action(function (QuizAttempt $record, array $data): void {
                        AttemptGrant::create([
                            'user_id' => $record->user_id,
                            'course_id' => $record->course_id,
                            'lesson_id' => $record->course_id ? null : $record->lesson_id,
                            'granted_by' => auth()->id(),
                            'reason' => filled($data['reason'] ?? null) ? trim($data['reason']) : null,
                        ]);

                        QuizAttempt::forgetStuckLearners();

                        Notification::make()
                            ->title(__t('admin_results.attempts.granted'))
                            ->body(__t('admin_results.attempts.granted_body', [
                                'name' => (string) $record->user?->name,
                                'quiz' => static::quizName($record),
                            ]))
                            ->success()
                            ->send();
                    }),
            ])
            ->emptyStateHeading(__t('admin_results.attempts.empty'))
            ->emptyStateDescription(__t('admin_results.attempts.empty_description'));
    }

    protected static function quizName(QuizAttempt $record): string
    {
        return $record->course_id
            ? __t('admin_results.attempts.final_quiz')
            : '"'.($record->lesson?->title ?? __t('admin_results.attempts.a_lesson')).'"';
    }
}
