<?php

namespace Tests\Feature;

use App\Actions\AddLessonsToCourse;
use App\Filament\Resources\Courses\Pages\CreateCourse;
use App\Filament\Resources\Courses\Pages\EditCourse;
use App\Filament\Resources\Courses\RelationManagers\LessonsRelationManager;
use App\Filament\Resources\Lessons\Pages\ListLessons;
use App\Filament\Resources\Lessons\Tables\LessonPickerTable;
use App\Models\Course;
use App\Models\Lesson;
use App\Models\Product;
use App\Models\User;
use Filament\Actions\Testing\TestAction;
use Filament\Forms\Components\TableSelect\Livewire\TableSelectLivewireComponent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Finding lessons and putting them in courses: the wider search, the new
 * filters, the lesson picker, the course form's Lessons box, Browse lessons and
 * the Lessons list's Add to course.
 */
class LessonFindingTest extends TestCase
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

    private function course(string $slug = 'course', array $extra = []): Course
    {
        return Course::create(['title' => ucfirst($slug), 'slug' => $slug, 'level' => 'beginner', ...$extra]);
    }

    private function lesson(string $slug, array $extra = []): Lesson
    {
        return Lesson::create(['title' => ucfirst(str_replace('-', ' ', $slug)), 'slug' => $slug, ...$extra]);
    }

    /** A lesson with attributes that are not fillable. */
    private function forced(string $slug, array $attributes): Lesson
    {
        $lesson = $this->lesson($slug);
        $lesson->forceFill($attributes)->save();

        return $lesson;
    }

    private function pivotOrder(Course $course): array
    {
        return DB::table('course_lesson')->where('course_id', $course->id)->orderBy('sort_order')->pluck('lesson_id')->all();
    }

    public function test_the_lessons_list_search_reaches_summary_slug_and_tags(): void
    {
        $bySummary = $this->lesson('one', ['summary' => 'How to set up Geofences']);
        $bySlug = $this->lesson('geofence-basics');
        $byTag = $this->lesson('three', ['tags' => ['GeoFence']]);
        $other = $this->lesson('unrelated', ['summary' => 'Fuel reports']);

        Livewire::actingAs($this->admin())
            ->test(ListLessons::class)
            ->searchTable('geofence')
            ->assertCanSeeTableRecords([$bySummary, $bySlug, $byTag])
            ->assertCanNotSeeTableRecords([$other]);
    }

    public function test_a_tag_written_in_another_alphabet_is_found(): void
    {
        $russian = $this->lesson('ru-tag', ['tags' => ['Топливо']]);
        $other = $this->lesson('no-tag');

        Livewire::actingAs($this->admin())
            ->test(ListLessons::class)
            ->filterTable('tag', 'топливо')
            ->assertCanSeeTableRecords([$russian])
            ->assertCanNotSeeTableRecords([$other]);
    }

    public function test_the_lessons_list_filters_by_author_and_date_added(): void
    {
        $admin = $this->admin();
        $writer = User::create(['name' => 'Writer', 'email' => 'w@pilot.local', 'password' => bcrypt('x'), 'role' => User::ROLE_CREATOR]);
        // created_by is not fillable on purpose (set on create), so it is forced here.
        $mine = $this->forced('mine', ['created_by' => $writer->id]);
        $theirs = $this->forced('theirs', ['created_by' => $admin->id]);
        $old = $this->forced('old', ['created_by' => $admin->id, 'created_at' => now()->subMonths(2)]);

        Livewire::actingAs($admin)
            ->test(ListLessons::class)
            ->filterTable('created_by', $writer->id)
            ->assertCanSeeTableRecords([$mine])
            ->assertCanNotSeeTableRecords([$theirs, $old])
            ->resetTableFilters()
            ->filterTable('added', ['from' => now()->subWeek()->toDateString()])
            ->assertCanSeeTableRecords([$mine, $theirs])
            ->assertCanNotSeeTableRecords([$old]);
    }

    // Ticked rows arrive in the list's order (newest first), so that is the order they join in.
    public function test_add_to_course_from_the_lessons_list_keeps_the_list_order_and_skips_what_is_there(): void
    {
        $course = $this->course();
        $already = $this->lesson('already', ['course_id' => $course->id]);
        $second = $this->forced('second', ['created_at' => now()->subDay()]);
        $first = $this->lesson('first');

        Livewire::actingAs($this->admin())
            ->test(ListLessons::class)
            ->selectTableRecords([$first->id, $already->id, $second->id])
            ->callAction(TestAction::make('addToCourse')->table()->bulk(), data: ['course_id' => $course->id])
            ->assertHasNoFormErrors()
            ->assertNotified();

        $this->assertSame([$already->id, $first->id, $second->id], $this->pivotOrder($course));
    }

    public function test_nothing_is_added_when_two_lessons_would_share_a_slug(): void
    {
        $course = $this->course();
        $this->lesson('intro', ['course_id' => $course->id]);
        $other = $this->course('other');
        $clash = $this->lesson('intro', ['course_id' => $other->id]);
        $fine = $this->lesson('fine');

        $result = app(AddLessonsToCourse::class)->handle($course, [$fine->id, $clash->id], $this->admin());

        $this->assertSame([$clash->title], $result['clashes']->all());
        $this->assertCount(1, $this->pivotOrder($course));
    }

    public function test_a_creator_cannot_add_another_products_lessons_or_fill_another_products_course(): void
    {
        $garm = Product::create(['name' => 'GARM', 'slug' => 'garm']);
        $ptm = Product::create(['name' => 'PTM', 'slug' => 'ptm']);
        $creator = User::create(['name' => 'Creator', 'email' => 'c@pilot.local', 'password' => bcrypt('x'), 'role' => User::ROLE_CREATOR]);
        $creator->products()->attach($garm);

        $mineCourse = $this->course('mine', ['product_id' => $garm->id]);
        $theirCourse = $this->course('theirs', ['product_id' => $ptm->id]);
        $theirLesson = $this->lesson('their-lesson', ['course_id' => $theirCourse->id]);
        $myLesson = $this->lesson('my-lesson', ['course_id' => $mineCourse->id]);

        $result = app(AddLessonsToCourse::class)->handle($mineCourse, [$theirLesson->id], $creator);
        $this->assertCount(0, $result['added']);

        $result = app(AddLessonsToCourse::class)->handle($theirCourse, [$myLesson->id], $creator);
        $this->assertCount(0, $result['added']);

        $this->assertSame([$myLesson->id], $this->pivotOrder($mineCourse));
        $this->assertSame([$theirLesson->id], $this->pivotOrder($theirCourse));
    }

    public function test_the_picker_hides_lessons_already_in_the_course_and_scopes_creators(): void
    {
        $garm = Product::create(['name' => 'GARM', 'slug' => 'garm']);
        $creator = User::create(['name' => 'Creator', 'email' => 'c@pilot.local', 'password' => bcrypt('x'), 'role' => User::ROLE_CREATOR]);
        $creator->products()->attach($garm);

        $course = $this->course('mine', ['product_id' => $garm->id]);
        $inCourse = $this->lesson('in-course', ['course_id' => $course->id]);
        $otherCourse = $this->course('other-mine', ['product_id' => $garm->id]);
        $offered = $this->lesson('offered', ['course_id' => $otherCourse->id]);
        $foreign = $this->lesson('foreign', ['course_id' => $this->course('foreign')->id]);

        Livewire::actingAs($creator)
            ->test(TableSelectLivewireComponent::class, [
                'tableConfiguration' => base64_encode(LessonPickerTable::class),
                'tableArguments' => ['exclude_course' => $course->id],
                'state' => [],
            ])
            ->assertCanSeeTableRecords([$offered])
            ->assertCanNotSeeTableRecords([$inCourse, $foreign]);
    }

    public function test_lessons_picked_on_the_course_form_join_the_new_course_in_that_order(): void
    {
        $a = $this->lesson('a');
        $b = $this->lesson('b');

        Livewire::actingAs($this->admin())
            ->test(CreateCourse::class)
            ->fillForm(['title' => 'Built at once', 'slug' => 'built-at-once', 'level' => 'beginner', 'lesson_ids' => [$b->id, $a->id]])
            ->call('create')
            ->assertHasNoFormErrors();

        $course = Course::where('slug', 'built-at-once')->firstOrFail();
        $this->assertSame([$b->id, $a->id], $this->pivotOrder($course));
    }

    public function test_the_course_form_refuses_two_picked_lessons_with_one_slug(): void
    {
        $one = $this->lesson('intro', ['course_id' => $this->course('x')->id]);
        $two = $this->lesson('intro', ['course_id' => $this->course('y')->id]);

        Livewire::actingAs($this->admin())
            ->test(CreateCourse::class)
            ->fillForm(['title' => 'Clash', 'slug' => 'clash', 'level' => 'beginner', 'lesson_ids' => [$one->id, $two->id]])
            ->call('create')
            ->assertHasFormErrors(['lesson_ids']);

        $this->assertFalse(Course::where('slug', 'clash')->exists());
    }

    public function test_browse_lessons_on_the_lessons_tab_adds_the_ticked_lessons(): void
    {
        $course = $this->course();
        $first = $this->lesson('first');
        $second = $this->lesson('second');

        Livewire::actingAs($this->admin())
            ->test(LessonsRelationManager::class, ['ownerRecord' => $course, 'pageClass' => EditCourse::class])
            ->callAction(TestAction::make('browse')->table(), data: ['lesson_ids' => [$second->id, $first->id]])
            ->assertHasNoFormErrors();

        $this->assertSame([$second->id, $first->id], $this->pivotOrder($course));
    }
}
