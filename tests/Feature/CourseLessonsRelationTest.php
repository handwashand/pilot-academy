<?php

namespace Tests\Feature;

use App\Filament\Resources\Courses\Pages\EditCourse;
use App\Filament\Resources\Courses\RelationManagers\LessonsRelationManager;
use App\Models\Course;
use App\Models\Lesson;
use App\Models\Product;
use App\Models\User;
use Database\Seeders\PilotQuickStartSeeder;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\TestCase;

class CourseLessonsRelationTest extends TestCase
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

    private function newCourse(string $title, array $overrides = []): Course
    {
        return Course::create([
            'title' => $title,
            'slug' => Str::slug($title),
            'description' => 'Course used in tests.',
            'level' => 'beginner',
            ...$overrides,
        ]);
    }

    private function lessonIn(Course $course, string $title, array $overrides = []): Lesson
    {
        return $course->lessons()->create([
            'title' => $title,
            'slug' => Str::slug($title),
            'summary' => 'Lesson used in tests.',
            'content' => '<p>Body.</p>',
            'sort_order' => 0,
            ...$overrides,
        ]);
    }

    /** The relation manager, mounted against one course. */
    private function manager(Course $course, ?User $as = null)
    {
        return Livewire::actingAs($as ?? $this->admin())
            ->test(LessonsRelationManager::class, [
                'ownerRecord' => $course,
                'pageClass' => EditCourse::class,
            ]);
    }

    public function test_it_lists_only_this_courses_lessons(): void
    {
        $a = $this->newCourse('Course A');
        $b = $this->newCourse('Course B');

        $mine = $this->lessonIn($a, 'Mine');
        $theirs = $this->lessonIn($b, 'Theirs');

        $this->manager($a)
            ->assertCanSeeTableRecords([$mine])
            ->assertCanNotSeeTableRecords([$theirs]);
    }

    public function test_an_existing_lesson_can_be_moved_into_the_course(): void
    {
        $from = $this->newCourse('Source course');
        $to = $this->newCourse('Target course');
        $lesson = $this->lessonIn($from, 'Movable lesson');

        $this->manager($to)
            ->callAction(TestAction::make('associate')->table(), [
                'recordId' => [$lesson->id],
            ])
            ->assertHasNoActionErrors();

        $this->assertSame($to->id, $lesson->fresh()->course_id);
    }

    public function test_moving_a_lesson_keeps_its_content_and_student_progress(): void
    {
        $from = $this->newCourse('Source course');
        $to = $this->newCourse('Target course');
        $lesson = $this->lessonIn($from, 'Movable lesson', ['content' => '<p>Keep me.</p>']);

        $student = User::firstOrCreate(['email' => 'mover@partner.com'], [
            'name' => 'Mover',
            'password' => bcrypt('secret'),
            'role' => 'learner',
        ]);
        $student->completedLessons()->syncWithoutDetaching([$lesson->id => ['completed_at' => now()]]);

        $this->manager($to)->callAction(TestAction::make('associate')->table(), [
            'recordId' => [$lesson->id],
        ]);

        $fresh = $lesson->fresh();
        $this->assertSame('<p>Keep me.</p>', $fresh->content);
        $this->assertTrue($student->completedLessons()->whereKey($lesson->id)->exists());
    }

    public function test_lessons_can_be_reordered(): void
    {
        $course = $this->newCourse('Ordered course');
        $first = $this->lessonIn($course, 'First', ['sort_order' => 1]);
        $second = $this->lessonIn($course, 'Second', ['sort_order' => 2]);
        $third = $this->lessonIn($course, 'Third', ['sort_order' => 3]);

        // Drag the third lesson to the top.
        $this->manager($course)->call('reorderTable', [$third->id, $first->id, $second->id]);

        $order = $course->lessons()->orderBy('sort_order')->pluck('title')->all();

        $this->assertSame(['Third', 'First', 'Second'], $order);
    }

    public function test_the_new_order_is_what_students_see(): void
    {
        $course = $this->newCourse('Ordered course', ['status' => Course::STATUS_PUBLISHED]);
        $a = $this->lessonIn($course, 'Alpha', ['sort_order' => 1, 'status' => Lesson::STATUS_PUBLISHED]);
        $b = $this->lessonIn($course, 'Beta', ['sort_order' => 2, 'status' => Lesson::STATUS_PUBLISHED]);

        $this->manager($course)->call('reorderTable', [$b->id, $a->id]);

        $this->assertSame(
            ['Beta', 'Alpha'],
            $course->fresh()->publishedLessons()->pluck('title')->all()
        );
    }

    public function test_a_creator_cannot_pull_in_a_lesson_from_a_product_they_do_not_own(): void
    {
        $mine = Product::create(['name' => 'GARM', 'slug' => 'garm']);
        $theirs = Product::create(['name' => 'PTM', 'slug' => 'ptm']);

        $ownCourse = $this->newCourse('My course', ['product_id' => $mine->id]);
        $otherCourse = $this->newCourse('Their course', ['product_id' => $theirs->id]);
        $offLimits = $this->lessonIn($otherCourse, 'Off limits');

        $creator = User::firstOrCreate(['email' => 'creator@pilot.local'], [
            'name' => 'Creator',
            'password' => bcrypt('secret'),
            'role' => 'creator',
        ]);
        $creator->products()->sync([$mine->id]);

        $this->manager($ownCourse, $creator)
            ->callAction(TestAction::make('associate')->table(), [
                'recordId' => [$offLimits->id],
            ]);

        // The lesson must not have moved out of the product they do not own.
        $this->assertSame($otherCourse->id, $offLimits->fresh()->course_id);
    }

    /**
     * The control for the test above: without this, that one could pass simply
     * because a creator cannot work the action at all, and would prove nothing.
     */
    public function test_a_creator_can_move_a_lesson_between_their_own_courses(): void
    {
        $mine = Product::create(['name' => 'GARM', 'slug' => 'garm']);

        $from = $this->newCourse('My first course', ['product_id' => $mine->id]);
        $to = $this->newCourse('My second course', ['product_id' => $mine->id]);
        $lesson = $this->lessonIn($from, 'My lesson');

        $creator = User::firstOrCreate(['email' => 'creator@pilot.local'], [
            'name' => 'Creator',
            'password' => bcrypt('secret'),
            'role' => 'creator',
        ]);
        $creator->products()->sync([$mine->id]);

        $this->manager($to, $creator)
            ->callAction(TestAction::make('associate')->table(), [
                'recordId' => [$lesson->id],
            ])
            ->assertHasNoActionErrors();

        $this->assertSame($to->id, $lesson->fresh()->course_id);
    }
}
