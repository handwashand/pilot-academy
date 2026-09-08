<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Lesson;
use App\Models\User;
use Database\Seeders\PilotQuickStartSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LearnerExperienceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(PilotQuickStartSeeder::class);
    }

    private function course(): Course
    {
        return Course::published()->firstOrFail();
    }

    /** There are no model factories in this repo; tests build rows directly. */
    private function lessonIn(Course $course, array $overrides = []): Lesson
    {
        static $n = 0;
        $n++;

        return $course->lessons()->create([
            'title' => "Test lesson {$n}",
            'slug' => "test-lesson-{$n}",
            'summary' => 'Lesson used in tests.',
            'content' => '<p>Lesson body.</p>',
            'sort_order' => 90 + $n,
            ...$overrides,
        ]);
    }

    private function newCourse(array $overrides = []): Course
    {
        static $n = 0;
        $n++;

        return Course::create([
            'title' => "Test course {$n}",
            'slug' => "test-course-{$n}",
            'description' => 'Course used in tests.',
            'level' => 'beginner',
            'sort_order' => 50 + $n,
            ...$overrides,
        ]);
    }

    // ---------------------------------------------------------------- search

    public function test_search_finds_a_published_lesson_by_title(): void
    {
        $lesson = $this->course()->publishedLessons()->firstOrFail();

        $this->get(route('academy.search', ['q' => $lesson->title]))
            ->assertOk()
            ->assertSee($lesson->title, false);
    }

    public function test_search_is_case_insensitive(): void
    {
        $lesson = $this->course()->publishedLessons()->firstOrFail();

        $this->get(route('academy.search', ['q' => mb_strtoupper($lesson->title)]))
            ->assertOk()
            ->assertSee($lesson->title, false);
    }

    public function test_search_never_returns_a_draft_lesson(): void
    {
        $draft = $this->lessonIn($this->course(), [
            'title' => 'Zeppelin Calibration Secrets',
            'status' => Lesson::STATUS_DRAFT,
        ]);

        $this->get(route('academy.search', ['q' => 'Zeppelin']))
            ->assertOk()
            ->assertDontSee($draft->title);
    }

    public function test_search_never_returns_a_lesson_from_an_unpublished_course(): void
    {
        $draftCourse = $this->newCourse([
            'title' => 'Zeppelin Fleet Onboarding',
            'status' => Course::STATUS_DRAFT,
        ]);

        // Published lesson, unpublished course: the course is the gate.
        $lesson = $this->lessonIn($draftCourse, [
            'title' => 'Zeppelin Mooring Basics',
            'status' => Lesson::STATUS_PUBLISHED,
        ]);

        $this->get(route('academy.search', ['q' => 'Zeppelin']))
            ->assertOk()
            ->assertDontSee($lesson->title)
            ->assertDontSee($draftCourse->title);
    }

    public function test_an_empty_search_asks_for_a_term_rather_than_listing_everything(): void
    {
        $this->get(route('academy.search', ['q' => '']))
            ->assertOk()
            ->assertSee('Type something above to search.');
    }

    // -------------------------------------------------------------- duration

    public function test_duration_is_formatted_for_people(): void
    {
        $this->assertNull(Course::formatMinutes(null));
        $this->assertNull(Course::formatMinutes(0));
        $this->assertSame('8 min', Course::formatMinutes(8));
        $this->assertSame('45 min', Course::formatMinutes(45));
        $this->assertSame('1 hr', Course::formatMinutes(60));
        $this->assertSame('1 hr 4 min', Course::formatMinutes(64));
        $this->assertSame('2 hr 5 min', Course::formatMinutes(125));
    }

    public function test_a_course_with_no_duration_of_its_own_adds_up_its_lessons(): void
    {
        $course = $this->newCourse(['duration_minutes' => null]);
        $this->lessonIn($course, ['duration_minutes' => 10, 'status' => Lesson::STATUS_PUBLISHED]);
        $this->lessonIn($course, ['duration_minutes' => 20, 'status' => Lesson::STATUS_PUBLISHED]);

        $this->assertSame(30, $course->fresh()->durationMinutes());
        $this->assertSame('30 min', $course->fresh()->durationLabel());
    }

    public function test_a_draft_lesson_does_not_count_towards_the_course_duration(): void
    {
        $course = $this->newCourse(['duration_minutes' => null]);
        $this->lessonIn($course, ['duration_minutes' => 10, 'status' => Lesson::STATUS_PUBLISHED]);
        $this->lessonIn($course, ['duration_minutes' => 99, 'status' => Lesson::STATUS_DRAFT]);

        $this->assertSame(10, $course->fresh()->durationMinutes());
    }

    public function test_a_course_prefers_its_own_duration_over_the_lesson_total(): void
    {
        $course = $this->newCourse(['duration_minutes' => 90]);
        $this->lessonIn($course, ['duration_minutes' => 10, 'status' => Lesson::STATUS_PUBLISHED]);

        $this->assertSame(90, $course->fresh()->durationMinutes());
    }

    public function test_nothing_is_shown_when_nobody_filled_a_duration_in(): void
    {
        $course = $this->newCourse(['duration_minutes' => null]);
        $lesson = $this->lessonIn($course, ['duration_minutes' => null]);

        $this->assertNull($lesson->durationLabel());
        $this->assertNull($course->fresh()->durationLabel());
    }

    public function test_the_course_page_shows_the_running_time(): void
    {
        $course = $this->course();
        $course->publishedLessons()->update(['duration_minutes' => 15]);
        $course->update(['duration_minutes' => null]);

        $expected = 15 * $course->publishedLessons()->count();

        $this->get(route('academy.course', $course))
            ->assertOk()
            ->assertSee(Course::formatMinutes($expected));
    }

    // ------------------------------------------------------ course completion

    public function test_finishing_every_lesson_shows_a_completion_card(): void
    {
        $course = $this->newCourse(['status' => Course::STATUS_PUBLISHED, 'final_quiz_enabled' => false]);
        $lesson = $this->lessonIn($course, ['status' => Lesson::STATUS_PUBLISHED]);

        $user = User::firstOrCreate(['email' => 'finisher@partner.com'], [
            'name' => 'Finisher',
            'password' => bcrypt('secret'),
            'role' => 'learner',
        ]);
        $user->completedLessons()->syncWithoutDetaching([$lesson->id => ['completed_at' => now()]]);

        $this->actingAs($user)
            ->get(route('academy.course', $course))
            ->assertOk()
            ->assertSee('Course complete');
    }

    public function test_an_unfinished_course_shows_no_completion_card(): void
    {
        $course = $this->newCourse(['status' => Course::STATUS_PUBLISHED, 'final_quiz_enabled' => false]);
        $this->lessonIn($course, ['status' => Lesson::STATUS_PUBLISHED]);

        $this->get(route('academy.course', $course))
            ->assertOk()
            ->assertDontSee('Course complete');
    }
}
