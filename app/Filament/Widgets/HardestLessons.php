<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\Lessons\LessonResource;
use App\Filament\Widgets\Concerns\ReportsOnLearners;
use App\Models\Lesson;
use App\Models\QuizAttempt;
use Filament\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Support\Facades\DB;

/**
 * Lessons students keep failing, worst first.
 *
 * A high fail rate usually means the question is ambiguous rather than the
 * students are weak, so this is really a list of questions worth rereading.
 * Attempts have been recorded since the quiz limits shipped; nothing has ever
 * shown them.
 */
class HardestLessons extends TableWidget
{
    use ReportsOnLearners;

    protected static ?int $sort = 4;

    protected int|string|array $columnSpan = 'full';

    /** Below this, a fail rate is noise rather than a signal. */
    private const MIN_ATTEMPTS = 3;

    public function table(Table $table): Table
    {
        return $table
            ->heading('Lessons students struggle with')
            ->description('Graded attempts by students, worst pass rate first. A hard lesson is often an unclear question.')
            ->query($this->hardestLessons())
            ->defaultSort('fail_rate', 'desc')
            ->columns([
                TextColumn::make('title')
                    ->label('Lesson')
                    ->weight('bold')
                    ->description(fn (Lesson $record): ?string => $record->course?->title),

                TextColumn::make('attempts')
                    ->label('Attempts')
                    ->badge()
                    ->color('gray'),

                TextColumn::make('failed')
                    ->label('Failed')
                    ->badge()
                    ->color('danger'),

                TextColumn::make('fail_rate')
                    ->label('Fail rate')
                    ->badge()
                    ->formatStateUsing(fn ($state): string => round((float) $state).'%')
                    ->color(fn ($state): string => match (true) {
                        (float) $state >= 60 => 'danger',
                        (float) $state >= 30 => 'warning',
                        default => 'success',
                    })
                    ->sortable(),
            ])
            ->recordActions([
                Action::make('open')
                    ->label('Review questions')
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->url(fn (Lesson $record): string => LessonResource::getUrl('edit', ['record' => $record])),
            ])
            ->paginated([5, 10, 25])
            ->defaultPaginationPageOption(5)
            ->emptyStateHeading('No struggles to report')
            ->emptyStateDescription('Once students have made a few graded attempts, the toughest lessons show up here.');
    }

    private function hardestLessons()
    {
        // One grouped aggregate over the attempts, joined back onto lessons —
        // not a count per lesson.
        $stats = $this->scopeToLearners(
            DB::table('quiz_attempts')
                ->whereNotNull('lesson_id')
                // in_progress attempts have no outcome yet and would drag every
                // rate down; only graded ones count.
                ->whereIn('status', [
                    QuizAttempt::STATUS_PASSED,
                    QuizAttempt::STATUS_FAILED,
                    QuizAttempt::STATUS_EXPIRED,
                ])
                ->groupBy('lesson_id')
                ->havingRaw('count(*) >= '.self::MIN_ATTEMPTS)
                ->select('lesson_id')
                ->selectRaw('count(*) as attempts')
                ->selectRaw("sum(case when status = '".QuizAttempt::STATUS_PASSED."' then 0 else 1 end) as failed"),
        );

        return Lesson::query()
            ->with('course:id,title')
            ->joinSub($stats, 'stats', fn ($join) => $join->on('stats.lesson_id', '=', 'lessons.id'))
            ->select('lessons.*', 'stats.attempts', 'stats.failed')
            ->selectRaw('(stats.failed * 100.0) / stats.attempts as fail_rate');
    }
}
