<?php

namespace App\Actions;

use App\Models\Certificate;
use App\Models\Course;
use App\Models\User;
use Illuminate\Support\Facades\Log;

/**
 * The all-courses certificate: given once a student has finished every
 * published course (each course's rule is Course::isFinishedWith()).
 *
 * Called where badges are checked: after a lesson is finished, after a final
 * quiz, and when the profile opens. Safe to call any number of times.
 *
 * A certificate records the courses it covered on its day. When more courses
 * are published later, it stays valid; finishing those too issues a new one
 * that covers them, and the earlier one remains as it was.
 */
class IssueAcademyCertificate
{
    public function __construct(private readonly IssueCertificate $certificates) {}

    public function handle(User $user): ?Certificate
    {
        // A course with no published lessons is not something to finish yet.
        $courses = Course::query()->published()
            ->with('publishedLessons:lessons.id')
            ->get()
            ->filter(fn (Course $course): bool => $course->publishedLessons->isNotEmpty());

        if ($courses->isEmpty()) {
            return null;
        }

        $completed = $user->completedLessons()->pluck('lessons.id')->map(fn ($id): int => (int) $id)->all();
        $certified = $user->certificates()->forCourses()->whereNull('revoked_at')->pluck('course_id')->map(fn ($id): int => (int) $id)->all();

        if (! $courses->every(fn (Course $course): bool => $course->isFinishedWith($completed, $certified))) {
            return null;
        }

        $covered = $courses->pluck('id')->map(fn ($id): int => (int) $id)->sort()->values()->all();

        $held = $user->certificates()->forAcademy()->whereNull('revoked_at')->get();

        foreach ($held as $certificate) {
            if (array_diff($covered, $certificate->covered_course_ids ?? []) === []) {
                return $certificate;
            }
        }

        $certificate = Certificate::create([
            'user_id' => $user->id,
            'course_id' => null,
            'kind' => Certificate::KIND_ACADEMY,
            'covered_course_ids' => $covered,
            'number' => $this->certificates->uniqueNumber('ALL'),
            'name' => $user->certificate_name ?: $user->name,
            'score_percent' => null,
            'issued_at' => now(),
        ]);

        // As with a course certificate, a PDF or mail failure never loses it.
        try {
            $this->certificates->renderPdf($certificate);
        } catch (\Throwable $e) {
            Log::warning('Certificate PDF render failed', ['certificate' => $certificate->number, 'error' => $e->getMessage()]);
        }

        $this->certificates->email($certificate);

        return $certificate;
    }
}
