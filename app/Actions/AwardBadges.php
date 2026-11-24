<?php

namespace App\Actions;

use App\Models\Product;
use App\Models\QuizAttempt;
use App\Models\User;
use App\Models\UserBadge;

/**
 * Gives a student every badge they have earned and do not hold yet. Safe to
 * call any number of times: a badge already held is never given again (the
 * table's unique key says so too), and one is never taken away — a revoked
 * certificate or a lesson added to a finished product does not undo it.
 *
 * Called after a lesson is finished, after a final quiz is submitted, and when
 * the student opens their profile — that last one also catches up students
 * who earned badges before badges existed.
 *
 * Everything is read from what the academy already records:
 *  - first lesson       a finished lesson (lesson_user);
 *  - first certificate  a certificate that has not been revoked;
 *  - product complete   every published course of a product finished — all its
 *                       published lessons, plus its certificate when the course
 *                       has a final quiz (the home page's rule);
 *  - perfect score      the first final-quiz attempt on a course passed at 100%.
 */
class AwardBadges
{
    /** @return array<int, UserBadge> The badges given this time. */
    public function handle(User $user): array
    {
        $completed = $user->completedLessons()->pluck('lessons.id')->map(fn ($id): int => (int) $id)->all();
        $certified = $user->certificates()->whereNull('revoked_at')->pluck('course_id')->map(fn ($id): int => (int) $id)->unique()->all();

        $earned = [];

        if ($completed !== []) {
            $earned[] = [UserBadge::FIRST_LESSON, ''];
        }

        if ($certified !== []) {
            $earned[] = [UserBadge::FIRST_CERTIFICATE, ''];
        }

        foreach ($this->completedProducts($completed, $certified) as $productId) {
            $earned[] = [UserBadge::PRODUCT_COMPLETE, "product:{$productId}"];
        }

        foreach ($this->perfectCourses($user) as $courseId) {
            $earned[] = [UserBadge::PERFECT_SCORE, "course:{$courseId}"];
        }

        $held = $user->badges()->get()->map(fn (UserBadge $badge): string => "{$badge->badge}|{$badge->subject}")->all();
        $given = [];

        foreach ($earned as [$badge, $subject]) {
            if (in_array("{$badge}|{$subject}", $held, true)) {
                continue;
            }

            $given[] = $user->badges()->firstOrCreate(
                ['badge' => $badge, 'subject' => $subject],
                ['awarded_at' => now()],
            );
        }

        return $given;
    }

    /**
     * @param  array<int, int>  $completed
     * @param  array<int, int>  $certified
     * @return array<int, int> Products whose every published course is finished.
     */
    private function completedProducts(array $completed, array $certified): array
    {
        if ($completed === []) {
            return [];
        }

        $products = Product::query()
            ->with(['courses' => fn ($query) => $query->published()->with('publishedLessons:lessons.id')])
            ->get();

        $done = [];

        foreach ($products as $product) {
            // A course with no published lessons is not something to finish yet.
            $courses = $product->courses->filter(fn ($course): bool => $course->publishedLessons->isNotEmpty());

            if ($courses->isEmpty()) {
                continue;
            }

            $allFinished = $courses->every(fn ($course): bool => $course->publishedLessons->pluck('id')->diff($completed)->isEmpty()
                && (! $course->final_quiz_enabled || in_array((int) $course->id, $certified, true)));

            if ($allFinished) {
                $done[] = (int) $product->id;
            }
        }

        return $done;
    }

    /** @return array<int, int> Courses whose first final-quiz attempt was passed with every answer right. */
    private function perfectCourses(User $user): array
    {
        $first = QuizAttempt::query()
            ->where('user_id', $user->id)
            ->whereNotNull('course_id')
            ->whereIn('status', [QuizAttempt::STATUS_PASSED, QuizAttempt::STATUS_FAILED])
            ->orderBy('submitted_at')
            ->orderBy('id')
            ->get(['course_id', 'status', 'score', 'total'])
            ->unique('course_id');

        return $first
            ->filter(fn (QuizAttempt $attempt): bool => $attempt->status === QuizAttempt::STATUS_PASSED
                && (int) $attempt->total > 0
                && (int) $attempt->score === (int) $attempt->total)
            ->map(fn (QuizAttempt $attempt): int => (int) $attempt->course_id)
            ->values()
            ->all();
    }
}
