<?php

namespace Tests\Feature;

use App\Filament\Resources\Courses\CourseResource;
use App\Filament\Resources\Courses\Pages\CreateCourse;
use App\Filament\Resources\Courses\Pages\ListCourses;
use App\Filament\Resources\Lessons\LessonResource;
use App\Filament\Resources\Lessons\Pages\ListLessons;
use App\Filament\Resources\Users\Pages\CreateUser;
use App\Filament\Resources\Users\Pages\EditUser;
use App\Filament\Widgets\ActivityOverTime;
use App\Filament\Widgets\CompletionsByCompany;
use App\Filament\Widgets\HardestLessons;
use App\Filament\Widgets\StalledLearners;
use App\Filament\Widgets\StudentProgressOverview;
use App\Models\ActivityEvent;
use App\Models\Certificate;
use App\Models\Company;
use App\Models\Course;
use App\Models\Lesson;
use App\Models\Product;
use App\Models\QuizAttempt;
use App\Models\User;
use Database\Seeders\PilotQuickStartSeeder;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Filament\Widgets\AccountWidget;
use Filament\Widgets\FilamentInfoWidget;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Seeded data throughout — an empty table hides half the bugs.
        $this->seed(PilotQuickStartSeeder::class);
    }

    private function admin(): User
    {
        return User::firstOrCreate(['email' => 'admin@pilot.local'], [
            'name' => 'Pilot Admin', 'password' => bcrypt('password'), 'role' => User::ROLE_ADMIN,
        ]);
    }

    private function creator(): User
    {
        return User::firstOrCreate(['email' => 'creator@pilot.local'], [
            'name' => 'Creator', 'password' => bcrypt('password'), 'role' => User::ROLE_CREATOR,
        ]);
    }

    private function learner(string $email = 'learner@partner.com', ?Company $company = null): User
    {
        return User::firstOrCreate(['email' => $email], [
            'name' => 'Learner '.$email, 'password' => bcrypt('secret'),
            'role' => User::ROLE_LEARNER, 'company_id' => $company?->id,
        ]);
    }

    // --- Widgets render with data ----------------------------------------

    public function test_the_dashboard_renders_for_an_admin(): void
    {
        $company = Company::create(['name' => 'Partner Co']);
        $learner = $this->learner('active@partner.com', $company);
        $learner->completedLessons()->attach(Lesson::first()->id, ['completed_at' => now()]);

        $this->actingAs($this->admin())->get('/admin')->assertStatus(200);

        Livewire::actingAs($this->admin())->test(StudentProgressOverview::class)->assertSuccessful();
        Livewire::actingAs($this->admin())->test(CompletionsByCompany::class)->assertSuccessful();
        Livewire::actingAs($this->admin())->test(StalledLearners::class)->assertSuccessful();
    }

    public function test_the_widgets_render_on_an_empty_academy(): void
    {
        Course::query()->delete();
        Company::query()->delete();

        Livewire::actingAs($this->admin())->test(StudentProgressOverview::class)->assertSuccessful();
        Livewire::actingAs($this->admin())->test(CompletionsByCompany::class)->assertSuccessful();
        Livewire::actingAs($this->admin())->test(StalledLearners::class)->assertSuccessful();
    }

    public function test_learner_widgets_are_hidden_from_creators(): void
    {
        $this->actingAs($this->admin());
        $this->assertTrue(StudentProgressOverview::canView());
        $this->assertTrue(CompletionsByCompany::canView());
        $this->assertTrue(StalledLearners::canView());

        $this->actingAs($this->creator());
        $this->assertFalse(StudentProgressOverview::canView());
        $this->assertFalse(CompletionsByCompany::canView());
        $this->assertFalse(StalledLearners::canView());
    }

    public function test_a_learner_cannot_reach_the_panel_at_all(): void
    {
        $this->actingAs($this->learner())->get('/admin')->assertStatus(403);
    }

    /**
     * Signing out belongs in the profile menu, top right — not as a card in the
     * middle of the dashboard, which is where Filament puts it out of the box.
     */
    public function test_the_dashboard_carries_no_sign_out_card(): void
    {
        $widgets = Filament::getPanel('admin')->getWidgets();

        $this->assertNotContains(AccountWidget::class, $widgets);
        $this->assertNotContains(FilamentInfoWidget::class, $widgets);

        // Our own widgets are still discovered and shown.
        $this->assertContains(StudentProgressOverview::class, $widgets);
        $this->assertContains(CompletionsByCompany::class, $widgets);
        $this->assertContains(StalledLearners::class, $widgets);
    }

    public function test_sign_out_is_still_reachable_from_the_profile_menu(): void
    {
        // The menu is built for the signed-in user, so sign in first.
        $this->actingAs($this->admin());

        // Filament builds the logout item into the user menu by default; the
        // dashboard must not be the only way out.
        $this->assertArrayHasKey('logout', Filament::getPanel('admin')->getUserMenuItems());

        $this->post(Filament::getPanel('admin')->getLogoutUrl());
        $this->assertGuest();
    }

    // --- The numbers are learner-only ------------------------------------

    public function test_staff_activity_never_lands_in_the_student_figures(): void
    {
        $lesson = Lesson::first();
        $this->learner()->completedLessons()->attach($lesson->id, ['completed_at' => now()]);
        $this->creator()->completedLessons()->attach($lesson->id, ['completed_at' => now()]);
        $this->admin()->completedLessons()->attach($lesson->id, ['completed_at' => now()]);

        $stats = $this->stats();

        $this->assertSame(1, $stats['Students']);
        $this->assertSame(1, $stats['Active students']);
        $this->assertSame(1, $stats['Lesson completions']);
    }

    public function test_staff_certificates_are_left_out_of_the_totals(): void
    {
        Course::first()->update(['final_quiz_enabled' => true]);
        $this->certificateFor($this->learner(), 'PA-L', 90);
        $this->certificateFor($this->admin(), 'PA-A', 50);

        $this->assertSame(2, Certificate::count());
        $stats = $this->stats();

        // One certificate, and the average is the learner's 90 — not 70.
        $this->assertSame(1, $stats['Certificates issued']);
    }

    // --- Completions by company ------------------------------------------

    public function test_a_company_with_no_students_charts_as_zero(): void
    {
        $busy = Company::create(['name' => 'Busy Co']);
        Company::create(['name' => 'Empty Co']);

        $this->learner('busy@partner.com', $busy)
            ->completedLessons()->attach(Lesson::first()->id, ['completed_at' => now()]);

        $data = $this->chartData();

        // Both companies appear; the empty one is 0 rather than missing.
        $this->assertSame(['Busy Co', 'Empty Co'], $data['labels']);
        $this->assertGreaterThan(0, $data['datasets'][0]['data'][0]);
        $this->assertSame(0, $data['datasets'][0]['data'][1]);
    }

    // --- The actionable list ---------------------------------------------

    public function test_it_lists_a_student_who_started_and_went_quiet(): void
    {
        $quiet = $this->learner('quiet@partner.com');
        $quiet->completedLessons()->attach(Lesson::first()->id, ['completed_at' => now()->subMonth()]);

        Livewire::actingAs($this->admin())
            ->test(StalledLearners::class)
            ->assertCanSeeTableRecords([$quiet]);
    }

    public function test_a_recently_active_student_is_not_listed(): void
    {
        $active = $this->learner('active@partner.com');
        $active->completedLessons()->attach(Lesson::first()->id, ['completed_at' => now()->subDay()]);

        Livewire::actingAs($this->admin())
            ->test(StalledLearners::class)
            ->assertCanNotSeeTableRecords([$active]);
    }

    public function test_finishing_late_still_counts_as_finished(): void
    {
        // Quiet for a month, but holds a certificate — not outstanding.
        $finished = $this->learner('finished@partner.com');
        $finished->completedLessons()->attach(Lesson::first()->id, ['completed_at' => now()->subMonth()]);
        $this->certificateFor($finished, 'PA-DONE', 88);

        Livewire::actingAs($this->admin())
            ->test(StalledLearners::class)
            ->assertCanNotSeeTableRecords([$finished]);
    }

    public function test_a_student_who_never_started_is_not_listed(): void
    {
        $never = $this->learner('never@partner.com');

        Livewire::actingAs($this->admin())
            ->test(StalledLearners::class)
            ->assertCanNotSeeTableRecords([$never]);
    }

    // --- Hardest lessons --------------------------------------------------

    /** @param array<string, int> $outcomes status => how many attempts */
    private function attempts(Lesson $lesson, array $outcomes, ?User $user = null): void
    {
        $user ??= $this->learner('attempts@partner.com');

        foreach ($outcomes as $status => $count) {
            for ($i = 0; $i < $count; $i++) {
                QuizAttempt::create([
                    'user_id' => $user->id, 'lesson_id' => $lesson->id,
                    'status' => $status, 'started_at' => now(), 'submitted_at' => now(),
                    'score' => 1, 'total' => 3,
                ]);
            }
        }
    }

    public function test_it_ranks_lessons_by_fail_rate(): void
    {
        $lessons = Lesson::published()->orderBy('id')->take(2)->get();

        $this->attempts($lessons[0], [QuizAttempt::STATUS_FAILED => 4, QuizAttempt::STATUS_PASSED => 1]);
        $this->attempts($lessons[1], [QuizAttempt::STATUS_PASSED => 4, QuizAttempt::STATUS_FAILED => 1]);

        Livewire::actingAs($this->admin())
            ->test(HardestLessons::class)
            ->assertSuccessful()
            ->assertCanSeeTableRecords([$lessons[0], $lessons[1]])
            // Worst first.
            ->assertCanSeeTableRecords([$lessons[0], $lessons[1]], inOrder: true);
    }

    public function test_a_lesson_with_too_few_attempts_is_ignored(): void
    {
        $lesson = Lesson::published()->first();
        $this->attempts($lesson, [QuizAttempt::STATUS_FAILED => 2]);

        Livewire::actingAs($this->admin())
            ->test(HardestLessons::class)
            ->assertCanNotSeeTableRecords([$lesson]);
    }

    public function test_attempts_still_in_progress_do_not_count_as_failures(): void
    {
        $lesson = Lesson::published()->first();
        $this->attempts($lesson, [
            QuizAttempt::STATUS_PASSED => 3,
            QuizAttempt::STATUS_IN_PROGRESS => 5,
        ]);

        // Three graded attempts, all passed: a 0% fail rate, not 62%.
        $row = $this->hardestRowFor($lesson);

        $this->assertNotNull($row);
        $this->assertSame(3, (int) $row->attempts);
        $this->assertSame(0, (int) $row->failed);
    }

    public function test_staff_attempts_are_excluded(): void
    {
        $lesson = Lesson::published()->first();

        // Admins take quizzes while checking a course; that is not student data.
        $this->attempts($lesson, [QuizAttempt::STATUS_FAILED => 5], $this->admin());

        Livewire::actingAs($this->admin())
            ->test(HardestLessons::class)
            ->assertCanNotSeeTableRecords([$lesson]);
    }

    private function hardestRowFor(Lesson $lesson): ?object
    {
        $widget = new HardestLessons;

        return (fn () => $this->hardestLessons())->call($widget)
            ->where('lessons.id', $lesson->id)
            ->first();
    }

    // --- Activity over time -----------------------------------------------

    public function test_the_activity_chart_covers_thirty_days_and_counts_per_day(): void
    {
        $learner = $this->learner('busy@partner.com');

        ActivityEvent::record($learner, ActivityEvent::TYPE_LESSON_COMPLETED, 'Lesson one');
        ActivityEvent::record($learner, ActivityEvent::TYPE_LESSON_COMPLETED, 'Lesson two');
        ActivityEvent::record($learner, ActivityEvent::TYPE_LOGIN);

        $data = $this->activityData();

        $this->assertCount(30, $data['labels']);
        $this->assertCount(30, $data['datasets'][0]['data']);

        // Today is the last bucket.
        $this->assertSame(2, end($data['datasets'][0]['data']));
        $this->assertSame(1, end($data['datasets'][1]['data']));
    }

    public function test_activity_older_than_the_window_is_not_charted(): void
    {
        $learner = $this->learner('old@partner.com');
        ActivityEvent::record($learner, ActivityEvent::TYPE_LESSON_COMPLETED, 'Ancient');
        ActivityEvent::query()->update(['created_at' => now()->subMonths(3)]);

        $data = $this->activityData();

        $this->assertSame(0, array_sum($data['datasets'][0]['data']));
    }

    public function test_staff_activity_is_not_charted(): void
    {
        ActivityEvent::record($this->admin(), ActivityEvent::TYPE_LESSON_COMPLETED, 'Admin checking');
        ActivityEvent::record($this->creator(), ActivityEvent::TYPE_LOGIN);

        $data = $this->activityData();

        $this->assertSame(0, array_sum($data['datasets'][0]['data']));
        $this->assertSame(0, array_sum($data['datasets'][1]['data']));
    }

    // --- Bulk publishing --------------------------------------------------

    public function test_bulk_publish_skips_courses_with_no_published_lesson(): void
    {
        $ready = Course::first();
        $ready->unpublish();

        $empty = Course::create(['title' => 'Empty', 'slug' => 'empty', 'level' => 'beginner']);

        Livewire::actingAs($this->admin())
            ->test(ListCourses::class)
            ->selectTableRecords([$ready->id, $empty->id])
            ->callAction(TestAction::make('publish')->table()->bulk());

        // The one that could go live did; the empty one was left alone.
        $this->assertSame(Course::STATUS_PUBLISHED, $ready->fresh()->status);
        $this->assertSame(Course::STATUS_DRAFT, $empty->fresh()->status);
    }

    public function test_bulk_unpublish_takes_courses_off_the_student_site(): void
    {
        $course = Course::first();

        Livewire::actingAs($this->admin())
            ->test(ListCourses::class)
            ->selectTableRecords([$course->id])
            ->callAction(TestAction::make('unpublish')->table()->bulk());

        $this->assertSame(Course::STATUS_DRAFT, $course->fresh()->status);
        // Content is untouched.
        $this->assertGreaterThan(0, $course->lessons()->count());
    }

    public function test_bulk_publishing_lessons(): void
    {
        $lessons = Lesson::published()->take(2)->get();
        $lessons->each->unpublish();

        Livewire::actingAs($this->admin())
            ->test(ListLessons::class)
            ->selectTableRecords($lessons->pluck('id')->all())
            ->callAction(TestAction::make('publish')->table()->bulk());

        $lessons->each(fn (Lesson $lesson) => $this->assertSame(
            Lesson::STATUS_PUBLISHED,
            $lesson->fresh()->status,
        ));
    }

    private function activityData(): array
    {
        $widget = new ActivityOverTime;

        return (fn (): array => $this->getData())->call($widget);
    }

    // --- Navigation badges ------------------------------------------------

    public function test_badges_count_drafts_and_are_scoped_to_the_viewer(): void
    {
        $product = Product::create(['name' => 'GARM', 'slug' => 'garm']);
        $mine = Course::create(['product_id' => $product->id, 'title' => 'Mine', 'slug' => 'mine', 'level' => 'beginner']);
        Course::create(['title' => 'Someone else\'s', 'slug' => 'theirs', 'level' => 'beginner']);

        $creator = $this->creator();
        $creator->products()->attach($product);

        // The seeded course is published, so only the two new drafts count.
        $this->actingAs($this->admin());
        $this->assertSame('2', CourseResource::getNavigationBadge());

        // A creator is told about their own backlog and nobody else's.
        $this->actingAs($creator);
        $this->assertSame('1', CourseResource::getNavigationBadge());
        $this->assertNotNull(CourseResource::getNavigationBadgeTooltip());

        $mine->delete();
        $this->assertNull(CourseResource::getNavigationBadge());
    }

    public function test_lesson_badge_counts_drafts(): void
    {
        $this->actingAs($this->admin());
        $this->assertNull(LessonResource::getNavigationBadge());

        Lesson::first()->unpublish();
        $this->assertSame('1', LessonResource::getNavigationBadge());
    }

    // --- Forms are submitted, not merely rendered -------------------------

    public function test_creating_a_course_through_the_form_actually_saves(): void
    {
        $product = Product::create(['name' => 'GARM', 'slug' => 'garm']);

        Livewire::actingAs($this->admin())
            ->test(CreateCourse::class)
            ->fillForm([
                'product_id' => $product->id,
                'title' => 'Brand new course',
                'slug' => 'brand-new-course',
                'level' => 'beginner',
                'sort_order' => 3,
                'pass_percent' => 80,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $course = Course::where('slug', 'brand-new-course')->first();

        $this->assertNotNull($course);
        $this->assertSame(Course::STATUS_DRAFT, $course->status);
        $this->assertTrue($course->product->is($product));
    }

    public function test_creating_a_creator_attaches_the_chosen_products(): void
    {
        $product = Product::create(['name' => 'PTM', 'slug' => 'ptm']);

        Livewire::actingAs($this->admin())
            ->test(CreateUser::class)
            ->fillForm([
                'name' => 'New Creator',
                'email' => 'new.creator@pilot.local',
                'password' => 'password123',
                'role' => User::ROLE_CREATOR,
                'products' => [$product->id],
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $created = User::where('email', 'new.creator@pilot.local')->first();

        $this->assertNotNull($created);
        $this->assertTrue($created->isCreator());
        // The relationship, not just the row — a saved user with no products
        // is a creator who can manage nothing.
        $this->assertTrue($created->ownsProduct($product));
    }

    public function test_saving_a_user_with_a_blank_password_keeps_the_old_one(): void
    {
        $user = User::create([
            'name' => 'Keeps Password', 'email' => 'keeps@partner.com',
            'password' => Hash::make('original-secret'), 'role' => User::ROLE_LEARNER,
        ]);
        $before = $user->password;

        Livewire::actingAs($this->admin())
            ->test(EditUser::class, ['record' => $user->getRouteKey()])
            ->fillForm(['name' => 'Renamed', 'password' => ''])
            ->call('save')
            ->assertHasNoFormErrors();

        $user->refresh();

        $this->assertSame('Renamed', $user->name);
        $this->assertSame($before, $user->password, 'A blank password field must not wipe the stored hash.');
        $this->assertTrue(Hash::check('original-secret', $user->password));
    }

    // --- helpers ----------------------------------------------------------

    private function certificateFor(User $user, string $number, int $score): Certificate
    {
        return Certificate::create([
            'user_id' => $user->id,
            'course_id' => Course::first()->id,
            'number' => $number,
            'name' => $user->name,
            'score_percent' => $score,
            'issued_at' => now(),
        ]);
    }

    /** @return array<string, int> */
    private function stats(): array
    {
        $widget = new StudentProgressOverview;
        $out = [];

        foreach ((fn (): array => $this->getStats())->call($widget) as $stat) {
            $out[$stat->getLabel()] = (int) $stat->getValue();
        }

        return $out;
    }

    private function chartData(): array
    {
        $widget = new CompletionsByCompany;

        return (fn (): array => $this->getData())->call($widget);
    }
}
