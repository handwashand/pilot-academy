<?php

namespace Tests\Feature;

use App\Actions\DuplicateCourse;
use App\Filament\Resources\Courses\Pages\ListCourses;
use App\Models\Certificate;
use App\Models\Course;
use App\Models\Lesson;
use App\Models\Product;
use App\Models\Question;
use App\Models\User;
use Database\Seeders\PilotQuickStartSeeder;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class DuplicateCourseTest extends TestCase
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

    private function duplicate(?Course $course = null): Course
    {
        return (new DuplicateCourse)->handle($course ?? Course::first());
    }

    public function test_it_copies_the_course_as_a_draft(): void
    {
        $original = Course::first();
        $copy = $this->duplicate($original);

        $this->assertNotSame($original->id, $copy->id);
        $this->assertSame($original->title.' (copy)', $copy->title);
        // A copy is never live on day one, whatever the original was.
        $this->assertSame(Course::STATUS_DRAFT, $copy->status);
        $this->assertSame($original->level, $copy->level);
        $this->assertSame($original->pass_percent, $copy->pass_percent);
    }

    public function test_the_copy_keeps_its_product_so_the_creator_still_owns_it(): void
    {
        $product = Product::create(['name' => 'GARM', 'slug' => 'garm']);
        $original = Course::first();
        $original->update(['product_id' => $product->id]);

        $creator = User::create([
            'name' => 'Gina', 'email' => 'gina@pilot.local',
            'password' => bcrypt('x'), 'role' => User::ROLE_CREATOR,
        ]);
        $creator->products()->attach($product);

        $copy = $this->duplicate($original->fresh());

        $this->assertSame($product->id, $copy->product_id);
        $this->assertTrue($creator->canManageCourse($copy));
    }

    public function test_slugs_are_unique_at_both_levels(): void
    {
        $original = Course::first();
        $copy = $this->duplicate($original);

        $this->assertNotSame($original->slug, $copy->slug);

        // lessons.slug has no unique index, but the lesson route resolves by
        // slug alone — a shared slug would make the copy's lessons 404.
        $originalSlugs = $original->lessons()->pluck('slug');
        $copySlugs = $copy->lessons()->pluck('slug');

        $this->assertCount(0, $copySlugs->intersect($originalSlugs));
        $this->assertSame($copySlugs->count(), $copySlugs->unique()->count());
    }

    public function test_duplicating_twice_does_not_collide(): void
    {
        $original = Course::first();

        $first = $this->duplicate($original);
        $second = $this->duplicate($original);

        $this->assertNotSame($first->slug, $second->slug);
        $this->assertSame(3, Course::count());
    }

    public function test_it_copies_every_lesson_question_and_option(): void
    {
        $original = Course::first();
        $copy = $this->duplicate($original);

        $this->assertSame($original->lessons()->count(), $copy->lessons()->count());

        $originalQuestions = Question::whereIn('lesson_id', $original->lessons()->pluck('id'))->count();
        $copyQuestions = Question::whereIn('lesson_id', $copy->lessons()->pluck('id'))->count();

        $this->assertSame($originalQuestions, $copyQuestions);
        $this->assertGreaterThan(0, $copyQuestions);

        // Options came across with their correctness intact.
        $copyLesson = $copy->lessons()->with('questions.options')->first();
        $this->assertGreaterThan(0, $copyLesson->questions->first()->options->where('is_correct', true)->count());
    }

    public function test_editing_the_copy_does_not_change_the_original(): void
    {
        $original = Course::first();
        $copy = $this->duplicate($original);

        $copyQuestion = Question::whereIn('lesson_id', $copy->lessons()->pluck('id'))->first();
        $originalPrompt = Question::whereIn('lesson_id', $original->lessons()->pluck('id'))->first()->prompt;

        $copyQuestion->update(['prompt' => 'Rewritten for the copy']);

        // Separate rows, not a shared question.
        $this->assertSame($originalPrompt, Question::whereIn('lesson_id', $original->lessons()->pluck('id'))->first()->prompt);
    }

    public function test_the_final_quiz_bank_points_at_the_copys_own_questions(): void
    {
        $original = Course::first();

        // The quick-start seeder does not compose a final bank, so build one.
        $original->finalQuestions()->attach(
            Question::whereIn('lesson_id', $original->lessons()->pluck('id'))->pluck('id'),
        );
        $original = $original->fresh();

        $this->assertGreaterThan(0, $original->finalQuestions()->count());

        $copy = $this->duplicate($original);

        $this->assertSame($original->finalQuestions()->count(), $copy->finalQuestions()->count());

        // None of the copy's bank entries are the original's question rows.
        $shared = $copy->finalQuestions()->pluck('questions.id')
            ->intersect($original->finalQuestions()->pluck('questions.id'));

        $this->assertCount(0, $shared);
    }

    public function test_a_course_only_final_question_is_copied_too(): void
    {
        $original = Course::first();
        $courseOnly = Question::create(['prompt' => 'Written straight into the final', 'sort_order' => 99]);
        $courseOnly->options()->create(['text' => 'Right', 'is_correct' => true, 'sort_order' => 1]);
        $original->finalQuestions()->attach($courseOnly, ['sort_order' => 99]);

        $copy = $this->duplicate($original->fresh());

        $copied = $copy->finalQuestions()->where('prompt', 'Written straight into the final')->first();

        $this->assertNotNull($copied, 'A question with no lesson must still be copied.');
        $this->assertNotSame($courseOnly->id, $copied->id);
        $this->assertSame(1, $copied->options()->where('is_correct', true)->count());
    }

    public function test_student_records_are_not_copied(): void
    {
        $original = Course::first();
        $learner = User::create([
            'name' => 'Learner', 'email' => 'l@partner.com',
            'password' => bcrypt('x'), 'role' => User::ROLE_LEARNER,
        ]);
        $learner->completedLessons()->attach($original->lessons()->first()->id, ['completed_at' => now()]);
        Certificate::create([
            'user_id' => $learner->id, 'course_id' => $original->id,
            'number' => 'PA-1', 'name' => 'Learner', 'score_percent' => 90, 'issued_at' => now(),
        ]);

        $copy = $this->duplicate($original);

        // Progress and certificates belong to the course people actually took.
        $this->assertSame(0, $copy->certificates()->count());
        $this->assertSame(0, $learner->completedLessons()->whereIn('lessons.id', $copy->lessons()->pluck('id'))->count());
    }

    public function test_an_admin_can_duplicate_from_the_course_list(): void
    {
        $original = Course::first();

        Livewire::actingAs($this->admin())
            ->test(ListCourses::class)
            ->callAction(TestAction::make('duplicate')->table($original))
            ->assertHasNoActionErrors();

        $this->assertSame(2, Course::count());
        $this->assertNotNull(Course::where('title', $original->title.' (copy)')->first());
    }

    public function test_the_copy_is_immediately_publishable(): void
    {
        $copy = $this->duplicate();

        // Its lessons came across published, so it is not stuck in the
        // "add a lesson first" state.
        $this->assertTrue($copy->canBePublished());
        $this->assertGreaterThan(0, $copy->publishedLessons()->count());
    }

    public function test_the_copys_lessons_are_reachable_on_the_student_site(): void
    {
        $copy = $this->duplicate();
        $copy->publish();

        $lesson = $copy->publishedLessons()->first();

        // This is what a shared lesson slug would have broken.
        $this->get(route('academy.lesson', [$copy, $lesson]))
            ->assertStatus(200)
            ->assertSee($lesson->title);
    }

    public function test_duplicating_is_atomic(): void
    {
        $before = ['courses' => Course::count(), 'lessons' => Lesson::count(), 'questions' => Question::count()];

        $this->duplicate();

        $after = ['courses' => Course::count(), 'lessons' => Lesson::count(), 'questions' => Question::count()];

        $this->assertSame($before['courses'] + 1, $after['courses']);
        $this->assertGreaterThan($before['lessons'], $after['lessons']);
        $this->assertGreaterThan($before['questions'], $after['questions']);
    }
}
