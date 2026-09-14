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
                    ->label('Student')
                    ->description(fn (QuizAttempt $record): ?string => $record->user?->email)
                    ->searchable(),

                TextColumn::make('user.company.name')
                    ->label('Partner')
                    ->badge()
                    ->placeholder('—'),

                TextColumn::make('quiz')
                    ->label('Quiz')
                    ->state(fn (QuizAttempt $record): string => static::quizName($record))
                    ->description(fn (QuizAttempt $record): ?string => $record->course_id
                        ? $record->course?->title
                        : $record->lesson?->course?->title),

                TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => QuizAttempt::STATUS_LABELS[$state] ?? $state)
                    ->color(fn (string $state): string => match ($state) {
                        QuizAttempt::STATUS_PASSED => 'success',
                        QuizAttempt::STATUS_FAILED => 'danger',
                        QuizAttempt::STATUS_EXPIRED => 'warning',
                        default => 'gray',
                    }),

                TextColumn::make('score')
                    ->label('Score')
                    ->state(fn (QuizAttempt $record): ?string => $record->total
                        ? "{$record->score}/{$record->total} ({$record->scorePercent()}%)"
                        : null)
                    ->placeholder('—'),

                TextColumn::make('submitted_at')
                    ->label('Submitted')
                    ->dateTime('d M Y, H:i')
                    ->sortable()
                    ->placeholder('Not submitted'),
            ])
            ->filters([
                Filter::make('out_of_attempts')
                    ->label('Out of attempts, not passed')
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
                    ->label('Quiz type')
                    ->options(['final' => 'Final quizzes', 'lesson' => 'Lesson knowledge checks'])
                    ->query(fn (Builder $query, array $data): Builder => match ($data['value'] ?? null) {
                        'final' => $query->whereNotNull('course_id'),
                        'lesson' => $query->whereNotNull('lesson_id'),
                        default => $query,
                    }),

                SelectFilter::make('course')
                    ->label('Course')
                    ->options(fn (): array => Course::query()->orderBy('title')->pluck('title', 'id')->all())
                    ->query(fn (Builder $query, array $data): Builder => $query->when(
                        $data['value'] ?? null,
                        fn (Builder $query, $courseId) => $query->where(fn (Builder $where) => $where
                            ->where('course_id', $courseId)
                            ->orWhereHas('lesson.courses', fn (Builder $courses) => $courses->whereKey($courseId))),
                    )),

                SelectFilter::make('partner')
                    ->label('Partner')
                    ->options(fn (): array => Company::query()->orderBy('name')->pluck('name', 'id')->all())
                    ->query(fn (Builder $query, array $data): Builder => $query->when(
                        $data['value'] ?? null,
                        fn (Builder $query, $companyId) => $query->whereHas('user', fn (Builder $user) => $user->where('company_id', $companyId)),
                    )),

                SelectFilter::make('status')
                    ->options(QuizAttempt::STATUS_LABELS),
            ])
            ->recordActions([
                Action::make('grantAttempt')
                    ->label('Grant another attempt')
                    ->icon('heroicon-o-arrow-path')
                    ->color('warning')
                    ->visible(fn (QuizAttempt $record): bool => $record->isStuck())
                    ->modalHeading('Grant another attempt')
                    ->modalDescription(fn (QuizAttempt $record): string => "{$record->user?->name} gets one more attempt at ".static::quizName($record)
                        .'. Nobody else is affected: Max attempts stays as it is for everyone else.')
                    ->schema([
                        TextInput::make('reason')
                            ->label('Reason (optional)')
                            ->placeholder('For example: the connection dropped during the quiz')
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
                            ->title('Another attempt granted')
                            ->body("{$record->user?->name} can try ".static::quizName($record).' once more.')
                            ->success()
                            ->send();
                    }),
            ])
            ->emptyStateHeading('No quiz attempts yet')
            ->emptyStateDescription('Attempts are recorded for final quizzes, and for lesson knowledge checks that have a time limit or a number of attempts.');
    }

    protected static function quizName(QuizAttempt $record): string
    {
        return $record->course_id
            ? 'the final quiz'
            : '"'.($record->lesson?->title ?? 'a lesson').'"';
    }
}
