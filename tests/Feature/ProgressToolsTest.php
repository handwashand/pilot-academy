<?php

namespace Tests\Feature;

use App\Filament\Resources\Users\Pages\ListUsers;
use App\Filament\Resources\Users\Tables\UsersTable;
use App\Filament\Widgets\MostOpenedCourses;
use App\Models\ActivityEvent;
use App\Models\Certificate;
use App\Models\Company;
use App\Models\Course;
use App\Models\Lesson;
use App\Models\User;
use Closure;
use Database\Seeders\PilotQuickStartSeeder;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Tests\TestCase;

/**
 * The learner-progress export, the most-opened chart, and "continue where you
 * left off" on the student home.
 */
class ProgressToolsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(PilotQuickStartSeeder::class);
    }

    private function admin(): User
    {
        return User::firstOrCreate(['email' => 'admin@pilot.local'], [
            'name' => 'Pilot Admin', 'password' => bcrypt('password'), 'role' => User::ROLE_ADMIN,
        ]);
    }

    private function learner(string $email = 'learner@partner.com'): User
    {
        return User::firstOrCreate(['email' => $email], [
            'name' => 'Learner '.$email, 'password' => bcrypt('secret'), 'role' => User::ROLE_LEARNER,
        ]);
    }

    // --- CSV export -------------------------------------------------------

    /** Capture what the streamed download actually writes. */
    private function exportCsv(): string
    {
        $response = Closure::bind(
            fn (): StreamedResponse => UsersTable::exportProgress(),
            null,
            UsersTable::class,
        )();

        ob_start();
        $response->sendContent();

        return ob_get_clean();
    }

    public function test_the_export_button_is_on_the_users_screen(): void
    {
        Livewire::actingAs($this->admin())
            ->test(ListUsers::class)
            ->assertActionVisible(TestAction::make('exportProgress')->table());
    }

    public function test_the_export_lists_learners_with_their_progress(): void
    {
        $company = Company::create(['name' => 'Partner Co']);
        $learner = $this->learner('anna@partner.com');
        $learner->update(['company_id' => $company->id]);
        $learner->completedLessons()->attach(Lesson::first()->id, ['completed_at' => now()]);

        $csv = $this->exportCsv();

        $this->assertStringContainsString('Name,Email,Partner', $csv);
        $this->assertStringContainsString('anna@partner.com', $csv);
        $this->assertStringContainsString('Partner Co', $csv);
    }

    public function test_the_export_leaves_staff_out(): void
    {
        $this->learner('student@partner.com');
        $this->admin();
        User::create([
            'name' => 'A Creator', 'email' => 'creator@pilot.local',
            'password' => bcrypt('x'), 'role' => User::ROLE_CREATOR,
        ]);

        $csv = $this->exportCsv();

        $this->assertStringContainsString('student@partner.com', $csv);
        $this->assertStringNotContainsString('admin@pilot.local', $csv);
        $this->assertStringNotContainsString('creator@pilot.local', $csv);
    }

    public function test_the_export_counts_only_valid_certificates(): void
    {
        $learner = $this->learner('cert@partner.com');

        Certificate::create([
            'user_id' => $learner->id, 'course_id' => Course::first()->id,
            'number' => 'PA-OK', 'name' => $learner->name, 'score_percent' => 90, 'issued_at' => now(),
        ]);
        Certificate::create([
            'user_id' => $learner->id, 'course_id' => Course::first()->id,
            'number' => 'PA-REVOKED', 'name' => $learner->name, 'score_percent' => 90,
            'issued_at' => now(), 'revoked_at' => now(),
        ]);

        $row = collect(explode("\n", $this->exportCsv()))
            ->first(fn (string $line): bool => str_contains($line, 'cert@partner.com'));

        // Two certificates on file, one revoked: the report says 1.
        $this->assertSame('1', explode(',', $row)[4]);
    }

    // --- Most opened courses ----------------------------------------------

    public function test_the_chart_ranks_courses_by_how_often_students_opened_them(): void
    {
        $learner = $this->learner();

        foreach (range(1, 3) as $ignored) {
            ActivityEvent::record($learner, ActivityEvent::TYPE_COURSE_OPENED, 'Popular course');
        }
        ActivityEvent::record($learner, ActivityEvent::TYPE_COURSE_OPENED, 'Quiet course');

        $data = $this->chartData();

        $this->assertSame(['Popular course', 'Quiet course'], $data['labels']);
        $this->assertSame([3, 1], $data['datasets'][0]['data']);
    }

    public function test_staff_opens_are_not_counted(): void
    {
        ActivityEvent::record($this->admin(), ActivityEvent::TYPE_COURSE_OPENED, 'Admin checking');

        $this->assertSame([], $this->chartData()['labels']);
    }

    public function test_opens_outside_the_window_are_not_counted(): void
    {
        ActivityEvent::record($this->learner(), ActivityEvent::TYPE_COURSE_OPENED, 'Ancient');
        ActivityEvent::query()->update(['created_at' => now()->subYear()]);

        $this->assertSame([], $this->chartData()['labels']);
    }

    private function chartData(): array
    {
        $widget = new MostOpenedCourses;

        return (fn (): array => $this->getData())->call($widget);
    }

    // --- Continue where you left off --------------------------------------

    public function test_it_offers_the_next_unfinished_lesson(): void
    {
        $course = Course::first();
        $lessons = $course->publishedLessons()->get();
        $learner = $this->learner();

        $learner->completedLessons()->attach($lessons[0]->id, ['completed_at' => now()]);

        $this->actingAs($learner)
            ->get('/')
            ->assertStatus(200)
            ->assertSee('Continue where you left off')
            // The next one along, not the one just finished.
            ->assertSee($lessons[1]->title)
            ->assertSee(route('academy.lesson', [$course, $lessons[1]]), false);
    }

    public function test_nothing_is_offered_to_someone_who_has_not_started(): void
    {
        $this->actingAs($this->learner())
            ->get('/')
            ->assertStatus(200)
            ->assertDontSee('Continue where you left off');
    }

    public function test_nothing_is_offered_once_the_course_is_finished(): void
    {
        $learner = $this->learner();
        $course = Course::first();

        foreach ($course->publishedLessons()->get() as $lesson) {
            $learner->completedLessons()->attach($lesson->id, ['completed_at' => now()]);
        }

        $this->actingAs($learner)
            ->get('/')
            ->assertStatus(200)
            ->assertDontSee('Continue where you left off');
    }

    public function test_an_anonymous_visitor_with_session_progress_is_offered_the_next_lesson(): void
    {
        $course = Course::first();
        $lessons = $course->publishedLessons()->get();

        // Anonymous progress lives in the session, not the database.
        $this->withSession(['completed_lessons' => [$lessons[0]->id]])
            ->get('/')
            ->assertStatus(200)
            ->assertSee('Continue where you left off')
            ->assertSee($lessons[1]->title);
    }
}
