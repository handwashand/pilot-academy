<?php

namespace Tests\Feature;

use App\Models\Certificate;
use App\Models\Course;
use App\Models\Question;
use App\Models\QuizAttempt;
use App\Models\User;
use Database\Seeders\PilotQuickStartSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The home page's next-step card and the student's own progress card.
 *
 * The card used to vanish at exactly the moment the final quiz unlocked, and a
 * final quiz score was shown once, on the page after submitting, and never again.
 */
class HomeNextStepTest extends TestCase
{
    use RefreshDatabase;

    private Course $course;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(PilotQuickStartSeeder::class);

        $this->course = Course::first();
    }

    private function learner(): User
    {
        return User::create([
            'name' => 'Partner Student',
            'email' => 'student@partner.com',
            'password' => bcrypt('secret'),
            'role' => User::ROLE_LEARNER,
        ]);
    }

    private function finishEveryLesson(User $learner): void
    {
        foreach ($this->course->publishedLessons()->get() as $lesson) {
            $learner->completedLessons()->attach($lesson->id, ['completed_at' => now()]);
        }
    }

    private function enableFinalQuiz(?int $maxAttempts = null): void
    {
        $this->course->update([
            'final_quiz_enabled' => true,
            'pass_percent' => 80,
            'final_quiz_max_attempts' => $maxAttempts,
        ]);

        $this->course->finalQuestions()->sync(
            Question::whereIn('lesson_id', $this->course->lessons()->pluck('id'))->pluck('id')->all()
        );
    }

    private function finalAttempt(User $learner, string $status, int $score, int $total = 10): void
    {
        QuizAttempt::create([
            'user_id' => $learner->id,
            'course_id' => $this->course->id,
            'status' => $status,
            'score' => $score,
            'total' => $total,
            'started_at' => now()->subMinutes(10),
            'submitted_at' => now(),
        ]);
    }

    // --- Where to start ---------------------------------------------------

    public function test_a_signed_in_learner_who_has_not_started_is_shown_where_to_start(): void
    {
        $first = $this->course->publishedLessons()->first();

        $this->actingAs($this->learner())
            ->get('/')
            ->assertStatus(200)
            ->assertSee('Start here')
            ->assertSee(route('academy.lesson', [$this->course, $first]), false)
            ->assertDontSee('Continue where you left off')
            // Day one: nothing to pick up from, so no "welcome back".
            ->assertDontSee('Pick up where you left off')
            // Nothing to summarise yet.
            ->assertDontSee('Your progress');
    }

    public function test_a_guest_who_has_not_started_gets_no_card(): void
    {
        $this->get('/')
            ->assertStatus(200)
            ->assertDontSee('Start here')
            ->assertDontSee('Your progress');
    }

    // --- The final quiz ---------------------------------------------------

    public function test_finishing_every_lesson_points_at_the_final_quiz(): void
    {
        $this->enableFinalQuiz(maxAttempts: 3);
        $learner = $this->learner();
        $this->finishEveryLesson($learner);

        $this->actingAs($learner)
            ->get('/')
            ->assertSee('Your final quiz is ready')
            ->assertSee(route('academy.final.show', $this->course), false)
            ->assertSee('80% to pass')
            ->assertSee('3 attempts left')
            ->assertDontSee('Continue where you left off');
    }

    public function test_a_course_without_a_final_quiz_is_simply_finished(): void
    {
        $learner = $this->learner();
        $this->finishEveryLesson($learner);

        $this->actingAs($learner)
            ->get('/')
            ->assertDontSee('Your final quiz is ready')
            ->assertDontSee('Continue where you left off')
            ->assertSee('Your progress');
    }

    public function test_nothing_is_offered_once_the_certificate_is_issued(): void
    {
        $this->enableFinalQuiz();
        $learner = $this->learner();
        $this->finishEveryLesson($learner);

        Certificate::create([
            'user_id' => $learner->id,
            'course_id' => $this->course->id,
            'number' => 'PA-TEST-0001',
            'name' => $learner->name,
            'score_percent' => 90,
            'issued_at' => now(),
        ]);

        $this->actingAs($learner)
            ->get('/')
            ->assertDontSee('Your final quiz is ready')
            ->assertSee(route('certificates.index'), false);
    }

    public function test_a_student_out_of_attempts_is_not_sent_to_the_final_quiz(): void
    {
        $this->enableFinalQuiz(maxAttempts: 1);
        $learner = $this->learner();
        $this->finishEveryLesson($learner);
        $this->finalAttempt($learner, QuizAttempt::STATUS_FAILED, 5);

        $this->actingAs($learner)
            ->get('/')
            ->assertDontSee('Your final quiz is ready')
            ->assertSee('no attempts left — contact your administrator');
    }

    public function test_a_guest_who_finished_every_lesson_is_not_offered_the_final_quiz(): void
    {
        $this->enableFinalQuiz();

        // Anonymous progress lives in the session, and does not carry over to
        // an account — so the quiz, which needs one, is not offered.
        $this->withSession(['completed_lessons' => $this->course->publishedLessons()->pluck('lessons.id')->all()])
            ->get('/')
            ->assertDontSee('Your final quiz is ready');
    }

    // --- Your progress ----------------------------------------------------

    public function test_a_failed_final_quiz_score_stays_visible_with_attempts_left(): void
    {
        $this->enableFinalQuiz(maxAttempts: 3);
        $learner = $this->learner();
        $this->finishEveryLesson($learner);
        $this->finalAttempt($learner, QuizAttempt::STATUS_FAILED, 6);

        $this->actingAs($learner)
            ->get('/')
            ->assertSee('Your progress')
            ->assertSee('Last final quiz')
            ->assertSee('60%')
            ->assertSee('Not passed yet')
            ->assertSee('2 attempts left');
    }

    public function test_a_passed_final_quiz_says_so(): void
    {
        $this->enableFinalQuiz(maxAttempts: 3);
        $learner = $this->learner();
        $this->finishEveryLesson($learner);
        $this->finalAttempt($learner, QuizAttempt::STATUS_PASSED, 9);

        // Passing always issues the certificate.
        Certificate::create([
            'user_id' => $learner->id,
            'course_id' => $this->course->id,
            'number' => 'PA-TEST-0002',
            'name' => $learner->name,
            'score_percent' => 90,
            'issued_at' => now(),
        ]);

        $this->actingAs($learner)
            ->get('/')
            ->assertSee('90%')
            ->assertSee('Passed')
            ->assertDontSee('Your final quiz is ready')
            ->assertDontSee('attempts left');
    }

    public function test_the_final_quiz_page_and_the_home_page_agree_on_attempts_left(): void
    {
        $this->enableFinalQuiz(maxAttempts: 2);
        $learner = $this->learner();
        $this->finishEveryLesson($learner);
        $this->finalAttempt($learner, QuizAttempt::STATUS_FAILED, 5);

        $this->assertSame(1, $this->course->fresh()->finalQuizAttemptsLeftFor($learner));

        $this->actingAs($learner)
            ->get(route('academy.final.show', $this->course))
            ->assertSee('1</strong> attempt(s) left', false);
    }
}
