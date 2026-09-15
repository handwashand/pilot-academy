<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class QuizAttempt extends Model
{
    public const STATUS_IN_PROGRESS = 'in_progress';

    public const STATUS_PASSED = 'passed';

    public const STATUS_FAILED = 'failed';

    public const STATUS_EXPIRED = 'expired';

    public const STATUS_LABELS = [
        self::STATUS_IN_PROGRESS => 'In progress',
        self::STATUS_PASSED => 'Passed',
        self::STATUS_FAILED => 'Failed',
        self::STATUS_EXPIRED => 'Time expired',
    ];

    /** @return array<string, string> The statuses in the reader's language (lang/{code}/labels.php). */
    public static function statusLabels(): array
    {
        return collect(self::STATUS_LABELS)
            ->mapWithKeys(fn (string $english, string $status): array => [$status => __t("labels.attempt_status.{$status}")])
            ->all();
    }

    protected $fillable = [
        'user_id',
        'lesson_id',
        'course_id',
        'question_ids',
        'status',
        'started_at',
        'submitted_at',
        'score',
        'total',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'submitted_at' => 'datetime',
        'question_ids' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function lesson(): BelongsTo
    {
        return $this->belongsTo(Lesson::class);
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    /** Percentage score (0-100), or null if not yet graded. */
    public function scorePercent(): ?int
    {
        if (! $this->total) {
            return null;
        }

        return (int) round($this->score / $this->total * 100);
    }

    /**
     * Learners out of attempts at a quiz they have not passed — the people an
     * admin has to decide about. One entry per student per quiz, with exactly
     * one of course_id (final quiz) and lesson_id (knowledge check) set.
     *
     * Remembered for the request, because the sidebar badge, the filter and
     * every table row ask; forgotten when an attempt is granted.
     *
     * @return Collection<int, array{user_id: int, course_id: ?int, lesson_id: ?int}>
     */
    public static function stuckLearners(): Collection
    {
        if (app()->bound('quiz-attempts.stuck')) {
            return app('quiz-attempts.stuck');
        }

        $learnerIds = User::query()->learners()->select('id');
        $grants = AttemptGrant::query()
            ->selectRaw('user_id, course_id, lesson_id, count(*) as total')
            ->groupBy('user_id', 'course_id', 'lesson_id')
            ->get()
            ->mapWithKeys(fn ($grant): array => [
                $grant->user_id.'-'.($grant->course_id ?? 0).'-'.($grant->lesson_id ?? 0) => (int) $grant->total,
            ]);

        $stuck = collect();

        // Final quizzes: passing issues a certificate, so either one means done.
        $courses = Course::query()
            ->where('final_quiz_enabled', true)
            ->where('final_quiz_max_attempts', '>', 0)
            ->pluck('final_quiz_max_attempts', 'id');

        if ($courses->isNotEmpty()) {
            $certified = Certificate::query()->whereNull('revoked_at')->whereIn('course_id', $courses->keys())
                ->get(['user_id', 'course_id'])
                ->mapWithKeys(fn (Certificate $certificate): array => [$certificate->user_id.'-'.$certificate->course_id => true]);

            static::query()
                ->whereIn('course_id', $courses->keys())
                ->whereIn('user_id', $learnerIds)
                ->whereIn('status', [self::STATUS_PASSED, self::STATUS_FAILED])
                ->selectRaw('user_id, course_id, count(*) as used, sum(case when status = ? then 1 else 0 end) as passed', [self::STATUS_PASSED])
                ->groupBy('user_id', 'course_id')
                ->get()
                ->each(function ($row) use ($courses, $certified, $grants, $stuck): void {
                    $userId = (int) $row->user_id;
                    $courseId = (int) $row->course_id;

                    if ((int) $row->passed > 0 || $certified->has("{$userId}-{$courseId}")) {
                        return;
                    }

                    $allowed = (int) $courses[$courseId] + $grants->get("{$userId}-{$courseId}-0", 0);

                    if ((int) $row->used >= $allowed) {
                        $stuck->push(['user_id' => $userId, 'course_id' => $courseId, 'lesson_id' => null]);
                    }
                });
        }

        // Lesson knowledge checks: a finished lesson is done whatever the attempts.
        $lessons = Lesson::query()->where('quiz_max_attempts', '>', 0)->pluck('quiz_max_attempts', 'id');

        if ($lessons->isNotEmpty()) {
            $finished = DB::table('lesson_user')->whereIn('lesson_id', $lessons->keys())
                ->get(['user_id', 'lesson_id'])
                ->mapWithKeys(fn ($row): array => [$row->user_id.'-'.$row->lesson_id => true]);

            static::query()
                ->whereIn('lesson_id', $lessons->keys())
                ->whereIn('user_id', $learnerIds)
                ->whereIn('status', [self::STATUS_PASSED, self::STATUS_FAILED, self::STATUS_EXPIRED])
                ->selectRaw('user_id, lesson_id, count(*) as used, sum(case when status = ? then 1 else 0 end) as passed', [self::STATUS_PASSED])
                ->groupBy('user_id', 'lesson_id')
                ->get()
                ->each(function ($row) use ($lessons, $finished, $grants, $stuck): void {
                    $userId = (int) $row->user_id;
                    $lessonId = (int) $row->lesson_id;

                    if ((int) $row->passed > 0 || $finished->has("{$userId}-{$lessonId}")) {
                        return;
                    }

                    $allowed = (int) $lessons[$lessonId] + $grants->get("{$userId}-0-{$lessonId}", 0);

                    if ((int) $row->used >= $allowed) {
                        $stuck->push(['user_id' => $userId, 'course_id' => null, 'lesson_id' => $lessonId]);
                    }
                });
        }

        app()->instance('quiz-attempts.stuck', $stuck);

        return $stuck;
    }

    public static function forgetStuckLearners(): void
    {
        app()->forgetInstance('quiz-attempts.stuck');
    }

    /** Is this attempt's student out of attempts at this quiz, without a pass? */
    public function isStuck(): bool
    {
        $userId = (int) $this->user_id;
        $courseId = $this->course_id ? (int) $this->course_id : null;
        $lessonId = $courseId === null && $this->lesson_id ? (int) $this->lesson_id : null;

        return static::stuckLearners()->contains(fn (array $pair): bool => $pair['user_id'] === $userId
            && $pair['course_id'] === $courseId
            && $pair['lesson_id'] === $lessonId);
    }
}
