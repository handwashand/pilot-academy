<?php

namespace Tests\Feature;

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
 * The database allows one slug per home course (`lessons.course_id`), deleted
 * lessons included. A lesson in the Recycle bin, or one taken out of the course
 * it still calls home, is invisible to the course yet still holds its slug —
 * the form must say so instead of the save failing with a database error.
 */
class LessonSlugClashTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::create([
            'name' => 'Pilot Admin',
            'email' => 'admin@pilot.test',
            'password' => bcrypt('secret'),
            'role' => User::ROLE_ADMIN,
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

    private function createLesson(Course $course, string $slug)
    {
        return Livewire::actingAs($this->admin())
            ->test(CreateLesson::class)
            ->fillForm(['title' => 'Setting Up Tariffs', 'slug' => $slug, 'course_ids' => [$course->id]])
            ->call('create');
    }

    public function test_a_deleted_lesson_in_the_course_is_named_instead_of_a_database_error(): void
    {
        $course = $this->course('Video Telematics');
        $course->lessons()->create(['title' => 'Old tariffs', 'slug' => 'setting-up-tariffs'])->delete();

        $this->createLesson($course, 'setting-up-tariffs')
            ->assertHasFormErrors(['slug']);

        $this->assertSame(0, Lesson::where('slug', 'setting-up-tariffs')->count(), 'Only the deleted one exists.');
    }

    public function test_a_lesson_taken_out_of_the_course_still_holds_its_slug(): void
    {
        $course = $this->course('Video Telematics');
        $old = $course->lessons()->create(['title' => 'Old tariffs', 'slug' => 'setting-up-tariffs']);
        $course->lessons()->detach($old->id);
        $this->assertSame($course->id, (int) $old->fresh()->course_id, 'Removed from its only course, it keeps its home.');

        $this->createLesson($course, 'setting-up-tariffs')
            ->assertHasFormErrors(['slug']);
    }

    public function test_the_same_slug_in_another_course_is_fine(): void
    {
        $this->course('Video Telematics')->lessons()->create(['title' => 'Old', 'slug' => 'setting-up-tariffs'])->delete();
        $other = $this->course('Fleet Basics');

        $this->createLesson($other, 'setting-up-tariffs')
            ->assertHasNoFormErrors();

        $this->assertSame($other->id, (int) Lesson::where('slug', 'setting-up-tariffs')->value('course_id'));
    }

    public function test_editing_a_slug_onto_a_deleted_siblings_is_refused(): void
    {
        $course = $this->course('Video Telematics');
        $course->lessons()->create(['title' => 'Old', 'slug' => 'setting-up-tariffs'])->delete();
        $lesson = $course->lessons()->create(['title' => 'New', 'slug' => 'new-tariffs']);

        Livewire::actingAs($this->admin())
            ->test(EditLesson::class, ['record' => $lesson->getRouteKey()])
            ->fillForm(['slug' => 'setting-up-tariffs'])
            ->call('save')
            ->assertHasFormErrors(['slug']);

        $this->assertSame('new-tariffs', $lesson->fresh()->slug);
    }

    public function test_adding_an_unassigned_lesson_beside_a_deleted_namesake_is_stopped(): void
    {
        $course = $this->course('Video Telematics');
        $course->lessons()->create(['title' => 'Old', 'slug' => 'setting-up-tariffs'])->delete();

        $home = $this->course('Drafts');
        $loose = $home->lessons()->create(['title' => 'Tariffs', 'slug' => 'setting-up-tariffs']);
        $home->lessons()->detach($loose->id);

        Livewire::actingAs($this->admin())
            ->test(LessonsRelationManager::class, ['ownerRecord' => $course, 'pageClass' => EditCourse::class])
            ->callAction(TestAction::make('attach')->table(), ['recordId' => [$loose->id]])
            ->assertNotified();

        $this->assertSame(0, $course->lessons()->count());
        $this->assertSame($home->id, (int) $loose->fresh()->course_id);
    }

    public function test_taking_a_lesson_out_of_its_home_does_not_move_it_onto_a_namesake(): void
    {
        $home = $this->course('Drafts');
        $next = $this->course('Video Telematics');
        $next->lessons()->create(['title' => 'Old', 'slug' => 'setting-up-tariffs'])->delete();

        $lesson = $home->lessons()->create(['title' => 'Tariffs', 'slug' => 'setting-up-tariffs']);
        $next->lessons()->attach($lesson->id);

        $home->lessons()->detach($lesson->id);

        $this->assertSame($home->id, (int) $lesson->fresh()->course_id, 'It keeps its old home rather than clash.');
        $this->assertTrue($next->lessons()->whereKey($lesson->id)->exists());
    }
}
