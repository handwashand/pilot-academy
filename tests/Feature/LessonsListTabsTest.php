<?php

namespace Tests\Feature;

use App\Filament\Resources\Lessons\Pages\ListLessons;
use App\Models\Course;
use App\Models\Lesson;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * The Lessons list with 140+ lessons: tabs for All, Drafts, Not in any course
 * and each course; 50 rows per page; filters above the table; search, filters
 * and sort kept in the session.
 */
class LessonsListTabsTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::create(['name' => 'Pilot Admin', 'email' => 'admin@pilot.local', 'password' => bcrypt('password'), 'role' => User::ROLE_ADMIN]);
    }

    private function course(string $slug, array $extra = []): Course
    {
        return Course::create(['title' => ucfirst($slug), 'slug' => $slug, 'level' => 'beginner', ...$extra]);
    }

    private function lesson(string $slug, ?Course $course = null, array $extra = []): Lesson
    {
        return Lesson::create(['title' => ucfirst($slug), 'slug' => $slug, 'course_id' => $course?->id, ...$extra]);
    }

    public function test_there_is_a_tab_per_course_in_course_order_with_counts(): void
    {
        $second = $this->course('second', ['sort_order' => 2]);
        $first = $this->course('first', ['sort_order' => 1]);
        $this->lesson('a', $first);
        $this->lesson('b', $first);
        $this->lesson('c', $second);

        $tabs = Livewire::actingAs($this->admin())->test(ListLessons::class)->instance()->getTabs();

        $this->assertSame(['all', 'drafts', 'no_course', 'course-'.$first->id, 'course-'.$second->id], array_keys($tabs));
        // Filament hands badges back as strings.
        $this->assertEquals(2, $tabs['course-'.$first->id]->getBadge());
        $this->assertEquals(1, $tabs['course-'.$second->id]->getBadge());
    }

    public function test_a_course_tab_lists_only_that_courses_lessons(): void
    {
        $fuel = $this->course('fuel');
        $video = $this->course('video');
        $inFuel = $this->lesson('in-fuel', $fuel);
        $inVideo = $this->lesson('in-video', $video);
        $shared = $this->lesson('shared', $video);
        $fuel->lessons()->attach($shared->id);

        Livewire::actingAs($this->admin())
            ->test(ListLessons::class)
            ->set('activeTab', 'course-'.$fuel->id)
            ->assertCanSeeTableRecords([$inFuel, $shared])
            ->assertCanNotSeeTableRecords([$inVideo]);
    }

    public function test_the_drafts_and_no_course_tabs_filter(): void
    {
        $course = $this->course('course');
        $draft = $this->lesson('draft', $course, ['status' => Lesson::STATUS_DRAFT]);
        $published = $this->lesson('published', $course, ['status' => Lesson::STATUS_PUBLISHED]);
        $loose = $this->lesson('loose', null, ['status' => Lesson::STATUS_PUBLISHED]);

        $page = Livewire::actingAs($this->admin())->test(ListLessons::class);

        $page->set('activeTab', 'drafts')
            ->assertCanSeeTableRecords([$draft])
            ->assertCanNotSeeTableRecords([$published, $loose]);

        $page->set('activeTab', 'no_course')
            ->assertCanSeeTableRecords([$loose])
            ->assertCanNotSeeTableRecords([$draft, $published]);
    }

    public function test_a_creator_only_gets_tabs_and_counts_for_their_own_products(): void
    {
        $garm = Product::create(['name' => 'GARM', 'slug' => 'garm']);
        $ptm = Product::create(['name' => 'PTM', 'slug' => 'ptm']);
        $creator = User::create(['name' => 'Creator', 'email' => 'c@pilot.local', 'password' => bcrypt('x'), 'role' => User::ROLE_CREATOR]);
        $creator->products()->attach($garm);
        $mine = $this->course('mine', ['product_id' => $garm->id]);
        $theirs = $this->course('theirs', ['product_id' => $ptm->id]);
        $this->lesson('my-lesson', $mine);
        $this->lesson('their-lesson', $theirs);

        $tabs = Livewire::actingAs($creator)->test(ListLessons::class)->instance()->getTabs();

        $this->assertArrayHasKey('course-'.$mine->id, $tabs);
        $this->assertArrayNotHasKey('course-'.$theirs->id, $tabs);
        $this->assertEquals(1, $tabs['all']->getBadge());
    }

    public function test_a_filter_applies_without_an_apply_button(): void
    {
        $course = $this->course('course');
        $inCourse = $this->lesson('in-course', $course);
        $loose = $this->lesson('loose');

        Livewire::actingAs($this->admin())
            ->test(ListLessons::class)
            ->set('tableFilters.no_course.isActive', true)
            ->assertCanSeeTableRecords([$loose])
            ->assertCanNotSeeTableRecords([$inCourse]);
    }

    public function test_fifty_rows_per_page_by_default(): void
    {
        $course = $this->course('big');
        foreach (range(1, 55) as $i) {
            $this->lesson('lesson-'.$i, $course);
        }

        Livewire::actingAs($this->admin())
            ->test(ListLessons::class)
            ->assertCountTableRecords(55)
            ->assertSet('tableRecordsPerPage', 50);
    }
}
