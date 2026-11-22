<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\User;
use Database\Seeders\PilotQuickStartSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The home page course card: a cover in the course's theme, one button that
 * says where the student is, and no progress bar for a visitor.
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

    /** The course card's button: [label, href]. */
    private function cta(string $html): array
    {
        preg_match('#<a href="([^"]+)" class="course-card__cta[^"]*">\s*([^<]+?)\s*<span#', $html, $m);

        return [$m[2] ?? null, $m[1] ?? null];
    }

    public function test_a_visitor_gets_start_course_and_no_progress_bar(): void
    {
        $html = $this->get('/')->assertOk()->getContent();

        $this->assertSame([__t('academy.home.start_course'), route('academy.course', $this->course)], $this->cta($html));
        $this->assertStringContainsString('course-card__cover', $html);
        $this->assertStringNotContainsString('aria-label="'.e(__t('academy.common.course_progress_aria', ['course' => $this->course->title, 'done' => 0, 'total' => $this->course->publishedLessons()->count()])), $html);
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

        $this->assertSame([__t('academy.home.continue_course'), route('academy.lesson', [$this->course, $lessons[1]])], $this->cta($html));
        $this->assertStringContainsString('role="progressbar"', $html);
    }

    public function test_a_student_who_finished_every_lesson_reviews_the_course(): void
    {
        $learner = $this->learner();
        foreach ($this->course->publishedLessons()->pluck('lessons.id') as $id) {
            $learner->completedLessons()->attach($id, ['completed_at' => now()]);
        }

        $html = $this->actingAs($learner)->get('/')->assertOk()->getContent();

        $this->assertSame([__t('academy.home.review_course'), route('academy.course', $this->course)], $this->cta($html));
    }
}
