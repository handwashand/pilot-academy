<?php

namespace Tests\Feature;

use App\Filament\Resources\Courses\Pages\EditCourse;
use App\Filament\Resources\Courses\Pages\ListCourses;
use App\Models\Course;
use App\Models\Lesson;
use App\Models\User;
use Database\Seeders\PilotQuickStartSeeder;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CoursePublishingTest extends TestCase
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
            'name' => 'Pilot Admin',
            'password' => bcrypt('password'),
            'role' => 'admin',
        ]);
    }

    private function student(): User
    {
        return User::firstOrCreate(['email' => 'student@partner.com'], [
            'name' => 'Partner Student',
            'password' => bcrypt('secret'),
            'role' => 'learner',
        ]);
    }

    /** A second course, not published, with one published lesson so it *could* be. */
    private function draftCourse(): Course
    {
        $course = Course::create([
            'title' => 'Introduction to Leadership',
            'slug' => 'introduction-to-leadership',
            'description' => 'Draft course used in tests.',
            'level' => 'beginner',
            'sort_order' => 5,
        ]);

        $course->lessons()->create([
            'title' => 'Leading a team',
            'slug' => 'leading-a-team',
            'summary' => 'First steps.',
            'content' => '<p>Draft lesson body.</p>',
            'status' => Lesson::STATUS_PUBLISHED,
            'sort_order' => 1,
        ]);

        return $course->fresh();
    }

    // --- Course creation -------------------------------------------------

    public function test_a_new_course_is_a_draft(): void
    {
        $course = Course::create(['title' => 'Fresh', 'slug' => 'fresh', 'level' => 'beginner']);

        $this->assertSame(Course::STATUS_DRAFT, $course->status);
        $this->assertFalse($course->isPublished());
        $this->assertDatabaseHas('courses', ['slug' => 'fresh', 'status' => 'draft']);
    }

    public function test_a_new_course_does_not_appear_in_the_learner_listing(): void
    {
        $this->draftCourse();

        $this->get('/')
            ->assertStatus(200)
            ->assertSee('Pilot Quick Start')
            ->assertDontSee('Introduction to Leadership');
    }

    // --- Publishing ------------------------------------------------------

    public function test_an_admin_can_publish_a_draft_course(): void
    {
        $course = $this->draftCourse();

        Livewire::actingAs($this->admin())
            ->test(ListCourses::class)
            ->callAction(TestAction::make('publish')->table($course))
            ->assertHasNoActionErrors();

        $this->assertSame(Course::STATUS_PUBLISHED, $course->fresh()->status);
        $this->assertDatabaseHas('courses', ['id' => $course->id, 'status' => 'published']);
    }

    public function test_an_empty_course_is_not_published_by_the_publish_action(): void
    {
        $empty = Course::create(['title' => 'Empty', 'slug' => 'empty', 'level' => 'beginner']);

        Livewire::actingAs($this->admin())
            ->test(ListCourses::class)
            ->callAction(TestAction::make('publish')->table($empty));

        $this->assertSame(Course::STATUS_DRAFT, $empty->fresh()->status);
    }

    public function test_the_edit_form_refuses_to_publish_an_empty_course(): void
    {
        $empty = Course::create(['title' => 'Empty', 'slug' => 'empty', 'level' => 'beginner']);

        Livewire::actingAs($this->admin())
            ->test(EditCourse::class, ['record' => $empty->id])
            ->fillForm(['status' => Course::STATUS_PUBLISHED])
            ->call('save')
            ->assertHasFormErrors(['status']);

        $this->assertSame(Course::STATUS_DRAFT, $empty->fresh()->status);
    }

    public function test_a_published_course_appears_in_the_learner_listing(): void
    {
        $course = $this->draftCourse();
        $course->publish();

        $this->get('/')
            ->assertStatus(200)
            ->assertSee('Introduction to Leadership');

        $this->get(route('academy.course', $course))->assertStatus(200);
        $this->get('/sitemap.xml')->assertSee(route('academy.course', $course), false);
    }

    public function test_a_course_without_a_published_lesson_cannot_be_published(): void
    {
        $empty = Course::create(['title' => 'Empty', 'slug' => 'empty', 'level' => 'beginner']);

        $this->assertFalse($empty->canBePublished());
        $this->assertTrue($this->draftCourse()->canBePublished());
    }

    // --- Unpublishing ----------------------------------------------------

    public function test_unpublishing_hides_the_course_but_keeps_its_content(): void
    {
        $course = Course::first();
        $lessonCount = $course->lessons()->count();

        $this->get('/')->assertSee('Pilot Quick Start');

        Livewire::actingAs($this->admin())
            ->test(ListCourses::class)
            ->callAction(TestAction::make('unpublish')->table($course))
            ->assertHasNoActionErrors();

        $this->assertSame(Course::STATUS_DRAFT, $course->fresh()->status);
        $this->get('/')->assertStatus(200)->assertDontSee('Pilot Quick Start');
        $this->get('/sitemap.xml')->assertDontSee(route('academy.course', $course), false);

        // Nothing was deleted.
        $this->assertDatabaseHas('courses', ['id' => $course->id]);
        $this->assertSame($lessonCount, $course->lessons()->count());
        $this->assertGreaterThan(0, Lesson::where('course_id', $course->id)->first()->questions()->count());
    }

    // --- Access control --------------------------------------------------

    public function test_a_learner_cannot_open_a_draft_course_directly(): void
    {
        $course = $this->draftCourse();
        $lesson = $course->lessons()->first();

        $this->get(route('academy.course', $course))->assertNotFound();
        $this->get(route('academy.lesson', [$course, $lesson]))->assertNotFound();

        $this->actingAs($this->student())->get(route('academy.course', $course))->assertNotFound();
        $this->actingAs($this->student())->get(route('academy.lesson', [$course, $lesson]))->assertNotFound();
    }

    public function test_a_learner_cannot_open_an_archived_course_directly(): void
    {
        $course = Course::first();
        $course->update(['status' => Course::STATUS_ARCHIVED]);
        $lesson = $course->lessons()->first();

        $this->get('/')->assertDontSee('Pilot Quick Start');
        $this->get(route('academy.course', $course))->assertNotFound();
        $this->actingAs($this->student())
            ->get(route('academy.lesson', [$course, $lesson]))
            ->assertNotFound();
    }

    public function test_a_learner_cannot_submit_a_quiz_in_an_unpublished_course(): void
    {
        $course = Course::first();
        $lesson = $course->lessons()->with('questions.options')->first();
        $course->unpublish();

        $answers = [];
        foreach ($lesson->questions as $question) {
            $answers[$question->id] = $question->options->firstWhere('is_correct', true)->id;
        }

        $this->actingAs($this->student())
            ->post(route('academy.quiz', [$course, $lesson]), ['answers' => $answers])
            ->assertNotFound();

        $this->assertDatabaseMissing('lesson_user', ['lesson_id' => $lesson->id]);
    }

    public function test_a_learner_cannot_open_the_final_quiz_of_an_unpublished_course(): void
    {
        $course = Course::first();
        $course->update(['final_quiz_enabled' => true, 'status' => Course::STATUS_DRAFT]);

        $this->actingAs($this->student())
            ->get(route('academy.final.show', $course))
            ->assertNotFound();
    }

    public function test_an_admin_can_still_open_and_edit_a_draft_course(): void
    {
        $course = $this->draftCourse();
        $admin = $this->admin();

        // Admin panel: the draft is listed and editable.
        $this->actingAs($admin)->get('/admin/courses')->assertStatus(200)->assertSee('Introduction to Leadership');
        $this->actingAs($admin)->get("/admin/courses/{$course->id}/edit")->assertStatus(200);

        // Public pages: previewable, and flagged as not live.
        $this->actingAs($admin)
            ->get(route('academy.course', $course))
            ->assertStatus(200)
            ->assertSee('students cannot see this course');
    }

    public function test_only_admins_can_reach_the_publish_actions(): void
    {
        $course = $this->draftCourse();

        // The publish/unpublish actions live in the admin panel, and the panel
        // is closed to student accounts and to guests.
        $this->get('/admin/courses')->assertRedirect();
        $this->actingAs($this->student())->get('/admin/courses')->assertStatus(403);

        $this->assertSame(Course::STATUS_DRAFT, $course->fresh()->status);
    }

    // --- Course list -----------------------------------------------------

    public function test_the_admin_course_list_shows_the_status_and_matching_actions(): void
    {
        $draft = $this->draftCourse();
        $published = Course::first();

        Livewire::actingAs($this->admin())
            ->test(ListCourses::class)
            ->assertCanSeeTableRecords([$draft, $published])
            ->assertTableColumnStateSet('status', Course::STATUS_DRAFT, $draft)
            ->assertTableColumnStateSet('status', Course::STATUS_PUBLISHED, $published)
            ->assertActionVisible(TestAction::make('publish')->table($draft))
            ->assertActionHidden(TestAction::make('unpublish')->table($draft))
            ->assertActionVisible(TestAction::make('unpublish')->table($published))
            ->assertActionHidden(TestAction::make('publish')->table($published));
    }

    public function test_an_archived_course_offers_neither_publish_nor_unpublish(): void
    {
        $course = Course::first();
        $course->update(['status' => Course::STATUS_ARCHIVED]);

        Livewire::actingAs($this->admin())
            ->test(ListCourses::class)
            ->assertTableColumnStateSet('status', Course::STATUS_ARCHIVED, $course)
            ->assertActionHidden(TestAction::make('publish')->table($course))
            ->assertActionHidden(TestAction::make('unpublish')->table($course))
            ->assertActionVisible(TestAction::make('edit')->table($course));
    }

    // --- Existing data / migration ---------------------------------------

    public function test_courses_that_were_visible_before_the_migration_stay_visible(): void
    {
        // The seeded course stands in for production data: visible before, and
        // still visible after the status migration ran as part of this test's
        // fresh database.
        $this->assertSame(Course::STATUS_PUBLISHED, Course::first()->status);

        $this->get('/')->assertStatus(200)->assertSee('Pilot Quick Start');
    }
}
