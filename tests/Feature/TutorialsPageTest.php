<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Lesson;
use Database\Seeders\LanguageSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Tutorials gathers every lesson video under its course. Nothing is maintained
 * by hand: a lesson joins the list the moment it has a video.
 */
class TutorialsPageTest extends TestCase
{
    use RefreshDatabase;

    private function course(string $slug, string $title): Course
    {
        return Course::create([
            'slug' => $slug,
            'title' => $title,
            'level' => 'beginner',
            'status' => Course::STATUS_PUBLISHED,
        ]);
    }

    private function lesson(Course $course, string $slug, string $title, array $overrides = []): Lesson
    {
        return $course->lessons()->create([
            'slug' => $slug,
            'title' => $title,
            'summary' => 'A lesson.',
            'content' => '<p>Body.</p>',
            'status' => Lesson::STATUS_PUBLISHED,
            ...$overrides,
        ]);
    }

    public function test_only_lessons_with_a_video_are_listed_under_their_course(): void
    {
        $course = $this->course('monitoring', 'Pilot Monitoring');
        $this->lesson($course, 'history', 'Working with History', [
            'video_sources' => [['type' => 'youtube', 'youtube_url' => 'https://www.youtube.com/watch?v=abcdefghijk']],
        ]);
        $this->lesson($course, 'reading-only', 'A lesson with no video');

        $this->get(route('academy.tutorials'))
            ->assertOk()
            ->assertSee('Tutorials')
            ->assertSee('Pilot Monitoring')
            ->assertSee('Working with History')
            ->assertSee('1 video')
            ->assertDontSee('A lesson with no video');
    }

    public function test_a_course_with_no_videos_is_left_out_and_drafts_never_appear(): void
    {
        $this->course('reading', 'Reading only course');

        $draft = $this->course('draft', 'Draft course');
        $this->lesson($draft, 'draft-lesson', 'Hidden lesson', [
            'video_sources' => [['type' => 'youtube', 'youtube_url' => 'https://www.youtube.com/watch?v=abcdefghijk']],
        ]);
        $draft->update(['status' => Course::STATUS_DRAFT]);

        $this->get(route('academy.tutorials'))
            ->assertOk()
            ->assertDontSee('Reading only course')
            ->assertDontSee('Draft course')
            ->assertSee('No videos yet.');
    }

    public function test_the_page_reads_in_the_visitors_language(): void
    {
        $this->seed(LanguageSeeder::class);
        $course = $this->course('monitoring', 'Pilot Monitoring');
        $this->lesson($course, 'history', 'Working with History', [
            'video_sources' => [['type' => 'youtube', 'youtube_url' => 'https://www.youtube.com/watch?v=abcdefghijk']],
        ]);

        $this->withHeader('Accept-Language', 'ru-RU,ru;q=0.9')
            ->get(route('academy.tutorials'))
            ->assertOk()
            ->assertSee('Видеоуроки')
            ->assertSee('Открыть курс')
            ->assertDontSee('Open the course');
    }
}
