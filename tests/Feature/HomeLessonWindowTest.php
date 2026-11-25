<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\User;
use Database\Seeders\PilotQuickStartSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The signed-in home page shows three lesson cards for each course the student
 * has started, starting at their first unfinished lesson, plus a link to the
 * full list. Every card of every course made the page 16,000px tall on a
 * phone; since 2026-11-25 (owner's choice) a course not started yet is a
 * compact tile instead.
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

    /** @return array<int, string> lesson URLs shown as cards in the course's lesson row */
    private function cardLinks(string $html): array
    {
        if (! preg_match('#<div class="lesson-row">(.*?)</div>\s*(?:<a|</div>)#s', $html, $row)) {
            return [];
        }
        preg_match_all('#href="([^"]*/courses/'.preg_quote($this->course->slug, '#').'/lessons/[^"]+)"#', $row[1], $m);

        return array_values(array_unique($m[1]));
    }

    private function lessonUrl(int $index): string
    {
        $lesson = $this->course->publishedLessons()->get()->values()[$index];

        return route('academy.lesson', [$this->course, $lesson]);
    }

    public function test_a_course_not_started_is_a_tile_without_lesson_cards(): void
    {
        $html = $this->actingAs($this->learner())->get('/')->assertOk()->getContent();

        $this->assertSame([], $this->cardLinks($html));
        $this->assertStringContainsString('class="course-tile"', $html);
        $this->assertStringContainsString(__t('academy.home.explore_courses'), $html);
    }

    public function test_the_cards_start_at_the_first_unfinished_lesson(): void
    {
        $learner = $this->learner();
        $lessons = $this->course->publishedLessons()->get()->values();
        $learner->completedLessons()->attach([$lessons[0]->id => ['completed_at' => now()], $lessons[1]->id => ['completed_at' => now()]]);

        $html = $this->actingAs($learner)->get('/')->assertOk()->getContent();

        $this->assertSame([$this->lessonUrl(2), $this->lessonUrl(3), $this->lessonUrl(4)], $this->cardLinks($html));
        $this->assertStringContainsString(__t('academy.home.your_courses'), $html);
        $this->assertStringContainsString(__t('academy.home.see_all_lessons', ['count' => $lessons->count()]), $html);
    }

    public function test_every_lesson_done_with_the_final_quiz_ahead_shows_the_last_three(): void
    {
        $this->course->update(['final_quiz_enabled' => true]);
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
