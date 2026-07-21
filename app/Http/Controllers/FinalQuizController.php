<?php

namespace App\Http\Controllers;

use App\Actions\IssueCertificate;
use App\Models\Certificate;
use App\Models\Course;
use App\Models\Question;
use App\Models\QuizAttempt;
use Illuminate\Http\Request;

class FinalQuizController extends Controller
{
    /** Final quiz landing / active attempt / result screen. */
    public function show(Request $request, Course $course)
    {
        abort_unless($course->is_published && $course->final_quiz_enabled, 404);

        $user = $request->user();
        $state = $this->state($request, $course, $user);

        return view('academy.final', [
            'course' => $course,
            'state' => $state,
        ]);
    }

    /** Begin an attempt: confirm the certificate name and draw a fresh question set. */
    public function start(Request $request, Course $course)
    {
        abort_unless($course->is_published && $course->final_quiz_enabled, 404);

        $user = $request->user();
        $state = $this->state($request, $course, $user);

        // Only start from a clean pre-start state.
        if ($state['mode'] !== 'prestart') {
            return redirect()->route('academy.final.show', $course);
        }

        $data = $request->validate([
            'certificate_name' => ['required', 'string', 'max:255'],
        ]);

        $user->forceFill(['certificate_name' => trim($data['certificate_name'])])->save();

        $questionIds = $this->drawQuestions($course);

        if (empty($questionIds)) {
            return redirect()->route('academy.final.show', $course);
        }

        QuizAttempt::create([
            'user_id' => $user->id,
            'course_id' => $course->id,
            'question_ids' => $questionIds,
            'status' => QuizAttempt::STATUS_IN_PROGRESS,
            'started_at' => now(),
        ]);

        return redirect()->route('academy.final.show', $course);
    }

    /** Grade an in-progress attempt and, on pass, issue the certificate. */
    public function submit(Request $request, Course $course, IssueCertificate $issue)
    {
        abort_unless($course->is_published && $course->final_quiz_enabled, 404);

        $user = $request->user();

        $attempt = QuizAttempt::where('user_id', $user->id)
            ->where('course_id', $course->id)
            ->where('status', QuizAttempt::STATUS_IN_PROGRESS)
            ->latest()
            ->first();

        if (! $attempt) {
            return redirect()->route('academy.final.show', $course);
        }

        $questions = $this->attemptQuestions($attempt);
        $answers = $request->input('answers', []);

        $score = 0;
        $total = $questions->count();

        foreach ($questions as $question) {
            $raw = $answers[$question->id] ?? [];
            $chosen = is_array($raw) ? $raw : [$raw];
            if ($question->isAnsweredCorrectly($chosen)) {
                $score++;
            }
        }

        $percent = $total > 0 ? (int) round($score / $total * 100) : 0;
        $passed = $percent >= $course->pass_percent;

        $attempt->update([
            'status' => $passed ? QuizAttempt::STATUS_PASSED : QuizAttempt::STATUS_FAILED,
            'submitted_at' => now(),
            'score' => $score,
            'total' => $total,
        ]);

        if ($passed) {
            $issue->handle($user, $course, $attempt);
        }

        return redirect()->route('academy.final.show', $course)->with('final_result', [
            'passed' => $passed,
            'score' => $score,
            'total' => $total,
            'percent' => $percent,
        ]);
    }

    /**
     * Resolve the current final-quiz state for the view.
     * mode: unavailable | locked | passed | active | exhausted | prestart
     */
    private function state(Request $request, Course $course, $user): array
    {
        $bankCount = $course->finalQuestions()->count();

        if ($bankCount === 0) {
            return ['mode' => 'unavailable'];
        }

        $certificate = Certificate::where('user_id', $user->id)
            ->where('course_id', $course->id)
            ->whereNull('revoked_at')
            ->latest('issued_at')
            ->first();

        if ($certificate) {
            return ['mode' => 'passed', 'certificate' => $certificate];
        }

        if (! $course->isCompletedBy($user)) {
            return ['mode' => 'locked'];
        }

        $maxAttempts = $course->final_quiz_max_attempts;
        $used = QuizAttempt::where('user_id', $user->id)
            ->where('course_id', $course->id)
            ->whereIn('status', [QuizAttempt::STATUS_PASSED, QuizAttempt::STATUS_FAILED])
            ->count();
        $attemptsRemaining = $maxAttempts ? max(0, $maxAttempts - $used) : null;

        $inProgress = QuizAttempt::where('user_id', $user->id)
            ->where('course_id', $course->id)
            ->where('status', QuizAttempt::STATUS_IN_PROGRESS)
            ->latest()
            ->first();

        if ($inProgress) {
            return [
                'mode' => 'active',
                'attempt' => $inProgress,
                'questions' => $this->attemptQuestions($inProgress),
                'attemptsRemaining' => $attemptsRemaining,
            ];
        }

        if ($maxAttempts && $used >= $maxAttempts) {
            return ['mode' => 'exhausted', 'maxAttempts' => $maxAttempts];
        }

        return [
            'mode' => 'prestart',
            'passPercent' => $course->pass_percent,
            'questionCount' => $course->questions_per_attempt && $course->questions_per_attempt < $bankCount
                ? $course->questions_per_attempt
                : $bankCount,
            'maxAttempts' => $maxAttempts,
            'attemptsRemaining' => $attemptsRemaining,
            'certificateName' => $user->certificate_name ?: $user->name,
        ];
    }

    /** Randomly draw the question ids for a new attempt. */
    private function drawQuestions(Course $course): array
    {
        $bank = $course->finalQuestions()->pluck('questions.id');
        $per = $course->questions_per_attempt;

        $selected = $per && $per > 0 && $per < $bank->count()
            ? $bank->shuffle()->take($per)
            : $bank->shuffle();

        return $selected->values()->all();
    }

    /** Load an attempt's questions (with options), in the stored order. */
    private function attemptQuestions(QuizAttempt $attempt)
    {
        $ids = $attempt->question_ids ?? [];

        if (empty($ids)) {
            return collect();
        }

        return Question::with('options')
            ->whereIn('id', $ids)
            ->get()
            ->sortBy(fn ($q) => array_search($q->id, $ids))
            ->values();
    }
}
