<?php

namespace App\Http\Controllers;

use App\Models\ActivityEvent;
use App\Models\Course;
use App\Models\Lesson;
use App\Models\QuizAttempt;
use Illuminate\Http\Request;

class AcademyController extends Controller
{
    private const SESSION_KEY = 'completed_lessons';

    public function home(Request $request)
    {
        $courses = Course::where('is_published', true)
            ->withCount('publishedLessons')
            ->with('publishedLessons.mediaItem')
            ->orderBy('sort_order')
            ->get();

        return view('academy.home', [
            'courses' => $courses,
            'completed' => $this->completedIds($request),
        ]);
    }

    public function course(Request $request, Course $course)
    {
        abort_unless($course->is_published, 404);
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
        ]);
    }

    public function lesson(Request $request, Course $course, Lesson $lesson)
    {
        abort_unless($course->is_published && $lesson->is_published, 404);
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
            'finalUnlocked' => $course->finalQuizUnlockedFor($user),
            'certificate' => $user
                ? $course->certificates()->where('user_id', $user->id)->whereNull('revoked_at')->latest('issued_at')->first()
                : null,
        ]);
    }

    /**
     * Begin a timed/limited quiz attempt (logged-in students only).
     */
    public function startQuiz(Request $request, Course $course, Lesson $lesson)
    {
        abort_unless($lesson->course_id === $course->id, 404);
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

    public function setName(Request $request)
    {
        $data = $request->validate(['name' => 'required|string|max:60']);
        $request->session()->put('student_name', trim($data['name']));

        return redirect()->route('academy.home');
    }

    public function sitemap()
    {
        $courses = Course::where('is_published', true)
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
