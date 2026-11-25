<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\User;
use Database\Seeders\PilotQuickStartSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The home page groups courses (owner's choice, 2026-11-25): a visitor sees
 * one course to start with and the next six as tiles, with the rest on the
 * Courses page; a student sees courses started, courses to explore and
 * courses completed, each with what to do next.
 */
class HomeCourseCardTest extends TestCase
{
    use RefreshDatabase;

    private Course $course;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(PilotQuickStartSeeder::class);
        $this->course = Course::first();
    }

    private function learner(): User
    {
        return User::create(['name' => 'Partner Student', 'email' => 'student@partner.com', 'password' => bcrypt('secret'), 'role' => User::ROLE_LEARNER]);
    }

    private function moreCourses(int $count): void
    {
        for ($i = 1; $i <= $count; $i++) {
            $course = Course::create(['title' => "Extra course {$i}", 'slug' => "extra-course-{$i}", 'level' => 'beginner', 'status' => Course::STATUS_PUBLISHED, 'sort_order' => 100 + $i]);
            $course->lessons()->create(['title' => "Extra lesson {$i}", 'slug' => "extra-lesson-{$i}", 'status' => 'published']);
        }
    }

    public function test_a_visitor_gets_the_first_course_to_start_with_and_no_progress_bar(): void
    {
        $html = $this->get('/')->assertOk()->getContent();

        $this->assertStringContainsString('class="course-feature"', $html);
        $this->assertStringContainsString('href="'.route('academy.course', $this->course).'" class="course-feature__cta"', $html);
        $this->assertStringContainsString(__t('academy.home.start_course'), $html);
        $this->assertStringNotContainsString('role="progressbar"', $html);
    }

    public function test_a_visitor_sees_six_more_courses_and_a_link_to_all_of_them(): void
    {
        $this->moreCourses(8);
        $total = Course::published()->count();

        $html = $this->get('/')->assertOk()->getContent();

        $this->assertSame(6, substr_count($html, 'class="course-tile"'));
        $this->assertStringContainsString(__t('academy.home.see_all_courses', ['count' => $total]), $html);
        $this->assertStringContainsString('href="'.route('academy.courses').'"', $html);
    }

    public function test_the_visitor_hero_shows_what_is_on_offer_and_links_to_the_courses(): void
    {
        $lessons = $this->course->publishedLessons()->count();

        $this->get('/')->assertOk()
            ->assertSee(__tc('academy.home.hero_courses', 1))
            ->assertSee(__tc('academy.common.lessons', $lessons))
            ->assertSee(__t('academy.home.hero_certificates'))
            ->assertSee('href="'.route('academy.courses').'" class="hero-browse"', false);
    }

    public function test_a_student_part_way_through_continues_at_the_first_unfinished_lesson(): void
    {
        $learner = $this->learner();
        $lessons = $this->course->publishedLessons()->get()->values();
        $learner->completedLessons()->attach($lessons[0]->id, ['completed_at' => now()]);

        $html = $this->actingAs($learner)->get('/')->assertOk()->getContent();

        $this->assertStringContainsString('href="'.route('academy.lesson', [$this->course, $lessons[1]]).'" class="course-continue', $html);
        $this->assertStringContainsString('role="progressbar"', $html);
    }

    public function test_courses_not_started_are_tiles_while_a_started_one_keeps_its_lessons(): void
    {
        $this->moreCourses(2);
        $learner = $this->learner();
        $learner->completedLessons()->attach($this->course->publishedLessons()->first()->id, ['completed_at' => now()]);

        $html = $this->actingAs($learner)->get('/')->assertOk()->getContent();

        $this->assertSame(1, substr_count($html, 'class="lesson-row"'));
        $this->assertSame(2, substr_count($html, 'class="course-tile"'));
        $this->assertStringContainsString('Extra course 1', $html);
    }

    public function test_a_finished_course_is_listed_as_completed_with_a_review_link(): void
    {
        $learner = $this->learner();
        foreach ($this->course->publishedLessons()->pluck('lessons.id') as $id) {
            $learner->completedLessons()->attach($id, ['completed_at' => now()]);
        }

        $html = $this->actingAs($learner)->get('/')->assertOk()->getContent();

        $this->assertStringContainsString('class="course-done"', $html);
        $this->assertStringContainsString(__t('academy.home.review_course'), $html);
        $this->assertStringNotContainsString('class="lesson-row"', $html);
    }

    public function test_more_ways_to_learn_links_case_studies_tutorials_and_webinars(): void
    {
        $this->get('/')->assertOk()
            ->assertSee(__t('academy.home.more_ways'))
            ->assertSee('href="'.route('academy.case-studies.index').'" class="more-tile"', false)
            ->assertSee('href="'.route('academy.tutorials').'" class="more-tile"', false)
            ->assertSee('href="'.route('academy.webinars').'" class="more-tile"', false);
    }
}
