<?php

namespace Tests\Feature;

use App\Actions\NotifyContentOwners;
use App\Filament\Resources\Courses\CourseResource;
use App\Filament\Resources\Courses\Pages\EditCourse;
use App\Filament\Resources\Courses\Pages\ListCourses;
use App\Filament\Resources\Lessons\LessonResource;
use App\Filament\Resources\Lessons\Pages\ListLessons;
use App\Models\Course;
use App\Models\Lesson;
use App\Models\Product;
use App\Models\User;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Content that would strand students is flagged where the work happens,
 * refused before it goes live, and reported to whoever owns it — rather than
 * sitting in a card on top of everyone's dashboard.
 */
class ContentHealthWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private function admin(string $email = 'admin@pilot.local'): User
    {
        return User::create([
            'name' => 'Pilot Admin',
            'email' => $email,
            'password' => 'password',
            'role' => User::ROLE_ADMIN,
        ]);
    }

    /** A course with one lesson; the lesson has a proper question unless told otherwise. */
    private function course(string $status = Course::STATUS_DRAFT, bool $withQuestion = true, ?Product $product = null): Course
    {
        $slug = 'course-'.uniqid();

        $course = Course::create([
            'product_id' => $product?->id,
            'title' => 'Course '.$slug,
            'slug' => $slug,
            'level' => 'beginner',
            'status' => $status,
        ]);

        $lesson = $course->lessons()->create([
            'title' => 'Lesson of '.$slug,
            'slug' => 'lesson-'.$slug,
            'content' => '<p>Body.</p>',
            'sort_order' => 1,
        ]);

        if ($withQuestion) {
            $question = $lesson->questions()->create(['prompt' => 'Which one?', 'type' => 'single', 'sort_order' => 1]);
            $question->options()->create(['text' => 'Right', 'is_correct' => true, 'sort_order' => 1]);
            $question->options()->create(['text' => 'Wrong', 'is_correct' => false, 'sort_order' => 2]);
        }

        return $course->fresh();
    }

    // --- Flagged where the work happens ------------------------------------

    public function test_the_courses_list_flags_a_broken_course_and_filters_to_it(): void
    {
        $broken = $this->course(Course::STATUS_PUBLISHED, withQuestion: false);
        $healthy = $this->course(Course::STATUS_PUBLISHED);

        Livewire::actingAs($this->admin())
            ->test(ListCourses::class)
            ->assertTableColumnStateSet('attention', '1 problem', $broken)
            ->filterTable('needs_attention')
            ->assertCanSeeTableRecords([$broken])
            ->assertCanNotSeeTableRecords([$healthy]);
    }

    public function test_the_lessons_list_flags_a_broken_lesson(): void
    {
        $broken = $this->course(Course::STATUS_PUBLISHED, withQuestion: false)->lessons()->first();
        $healthy = $this->course(Course::STATUS_PUBLISHED)->lessons()->first();

        Livewire::actingAs($this->admin())
            ->test(ListLessons::class)
            ->filterTable('needs_attention')
            ->assertCanSeeTableRecords([$broken])
            ->assertCanNotSeeTableRecords([$healthy]);
    }

    public function test_the_edit_pages_say_what_students_hit(): void
    {
        $course = $this->course(Course::STATUS_PUBLISHED, withQuestion: false);
        $this->actingAs($this->admin());

        $this->get(CourseResource::getUrl('edit', ['record' => $course]))
            ->assertOk()
            ->assertSee('Students hit 1 problem here right now');

        $this->get(LessonResource::getUrl('edit', ['record' => $course->lessons()->first()]))
            ->assertOk()
            ->assertSee('Published lesson with no quiz questions');
    }

    // --- Checked before going live -----------------------------------------

    public function test_a_course_with_a_broken_lesson_cannot_be_published(): void
    {
        $broken = $this->course(withQuestion: false);
        $healthy = $this->course();
        $admin = $this->admin();

        Livewire::actingAs($admin)
            ->test(ListCourses::class)
            ->callAction(TestAction::make('publish')->table($broken))
            ->callAction(TestAction::make('publish')->table($healthy));

        $this->assertFalse($broken->fresh()->isPublished(), 'A course students could not finish must not go live.');
        $this->assertTrue($healthy->fresh()->isPublished());
    }

    public function test_the_course_form_refuses_to_publish_a_broken_course(): void
    {
        $broken = $this->course(withQuestion: false);

        Livewire::actingAs($this->admin())
            ->test(EditCourse::class, ['record' => $broken->getRouteKey()])
            ->fillForm(['status' => Course::STATUS_PUBLISHED])
            ->call('save')
            ->assertHasFormErrors(['status']);

        $this->assertFalse($broken->fresh()->isPublished());
    }

    public function test_a_lesson_without_questions_cannot_go_live_in_a_live_course(): void
    {
        $course = $this->course(Course::STATUS_PUBLISHED);
        $draft = $course->lessons()->create([
            'title' => 'No quiz yet', 'slug' => 'no-quiz-yet', 'content' => '<p>Body.</p>',
            'sort_order' => 2, 'status' => Lesson::STATUS_DRAFT,
        ]);

        Livewire::actingAs($this->admin())
            ->test(ListLessons::class)
            ->callAction(TestAction::make('publish')->table($draft));

        $this->assertFalse($draft->fresh()->isPublished());

        // In a draft course nobody sees it yet, so it may be published freely.
        $draftCourse = $this->course();
        $inDraftCourse = $draftCourse->lessons()->create([
            'title' => 'Also no quiz', 'slug' => 'also-no-quiz', 'content' => '<p>Body.</p>',
            'sort_order' => 2, 'status' => Lesson::STATUS_DRAFT,
        ]);

        Livewire::actingAs($this->admin('admin2@pilot.local'))
            ->test(ListLessons::class)
            ->callAction(TestAction::make('publish')->table($inDraftCourse));

        $this->assertTrue($inDraftCourse->fresh()->isPublished());
    }

    public function test_unpublishing_the_last_live_lesson_of_a_live_course_warns_first(): void
    {
        $lesson = $this->course(Course::STATUS_PUBLISHED)->lessons()->first();

        Livewire::actingAs($this->admin())
            ->test(ListLessons::class)
            ->mountAction(TestAction::make('unpublish')->table($lesson))
            ->assertMountedActionModalSee('leaves students an empty course');
    }

    // --- The owner is told --------------------------------------------------

    public function test_the_products_creator_is_notified_once_and_the_alert_clears_when_fixed(): void
    {
        $product = Product::create(['name' => 'GARM', 'slug' => 'garm']);
        $creator = User::create(['name' => 'Gina', 'email' => 'gina@pilot.local', 'password' => 'password', 'role' => User::ROLE_CREATOR]);
        $creator->products()->attach($product);

        $course = $this->course(Course::STATUS_PUBLISHED, withQuestion: false, product: $product);

        // An admin made the change; the creator owns the product.
        $this->actingAs($this->admin());
        $notifier = app(NotifyContentOwners::class);

        $notifier->handle($course->id);
        $notifier->handle($course->id);

        $alerts = $creator->fresh()->unreadNotifications;
        $this->assertCount(1, $alerts, 'One problem, one alert — repeated checks must not pile up.');
        $this->assertSame('Published lesson with no quiz questions', $alerts->first()->data['title']);

        // Fixed: the alert comes off the bell on the next check.
        $lesson = $course->lessons()->first();
        $question = $lesson->questions()->create(['prompt' => 'Which?', 'type' => 'single', 'sort_order' => 1]);
        $question->options()->create(['text' => 'Right', 'is_correct' => true, 'sort_order' => 1]);

        $notifier->handle($course->id);

        $this->assertCount(0, $creator->fresh()->unreadNotifications);
    }

    public function test_whoever_made_the_change_is_not_notified_and_admins_own_unassigned_courses(): void
    {
        $actor = $this->admin();
        $otherAdmin = $this->admin('other@pilot.local');
        $course = $this->course(Course::STATUS_PUBLISHED, withQuestion: false);

        $this->actingAs($actor);
        app(NotifyContentOwners::class)->handle($course->id);

        $this->assertCount(0, $actor->fresh()->unreadNotifications, 'The person who made the change already sees it on the page.');
        $this->assertCount(1, $otherAdmin->fresh()->unreadNotifications);
    }

    public function test_a_healthy_course_notifies_nobody(): void
    {
        $otherAdmin = $this->admin('other@pilot.local');
        $course = $this->course(Course::STATUS_PUBLISHED);

        $this->actingAs($this->admin());
        app(NotifyContentOwners::class)->handle($course->id);

        $this->assertCount(0, $otherAdmin->fresh()->unreadNotifications);
    }
}
