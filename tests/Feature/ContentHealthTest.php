<?php

namespace Tests\Feature;

use App\Filament\Resources\Lessons\Pages\EditLesson;
use App\Filament\Widgets\ContentNeedingAttention;
use App\Models\Course;
use App\Models\Lesson;
use App\Models\Product;
use App\Models\Question;
use App\Models\User;
use Database\Seeders\PilotQuickStartSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ContentHealthTest extends TestCase
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

    /** The bug this rule exists to stop: grading can never return true. */
    public function test_a_question_with_no_correct_option_can_never_be_passed(): void
    {
        $question = Question::first();
        $question->options()->update(['is_correct' => false]);

        $ids = $question->options()->pluck('id')->all();

        $this->assertFalse($question->fresh()->isAnsweredCorrectly($ids));
        $this->assertFalse($question->fresh()->isAnsweredCorrectly([]));
    }

    public function test_the_lesson_form_refuses_a_question_with_no_correct_answer(): void
    {
        $lesson = Lesson::with('questions.options')->first();

        $data = $lesson->questions->map(fn (Question $q): array => [
            'prompt' => $q->prompt,
            'options' => $q->options->map(fn ($o): array => [
                'text' => $o->text,
                'is_correct' => false,   // nobody ticked one
            ])->all(),
        ])->all();

        Livewire::actingAs($this->admin())
            ->test(EditLesson::class, ['record' => $lesson->getRouteKey()])
            ->fillForm(['questions' => $data])
            ->call('save')
            ->assertHasFormErrors();
    }

    public function test_the_lesson_form_accepts_a_question_with_a_correct_answer(): void
    {
        $lesson = Lesson::with('questions.options')->first();

        $data = $lesson->questions->map(fn (Question $q): array => [
            'prompt' => $q->prompt,
            'options' => $q->options->map(fn ($o, int $i): array => [
                'text' => $o->text,
                'is_correct' => $i === 0,
            ])->values()->all(),
        ])->all();

        Livewire::actingAs($this->admin())
            ->test(EditLesson::class, ['record' => $lesson->getRouteKey()])
            ->fillForm(['questions' => $data])
            ->call('save')
            ->assertHasNoFormErrors();
    }

    // --- the health checks themselves -------------------------------------

    public function test_it_finds_a_published_course_with_no_published_lessons(): void
    {
        $course = Course::first();
        $course->lessons->each->unpublish();

        $this->assertContains($course->id, Course::publishedButEmpty()->pluck('id')->all());
    }

    public function test_it_finds_a_final_quiz_with_an_empty_question_bank(): void
    {
        $course = Course::first();
        $course->update(['final_quiz_enabled' => true]);
        $course->finalQuestions()->detach();

        $this->assertContains($course->id, Course::finalQuizWithoutQuestions()->pluck('id')->all());
    }

    public function test_a_healthy_academy_reports_nothing(): void
    {
        // The seeder builds a complete course: published lessons, questions
        // with answers, and a composed final bank.
        $this->assertSame(0, Course::publishedButEmpty()->count());
        $this->assertSame(0, Question::withoutCorrectAnswer()->count());
        $this->assertSame(0, Lesson::published()->withoutQuestions()->count());
    }

    public function test_it_finds_a_question_whose_answers_are_all_wrong(): void
    {
        $question = Question::first();
        $question->options()->update(['is_correct' => false]);

        $this->assertContains($question->id, Question::withoutCorrectAnswer()->pluck('id')->all());
    }

    public function test_it_finds_a_published_lesson_with_no_questions(): void
    {
        $lesson = Lesson::first();
        $lesson->questions()->delete();

        $this->assertContains($lesson->id, Lesson::published()->withoutQuestions()->pluck('id')->all());
    }

    // --- the widget -------------------------------------------------------

    public function test_the_widget_stays_quiet_when_everything_is_healthy(): void
    {
        $this->actingAs($this->admin());

        $this->assertCount(0, (new ContentNeedingAttention)->getProblems());

        Livewire::actingAs($this->admin())
            ->test(ContentNeedingAttention::class)
            ->assertSuccessful()
            ->assertDontSee('Content needing attention');
    }

    public function test_the_widget_reports_each_kind_of_breakage(): void
    {
        $course = Course::first();
        $course->update(['final_quiz_enabled' => true]);
        $course->finalQuestions()->detach();
        Question::first()->options()->update(['is_correct' => false]);
        Lesson::orderByDesc('id')->first()->questions()->delete();

        $this->actingAs($this->admin());
        $problems = (new ContentNeedingAttention)->getProblems();

        $this->assertContains('Final quiz is on but its question bank is empty', $problems->pluck('what'));
        $this->assertContains('Question with no correct answer — impossible to pass', $problems->pluck('what'));
        $this->assertContains('Published lesson with no quiz questions', $problems->pluck('what'));

        Livewire::actingAs($this->admin())
            ->test(ContentNeedingAttention::class)
            ->assertSuccessful()
            ->assertSee('Content needing attention');
    }

    public function test_a_creator_is_shown_only_their_own_products_problems(): void
    {
        $mine = Product::create(['name' => 'GARM', 'slug' => 'garm']);
        $theirs = Product::create(['name' => 'PTM', 'slug' => 'ptm']);

        $broken = fn (Product $p, string $slug): Course => Course::create([
            'product_id' => $p->id, 'title' => 'Broken '.$slug, 'slug' => $slug,
            'level' => 'beginner', 'status' => Course::STATUS_PUBLISHED,
            'final_quiz_enabled' => true,
        ]);

        $broken($mine, 'broken-garm');
        $broken($theirs, 'broken-ptm');

        $creator = User::firstOrCreate(['email' => 'creator@pilot.local'], [
            'name' => 'Creator', 'password' => bcrypt('x'), 'role' => User::ROLE_CREATOR,
        ]);
        $creator->products()->attach($mine);

        $this->actingAs($creator);
        $names = (new ContentNeedingAttention)->getProblems()->pluck('name');

        $this->assertContains('Broken broken-garm', $names);
        $this->assertNotContains('Broken broken-ptm', $names);
    }

    public function test_a_learner_never_sees_the_widget(): void
    {
        // Break something, so the widget has a reason to appear at all.
        Question::first()->options()->update(['is_correct' => false]);

        $this->actingAs($this->admin());
        $this->assertTrue(ContentNeedingAttention::canView());

        $learner = User::create([
            'name' => 'Learner', 'email' => 'l@partner.com',
            'password' => bcrypt('x'), 'role' => User::ROLE_LEARNER,
        ]);

        $this->actingAs($learner);
        $this->assertFalse(ContentNeedingAttention::canView());
    }

    /** Healthy academy, nothing to say — the card should not take up space. */
    public function test_the_widget_is_absent_from_a_healthy_dashboard(): void
    {
        $this->actingAs($this->admin());

        $this->assertFalse(ContentNeedingAttention::canView());
    }
}
