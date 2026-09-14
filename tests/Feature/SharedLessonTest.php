<?php

namespace Tests\Feature;

use App\Actions\FindContentProblems;
use App\Filament\Resources\Courses\Pages\EditCourse;
use App\Filament\Resources\Courses\RelationManagers\LessonsRelationManager;
use App\Filament\Resources\Lessons\Pages\CreateLesson;
use App\Filament\Resources\Lessons\Pages\EditLesson;
use App\Models\Course;
use App\Models\Lesson;
use App\Models\User;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * One lesson, in more than one course. The same lesson everywhere: one set of
 * questions, one completion, one publish status — with its own place in each
 * course.
 */
class SharedLessonTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::firstOrCreate(['email' => 'admin@pilot.local'], [
            'name' => 'Pilot Admin',
            'password' => 'password',
            'role' => User::ROLE_ADMIN,
        ]);
    }

    private function learner(): User
    {
        return User::create([
            'name' => 'Ana',
            'email' => 'ana@partner.com',
            'password' => 'secret123',
            'role' => User::ROLE_LEARNER,
        ]);
    }

    private function course(string $title): Course
    {
        return Course::create([
            'title' => $title,
            'slug' => Str::slug($title),
            'level' => 'beginner',
            'status' => Course::STATUS_PUBLISHED,
        ]);
    }

    /** A published lesson a student can finish: one question, one right answer. */
    private function lessonIn(Course $course, string $title, int $order = 1, bool $withQuestion = true): Lesson
    {
        $lesson = $course->lessons()->create([
            'title' => $title,
            'slug' => Str::slug($title),
            'content' => '<p>Body.</p>',
            'sort_order' => $order,
        ]);

        if ($withQuestion) {
            $question = $lesson->questions()->create(['prompt' => 'Which one?', 'type' => 'single', 'sort_order' => 1]);
            $question->options()->create(['text' => 'Right', 'is_correct' => true, 'sort_order' => 1]);
        }

        return $lesson->fresh();
    }

    private function manager(Course $course)
    {
        return Livewire::actingAs($this->admin())->test(LessonsRelationManager::class, [
            'ownerRecord' => $course,
            'pageClass' => EditCourse::class,
        ]);
    }

    public function test_a_lesson_written_inside_a_course_lives_there(): void
    {
        $course = $this->course('Course A');
        $lesson = $this->lessonIn($course, 'Intro');

        $this->assertSame($course->id, $lesson->course_id);
        $this->assertTrue($course->hasLesson($lesson));
        $this->assertSame(['Intro'], $course->lessons()->pluck('title')->all());
    }

    public function test_an_existing_lesson_can_be_added_to_a_second_course_and_stays_in_the_first(): void
    {
        $a = $this->course('Course A');
        $b = $this->course('Course B');
        $lesson = $this->lessonIn($a, 'Shared');

        $this->manager($b)
            ->callAction(TestAction::make('attach')->table(), ['recordId' => [$lesson->id]])
            ->assertHasNoActionErrors();

        $this->assertTrue($a->hasLesson($lesson));
        $this->assertTrue($b->hasLesson($lesson));
        $this->assertSame($a->id, $lesson->fresh()->course_id, 'The first course still owns it.');
    }

    public function test_students_open_the_shared_lesson_from_either_course_and_nowhere_else(): void
    {
        $a = $this->course('Course A');
        $b = $this->course('Course B');
        $c = $this->course('Course C');
        $this->lessonIn($c, 'Something else');
        $lesson = $this->lessonIn($a, 'Shared');
        $b->lessons()->attach($lesson);

        $this->get(route('academy.lesson', [$a, $lesson]))->assertOk();
        $this->get(route('academy.lesson', [$b, $lesson]))->assertOk();
        $this->get(route('academy.course', $b))->assertSee('Shared');
        $this->get(route('academy.lesson', [$c, $lesson]))->assertNotFound();
    }

    public function test_finishing_a_shared_lesson_once_counts_in_every_course_it_is_in(): void
    {
        $a = $this->course('Course A');
        $b = $this->course('Course B');
        $this->lessonIn($a, 'Only in A');
        $shared = $this->lessonIn($a, 'Shared', 2);
        $b->lessons()->attach($shared);
        $learner = $this->learner();

        $question = $shared->questions()->with('options')->first();

        $this->actingAs($learner)
            ->post(route('academy.quiz', [$a, $shared]), ['answers' => [$question->id => $question->options->first()->id]])
            ->assertRedirect();

        $this->assertTrue($b->fresh()->isCompletedBy($learner), 'B has only the shared lesson, finished in A.');
        $this->assertFalse($a->fresh()->isCompletedBy($learner), 'A still has a lesson to do.');
    }

    public function test_each_course_keeps_its_own_order(): void
    {
        $a = $this->course('Course A');
        $b = $this->course('Course B');
        $this->lessonIn($a, 'One', 1);
        $shared = $this->lessonIn($a, 'Shared', 2);
        $bFirst = $this->lessonIn($b, 'B first', 1);
        $b->lessons()->attach($shared);

        $this->assertSame(['B first', 'Shared'], $b->lessons()->pluck('title')->all(), 'Added to the end.');

        $this->manager($b)->call('reorderTable', [$shared->id, $bFirst->id]);

        $this->assertSame(['Shared', 'B first'], $b->fresh()->lessons()->pluck('title')->all());
        $this->assertSame(['One', 'Shared'], $a->fresh()->lessons()->pluck('title')->all(), 'Course A is untouched.');
    }

    public function test_removing_a_lesson_from_one_course_keeps_it_in_the_other(): void
    {
        $a = $this->course('Course A');
        $b = $this->course('Course B');
        $lesson = $this->lessonIn($a, 'Shared');
        $b->lessons()->attach($lesson);

        $this->manager($b)
            ->callAction(TestAction::make('detach')->table($lesson))
            ->assertHasNoActionErrors();

        $this->assertFalse($b->hasLesson($lesson));
        $this->assertTrue($a->hasLesson($lesson));
        $this->assertNotNull(Lesson::find($lesson->id));
    }

    public function test_a_lessons_last_course_cannot_be_removed(): void
    {
        $a = $this->course('Course A');
        $lesson = $this->lessonIn($a, 'Alone');

        $this->manager($a)->assertActionHidden(TestAction::make('detach')->table($lesson));
    }

    public function test_leaving_its_home_course_hands_the_lesson_to_the_next_one(): void
    {
        $a = $this->course('Course A');
        $b = $this->course('Course B');
        $lesson = $this->lessonIn($a, 'Shared');
        $b->lessons()->attach($lesson);

        $a->lessons()->detach($lesson);

        $this->assertSame($b->id, $lesson->fresh()->course_id);
    }

    public function test_deleting_a_course_keeps_the_lessons_it_shares(): void
    {
        $a = $this->course('Course A');
        $b = $this->course('Course B');
        $shared = $this->lessonIn($a, 'Shared');
        $onlyA = $this->lessonIn($a, 'Only in A', 2);
        $b->lessons()->attach($shared);

        $a->delete();

        $this->assertSame($b->id, $shared->fresh()?->course_id);
        $this->assertNull(Lesson::find($onlyA->id));
    }

    public function test_the_lesson_form_puts_a_new_lesson_in_several_courses(): void
    {
        $a = $this->course('Course A');
        $b = $this->course('Course B');

        Livewire::actingAs($this->admin())
            ->test(CreateLesson::class)
            ->fillForm([
                'course_ids' => [$b->id, $a->id],
                'title' => 'In both',
                'slug' => 'in-both',
                'status' => Lesson::STATUS_DRAFT,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $lesson = Lesson::where('slug', 'in-both')->firstOrFail();

        $this->assertSame($b->id, $lesson->course_id, 'The first course chosen owns it.');
        $this->assertTrue($a->hasLesson($lesson));
        $this->assertTrue($b->hasLesson($lesson));
    }

    public function test_the_lesson_form_can_take_a_lesson_out_of_a_course(): void
    {
        $a = $this->course('Course A');
        $b = $this->course('Course B');
        $lesson = $this->lessonIn($a, 'Shared');
        $b->lessons()->attach($lesson);

        Livewire::actingAs($this->admin())
            ->test(EditLesson::class, ['record' => $lesson->getRouteKey()])
            ->assertFormSet(['course_ids' => [$a->id, $b->id]])
            ->fillForm(['course_ids' => [$b->id]])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertFalse($a->hasLesson($lesson));
        $this->assertSame($b->id, $lesson->fresh()->course_id);
    }

    public function test_two_lessons_with_the_same_address_cannot_share_a_course(): void
    {
        $a = $this->course('Course A');
        $b = $this->course('Course B');
        $fromA = $this->lessonIn($a, 'Intro');
        $this->lessonIn($b, 'Intro');

        $this->manager($b)->callAction(TestAction::make('attach')->table(), ['recordId' => [$fromA->id]]);

        $this->assertFalse($b->hasLesson($fromA));
    }

    public function test_a_broken_shared_lesson_is_one_problem_that_names_both_courses(): void
    {
        $a = $this->course('Course A');
        $b = $this->course('Course B');
        $broken = $this->lessonIn($a, 'No questions', withQuestion: false);
        $b->lessons()->attach($broken);

        $problems = app(FindContentProblems::class)->forViewer($this->admin())->where('lesson_id', $broken->id);

        $this->assertCount(1, $problems);
        $this->assertEqualsCanonicalizing([$a->id, $b->id], $problems->first()['course_ids']);
        $this->assertCount(1, app(FindContentProblems::class)->forCourse($b)->where('lesson_id', $broken->id));
    }
}
