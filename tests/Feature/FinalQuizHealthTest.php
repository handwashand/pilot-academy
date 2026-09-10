<?php

namespace Tests\Feature;

use App\Filament\Pages\FinalQuizHealth;
use App\Models\Certificate;
use App\Models\Course;
use App\Models\QuizAttempt;
use App\Models\User;
use Database\Seeders\PilotQuickStartSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FinalQuizHealthTest extends TestCase
{
    use RefreshDatabase;

    private Course $course;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(PilotQuickStartSeeder::class);

        $this->course = Course::first();
    }

    private function user(string $email, string $role = User::ROLE_LEARNER): User
    {
        return User::create([
            'name' => 'User '.$email,
            'email' => $email,
            'password' => bcrypt('password'),
            'role' => $role,
        ]);
    }

    private function attempt(User $user, string $status, string $submittedAt): void
    {
        QuizAttempt::create([
            'user_id' => $user->id,
            'course_id' => $this->course->id,
            'status' => $status,
            'score' => $status === QuizAttempt::STATUS_PASSED ? 9 : 4,
            'total' => 10,
            'started_at' => $submittedAt,
            'submitted_at' => $submittedAt,
        ]);
    }

    // --- Access -----------------------------------------------------------

    public function test_admins_can_open_the_page(): void
    {
        $this->actingAs($this->user('admin@pilot.local', User::ROLE_ADMIN))
            ->get('/admin/final-quiz-health')
            ->assertStatus(200)
            ->assertSee('First-time pass rate')
            ->assertSee('Days to certificate')
            ->assertSee('Not measured');
    }

    /** Learner results are admin-only, like every learner report in the panel. */
    public function test_creators_and_learners_cannot(): void
    {
        $this->actingAs($this->user('creator@pilot.local', User::ROLE_CREATOR))
            ->get('/admin/final-quiz-health')
            ->assertStatus(403);
    }

    public function test_learners_cannot(): void
    {
        $this->actingAs($this->user('student@partner.com'))
            ->get('/admin/final-quiz-health')
            ->assertStatus(403);
    }

    public function test_it_sits_under_results(): void
    {
        $this->assertSame('Results', FinalQuizHealth::getNavigationGroup());
        $this->assertSame('Final quiz health', FinalQuizHealth::getNavigationLabel());
    }

    // --- The numbers ------------------------------------------------------

    public function test_only_each_learners_first_attempt_counts_and_staff_are_left_out(): void
    {
        $passedFirst = $this->user('a@partner.com');
        $this->attempt($passedFirst, QuizAttempt::STATUS_PASSED, '2026-09-01 10:00:00');

        // Failed first, passed later: a first-time fail, however it ended.
        $failedFirst = $this->user('b@partner.com');
        $this->attempt($failedFirst, QuizAttempt::STATUS_FAILED, '2026-09-01 10:00:00');
        $this->attempt($failedFirst, QuizAttempt::STATUS_PASSED, '2026-09-02 10:00:00');

        // An admin's preview must not move the figure.
        $this->attempt($this->user('admin@pilot.local', User::ROLE_ADMIN), QuizAttempt::STATUS_PASSED, '2026-09-01 09:00:00');

        $result = (new FinalQuizHealth)->firstTimePassRate();

        $this->assertSame(2, $result['sample']);
        $this->assertSame(1, $result['passed']);
        $this->assertSame(50, $result['rate']);
    }

    public function test_the_verdict_reads_both_ends_of_the_band_and_refuses_to_judge_small_samples(): void
    {
        $this->assertSame('No data yet', FinalQuizHealth::passRateStatus(null, 0)['label']);
        $this->assertSame('Too few to judge', FinalQuizHealth::passRateStatus(100, 3)['label']);
        $this->assertSame('Above the band', FinalQuizHealth::passRateStatus(95, 20)['label']);
        $this->assertSame('Below the band', FinalQuizHealth::passRateStatus(40, 20)['label']);
        $this->assertSame('Within the band', FinalQuizHealth::passRateStatus(72, 20)['label']);
    }

    public function test_the_page_breaks_the_rate_down_by_course(): void
    {
        $learner = $this->user('a@partner.com');
        $this->attempt($learner, QuizAttempt::STATUS_PASSED, '2026-09-01 10:00:00');

        $rows = (new FinalQuizHealth)->firstTimePassRateByCourse();

        $this->assertCount(1, $rows);
        $this->assertSame($this->course->title, $rows[0]['course']);
        $this->assertSame(100, $rows[0]['rate']);

        $this->actingAs($this->user('admin@pilot.local', User::ROLE_ADMIN))
            ->get('/admin/final-quiz-health')
            ->assertSee($this->course->title)
            ->assertSee('Too few to judge');
    }

    public function test_days_to_certificate_runs_from_the_first_finished_lesson(): void
    {
        $lessons = $this->course->publishedLessons()->get();

        foreach ([['a@partner.com', 3], ['b@partner.com', 10], ['c@partner.com', 5]] as [$email, $days]) {
            $learner = $this->user($email);
            $start = now()->subDays(30);

            $learner->completedLessons()->attach($lessons[0]->id, ['completed_at' => $start]);
            $learner->completedLessons()->attach($lessons[1]->id, ['completed_at' => $start->copy()->addDay()]);

            Certificate::create([
                'user_id' => $learner->id,
                'course_id' => $this->course->id,
                'number' => 'PA-'.$email,
                'name' => $learner->name,
                'score_percent' => 90,
                'issued_at' => $start->copy()->addDays($days),
            ]);
        }

        $result = (new FinalQuizHealth)->daysToCertificate();

        $this->assertSame(3, $result['sample']);
        $this->assertSame(5, $result['median']);
        $this->assertSame(3, $result['fastest']);
        $this->assertSame(10, $result['slowest']);
    }
}
