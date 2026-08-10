<?php

namespace Tests\Feature;

use App\Filament\Resources\Courses\Pages\ListCourses;
use App\Filament\Resources\Lessons\Pages\ListLessons;
use App\Filament\Widgets\CertificatesByCourse;
use App\Filament\Widgets\StudentProgressOverview;
use App\Models\Company;
use App\Models\Course;
use App\Models\Product;
use App\Models\User;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Two products with a course each, and a creator who owns only one of them.
 * Nearly every test here is really the same question: can the GARM creator
 * reach anything belonging to PTM?
 */
class CreatorRoleTest extends TestCase
{
    use RefreshDatabase;

    private Product $garm;

    private Product $ptm;

    private Course $garmCourse;

    private Course $ptmCourse;

    protected function setUp(): void
    {
        parent::setUp();

        $this->garm = Product::create(['name' => 'GARM', 'slug' => 'garm']);
        $this->ptm = Product::create(['name' => 'PTM', 'slug' => 'ptm']);

        $this->garmCourse = $this->courseFor($this->garm, 'Understanding GARM', 'understanding-garm');
        $this->ptmCourse = $this->courseFor($this->ptm, 'Understanding PTM', 'understanding-ptm');
    }

    private function courseFor(Product $product, string $title, string $slug): Course
    {
        $course = Course::create([
            'product_id' => $product->id,
            'title' => $title,
            'slug' => $slug,
            'level' => 'beginner',
            'status' => Course::STATUS_PUBLISHED,
        ]);

        $course->lessons()->create([
            'title' => "{$title} — lesson one",
            'slug' => "{$slug}-lesson-one",
            'content' => '<p>Body.</p>',
            'sort_order' => 1,
        ]);

        return $course->fresh();
    }

    private function admin(): User
    {
        return User::create([
            'name' => 'Pilot Admin',
            'email' => 'admin@pilot.local',
            'password' => bcrypt('password'),
            'role' => User::ROLE_ADMIN,
        ]);
    }

    /** A creator who owns GARM and nothing else. */
    private function garmCreator(): User
    {
        $creator = User::create([
            'name' => 'Gina GARM',
            'email' => 'gina@pilot.local',
            'password' => bcrypt('password'),
            'role' => User::ROLE_CREATOR,
        ]);

        $creator->products()->attach($this->garm);

        return $creator;
    }

    private function learner(): User
    {
        return User::create([
            'name' => 'Partner Student',
            'email' => 'student@partner.com',
            'password' => bcrypt('secret'),
            'role' => User::ROLE_LEARNER,
        ]);
    }

    // --- The role itself -------------------------------------------------

    public function test_a_new_account_is_a_learner(): void
    {
        $user = User::create(['name' => 'Fresh', 'email' => 'fresh@example.com', 'password' => bcrypt('x')]);

        $this->assertSame(User::ROLE_LEARNER, $user->role);
        $this->assertTrue($user->isLearner());
        $this->assertFalse($user->isCreator());
        $this->assertFalse($user->isAdmin());
    }

    public function test_an_admin_can_assign_and_remove_the_creator_role(): void
    {
        $admin = $this->admin();
        $user = $this->learner();

        $this->actingAs($admin)->get("/admin/users/{$user->id}/edit")->assertStatus(200);

        $user->update(['role' => User::ROLE_CREATOR]);
        $user->products()->sync([$this->garm->id]);

        $this->assertTrue($user->fresh()->isCreator());
        $this->assertTrue($user->fresh()->ownsProduct($this->garm));
        $this->assertFalse($user->fresh()->ownsProduct($this->ptm));

        // Taking the role away takes the content rights with it.
        $user->update(['role' => User::ROLE_LEARNER]);

        $this->assertFalse($user->fresh()->ownsProduct($this->garm));
        $this->assertFalse($user->fresh()->canManageCourse($this->garmCourse));
    }

    public function test_a_creator_can_own_several_products(): void
    {
        $creator = $this->garmCreator();
        $creator->products()->attach($this->ptm);

        $this->assertTrue($creator->canManageCourse($this->garmCourse));
        $this->assertTrue($creator->canManageCourse($this->ptmCourse));
    }

    // --- Panel access ----------------------------------------------------

    public function test_a_creator_reaches_the_panel_but_a_learner_does_not(): void
    {
        $this->actingAs($this->garmCreator())->get('/admin')->assertStatus(200);
        $this->actingAs($this->learner())->get('/admin')->assertStatus(403);
    }

    public function test_a_creator_is_kept_out_of_platform_administration(): void
    {
        $creator = $this->garmCreator();

        $this->actingAs($creator)->get('/admin/users')->assertForbidden();
        $this->actingAs($creator)->get('/admin/companies')->assertForbidden();
        $this->actingAs($creator)->get('/admin/products')->assertForbidden();
        $this->actingAs($creator)->get('/admin/certificates')->assertForbidden();
    }

    public function test_an_admin_still_reaches_everything(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->get('/admin/users')->assertStatus(200);
        $this->actingAs($admin)->get('/admin/companies')->assertStatus(200);
        $this->actingAs($admin)->get('/admin/products')->assertStatus(200);
        $this->actingAs($admin)->get('/admin/certificates')->assertStatus(200);
        $this->actingAs($admin)->get('/admin/courses')->assertStatus(200);
    }

    // --- Content scope ---------------------------------------------------

    public function test_a_creator_only_sees_their_own_products_courses(): void
    {
        Livewire::actingAs($this->garmCreator())
            ->test(ListCourses::class)
            ->assertCanSeeTableRecords([$this->garmCourse])
            ->assertCanNotSeeTableRecords([$this->ptmCourse]);
    }

    public function test_a_creator_only_sees_their_own_products_lessons(): void
    {
        Livewire::actingAs($this->garmCreator())
            ->test(ListLessons::class)
            ->assertCanSeeTableRecords([$this->garmCourse->lessons()->first()])
            ->assertCanNotSeeTableRecords([$this->ptmCourse->lessons()->first()]);
    }

    public function test_an_admin_sees_every_products_courses(): void
    {
        Livewire::actingAs($this->admin())
            ->test(ListCourses::class)
            ->assertCanSeeTableRecords([$this->garmCourse, $this->ptmCourse]);
    }

    /**
     * 404 rather than 403 on purpose: the scoping happens on the query, so a
     * course outside the creator's products does not exist as far as they are
     * concerned. That refuses the edit page without confirming it is real.
     */
    public function test_a_creator_cannot_open_another_products_course_by_url(): void
    {
        $creator = $this->garmCreator();

        $this->actingAs($creator)->get("/admin/courses/{$this->garmCourse->id}/edit")->assertStatus(200);
        $this->actingAs($creator)->get("/admin/courses/{$this->ptmCourse->id}/edit")->assertNotFound();
    }

    public function test_a_creator_cannot_open_another_products_lesson_by_url(): void
    {
        $creator = $this->garmCreator();
        $mine = $this->garmCourse->lessons()->first();
        $theirs = $this->ptmCourse->lessons()->first();

        $this->actingAs($creator)->get("/admin/lessons/{$mine->id}/edit")->assertStatus(200);
        $this->actingAs($creator)->get("/admin/lessons/{$theirs->id}/edit")->assertNotFound();
    }

    public function test_the_policies_deny_cross_product_writes(): void
    {
        $creator = $this->garmCreator();

        $this->assertTrue($creator->can('update', $this->garmCourse));
        $this->assertFalse($creator->can('update', $this->ptmCourse));
        $this->assertFalse($creator->can('delete', $this->ptmCourse));

        $this->assertTrue($creator->can('update', $this->garmCourse->lessons()->first()));
        $this->assertFalse($creator->can('update', $this->ptmCourse->lessons()->first()));
    }

    public function test_a_course_with_no_product_stays_admin_only(): void
    {
        $legacy = Course::create([
            'title' => 'Legacy course',
            'slug' => 'legacy-course',
            'level' => 'beginner',
            'status' => Course::STATUS_PUBLISHED,
        ]);

        $this->assertNull($legacy->product_id);
        $this->assertTrue($this->admin()->canManageCourse($legacy));
        $this->assertFalse($this->garmCreator()->canManageCourse($legacy));
    }

    // --- Publishing ------------------------------------------------------

    public function test_a_creator_can_publish_their_own_course(): void
    {
        $this->garmCourse->unpublish();

        Livewire::actingAs($this->garmCreator())
            ->test(ListCourses::class)
            ->callAction(TestAction::make('publish')->table($this->garmCourse))
            ->assertHasNoActionErrors();

        $this->assertSame(Course::STATUS_PUBLISHED, $this->garmCourse->fresh()->status);

        // And the learner portal now serves it.
        $this->get(route('academy.course', $this->garmCourse->fresh()))->assertStatus(200);
    }

    public function test_a_creator_cannot_publish_another_products_course(): void
    {
        $this->ptmCourse->unpublish();
        $creator = $this->garmCreator();

        // The row is not in their table at all, so there is no action to call.
        Livewire::actingAs($creator)
            ->test(ListCourses::class)
            ->assertCanNotSeeTableRecords([$this->ptmCourse]);

        // And the permission behind the action says no independently of the UI.
        $this->assertFalse($creator->canManageCourse($this->ptmCourse));
        $this->assertFalse($creator->can('update', $this->ptmCourse));
        $this->assertSame(Course::STATUS_DRAFT, $this->ptmCourse->fresh()->status);
    }

    // --- Drafts on the public site ---------------------------------------

    public function test_a_creator_previews_only_their_own_draft_courses(): void
    {
        $this->garmCourse->unpublish();
        $this->ptmCourse->unpublish();
        $creator = $this->garmCreator();

        $this->actingAs($creator)->get(route('academy.course', $this->garmCourse))->assertStatus(200);
        $this->actingAs($creator)->get(route('academy.course', $this->ptmCourse))->assertNotFound();
    }

    public function test_a_learner_still_sees_no_drafts_at_all(): void
    {
        $this->garmCourse->unpublish();

        $this->actingAs($this->learner())
            ->get(route('academy.course', $this->garmCourse))
            ->assertNotFound();
    }

    // --- Reporting -------------------------------------------------------

    public function test_reports_count_learners_only(): void
    {
        $this->learner();
        $this->garmCreator();
        $this->admin();

        $this->assertSame(1, User::learners()->count());
        $this->assertSame(3, User::count());
    }

    public function test_company_student_lists_exclude_creators_and_admins(): void
    {
        $company = Company::create(['name' => 'Partner Co']);

        $this->learner()->update(['company_id' => $company->id]);
        $this->garmCreator()->update(['company_id' => $company->id]);
        $this->admin()->update(['company_id' => $company->id]);

        $this->assertSame(3, $company->users()->count());
        $this->assertSame(1, $company->students()->count());
    }

    public function test_learner_data_widgets_are_hidden_from_creators(): void
    {
        $this->actingAs($this->admin());
        $this->assertTrue(StudentProgressOverview::canView());
        $this->assertTrue(CertificatesByCourse::canView());

        $this->actingAs($this->garmCreator());
        $this->assertFalse(StudentProgressOverview::canView());
        $this->assertFalse(CertificatesByCourse::canView());
    }
}
