<?php

namespace Tests\Feature;

use App\Filament\Resources\Lessons\Pages\CreateLesson;
use App\Filament\Resources\Lessons\Pages\EditLesson;
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
}
