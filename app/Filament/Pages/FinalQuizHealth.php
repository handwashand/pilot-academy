<?php

namespace App\Filament\Pages;

use App\Models\Certificate;
use App\Models\Course;
use App\Models\QuizAttempt;
use App\Models\User;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use UnitEnum;

/**
 * Is the final quiz measuring what it should?
 *
 * Modelled on Support Training Hub's "Success metrics": one card per measure,
 * each with its value, what it is judged against, what the number means, and
 * how many results it rests on — and an honest "no data" or "not measured"
 * instead of a missing card. A certificate is a public claim; these say whether
 * the quiz behind it is too easy, too hard, or fine.
 *
 * Learners only: staff previewing a final quiz earn real attempts and
 * certificates, and would skew every figure here.
 */
class FinalQuizHealth extends Page
{
    /**
     * First-time pass rate band, in percent. Borrowed from Support Training
     * Hub, where it was agreed for their exam; nobody has agreed one for this
     * academy yet, so the page labels it as suggested. Change it here.
     */
    public const PASS_BAND = [65, 80];

    /** Below this many results a band verdict is noise, and the page says so. */
    public const SMALL_SAMPLE = 10;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChartBar;

    protected static ?string $navigationLabel = 'Final quiz health';

    protected static ?string $title = 'Final quiz health';

    protected static string|UnitEnum|null $navigationGroup = 'Results';

    protected static ?int $navigationSort = 2;

    protected string $view = 'filament.pages.final-quiz-health';

    /** Learner results — admins only, like every learner report in the panel. */
    public static function canAccess(): bool
    {
        return (bool) auth()->user()?->isAdmin();
    }

    /**
     * Each learner's first submitted final quiz attempt per course.
     *
     * @return Collection<int, QuizAttempt>
     */
    public function firstAttempts(): Collection
    {
        return QuizAttempt::query()
            ->whereNotNull('course_id')
            ->whereNotNull('submitted_at')
            ->whereIn('status', [QuizAttempt::STATUS_PASSED, QuizAttempt::STATUS_FAILED])
            ->whereIn('user_id', User::query()->learners()->select('id'))
            ->orderBy('submitted_at')
            ->orderBy('id')
            ->get(['id', 'user_id', 'course_id', 'status'])
            ->unique(fn (QuizAttempt $attempt): string => $attempt->user_id.'-'.$attempt->course_id)
            ->values();
    }

    /** @return array{sample: int, passed: int, rate: ?int, status: array{label: string, color: string, note: string}} */
    public function firstTimePassRate(): array
    {
        return static::summarise($this->firstAttempts());
    }

    /** @return array<int, array{course: string, sample: int, passed: int, rate: ?int, status: array{label: string, color: string, note: string}}> */
    public function firstTimePassRateByCourse(): array
    {
        $titles = Course::query()->pluck('title', 'id');

        return $this->firstAttempts()
            ->groupBy('course_id')
            ->map(fn (Collection $attempts, $courseId): array => [
                'course' => $titles[$courseId] ?? 'Deleted course',
                ...static::summarise($attempts),
            ])
            ->sortBy('course')
            ->values()
            ->all();
    }

    /**
     * Days from a learner's first finished lesson in a course to their
     * certificate for it. No target: nobody has set one, and the page does not
     * invent one.
     *
     * @return array{sample: int, median: ?int, fastest: ?int, slowest: ?int}
     */
    public function daysToCertificate(): array
    {
        $certificates = Certificate::query()
            ->whereNull('revoked_at')
            ->whereIn('user_id', User::query()->learners()->select('id'))
            ->get(['user_id', 'course_id', 'issued_at']);

        $firstLesson = DB::table('lesson_user')
            ->join('lessons', 'lessons.id', '=', 'lesson_user.lesson_id')
            ->whereNotNull('lesson_user.completed_at')
            ->whereIn('lesson_user.user_id', $certificates->pluck('user_id')->unique())
            ->groupBy('lesson_user.user_id', 'lessons.course_id')
            ->selectRaw('lesson_user.user_id as user_id, lessons.course_id as course_id, min(lesson_user.completed_at) as first_at')
            ->get()
            ->keyBy(fn ($row): string => $row->user_id.'-'.$row->course_id);

        $days = $certificates
            ->map(function (Certificate $certificate) use ($firstLesson): ?int {
                $first = $firstLesson[$certificate->user_id.'-'.$certificate->course_id]->first_at ?? null;

                return $first && $certificate->issued_at
                    ? max(0, (int) floor(Carbon::parse($first)->diffInDays($certificate->issued_at)))
                    : null;
            })
            ->filter(fn (?int $value): bool => $value !== null)
            ->sort()
            ->values();

        return [
            'sample' => $days->count(),
            'median' => $days->isEmpty() ? null : (int) round($days->median()),
            'fastest' => $days->first(),
            'slowest' => $days->last(),
        ];
    }

    /** @return array{sample: int, passed: int, rate: ?int, status: array{label: string, color: string, note: string}} */
    private static function summarise(Collection $attempts): array
    {
        $sample = $attempts->count();
        $passed = $attempts->where('status', QuizAttempt::STATUS_PASSED)->count();
        $rate = $sample > 0 ? (int) round($passed / $sample * 100) : null;

        return [
            'sample' => $sample,
            'passed' => $passed,
            'rate' => $rate,
            'status' => static::passRateStatus($rate, $sample),
        ];
    }

    /**
     * What a first-time pass rate means. Both ends of the band are findings:
     * a quiz almost everyone passes first time certifies little, and one most
     * people fail usually tests something the lessons did not teach.
     *
     * @return array{label: string, color: string, note: string}
     */
    public static function passRateStatus(?int $rate, int $sample): array
    {
        [$low, $high] = static::PASS_BAND;

        return match (true) {
            $sample === 0 => ['label' => 'No data yet', 'color' => 'gray', 'note' => 'No learner has sat a final quiz yet.'],
            $sample < static::SMALL_SAMPLE => ['label' => 'Too few to judge', 'color' => 'gray', 'note' => "Only {$sample} first ".($sample === 1 ? 'attempt' : 'attempts').' so far. Read the band once there are at least '.static::SMALL_SAMPLE.'.'],
            $rate > $high => ['label' => 'Above the band', 'color' => 'warning', 'note' => 'The quiz is likely too easy: passing it first time says little about what someone knows.'],
            $rate < $low => ['label' => 'Below the band', 'color' => 'danger', 'note' => 'The lessons probably do not teach what the quiz tests — or some questions are unclear.'],
            default => ['label' => 'Within the band', 'color' => 'success', 'note' => 'First-time passes are where they should be.'],
        };
    }
}
