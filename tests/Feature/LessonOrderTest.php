<?php

namespace Tests\Feature;

use App\Filament\Resources\Lessons\Pages\CreateLesson;
use App\Filament\Resources\Lessons\Pages\EditLesson;
use App\Filament\Resources\Lessons\Pages\ListLessons;
use App\Models\Course;
use App\Models\Lesson;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * A new lesson with no order goes to the end of its course, so lessons never
 * share a place. A form default of 0 used to give every new lesson the same one.
 */
class LessonOrderTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::create([
            'name' => 'Pilot Admin',
            'email' => 'admin@pilot.local',
            'password' => bcrypt('password'),
            'role' => User::ROLE_ADMIN,
        ]);
    }

    private function course(): Course
    {
        return Course::create(['title' => 'Course', 'slug' => 'course', 'level' => 'beginner']);
    }

    private function pivotOrder(Course $course, Lesson $lesson): int
    {
        return (int) DB::table('course_lesson')
            ->where('course_id', $course->id)
            ->where('lesson_id', $lesson->id)
            ->value('sort_order');
    }

    public function test_lessons_created_without_an_order_are_numbered_in_turn(): void
    {
        $course = $this->course();

        $lessons = collect(['one', 'two', 'three'])->map(fn (string $slug): Lesson => Lesson::create([
            'course_id' => $course->id,
            'title' => ucfirst($slug),
            'slug' => $slug,
        ]));

        $this->assertSame([1, 2, 3], $lessons->map(fn (Lesson $l): int => $l->sort_order)->all());
        $this->assertSame([1, 2, 3], $lessons->map(fn (Lesson $l): int => $this->pivotOrder($course, $l))->all());
    }

    public function test_an_order_typed_by_the_editor_is_kept(): void
    {
        $course = $this->course();
        $lesson = Lesson::create(['course_id' => $course->id, 'title' => 'Five', 'slug' => 'five', 'sort_order' => 5]);

        $this->assertSame(5, $lesson->sort_order);
        $this->assertSame(5, $this->pivotOrder($course, $lesson));
    }

    public function test_the_form_leaves_order_empty_and_the_lesson_goes_last(): void
    {
        $course = $this->course();
        Lesson::create(['course_id' => $course->id, 'title' => 'First', 'slug' => 'first']);

        Livewire::actingAs($this->admin())
            ->test(CreateLesson::class)
            ->assertFormSet(['sort_order' => null])
            ->fillForm(['course_ids' => [$course->id], 'title' => 'Second', 'slug' => 'second'])
            ->call('create')
            ->assertHasNoFormErrors();

        $second = Lesson::where('slug', 'second')->firstOrFail();
        $this->assertSame(2, $second->sort_order);
        $this->assertSame(2, $this->pivotOrder($course, $second));
    }

    public function test_the_renumbering_migration_breaks_ties_and_keeps_the_order(): void
    {
        $course = $this->course();
        $a = Lesson::create(['course_id' => $course->id, 'title' => 'A', 'slug' => 'a', 'sort_order' => 0]);
        $b = Lesson::create(['course_id' => $course->id, 'title' => 'B', 'slug' => 'b', 'sort_order' => 0]);
        $c = Lesson::create(['course_id' => $course->id, 'title' => 'C', 'slug' => 'c', 'sort_order' => 2]);
        $shared = Lesson::create(['course_id' => $course->id, 'title' => 'D', 'slug' => 'd', 'sort_order' => 1]);
        $other = Course::create(['title' => 'Other', 'slug' => 'other', 'level' => 'beginner']);
        $other->lessons()->attach($shared->id, ['sort_order' => 7]);

        (require database_path('migrations/2026_11_04_000002_renumber_lesson_order.php'))->up();

        $this->assertSame([1, 2, 3, 4], collect([$a, $b, $shared, $c])->map(fn (Lesson $l): int => $this->pivotOrder($course, $l))->all());
        $this->assertSame([1, 2, 3, 4], collect([$a, $b, $shared, $c])->map(fn (Lesson $l): int => $l->fresh()->sort_order)->all());
        // In another course it gets that course's own place, and its own number stays its home's.
        $this->assertSame(1, $this->pivotOrder($other, $shared));
    }

    public function test_emptying_the_order_on_edit_keeps_the_old_place(): void
    {
        $course = $this->course();
        $lesson = Lesson::create(['course_id' => $course->id, 'title' => 'Kept', 'slug' => 'kept', 'sort_order' => 4]);

        Livewire::actingAs($this->admin())
            ->test(EditLesson::class, ['record' => $lesson->getRouteKey()])
            ->fillForm(['sort_order' => null])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame(4, $lesson->fresh()->sort_order);
    }

    public function test_the_lessons_list_shows_the_place_in_the_course_and_a_dash_for_none(): void
    {
        $course = $this->course();
        $first = Lesson::create(['course_id' => $course->id, 'title' => 'First', 'slug' => 'first']);
        $second = Lesson::create(['course_id' => $course->id, 'title' => 'Second', 'slug' => 'second']);
        $loose = Lesson::create(['title' => 'Loose', 'slug' => 'loose']);

        // Reordering the course's Lessons tab writes only course_lesson.
        DB::table('course_lesson')->where('lesson_id', $first->id)->update(['sort_order' => 2]);
        DB::table('course_lesson')->where('lesson_id', $second->id)->update(['sort_order' => 1]);

        Livewire::actingAs($this->admin())
            ->test(ListLessons::class)
            ->assertTableColumnStateSet('sort_order', 2, $first)
            ->assertTableColumnStateSet('sort_order', 1, $second)
            ->assertTableColumnStateSet('sort_order', null, $loose);
    }
}
