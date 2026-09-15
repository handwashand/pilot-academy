<?php

namespace Tests\Feature;

use App\Filament\Resources\QuizAttempts\Pages\ListQuizAttempts;
use App\Filament\Resources\QuizAttempts\QuizAttemptResource;
use App\Models\AttemptGrant;
use App\Models\Certificate;
use App\Models\Course;
use App\Models\Question;
use App\Models\QuizAttempt;
use App\Models\User;
use Database\Seeders\PilotQuickStartSeeder;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Results → Quiz attempts: every learner's attempts, and granting one student
 * one more try instead of raising Max attempts for everyone.
 */
class QuizAttemptGrantTest extends TestCase
{
    use RefreshDatabase;

    private Course $course;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(PilotQuickStartSeeder::class);

        $this->course = Course::first();
        $this->course->update(['final_quiz_enabled' => true, 'pass_percent' => 80, 'final_quiz_max_attempts' => 1]);
        $this->course->finalQuestions()->sync(
            Question::whereIn('lesson_id', $this->course->lessons()->pluck('id'))->pluck('id')->all()
        );
    }

    private function admin(): User
    {
        return User::firstOrCreate(['email' => 'admin@pilot.local'], [
            'name' => 'Pilot Admin', 'password' => 'password', 'role' => User::ROLE_ADMIN,
        ]);
    }

    private function learner(string $email = 'student@partner.com'): User
    {
        $learner = User::create(['name' => 'Partner Student', 'email' => $email, 'password' => 'secret123', 'role' => User::ROLE_LEARNER]);

        foreach ($this->course->publishedLessons()->get() as $lesson) {
            $learner->completedLessons()->attach($lesson->id, ['completed_at' => now()]);
        }

        return $learner;
    }

    private function failedFinal(User $user): QuizAttempt
    {
        return QuizAttempt::create([
            'user_id' => $user->id, 'course_id' => $this->course->id, 'status' => QuizAttempt::STATUS_FAILED,
            'score' => 4, 'total' => 10, 'started_at' => now()->subMinutes(10), 'submitted_at' => now(),
        ]);
    }

    public function test_a_student_out_of_attempts_shows_in_the_badge_and_can_be_granted_one_more(): void
    {
        $learner = $this->learner();
        $attempt = $this->failedFinal($learner);
        $admin = $this->admin();

        $this->assertSame(0, $this->course->finalQuizAttemptsLeftFor($learner));

        $this->actingAs($admin);
        $this->assertSame('1', QuizAttemptResource::getNavigationBadge());

        Livewire::actingAs($admin)
            ->test(ListQuizAttempts::class)
            ->assertCanSeeTableRecords([$attempt])
            ->assertActionVisible(TestAction::make('grantAttempt')->table($attempt))
            ->callAction(TestAction::make('grantAttempt')->table($attempt), ['reason' => 'Connection dropped'])
            ->assertHasNoFormErrors();

        $grant = AttemptGrant::sole();
        $this->assertSame($learner->id, $grant->user_id);
        $this->assertSame($this->course->id, $grant->course_id);
        $this->assertNull($grant->lesson_id);
        $this->assertSame($admin->id, $grant->granted_by);
        $this->assertSame('Connection dropped', $grant->reason);

        // One more try for this student only; the course setting is untouched.
        $this->assertSame(1, $this->course->fresh()->finalQuizAttemptsLeftFor($learner));
        $this->assertSame(1, $this->course->fresh()->final_quiz_max_attempts);

        QuizAttempt::forgetStuckLearners();
        $this->assertNull(QuizAttemptResource::getNavigationBadge());

        $this->actingAs($learner)
            ->get(route('academy.final.show', $this->course))
            ->assertSee('You have 1 attempt left.');
    }

    public function test_a_lesson_knowledge_check_can_be_granted_another_attempt_too(): void
    {
        $lesson = $this->course->publishedLessons()->first();
        $lesson->update(['quiz_max_attempts' => 1]);

        $learner = User::create(['name' => 'Stuck', 'email' => 'stuck@partner.com', 'password' => 'secret123', 'role' => User::ROLE_LEARNER]);
        $attempt = QuizAttempt::create([
            'user_id' => $learner->id, 'lesson_id' => $lesson->id, 'status' => QuizAttempt::STATUS_FAILED,
            'score' => 0, 'total' => 1, 'started_at' => now()->subMinute(), 'submitted_at' => now(),
        ]);

        $this->assertSame(0, $lesson->quizAttemptsLeftFor($learner));

        Livewire::actingAs($this->admin())
            ->test(ListQuizAttempts::class)
            ->callAction(TestAction::make('grantAttempt')->table($attempt), ['reason' => ''])
            ->assertHasNoFormErrors();

        $this->assertSame($lesson->id, AttemptGrant::sole()->lesson_id);
        $this->assertSame(1, $lesson->fresh()->quizAttemptsLeftFor($learner));
    }

    public function test_nobody_is_offered_a_grant_they_do_not_need(): void
    {
        // Passed (certificate issued): nothing to decide.
        $passed = $this->learner('passed@partner.com');
        $attempt = $this->failedFinal($passed);
        Certificate::create([
            'user_id' => $passed->id, 'course_id' => $this->course->id, 'number' => 'PA-TEST-0001',
            'name' => $passed->name, 'score_percent' => 90, 'issued_at' => now(),
        ]);

        Livewire::actingAs($this->admin())
            ->test(ListQuizAttempts::class)
            ->assertActionHidden(TestAction::make('grantAttempt')->table($attempt));

        $this->assertCount(0, QuizAttempt::stuckLearners());
    }

    public function test_staff_attempts_are_left_out(): void
    {
        $admin = $this->admin();
        $staffAttempt = $this->failedFinal($admin);

        Livewire::actingAs($admin)
            ->test(ListQuizAttempts::class)
            ->assertCanNotSeeTableRecords([$staffAttempt]);
    }

    public function test_creators_cannot_open_quiz_attempts(): void
    {
        $creator = User::create(['name' => 'Creator', 'email' => 'creator@pilot.local', 'password' => 'secret123', 'role' => User::ROLE_CREATOR]);

        $this->actingAs($creator)
            ->get(QuizAttemptResource::getUrl())
            ->assertForbidden();
    }
}
