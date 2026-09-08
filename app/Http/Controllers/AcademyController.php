<?php

namespace App\Http\Controllers;

use App\Models\ActivityEvent;
use App\Models\Course;
use App\Models\CourseFeedback;
use App\Models\Lesson;
use App\Models\QuizAttempt;
use App\Models\VideoPosition;
use Illuminate\Http\Request;

class AcademyController extends Controller
{
    private const SESSION_KEY = 'completed_lessons';

    public function home(Request $request)
    {
        $courses = Course::published()
            ->withCount('publishedLessons')
            ->with('publishedLessons.mediaItem')
            ->orderBy('sort_order')
            ->get();

        $completed = $this->completedIds($request);

        return view('academy.home', [
            'courses' => $courses,
            'completed' => $completed,
            'resume' => $this->nextLesson($courses, $completed),
        ]);
    }

    /**
     * The first unfinished lesson in the first course still in progress, so a
     * returning student can carry on without hunting for where they stopped.
     * Nothing is suggested to someone who has not started, or who is done.
     */
    private function nextLesson($courses, array $completed): ?array
    {
        if (empty($completed)) {
            return null;
        }

        foreach ($courses as $course) {
            $lessons = $course->publishedLessons;

            $hasStarted = $lessons->contains(fn (Lesson $lesson): bool => in_array($lesson->id, $completed, true));
            $next = $lessons->first(fn (Lesson $lesson): bool => ! in_array($lesson->id, $completed, true));

            if ($hasStarted && $next) {
                return [
                    'course' => $course,
                    'lesson' => $next,
                    'done' => $lessons->whereIn('id', $completed)->count(),
                    'total' => $lessons->count(),
                ];
            }
        }

        return null;
    }

    public function course(Request $request, Course $course)
    {
        abort_unless($course->isVisibleTo($request->user()), 404);
        $course->load('publishedLessons.mediaItem');

        ActivityEvent::record($request->user(), ActivityEvent::TYPE_COURSE_OPENED, $course->title, $request->path());

        $user = $request->user();

        return view('academy.course', [
            'course' => $course,
            'completed' => $this->completedIds($request),
            'finalUnlocked' => $course->finalQuizUnlockedFor($user),
            'certificate' => $user
                ? $course->certificates()->where('user_id', $user->id)->whereNull('revoked_at')->latest('issued_at')->first()
                : null,
            // Somewhere to go once the course is finished, so completing it is
            // not a dead end. Null on the last course, which is fine.
            'nextCourse' => Course::published()
                ->where('sort_order', '>', $course->sort_order)
                ->orderBy('sort_order')
                ->first(),
            // Their own verdict, so the form shows what they already said
            // rather than asking twice.
            'feedback' => $user
                ? CourseFeedback::where('user_id', $user->id)->where('course_id', $course->id)->first()
                : null,
        ]);
    }

    public function lesson(Request $request, Course $course, Lesson $lesson)
    {
        abort_unless($course->isVisibleTo($request->user()) && $lesson->isVisibleTo($request->user()), 404);
        abort_unless($lesson->course_id === $course->id, 404);

        $lesson->load('questions.options');
        $lessons = $course->publishedLessons()->get();
        $currentIndex = $lessons->search(fn ($l) => $l->id === $lesson->id);
        $next = $currentIndex !== false ? $lessons->get($currentIndex + 1) : null;
        $prev = $currentIndex !== false && $currentIndex > 0 ? $lessons->get($currentIndex - 1) : null;

        ActivityEvent::record($request->user(), ActivityEvent::TYPE_LESSON_OPENED, $lesson->title, $request->path());

        $user = $request->user();

        return view('academy.lesson', [
            'course' => $course,
            'lesson' => $lesson,
            'lessons' => $lessons,
            'next' => $next,
            'prev' => $prev,
            'completed' => $this->completedIds($request),
            'quiz' => $this->quizState($request, $lesson),
            // Where they got to last time, so a 25-minute video does not start
            // from zero. Anonymous visitors have nowhere to keep this.
            'videoPosition' => $user
                ? (int) VideoPosition::where('user_id', $user->id)->where('lesson_id', $lesson->id)->value('seconds')
                : 0,
            'finalUnlocked' => $course->finalQuizUnlockedFor($user),
            'certificate' => $user
                ? $course->certificates()->where('user_id', $user->id)->whereNull('revoked_at')->latest('issued_at')->first()
                : null,
        ]);
    }

    /**
     * Remember where a student got to inside a lesson video. Sent every few
     * seconds while playing, so it stays a single upserted row per lesson.
     */
    public function saveVideoPosition(Request $request, Course $course, Lesson $lesson)
    {
        abort_unless($lesson->course_id === $course->id, 404);
        abort_unless($course->isVisibleTo($request->user()) && $lesson->isVisibleTo($request->user()), 404);

        $data = $request->validate([
            'seconds' => ['required', 'integer', 'min:0', 'max:86400'],
        ]);

        VideoPosition::updateOrCreate(
            ['user_id' => $request->user()->id, 'lesson_id' => $lesson->id],
            ['seconds' => $data['seconds']],
        );

        return response()->noContent();
    }

    /**
     * What a student thought of a course. Asked only once they have finished it,
     * and never shown to other students.
     */
    public function saveFeedback(Request $request, Course $course)
    {
        abort_unless($course->isVisibleTo($request->user()), 404);

        $user = $request->user();

        // Nothing to say about a course you have not taken.
        abort_unless($course->isCompletedBy($user), 403);

        $data = $request->validate([
            'is_positive' => ['required', 'boolean'],
            'comment' => ['nullable', 'string', 'max:1000'],
        ]);

        CourseFeedback::updateOrCreate(
            ['user_id' => $user->id, 'course_id' => $course->id],
            ['is_positive' => $request->boolean('is_positive'), 'comment' => $data['comment'] ?? null],
        );

        return redirect()->route('academy.course', $course)->with('feedback_saved', true);
    }

    /**
     * Begin a timed/limited quiz attempt (logged-in students only).
     */
    public function startQuiz(Request $request, Course $course, Lesson $lesson)
    {
        abort_unless($lesson->course_id === $course->id, 404);
        abort_unless($course->isVisibleTo($request->user()) && $lesson->isVisibleTo($request->user()), 404);
        $user = $request->user();
        abort_unless($user && $lesson->hasQuizLimits(), 403);

        $state = $this->quizState($request, $lesson);

        // Only start when allowed: not passed, not exhausted, not already running.
        if (! in_array($lesson->id, $this->completedIds($request), true)
            && in_array($state['mode'], ['prestart'], true)
        ) {
            QuizAttempt::create([
                'user_id' => $user->id,
                'lesson_id' => $lesson->id,
                'status' => QuizAttempt::STATUS_IN_PROGRESS,
                'started_at' => now(),
            ]);
        }

        return redirect()->route('academy.lesson', [$course, $lesson])->withFragment('quiz');
    }

    public function submitQuiz(Request $request, Course $course, Lesson $lesson)
    {
        abort_unless($lesson->course_id === $course->id, 404);
        abort_unless($course->isVisibleTo($request->user()) && $lesson->isVisibleTo($request->user()), 404);
        $lesson->load('questions.options');

        $user = $request->user();
        $limited = $user && $lesson->hasQuizLimits();

        [$allCorrect, $results, $score, $total] = $this->gradeAnswers($request, $lesson);

        if ($limited) {
            $attempt = QuizAttempt::where('user_id', $user->id)
                ->where('lesson_id', $lesson->id)
                ->where('status', QuizAttempt::STATUS_IN_PROGRESS)
                ->latest()
                ->first();

            // No active attempt (e.g. already finalized) — send back to the start screen.
            if (! $attempt) {
                return redirect()->route('academy.lesson', [$course, $lesson])->withFragment('quiz');
            }

            // Server-side time check (authoritative), with a small grace period.
            $timedOut = $lesson->quiz_time_limit_minutes
                && (int) $attempt->started_at->diffInSeconds(now()) > $lesson->quiz_time_limit_minutes * 60 + 3;

            if ($timedOut) {
                $attempt->update(['status' => QuizAttempt::STATUS_EXPIRED, 'submitted_at' => now(), 'score' => $score, 'total' => $total]);

                return redirect()->route('academy.lesson', [$course, $lesson])->with('quiz_timeup', true)->withFragment('quiz');
            }

            if ($allCorrect) {
                $attempt->update(['status' => QuizAttempt::STATUS_PASSED, 'submitted_at' => now(), 'score' => $score, 'total' => $total]);
                $this->completeLesson($request, $course, $lesson);

                return redirect()->route('academy.lesson', [$course, $lesson])->with('quiz_passed', true)->withFragment('quiz');
            }

            $attempt->update(['status' => QuizAttempt::STATUS_FAILED, 'submitted_at' => now(), 'score' => $score, 'total' => $total]);

            return redirect()->route('academy.lesson', [$course, $lesson])
                ->with('quiz_failed', true)
                ->with('quiz_score', "{$score}/{$total}")
                ->withFragment('quiz');
        }

        // Open mode (no limits, or anonymous): instant feedback, retry in place.
        if ($allCorrect) {
            $this->completeLesson($request, $course, $lesson);

            return redirect()->route('academy.lesson', [$course, $lesson])->with('quiz_passed', true)->withFragment('quiz');
        }

        return redirect()->route('academy.lesson', [$course, $lesson])
            ->withInput()
            ->with('quiz_results', $results)
            ->with('quiz_failed', true)
            ->withFragment('quiz');
    }

    /** Grade submitted answers. Returns [allCorrect, perQuestionResults, score, total]. */
    private function gradeAnswers(Request $request, Lesson $lesson): array
    {
        $answers = $request->input('answers', []);
        $results = [];
        $score = 0;
        $total = $lesson->questions->count();
        $allCorrect = $lesson->questions->isNotEmpty();

        foreach ($lesson->questions as $question) {
            $raw = $answers[$question->id] ?? [];
            $chosen = is_array($raw) ? $raw : [$raw];
            $isCorrect = $question->isAnsweredCorrectly($chosen);
            $results[$question->id] = $isCorrect;
            if ($isCorrect) {
                $score++;
            } else {
                $allCorrect = false;
            }
        }

        return [$allCorrect, $results, $score, $total];
    }

    /** Mark a lesson complete and log completion activity (lesson + course). */
    private function completeLesson(Request $request, Course $course, Lesson $lesson): void
    {
        $user = $request->user();
        $isNewCompletion = ! in_array($lesson->id, $this->completedIds($request), true);

        $this->markCompleted($request, $lesson->id);

        if ($user && $isNewCompletion) {
            ActivityEvent::record($user, ActivityEvent::TYPE_LESSON_COMPLETED, $lesson->title, $request->path());

            if ($course->isCompletedBy($user)
                && ! $user->activities()
                    ->where('type', ActivityEvent::TYPE_COURSE_COMPLETED)
                    ->where('label', $course->title)
                    ->exists()
            ) {
                ActivityEvent::record($user, ActivityEvent::TYPE_COURSE_COMPLETED, $course->title);
            }
        }
    }

    /**
     * Quiz access state for the lesson view.
     * mode: open (no limits / anonymous) | prestart | active | exhausted
     */
    private function quizState(Request $request, Lesson $lesson): array
    {
        $user = $request->user();

        if (! $lesson->hasQuizLimits() || ! $user) {
            return ['mode' => 'open'];
        }

        $timeLimit = $lesson->quiz_time_limit_minutes;
        $maxAttempts = $lesson->quiz_max_attempts;

        $base = QuizAttempt::where('user_id', $user->id)->where('lesson_id', $lesson->id);
        $used = (clone $base)->whereIn('status', [
            QuizAttempt::STATUS_PASSED,
            QuizAttempt::STATUS_FAILED,
            QuizAttempt::STATUS_EXPIRED,
        ])->count();
        $inProgress = (clone $base)->where('status', QuizAttempt::STATUS_IN_PROGRESS)->latest()->first();
        $attemptsRemaining = $maxAttempts ? max(0, $maxAttempts - $used) : null;

        if ($inProgress) {
            $secondsRemaining = null;
            if ($timeLimit) {
                $elapsed = (int) $inProgress->started_at->diffInSeconds(now());
                $secondsRemaining = max(0, $timeLimit * 60 - $elapsed);
            }

            return [
                'mode' => 'active',
                'timeLimit' => $timeLimit,
                'maxAttempts' => $maxAttempts,
                'attemptsUsed' => $used,
                'attemptsRemaining' => $attemptsRemaining,
                'secondsRemaining' => $secondsRemaining,
            ];
        }

        if ($maxAttempts && $used >= $maxAttempts) {
            return [
                'mode' => 'exhausted',
                'maxAttempts' => $maxAttempts,
                'attemptsUsed' => $used,
                'attemptsRemaining' => 0,
            ];
        }

        return [
            'mode' => 'prestart',
            'timeLimit' => $timeLimit,
            'maxAttempts' => $maxAttempts,
            'attemptsUsed' => $used,
            'attemptsRemaining' => $attemptsRemaining,
        ];
    }

    /**
     * Student-facing search. Deliberately a LIKE over titles and summaries:
     * this is dozens of rows, not thousands, so an index or a search engine
     * would be machinery with nothing to do.
     *
     * Only published lessons in published courses are ever returned — a draft
     * must not surface here just because someone guessed a word in its title.
     */
    public function search(Request $request)
    {
        $term = trim((string) $request->query('q', ''));
        $courses = collect();
        $lessons = collect();

        if ($term !== '') {
            // LOWER() on both sides rather than a bare LIKE: SQLite matches
            // case-insensitively, PostgreSQL does not, and the move to
            // PostgreSQL is already written.
            $like = '%'.mb_strtolower($term).'%';

            $courses = Course::published()
                ->where(fn ($query) => $query
                    ->whereRaw('LOWER(title) LIKE ?', [$like])
                    ->orWhereRaw('LOWER(description) LIKE ?', [$like]))
                ->orderBy('sort_order')
                ->limit(20)
                ->get();

            $lessons = Lesson::published()
                ->whereHas('course', fn ($query) => $query->published())
                ->where(fn ($query) => $query
                    ->whereRaw('LOWER(lessons.title) LIKE ?', [$like])
                    ->orWhereRaw('LOWER(lessons.summary) LIKE ?', [$like])
                    // The transcript is what makes a video findable at all —
                    // until it existed, spoken content matched nothing.
                    ->orWhereRaw('LOWER(lessons.transcript) LIKE ?', [$like]))
                ->with('course')
                ->orderBy('sort_order')
                ->limit(30)
                ->get();
        }

        return view('academy.search', [
            'term' => $term,
            'courses' => $courses,
            'lessons' => $lessons,
        ]);
    }

    public function setName(Request $request)
    {
        $data = $request->validate(['name' => 'required|string|max:60']);
        $request->session()->put('student_name', trim($data['name']));

        return redirect()->route('academy.home');
    }

    public function sitemap()
    {
        $courses = Course::published()
            ->with(['publishedLessons' => fn ($query) => $query->orderBy('sort_order')])
            ->orderBy('sort_order')
            ->get();

        return response()
            ->view('sitemap', ['courses' => $courses])
            ->header('Content-Type', 'application/xml');
    }

    /**
     * Completed lesson ids — from the user's account when logged in,
     * otherwise from the session (open / anonymous mode).
     */
    private function completedIds(Request $request): array
    {
        if ($user = $request->user()) {
            return $user->completedLessons()->pluck('lessons.id')->all();
        }

        return $request->session()->get(self::SESSION_KEY, []);
    }

    private function markCompleted(Request $request, int $lessonId): void
    {
        if ($user = $request->user()) {
            $user->completedLessons()->syncWithoutDetaching([
                $lessonId => ['completed_at' => now()],
            ]);

            return;
        }

        $ids = $this->completedIds($request);
        if (! in_array($lessonId, $ids, true)) {
            $ids[] = $lessonId;
            $request->session()->put(self::SESSION_KEY, $ids);
        }
    }
}
