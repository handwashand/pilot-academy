<?php

namespace Tests\Feature;

use App\Filament\Pages\RecycleBin;
use App\Filament\Resources\Courses\Pages\EditCourse;
use App\Filament\Resources\Lessons\Pages\CreateLesson;
use App\Filament\Resources\Lessons\Pages\ListLessons;
use App\Models\Certificate;
use App\Models\Company;
use App\Models\Course;
use App\Models\Lesson;
use App\Models\Product;
use App\Models\Question;
use App\Models\User;
use Filament\Actions\DeleteAction;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Deleted content is hidden, not removed, and comes back from the recycle
 * bin. A course no longer takes its lessons with it, and a lesson no longer
 * needs a course at all.
 */
class RecycleBinTest extends TestCase
{
    use RefreshDatabase;

    private function user(string $role, string $name, string ...$rights): User
    {
        $user = User::create([
            'name' => $name,
            'email' => Str::slug($name).'@pilot.local',
            'password' => 'password',
            'role' => $role,
        ]);

        foreach ($rights as $right) {
            $user->permissions()->create(['permission' => $right]);
        }

        return $user;
    }

    private function admin(string ...$rights): User
    {
        return $this->user(User::ROLE_ADMIN, 'Pilot Admin', ...$rights);
    }

    private function course(string $title, ?Product $product = null): Course
    {
        return Course::create([
            'product_id' => $product?->id,
            'title' => $title,
            'slug' => Str::slug($title),
            'level' => 'beginner',
            'status' => Course::STATUS_PUBLISHED,
        ]);
    }

    /** A published lesson with one question a student can answer. */
    private function lessonIn(Course $course, string $title): Lesson
    {
        $lesson = $course->lessons()->create(['title' => $title, 'slug' => Str::slug($title), 'content' => '<p>Body.</p>']);

        $question = $lesson->questions()->create(['prompt' => "{$title}?", 'type' => 'single', 'sort_order' => 1]);
        $question->options()->create(['text' => 'Right', 'is_correct' => true, 'sort_order' => 1]);
        $question->options()->create(['text' => 'Wrong', 'is_correct' => false, 'sort_order' => 2]);

        return $lesson->fresh();
    }

    public function test_deleting_a_course_hides_it_and_keeps_its_lessons_and_certificates(): void
    {
        $course = $this->course('Course A');
        $lesson = $this->lessonIn($course, 'Intro');
        $learner = $this->user(User::ROLE_LEARNER, 'Ana');
        $certificate = Certificate::create([
            'user_id' => $learner->id,
            'course_id' => $course->id,
            'number' => 'PA-TEST-0001',
            'name' => 'Ana',
            'score_percent' => 90,
            'issued_at' => now(),
        ]);

        Livewire::actingAs($this->admin())
            ->test(EditCourse::class, ['record' => $course->getRouteKey()])
            ->callAction(DeleteAction::class);

        $this->assertNull(Course::find($course->id), 'Hidden from the panel and the student site.');
        $this->assertNotNull(Course::withTrashed()->find($course->id), 'Still in the database.');
        $this->get(route('academy.course', $course))->assertNotFound();

        $this->assertNotNull(Lesson::find($lesson->id), 'The lesson did not go with it.');
        $this->assertSame(0, $lesson->courses()->count(), 'It is in no course now.');
        $this->assertSame(1, $lesson->questions()->count());

        $this->assertSame('Course A', $certificate->fresh()->course->title, 'A certificate still names the course it was earned in.');
        $this->get(route('certificates.verify', $certificate->number))->assertOk()->assertSee('Course A');
    }

    public function test_a_deleted_courses_lessons_are_listed_as_in_no_course_and_can_join_another(): void
    {
        $a = $this->course('Course A');
        $b = $this->course('Course B');
        $lesson = $this->lessonIn($a, 'Intro');
        $a->delete();

        Livewire::actingAs($this->admin())
            ->test(ListLessons::class)
            ->filterTable('no_course')
            ->assertCanSeeTableRecords([$lesson]);

        $b->lessons()->attach($lesson);

        $this->assertSame($b->id, $lesson->fresh()->course_id, 'The course it joins owns it now.');
        $this->get(route('academy.lesson', [$b, $lesson]))->assertOk();
    }

    public function test_restoring_a_course_brings_its_lessons_back(): void
    {
        $course = $this->course('Course A');
        $lesson = $this->lessonIn($course, 'Intro');
        $course->delete();

        Livewire::actingAs($this->admin(User::PERMISSION_RECYCLE_BIN))
            ->test(RecycleBin::class)
            ->assertCanSeeTableRecords([$course])
            ->callAction(TestAction::make('restore')->table($course))
            ->assertHasNoActionErrors();

        $this->assertNotNull(Course::find($course->id));
        $this->assertSame(['Intro'], $course->lessons()->pluck('title')->all());
        $this->get(route('academy.lesson', [$course, $lesson]))->assertOk();
    }

    public function test_a_deleted_lesson_takes_its_questions_out_of_the_final_quiz_and_brings_them_back(): void
    {
        $course = $this->course('Course A');
        $lesson = $this->lessonIn($course, 'Intro');
        $kept = $lesson->questions()->first();
        $goneBefore = $lesson->questions()->create(['prompt' => 'Deleted on its own', 'type' => 'single', 'sort_order' => 2]);
        $course->finalQuestions()->attach([$kept->id, $goneBefore->id]);

        $this->travel(-1)->hours();
        $goneBefore->delete();
        $this->travelBack();

        $lesson->delete();

        $this->assertSame(0, $course->finalQuestions()->count(), 'Nothing of a hidden lesson is asked in the final quiz.');
        $this->assertSame(0, $course->lessons()->count());

        Livewire::actingAs($this->admin(User::PERMISSION_RECYCLE_BIN))
            ->test(RecycleBin::class, ['type' => 'lessons'])
            ->assertCanSeeTableRecords([Lesson::withTrashed()->find($lesson->id)])
            ->callAction(TestAction::make('restore')->table($lesson));

        $this->assertSame(['Intro'], $course->lessons()->pluck('title')->all());
        $this->assertSame([$kept->id], $course->finalQuestions()->pluck('questions.id')->all());
        $this->assertTrue(Question::withTrashed()->find($goneBefore->id)->trashed(), 'A question deleted earlier, on its own, stays deleted.');
    }

    // One signed-in user per test: AuthenticateSession pins the session to the first.
    public function test_an_admin_without_the_right_cannot_open_the_recycle_bin(): void
    {
        $this->actingAs($this->admin())->get(RecycleBin::getUrl())->assertForbidden();
    }

    public function test_a_creator_cannot_open_the_recycle_bin_even_with_the_right(): void
    {
        $creator = $this->user(User::ROLE_CREATOR, 'Gina', User::PERMISSION_RECYCLE_BIN);

        $this->actingAs($creator)->get(RecycleBin::getUrl())->assertForbidden();
    }

    public function test_an_admin_with_the_right_opens_the_recycle_bin(): void
    {
        $this->actingAs($this->admin(User::PERMISSION_RECYCLE_BIN))
            ->get(RecycleBin::getUrl())
            ->assertOk()
            ->assertSee(__t('admin_pages.recycle_bin.nav'));
    }

    public function test_each_kind_of_content_has_its_own_list(): void
    {
        $product = Product::create(['name' => 'GARM', 'slug' => 'garm']);
        $course = $this->course('Course A');
        $product->delete();
        $course->delete();

        $holder = $this->admin(User::PERMISSION_RECYCLE_BIN);

        Livewire::actingAs($holder)->test(RecycleBin::class, ['type' => 'products'])
            ->assertCanSeeTableRecords([$product])
            ->assertSee('GARM')
            ->assertDontSee('Course A');

        // An address nobody wrote for us falls back to courses.
        Livewire::actingAs($holder)->test(RecycleBin::class, ['type' => 'users'])
            ->assertCanSeeTableRecords([$course]);
    }

    public function test_users_and_partners_are_still_deleted_for_good(): void
    {
        $company = Company::create(['name' => 'Partner Ltd']);
        $learner = $this->user(User::ROLE_LEARNER, 'Ana');

        $company->delete();
        $learner->delete();

        $this->assertDatabaseMissing('companies', ['id' => $company->id]);
        $this->assertDatabaseMissing('users', ['id' => $learner->id]);
    }

    public function test_a_lesson_can_be_written_without_a_course(): void
    {
        $admin = $this->admin();

        Livewire::actingAs($admin)
            ->test(CreateLesson::class)
            ->fillForm(['title' => 'Written first', 'slug' => 'written-first'])
            ->call('create')
            ->assertHasNoFormErrors();

        $lesson = Lesson::where('slug', 'written-first')->firstOrFail();

        $this->assertNull($lesson->course_id);
        $this->assertSame(0, $lesson->courses()->count());
        $this->assertSame($admin->id, $lesson->created_by);
    }

    public function test_a_creator_sees_the_course_less_lessons_they_wrote_and_no_one_elses(): void
    {
        $product = Product::create(['name' => 'GARM', 'slug' => 'garm']);
        $gina = $this->user(User::ROLE_CREATOR, 'Gina');
        $gina->products()->attach($product);
        $paul = $this->user(User::ROLE_CREATOR, 'Paul');

        Livewire::actingAs($gina)
            ->test(CreateLesson::class)
            ->fillForm(['title' => 'Ginas draft', 'slug' => 'ginas-draft'])
            ->call('create')
            ->assertHasNoFormErrors();

        $mine = Lesson::where('slug', 'ginas-draft')->firstOrFail();

        // Left behind by a deleted course: an admin's to pick up, not a creator's.
        $course = $this->course('GARM course', $product);
        $orphan = $this->lessonIn($course, 'Orphan');
        $course->delete();

        $this->assertTrue($gina->can('update', $mine));
        $this->assertFalse($paul->can('update', $mine));
        $this->assertFalse($gina->can('update', $orphan->fresh()));

        Livewire::actingAs($gina)->test(ListLessons::class)
            ->assertCanSeeTableRecords([$mine])
            ->assertCanNotSeeTableRecords([$orphan]);

        Livewire::actingAs($paul)->test(ListLessons::class)
            ->assertCanNotSeeTableRecords([$mine, $orphan]);
    }
}
