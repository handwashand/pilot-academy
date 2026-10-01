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
use Filament\Forms\Components\Select;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * A course's Lessons tab. Sharing a lesson with more courses is covered in
 * depth by SharedLessonTest; this is the tab itself.
 */
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

    private function creator(Product $product): User
    {
        $creator = User::firstOrCreate(['email' => 'creator@pilot.local'], [
            'name' => 'Creator',
            'password' => bcrypt('secret'),
            'role' => 'creator',
        ]);
        $creator->products()->sync([$product->id]);

        return $creator;
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

    public function test_a_lesson_can_be_written_from_inside_the_course(): void
    {
        $course = $this->newCourse('Fleet basics', ['language' => 'fr']);

        $this->manager($course)
            ->callAction(TestAction::make('create')->table(), [
                'title' => 'Draw a GeoZone',
                'slug' => 'draw-a-geozone',
                'summary' => 'Where to start.',
            ])
            ->assertHasNoActionErrors();

        $lesson = Lesson::where('slug', 'draw-a-geozone')->sole();

        $this->assertTrue($course->hasLesson($lesson), 'It is in the course it was written in.');
        $this->assertSame($course->id, $lesson->course_id, 'That course is its home.');
        $this->assertSame('fr', $lesson->language, 'It is written in the course\'s language.');
    }

    public function test_a_new_lesson_cannot_reuse_a_slug_already_in_the_course(): void
    {
        $course = $this->newCourse('Fleet basics');
        $this->lessonIn($course, 'Intro', ['slug' => 'intro']);

        $this->manager($course)
            ->callAction(TestAction::make('create')->table(), [
                'title' => 'Another intro',
                'slug' => 'intro',
            ])
            ->assertHasActionErrors(['slug']);

        $this->assertSame(1, $course->lessons()->count());
    }

    public function test_a_creator_can_write_a_lesson_in_their_own_course(): void
    {
        $mine = Product::create(['name' => 'GARM', 'slug' => 'garm']);
        $course = $this->newCourse('My course', ['product_id' => $mine->id]);

        $this->manager($course, $this->creator($mine))
            ->callAction(TestAction::make('create')->table(), [
                'title' => 'My lesson',
                'slug' => 'my-lesson',
            ])
            ->assertHasNoActionErrors();

        $this->assertTrue($course->hasLesson(Lesson::where('slug', 'my-lesson')->sole()));
    }

    /**
     * The picker is filled before anything is typed, and what it offers is
     * every lesson that is not already here — that is the whole point of it.
     */
    public function test_the_picker_offers_other_courses_lessons_and_not_this_courses(): void
    {
        $here = $this->newCourse('This course');
        $elsewhere = $this->newCourse('Another course');

        $already = $this->lessonIn($here, 'Already here');
        $available = $this->lessonIn($elsewhere, 'Somewhere else');

        $component = $this->manager($here);
        $component->mountAction(TestAction::make('attach')->table());

        $options = $this->pickerOptions($component);

        $this->assertArrayHasKey($available->id, $options, 'A lesson from another course is offered.');
        $this->assertArrayNotHasKey($already->id, $options, 'One this course already has is not.');
        $this->assertStringContainsString('Another course', $options[$available->id], 'It says which course it comes from.');
    }

    /** The options the attach modal's select is holding, keyed by lesson id. */
    private function pickerOptions($component): array
    {
        $select = collect($component->instance()->getSchema('mountedActionSchema0')->getComponents(withHidden: true))
            ->first(fn ($component): bool => $component instanceof Select);

        return $select->getOptions();
    }

    public function test_adding_an_existing_lesson_shares_it_rather_than_moving_it(): void
    {
        $from = $this->newCourse('Source course');
        $to = $this->newCourse('Target course');
        $lesson = $this->lessonIn($from, 'Shared lesson');

        $this->manager($to)
            ->callAction(TestAction::make('attach')->table(), [
                'recordId' => [$lesson->id],
            ])
            ->assertHasNoActionErrors();

        $this->assertTrue($to->hasLesson($lesson));
        $this->assertTrue($from->hasLesson($lesson), 'It stays in the course it was in.');
        $this->assertSame($from->id, $lesson->fresh()->course_id);
    }

    public function test_sharing_a_lesson_keeps_its_content_and_student_progress(): void
    {
        $from = $this->newCourse('Source course');
        $to = $this->newCourse('Target course');
        $lesson = $this->lessonIn($from, 'Shared lesson', ['content' => '<p>Keep me.</p>']);

        $student = User::firstOrCreate(['email' => 'mover@partner.com'], [
            'name' => 'Mover',
            'password' => bcrypt('secret'),
            'role' => 'learner',
        ]);
        $student->completedLessons()->syncWithoutDetaching([$lesson->id => ['completed_at' => now()]]);

        $this->manager($to)->callAction(TestAction::make('attach')->table(), [
            'recordId' => [$lesson->id],
        ]);

        $this->assertSame('<p>Keep me.</p>', $lesson->fresh()->content);
        $this->assertTrue($student->completedLessons()->whereKey($lesson->id)->exists());
        $this->assertTrue($to->fresh()->isCompletedBy($student), 'Finished once, finished in the new course too.');
    }

    public function test_lessons_can_be_reordered(): void
    {
        $course = $this->newCourse('Ordered course');
        $first = $this->lessonIn($course, 'First', ['sort_order' => 1]);
        $second = $this->lessonIn($course, 'Second', ['sort_order' => 2]);
        $third = $this->lessonIn($course, 'Third', ['sort_order' => 3]);

        // Drag the third lesson to the top.
        $this->manager($course)->call('reorderTable', [$third->id, $first->id, $second->id]);

        $this->assertSame(['Third', 'First', 'Second'], $course->lessons()->pluck('title')->all());
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

        $this->manager($ownCourse, $this->creator($mine))
            ->callAction(TestAction::make('attach')->table(), [
                'recordId' => [$offLimits->id],
            ]);

        $this->assertFalse($ownCourse->hasLesson($offLimits));
        $this->assertSame($otherCourse->id, $offLimits->fresh()->course_id);
    }

    /**
     * The control for the test above: without this, that one could pass simply
     * because a creator cannot work the action at all, and would prove nothing.
     */
    public function test_a_creator_can_share_a_lesson_between_their_own_courses(): void
    {
        $mine = Product::create(['name' => 'GARM', 'slug' => 'garm']);

        $from = $this->newCourse('My first course', ['product_id' => $mine->id]);
        $to = $this->newCourse('My second course', ['product_id' => $mine->id]);
        $lesson = $this->lessonIn($from, 'My lesson');

        $this->manager($to, $this->creator($mine))
            ->callAction(TestAction::make('attach')->table(), [
                'recordId' => [$lesson->id],
            ])
            ->assertHasNoActionErrors();

        $this->assertTrue($to->hasLesson($lesson));
        $this->assertTrue($from->hasLesson($lesson));
    }
}
