<?php

namespace App\Http\Controllers;

use App\Models\ActivityEvent;
use App\Models\Course;
use App\Models\Lesson;
use Illuminate\Http\Request;

class AcademyController extends Controller
{
    private const SESSION_KEY = 'completed_lessons';

    public function home(Request $request)
    {
        $courses = Course::where('is_published', true)
            ->withCount('publishedLessons')
            ->with('publishedLessons')
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
        $course->load('publishedLessons');

        ActivityEvent::record($request->user(), ActivityEvent::TYPE_COURSE_OPENED, $course->title, $request->path());

        return view('academy.course', [
            'course' => $course,
            'completed' => $this->completedIds($request),
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

        return view('academy.lesson', [
            'course' => $course,
            'lesson' => $lesson,
            'lessons' => $lessons,
            'next' => $next,
            'prev' => $prev,
            'completed' => $this->completedIds($request),
        ]);
    }

    public function submitQuiz(Request $request, Course $course, Lesson $lesson)
    {
        abort_unless($lesson->course_id === $course->id, 404);
        $lesson->load('questions.options');

        $answers = $request->input('answers', []);
        $results = [];
        $allCorrect = $lesson->questions->isNotEmpty();

        foreach ($lesson->questions as $question) {
            $chosen = (int) ($answers[$question->id] ?? 0);
            $correctOption = $question->options->firstWhere('is_correct', true);
            $isCorrect = $correctOption && $chosen === $correctOption->id;
            $results[$question->id] = $isCorrect;
            if (! $isCorrect) {
                $allCorrect = false;
            }
        }

        if ($allCorrect) {
            $user = $request->user();
            $isNewCompletion = ! in_array($lesson->id, $this->completedIds($request), true);

            $this->markCompleted($request, $lesson->id);

            if ($user && $isNewCompletion) {
                ActivityEvent::record($user, ActivityEvent::TYPE_LESSON_COMPLETED, $lesson->title, $request->path());

                $remaining = array_diff(
                    $course->publishedLessons()->pluck('lessons.id')->all(),
                    $user->completedLessons()->pluck('lessons.id')->all(),
                );

                if (empty($remaining)
                    && ! $user->activities()
                        ->where('type', ActivityEvent::TYPE_COURSE_COMPLETED)
                        ->where('label', $course->title)
                        ->exists()
                ) {
                    ActivityEvent::record($user, ActivityEvent::TYPE_COURSE_COMPLETED, $course->title);
                }
            }

            return redirect()
                ->route('academy.lesson', [$course, $lesson])
                ->with('quiz_passed', true);
        }

        return redirect()
            ->route('academy.lesson', [$course, $lesson])
            ->withInput()
            ->with('quiz_results', $results)
            ->with('quiz_failed', true);
    }

    public function setName(Request $request)
    {
        $data = $request->validate(['name' => 'required|string|max:60']);
        $request->session()->put('student_name', trim($data['name']));

        return redirect()->route('academy.home');
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
