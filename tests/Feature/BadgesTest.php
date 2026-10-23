<?php

namespace Tests\Feature;

use App\Actions\AwardBadges;
use App\Filament\Resources\Users\Pages\EditUser;
use App\Models\Certificate;
use App\Models\Course;
use App\Models\Product;
use App\Models\QuizAttempt;
use App\Models\User;
use App\Models\UserBadge;
use Database\Seeders\LanguageSeeder;
use Database\Seeders\PilotQuickStartSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Badges: four milestones, each read from what the academy already records,
 * given once and never taken away.
 */
class BadgesTest extends TestCase
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

    private function finishLessons(User $learner, ?int $leaveOut = 0): void
    {
        $lessons = $this->course->publishedLessons()->get();

        foreach ($lessons->slice(0, $lessons->count() - $leaveOut) as $lesson) {
            $learner->completedLessons()->attach($lesson->id, ['completed_at' => now()]);
        }
    }

    private function certify(User $learner): void
    {
        Certificate::create([
            'user_id' => $learner->id,
            'course_id' => $this->course->id,
            'number' => 'PA-BADGE-0001',
            'name' => $learner->name,
            'score_percent' => 100,
            'issued_at' => now(),
        ]);
    }

    private function finalAttempt(User $learner, string $status, int $score, int $total, int $minutesAgo): void
    {
        QuizAttempt::create([
            'user_id' => $learner->id,
            'course_id' => $this->course->id,
            'status' => $status,
            'score' => $score,
            'total' => $total,
            'started_at' => now()->subMinutes($minutesAgo + 5),
            'submitted_at' => now()->subMinutes($minutesAgo),
        ]);
    }

    /** @return array<int, string> */
    private function badges(User $learner): array
    {
        return $learner->badges()->get()->map(fn (UserBadge $b): string => trim("{$b->badge} {$b->subject}"))->all();
    }

    public function test_a_new_student_has_no_badges(): void
    {
        $learner = $this->learner();

        $this->assertSame([], app(AwardBadges::class)->handle($learner));
        $this->assertSame([], $this->badges($learner));
    }

    public function test_passing_a_lesson_quiz_gives_the_first_lesson_badge(): void
    {
        $learner = $this->learner();
        $lesson = $this->course->lessons()->with('questions.options')->first();

        $answers = [];
        foreach ($lesson->questions as $question) {
            $answers[$question->id] = $question->options->firstWhere('is_correct', true)->id;
        }

        $this->actingAs($learner)
            ->post(route('academy.quiz', [$this->course, $lesson]), ['answers' => $answers])
            ->assertSessionHas('quiz_passed', true);

        $this->assertSame([UserBadge::FIRST_LESSON], $this->badges($learner));
    }

    public function test_a_badge_is_given_once(): void
    {
        $learner = $this->learner();
        $this->finishLessons($learner);

        $this->assertNotEmpty(app(AwardBadges::class)->handle($learner));
        $this->assertSame([], app(AwardBadges::class)->handle($learner));
        $this->assertSame(1, $learner->badges()->where('badge', UserBadge::FIRST_LESSON)->count());
    }

    public function test_a_certificate_gives_the_first_certificate_badge(): void
    {
        $learner = $this->learner();
        $this->certify($learner);

        app(AwardBadges::class)->handle($learner);

        $this->assertContains(UserBadge::FIRST_CERTIFICATE, $this->badges($learner));
    }

    public function test_a_product_is_complete_only_when_every_course_is_finished(): void
    {
        $product = Product::create(['name' => 'Pilot Fleet', 'slug' => 'pilot-fleet']);
        $this->course->update(['product_id' => $product->id, 'final_quiz_enabled' => false]);
        $learner = $this->learner();

        $this->finishLessons($learner, leaveOut: 1);
        app(AwardBadges::class)->handle($learner);
        $this->assertNotContains(UserBadge::PRODUCT_COMPLETE." product:{$product->id}", $this->badges($learner));

        $learner->completedLessons()->detach();
        $this->finishLessons($learner);
        app(AwardBadges::class)->handle($learner);
        $this->assertContains(UserBadge::PRODUCT_COMPLETE." product:{$product->id}", $this->badges($learner));
    }

    public function test_a_course_with_a_final_quiz_needs_its_certificate_for_the_product_badge(): void
    {
        $product = Product::create(['name' => 'Pilot Fleet', 'slug' => 'pilot-fleet']);
        $this->course->update(['product_id' => $product->id, 'final_quiz_enabled' => true]);
        $learner = $this->learner();
        $this->finishLessons($learner);

        app(AwardBadges::class)->handle($learner);
        $this->assertNotContains(UserBadge::PRODUCT_COMPLETE." product:{$product->id}", $this->badges($learner));

        $this->certify($learner);
        app(AwardBadges::class)->handle($learner);
        $this->assertContains(UserBadge::PRODUCT_COMPLETE." product:{$product->id}", $this->badges($learner));
    }

    public function test_a_perfect_first_final_attempt_gives_the_perfect_score_badge(): void
    {
        $learner = $this->learner();
        $this->finalAttempt($learner, QuizAttempt::STATUS_PASSED, 10, 10, minutesAgo: 1);

        app(AwardBadges::class)->handle($learner);

        $this->assertContains(UserBadge::PERFECT_SCORE." course:{$this->course->id}", $this->badges($learner));
    }

    public function test_a_perfect_score_after_a_failed_first_attempt_does_not_count(): void
    {
        $learner = $this->learner();
        $this->finalAttempt($learner, QuizAttempt::STATUS_FAILED, 4, 10, minutesAgo: 60);
        $this->finalAttempt($learner, QuizAttempt::STATUS_PASSED, 10, 10, minutesAgo: 1);

        app(AwardBadges::class)->handle($learner);

        $this->assertNotContains(UserBadge::PERFECT_SCORE." course:{$this->course->id}", $this->badges($learner));
    }

    public function test_a_passed_first_attempt_below_full_marks_does_not_count(): void
    {
        $learner = $this->learner();
        $this->finalAttempt($learner, QuizAttempt::STATUS_PASSED, 9, 10, minutesAgo: 1);

        app(AwardBadges::class)->handle($learner);

        $this->assertNotContains(UserBadge::PERFECT_SCORE." course:{$this->course->id}", $this->badges($learner));
    }

    public function test_the_profile_catches_up_and_shows_badges(): void
    {
        $learner = $this->learner();
        $this->finishLessons($learner);

        $this->actingAs($learner)
            ->get(route('academy.profile'))
            ->assertOk()
            ->assertSee('Your badges')
            ->assertSee('First lesson')
            ->assertSee('You finished your first lesson.');

        $this->assertContains(UserBadge::FIRST_LESSON, $this->badges($learner));
    }

    public function test_the_profile_says_when_there_are_no_badges(): void
    {
        $this->actingAs($this->learner())
            ->get(route('academy.profile'))
            ->assertOk()
            ->assertSee('No badges yet.');
    }

    public function test_the_profile_shows_a_badge_in_the_students_language(): void
    {
        $this->seed(LanguageSeeder::class);
        $learner = $this->learner();
        $learner->update(['locale' => 'fr']);
        $this->finishLessons($learner);

        $this->actingAs($learner)
            ->get(route('academy.profile'))
            ->assertOk()
            ->assertSee('Première leçon');
    }

    public function test_an_admin_sees_a_students_badges_on_their_user_page(): void
    {
        $admin = User::create([
            'name' => 'Admin', 'email' => 'admin@pilot.test',
            'password' => bcrypt('secret'), 'role' => User::ROLE_ADMIN,
        ]);
        $learner = $this->learner();
        $this->certify($learner);
        app(AwardBadges::class)->handle($learner);

        Livewire::actingAs($admin)
            ->test(EditUser::class, ['record' => $learner->getRouteKey()])
            ->assertOk()
            ->assertSee('First certificate');
    }
}
