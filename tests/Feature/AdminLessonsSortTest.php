<?php

namespace Tests\Feature;

use App\Filament\Resources\Lessons\Pages\ListLessons;
use App\Models\Course;
use App\Models\Lesson;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * The Lessons list opens with the newest lesson first, and its Courses column
 * can be sorted by how many courses a lesson is in.
 */
class AdminLessonsSortTest extends TestCase
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

    private function lesson(string $title, string $slug, string $createdAt): Lesson
    {
        $lesson = Lesson::create([
            'title' => $title,
            'slug' => $slug,
            'status' => Lesson::STATUS_DRAFT,
        ]);

        // Written after creation: created_at is not fillable, and the point of
        // these tests is the timestamp, not the key order.
        $lesson->forceFill(['created_at' => $createdAt])->save();

        return $lesson;
    }

    private function course(string $title, string $slug): Course
    {
        return Course::create([
            'title' => $title,
            'slug' => $slug,
            'level' => 'beginner',
        ]);
    }

    public function test_the_list_opens_with_the_newest_lesson_first(): void
    {
        $oldest = $this->lesson('Oldest lesson', 'oldest-lesson', now()->subDays(2)->toDateTimeString());
        $middle = $this->lesson('Middle lesson', 'middle-lesson', now()->subDay()->toDateTimeString());
        $newest = $this->lesson('Newest lesson', 'newest-lesson', now()->toDateTimeString());

        Livewire::actingAs($this->admin())
            ->test(ListLessons::class)
            ->assertCanSeeTableRecords([$newest, $middle, $oldest], inOrder: true);
    }

    public function test_the_courses_column_sorts_by_how_many_courses_a_lesson_is_in(): void
    {
        $first = $this->course('First course', 'first-course');
        $second = $this->course('Second course', 'second-course');

        $inTwo = $this->lesson('In two courses', 'in-two-courses', now()->subDays(3)->toDateTimeString());
        $inOne = $this->lesson('In one course', 'in-one-course', now()->subDays(2)->toDateTimeString());
        $inNone = $this->lesson('In no course', 'in-no-course', now()->subDay()->toDateTimeString());

        $first->lessons()->attach([$inTwo->id, $inOne->id]);
        $second->lessons()->attach($inTwo->id);

        Livewire::actingAs($this->admin())
            ->test(ListLessons::class)
            ->sortTable('courses.title', 'desc')
            ->assertCanSeeTableRecords([$inTwo, $inOne, $inNone], inOrder: true);
    }
}
