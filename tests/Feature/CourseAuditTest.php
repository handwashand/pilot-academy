<?php

namespace Tests\Feature;

use App\Actions\DuplicateCourse;
use App\Filament\Resources\Courses\Pages\CreateCourse;
use App\Filament\Resources\Courses\Pages\EditCourse;
use App\Models\Course;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * When a course was written and by whom, shown read-only on its edit page.
 *
 * Courses that existed before the column did have no author recorded, and say
 * so rather than guessing.
 */
class CourseAuditTest extends TestCase
{
    use RefreshDatabase;

    private function user(string $role, string $email, string $name): User
    {
        return User::create([
            'name' => $name,
            'email' => $email,
            'password' => bcrypt('password'),
            'role' => $role,
        ]);
    }

    private function admin(): User
    {
        return $this->user(User::ROLE_ADMIN, 'admin@pilot.local', 'Pilot Admin');
    }

    private function course(string $title, ?User $author = null): Course
    {
        return Course::create([
            'title' => $title,
            'slug' => str($title)->slug()->value(),
            'level' => 'beginner',
            'created_by' => $author?->id,
        ]);
    }

    public function test_creating_a_course_records_who_created_it(): void
    {
        $admin = $this->admin();

        Livewire::actingAs($admin)
            ->test(CreateCourse::class)
            ->fillForm([
                'title' => 'Brand new course',
                'slug' => 'brand-new-course',
                'level' => 'beginner',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $course = Course::where('slug', 'brand-new-course')->firstOrFail();

        $this->assertSame($admin->id, $course->created_by);
        $this->assertTrue($course->creator->is($admin));
    }

    public function test_the_edit_page_says_who_created_the_course_and_when(): void
    {
        $author = $this->user(User::ROLE_CREATOR, 'author@partner.com', 'Claire Martin');
        $course = $this->course('Created by a creator', $author);

        Livewire::actingAs($this->admin())
            ->test(EditCourse::class, ['record' => $course->getRouteKey()])
            ->assertSee(__t('admin_courses.form.created_by_on', [
                'name' => 'Claire Martin',
                'date' => $course->created_at->isoFormat('LL'),
            ]));
    }

    public function test_a_course_with_no_recorded_author_says_unknown(): void
    {
        // Created outside the panel, as every course that existed before this
        // column did — nobody is signed in, so nothing is stamped.
        $course = $this->course('Written before the audit line');

        $this->assertNull($course->created_by);

        Livewire::actingAs($this->admin())
            ->test(EditCourse::class, ['record' => $course->getRouteKey()])
            ->assertSee(__t('admin_courses.form.created_by_unknown'));
    }

    public function test_a_duplicated_course_is_credited_to_whoever_made_the_copy(): void
    {
        $author = $this->user(User::ROLE_CREATOR, 'author@partner.com', 'Claire Martin');
        $duplicator = $this->admin();
        $original = $this->course('Original course', $author);

        $this->actingAs($duplicator);
        $copy = app(DuplicateCourse::class)->handle($original);

        $this->assertSame($duplicator->id, $copy->created_by, 'The copy is today\'s work, not the original author\'s.');
    }
}
