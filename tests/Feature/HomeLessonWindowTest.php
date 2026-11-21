<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\User;
use Database\Seeders\PilotQuickStartSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The signed-in home page shows three lesson cards per course, starting at the
 * student's first unfinished lesson, plus a link to the full list. Every card
 * of every course made the page 16,000px tall on a phone.
 */
class HomeLessonWindowTest extends TestCase
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

    /** @return array<int, string> lesson URLs shown as cards for the seeded course */
    private function cardLinks(string $html): array
    {
        preg_match_all('#href="([^"]*/courses/'.preg_quote($this->course->slug, '#').'/lessons/[^"]+)"#', $html, $m);

        return array_values(array_unique($m[1]));
    }

    private function lessonUrl(int $index): string
    {
        $lesson = $this->course->publishedLessons()->get()->values()[$index];

        return route('academy.lesson', [$this->course, $lesson]);
    }

    public function test_a_new_student_sees_the_first_three_lessons_and_a_link_to_all(): void
    {
        $total = $this->course->publishedLessons()->count();
        $this->assertGreaterThan(3, $total, 'The seeded course needs more than three lessons.');

        $html = $this->actingAs($this->learner())->get('/')->assertOk()->getContent();

        $this->assertSame([$this->lessonUrl(0), $this->lessonUrl(1), $this->lessonUrl(2)], $this->cardLinks($html));
        $this->assertStringContainsString(__t('academy.home.see_all_lessons', ['count' => $total]), $html);
    }

    public function test_the_cards_start_at_the_first_unfinished_lesson(): void
    {
        $learner = $this->learner();
        $lessons = $this->course->publishedLessons()->get()->values();
        $learner->completedLessons()->attach([$lessons[0]->id => ['completed_at' => now()], $lessons[1]->id => ['completed_at' => now()]]);

        $html = $this->actingAs($learner)->get('/')->assertOk()->getContent();

        $this->assertSame([$this->lessonUrl(2), $this->lessonUrl(3), $this->lessonUrl(4)], $this->cardLinks($html));
    }

    public function test_a_finished_course_shows_its_last_three_lessons(): void
    {
        $learner = $this->learner();
        $lessons = $this->course->publishedLessons()->get()->values();
        foreach ($lessons as $lesson) {
            $learner->completedLessons()->attach($lesson->id, ['completed_at' => now()]);
        }
        $n = $lessons->count();

        $html = $this->actingAs($learner)->get('/')->assertOk()->getContent();

        $this->assertSame([$this->lessonUrl($n - 3), $this->lessonUrl($n - 2), $this->lessonUrl($n - 1)], $this->cardLinks($html));
    }
}
