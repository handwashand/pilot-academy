<?php

namespace Tests\Feature;

use App\Mail\CertificateIssued;
use App\Models\Certificate;
use App\Models\Course;
use App\Models\Option;
use App\Models\Question;
use App\Models\QuizAttempt;
use App\Models\User;
use Database\Seeders\PilotQuickStartSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class FinalQuizCertificateTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(PilotQuickStartSeeder::class);
    }

    private function student(): User
    {
        return User::create([
            'name' => 'Final Student',
            'email' => 'final@example.com',
            'password' => bcrypt('secret'),
            'role' => 'learner',
        ]);
    }

    /** Enable the final quiz and fill its bank with every lesson question. */
    private function enableFinal(Course $course, array $overrides = []): Course
    {
        $course->update(array_merge(['final_quiz_enabled' => true, 'pass_percent' => 80], $overrides));

        $ids = Question::whereIn('lesson_id', $course->lessons()->pluck('id'))->pluck('id');
        $course->finalQuestions()->sync($ids->all());

        return $course->fresh();
    }

    private function completeAllLessons(User $student, Course $course): void
    {
        foreach ($course->publishedLessons()->pluck('lessons.id') as $lessonId) {
            $student->completedLessons()->syncWithoutDetaching([$lessonId => ['completed_at' => now()]]);
        }
    }

    /** Correct answers for the questions actually drawn into an attempt. */
    private function correctAnswersFor(QuizAttempt $attempt): array
    {
        $answers = [];
        foreach (Question::with('options')->whereIn('id', $attempt->question_ids)->get() as $q) {
            $answers[$q->id] = $q->correctOptionIds();
        }

        return $answers;
    }

    public function test_multiple_select_grading_requires_full_match(): void
    {
        $question = Question::create(['prompt' => 'Pick the two', 'type' => Question::TYPE_MULTIPLE, 'sort_order' => 1]);
        $a = Option::create(['question_id' => $question->id, 'text' => 'A', 'is_correct' => true]);
        $b = Option::create(['question_id' => $question->id, 'text' => 'B', 'is_correct' => true]);
        $c = Option::create(['question_id' => $question->id, 'text' => 'C', 'is_correct' => false]);

        $this->assertTrue($question->isAnsweredCorrectly([$a->id, $b->id]));
        $this->assertFalse($question->isAnsweredCorrectly([$a->id]));            // partial
        $this->assertFalse($question->isAnsweredCorrectly([$a->id, $b->id, $c->id])); // extra
        $this->assertFalse($question->isAnsweredCorrectly([]));                  // none
    }

    public function test_course_is_completed_by_helper(): void
    {
        $student = $this->student();
        $course = Course::first();

        $this->assertFalse($course->isCompletedBy($student));
        $this->completeAllLessons($student, $course);
        $this->assertTrue($course->fresh()->isCompletedBy($student->fresh()));
    }

    public function test_final_quiz_is_locked_until_lessons_completed(): void
    {
        $student = $this->student();
        $course = $this->enableFinal(Course::first());

        $this->actingAs($student)->get(route('academy.final.show', $course))
            ->assertStatus(200)
            ->assertSee('Complete all lessons');

        // Course page shows the locked state too.
        $this->actingAs($student)->get(route('academy.course', $course))
            ->assertSee('Complete all lessons to unlock the final quiz');
    }

    public function test_prestart_shows_name_field_after_completion(): void
    {
        $student = $this->student();
        $course = $this->enableFinal(Course::first());
        $this->completeAllLessons($student, $course);

        $this->actingAs($student)->get(route('academy.final.show', $course))
            ->assertStatus(200)
            ->assertSee('Full name for your certificate')
            ->assertSee('80%');
    }

    public function test_passing_final_quiz_issues_certificate_and_emails_it(): void
    {
        Mail::fake();
        Storage::fake('public');

        $student = $this->student();
        $course = $this->enableFinal(Course::first());
        $this->completeAllLessons($student, $course);

        // Start with a confirmed certificate name.
        $this->actingAs($student)->post(route('academy.final.start', $course), [
            'certificate_name' => 'Jane Q. Student',
        ])->assertRedirect(route('academy.final.show', $course));

        $attempt = QuizAttempt::where('user_id', $student->id)->where('course_id', $course->id)->first();
        $this->assertNotNull($attempt);
        $this->assertSame('Jane Q. Student', $student->fresh()->certificate_name);

        // Answer everything correctly.
        $this->actingAs($student)->post(route('academy.final.submit', $course), [
            'answers' => $this->correctAnswersFor($attempt),
        ])->assertRedirect(route('academy.final.show', $course));

        $certificate = Certificate::where('user_id', $student->id)->where('course_id', $course->id)->first();
        $this->assertNotNull($certificate);
        $this->assertSame(100, $certificate->score_percent);
        $this->assertSame('Jane Q. Student', $certificate->name);
        $this->assertStringStartsWith('PA-', $certificate->number);
        $this->assertNull($certificate->revoked_at);

        // PDF rendered and stored; email sent.
        $this->assertNotNull($certificate->pdf_path);
        Storage::disk('public')->assertExists($certificate->pdf_path);
        Mail::assertSent(CertificateIssued::class);

        // Passed state now shows on the final page.
        $this->actingAs($student)->get(route('academy.final.show', $course))
            ->assertSee('Download PDF')
            ->assertSee($certificate->number);
    }

    public function test_failing_final_quiz_does_not_issue_certificate(): void
    {
        Storage::fake('public');

        $student = $this->student();
        $course = $this->enableFinal(Course::first());
        $this->completeAllLessons($student, $course);

        $this->actingAs($student)->post(route('academy.final.start', $course), [
            'certificate_name' => 'Wrong Answers',
        ]);
        $attempt = QuizAttempt::where('user_id', $student->id)->where('course_id', $course->id)->first();

        // Submit no answers at all → 0%.
        $this->actingAs($student)->post(route('academy.final.submit', $course), ['answers' => []])
            ->assertRedirect(route('academy.final.show', $course))
            ->assertSessionHas('final_result');

        $this->assertSame('failed', $attempt->fresh()->status);
        $this->assertDatabaseCount('certificates', 0);
    }

    public function test_final_quiz_respects_attempt_limit(): void
    {
        $student = $this->student();
        $course = $this->enableFinal(Course::first(), ['final_quiz_max_attempts' => 1]);
        $this->completeAllLessons($student, $course);

        QuizAttempt::create([
            'user_id' => $student->id,
            'course_id' => $course->id,
            'status' => QuizAttempt::STATUS_FAILED,
            'started_at' => now(),
            'submitted_at' => now(),
        ]);

        $this->actingAs($student)->get(route('academy.final.show', $course))
            ->assertStatus(200)
            ->assertSee('No attempts remaining');
    }

    public function test_certificate_verification_page_states(): void
    {
        $student = $this->student();
        $course = Course::first();

        $certificate = Certificate::create([
            'user_id' => $student->id,
            'course_id' => $course->id,
            'number' => 'PA-TEST-1',
            'name' => 'Verify Me',
            'score_percent' => 90,
            'issued_at' => now(),
        ]);

        // Valid — pass/fail only, the score is never shown publicly.
        $this->get(route('certificates.verify', $certificate->number))
            ->assertStatus(200)
            ->assertSee('Valid certificate')
            ->assertSee('Verify Me')
            ->assertSee($course->title)
            ->assertDontSee('Score')
            ->assertDontSee('90%');

        // Revoked
        $certificate->update(['revoked_at' => now()]);
        $this->get(route('certificates.verify', $certificate->number))
            ->assertSee('Certificate revoked');

        // Unknown
        $this->get(route('certificates.verify', 'NOPE-404'))
            ->assertStatus(200)
            ->assertSee('No certificate found');
    }

    public function test_certificate_download_is_owner_only(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('certificates/PA-DL-1.pdf', '%PDF-1.4 test');

        $owner = $this->student();
        $other = User::create(['name' => 'Other', 'email' => 'other@example.com', 'password' => bcrypt('x'), 'role' => 'learner']);
        $course = Course::first();

        $certificate = Certificate::create([
            'user_id' => $owner->id,
            'course_id' => $course->id,
            'number' => 'PA-DL-1',
            'name' => 'Owner',
            'score_percent' => 88,
            'issued_at' => now(),
            'pdf_path' => 'certificates/PA-DL-1.pdf',
        ]);

        $this->actingAs($other)->get(route('certificates.download', $certificate))->assertStatus(403);
        $this->actingAs($owner)->get(route('certificates.download', $certificate))->assertStatus(200);
    }
}
