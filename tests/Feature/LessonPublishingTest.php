<?php

namespace Tests\Feature;

use App\Filament\Resources\Lessons\Pages\ListLessons;
use App\Models\Course;
use App\Models\Lesson;
use App\Models\User;
use Database\Seeders\PilotQuickStartSeeder;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class LessonPublishingTest extends TestCase
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
            'is_admin' => true,
        ]);
    }

    private function student(): User
    {
        return User::firstOrCreate(['email' => 'student@partner.com'], [
            'name' => 'Partner Student',
            'password' => bcrypt('secret'),
            'is_admin' => false,
        ]);
    }

    /** A new lesson in the seeded (published) course. */
    private function newLesson(array $overrides = []): Lesson
    {
        return Course::first()->lessons()->create([
            'title' => 'Reading the fuel report',
            'slug' => 'reading-the-fuel-report',
            'summary' => 'Lesson used in tests.',
            'content' => '<p>Lesson body.</p>',
            'sort_order' => 99,
            ...$overrides,
        ]);
    }

    private function draftLesson(): Lesson
    {
        return $this->newLesson(['status' => Lesson::STATUS_DRAFT]);
    }

    // --- Lesson creation -------------------------------------------------

    public function test_a_new_lesson_is_published_with_its_course(): void
    {
        $course = Course::first();
        $lesson = $this->newLesson();

        // The course is the gate, so a lesson written into a published course
        // goes live with it — no second publish step.
        $this->assertSame(Lesson::STATUS_PUBLISHED, $lesson->status);
        $this->get(route('academy.course', $course))->assertSee('Reading the fuel report');
    }

    public function test_a_new_lesson_in_a_draft_course_is_still_hidden(): void
    {
        $course = Course::first();
        $course->unpublish();
        $this->newLesson();

        $this->get('/')->assertStatus(200)->assertDontSee('Reading the fuel report');
        $this->get(route('academy.course', $course))->assertNotFound();
    }

    public function test_a_lesson_can_be_held_back_as_a_draft(): void
    {
        $course = Course::first();
        $this->draftLesson();

        $this->assertDatabaseHas('lessons', ['slug' => 'reading-the-fuel-report', 'status' => 'draft']);
        $this->get('/')->assertStatus(200)->assertDontSee('Reading the fuel report');
        $this->get(route('academy.course', $course))->assertStatus(200)->assertDontSee('Reading the fuel report');
    }

    // --- Publishing ------------------------------------------------------

    public function test_an_admin_can_publish_a_draft_lesson(): void
    {
        $lesson = $this->draftLesson();
        $course = Course::first();

        Livewire::actingAs($this->admin())
            ->test(ListLessons::class)
            ->callAction(TestAction::make('publish')->table($lesson))
            ->assertHasNoActionErrors();

        $this->assertSame(Lesson::STATUS_PUBLISHED, $lesson->fresh()->status);

        $this->get(route('academy.course', $course))->assertSee('Reading the fuel report');
        $this->get(route('academy.lesson', [$course, $lesson]))->assertStatus(200);
        $this->get('/sitemap.xml')->assertSee(route('academy.lesson', [$course, $lesson]), false);
    }

    // --- Unpublishing ----------------------------------------------------

    public function test_unpublishing_hides_the_lesson_but_keeps_its_content(): void
    {
        $course = Course::first();
        $lesson = $course->lessons()->first();
        $questionCount = $lesson->questions()->count();

        $this->get(route('academy.course', $course))->assertSee($lesson->title);

        Livewire::actingAs($this->admin())
            ->test(ListLessons::class)
            ->callAction(TestAction::make('unpublish')->table($lesson))
            ->assertHasNoActionErrors();

        $this->assertSame(Lesson::STATUS_DRAFT, $lesson->fresh()->status);
        $this->get(route('academy.course', $course))->assertDontSee($lesson->title);
        $this->get('/sitemap.xml')->assertDontSee(route('academy.lesson', [$course, $lesson]), false);

        // Nothing was deleted.
        $this->assertDatabaseHas('lessons', ['id' => $lesson->id]);
        $this->assertSame($questionCount, $lesson->questions()->count());
    }

    public function test_unpublishing_every_lesson_stops_the_course_being_republished(): void
    {
        $course = Course::first();
        $course->lessons->each->unpublish();

        $this->assertFalse($course->fresh()->canBePublished());
    }

    // --- Access control --------------------------------------------------

    public function test_a_learner_cannot_open_a_draft_lesson_directly(): void
    {
        $course = Course::first();
        $lesson = $this->draftLesson();

        $this->get(route('academy.lesson', [$course, $lesson]))->assertNotFound();
        $this->actingAs($this->student())
            ->get(route('academy.lesson', [$course, $lesson]))
            ->assertNotFound();
    }

    public function test_a_learner_cannot_open_an_archived_lesson_directly(): void
    {
        $course = Course::first();
        $lesson = $course->lessons()->first();
        $lesson->update(['status' => Lesson::STATUS_ARCHIVED]);

        $this->get(route('academy.course', $course))->assertDontSee($lesson->title);
        $this->actingAs($this->student())
            ->get(route('academy.lesson', [$course, $lesson]))
            ->assertNotFound();
    }

    public function test_a_learner_cannot_submit_the_quiz_of_an_unpublished_lesson(): void
    {
        $course = Course::first();
        $lesson = $course->lessons()->with('questions.options')->first();
        $lesson->unpublish();

        $answers = [];
        foreach ($lesson->questions as $question) {
            $answers[$question->id] = $question->options->firstWhere('is_correct', true)->id;
        }

        $this->actingAs($this->student())
            ->post(route('academy.quiz', [$course, $lesson]), ['answers' => $answers])
            ->assertNotFound();

        $this->assertDatabaseMissing('lesson_user', ['lesson_id' => $lesson->id]);
    }

    public function test_an_admin_can_still_open_and_edit_a_draft_lesson(): void
    {
        $course = Course::first();
        $lesson = $this->draftLesson();
        $admin = $this->admin();

        $this->actingAs($admin)->get('/admin/lessons')->assertStatus(200)->assertSee('Reading the fuel report');
        $this->actingAs($admin)->get("/admin/lessons/{$lesson->id}/edit")->assertStatus(200);

        $this->actingAs($admin)
            ->get(route('academy.lesson', [$course, $lesson]))
            ->assertStatus(200)
            ->assertSee('students cannot see this');
    }

    public function test_only_admins_can_reach_the_publish_actions(): void
    {
        $lesson = $this->draftLesson();

        $this->get('/admin/lessons')->assertRedirect();
        $this->actingAs($this->student())->get('/admin/lessons')->assertStatus(403);

        $this->assertSame(Lesson::STATUS_DRAFT, $lesson->fresh()->status);
    }

    // --- Lesson list -----------------------------------------------------

    public function test_the_admin_lesson_list_shows_the_status_and_matching_actions(): void
    {
        $draft = $this->draftLesson();
        $published = Course::first()->lessons()->published()->first();

        Livewire::actingAs($this->admin())
            ->test(ListLessons::class)
            ->assertCanSeeTableRecords([$draft, $published])
            ->assertTableColumnStateSet('status', Lesson::STATUS_DRAFT, $draft)
            ->assertTableColumnStateSet('status', Lesson::STATUS_PUBLISHED, $published)
            ->assertActionVisible(TestAction::make('publish')->table($draft))
            ->assertActionHidden(TestAction::make('unpublish')->table($draft))
            ->assertActionVisible(TestAction::make('unpublish')->table($published))
            ->assertActionHidden(TestAction::make('publish')->table($published));
    }

    public function test_an_archived_lesson_offers_neither_publish_nor_unpublish(): void
    {
        $lesson = Course::first()->lessons()->first();
        $lesson->update(['status' => Lesson::STATUS_ARCHIVED]);

        Livewire::actingAs($this->admin())
            ->test(ListLessons::class)
            ->assertTableColumnStateSet('status', Lesson::STATUS_ARCHIVED, $lesson)
            ->assertActionHidden(TestAction::make('publish')->table($lesson))
            ->assertActionHidden(TestAction::make('unpublish')->table($lesson))
            ->assertActionVisible(TestAction::make('edit')->table($lesson));
    }

    // --- Existing data / migration ---------------------------------------

    public function test_lessons_that_were_visible_before_the_migration_stay_visible(): void
    {
        $course = Course::first();

        $this->assertSame($course->lessons()->count(), $course->publishedLessons()->count());
        $this->get(route('academy.course', $course))
            ->assertStatus(200)
            ->assertSee($course->lessons()->first()->title);
    }
}
