<?php

namespace Tests\Feature;

use App\Filament\Resources\Tutorials\Pages\ListTutorials;
use App\Models\Course;
use App\Models\Lesson;
use App\Models\Tutorial;
use App\Models\User;
use Database\Seeders\LanguageSeeder;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
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

    /** An admin adds a video that belongs to no course, by link or by upload. */
    public function test_a_standalone_tutorial_appears_above_the_course_videos(): void
    {
        $course = $this->course('monitoring', 'Pilot Monitoring');
        $this->lesson($course, 'history', 'Working with History', [
            'video_sources' => [['type' => 'youtube', 'youtube_url' => 'https://www.youtube.com/watch?v=abcdefghijk']],
        ]);

        Tutorial::create([
            'title' => 'Adding your first object',
            'slug' => 'adding-your-first-object',
            'summary' => 'Five minutes, start to finish.',
            'youtube_url' => 'https://www.youtube.com/watch?v=abcdefghijk',
            'duration_minutes' => 5,
            'status' => Tutorial::STATUS_PUBLISHED,
        ]);

        $this->get(route('academy.tutorials'))
            ->assertOk()
            ->assertSee('2 videos')
            ->assertSeeInOrder([
                'Pilot how-tos',
                'Adding your first object',
                'From the courses',
                'Pilot Monitoring',
                'Working with History',
            ]);
    }

    public function test_an_uploaded_tutorial_plays_and_a_draft_is_hidden(): void
    {
        $uploaded = Tutorial::create([
            'title' => 'Setting up a GeoZone',
            'slug' => 'setting-up-a-geozone',
            'type' => Tutorial::TYPE_UPLOAD,
            'video_path' => 'tutorial-videos/geozone.mp4',
            'status' => Tutorial::STATUS_PUBLISHED,
        ]);

        $this->get(route('academy.tutorial', $uploaded))
            ->assertOk()
            ->assertSee('<video', false)
            ->assertSee('tutorial-videos/geozone.mp4');

        $draft = Tutorial::create([
            'title' => 'Not ready',
            'slug' => 'not-ready',
            'youtube_url' => 'https://www.youtube.com/watch?v=abcdefghijk',
        ]);

        $this->get(route('academy.tutorials'))
            ->assertOk()
            ->assertDontSee('Not ready');

        $this->get(route('academy.tutorial', $draft))->assertNotFound();
    }

    public function test_a_tutorial_without_a_working_video_cannot_be_published(): void
    {
        $admin = User::create([
            'name' => 'Pilot Admin',
            'email' => 'admin@pilot.local',
            'password' => bcrypt('password'),
            'role' => User::ROLE_ADMIN,
        ]);

        // A playlist link is not one video, so it never becomes a tutorial.
        $broken = Tutorial::create([
            'title' => 'Broken link',
            'slug' => 'broken-link',
            'youtube_url' => 'https://www.youtube.com/playlist?list=PL123',
        ]);

        Livewire::actingAs($admin)
            ->test(ListTutorials::class)
            ->callAction(TestAction::make('publish')->table($broken));

        $this->assertSame(Tutorial::STATUS_DRAFT, $broken->fresh()->status);

        $good = Tutorial::create([
            'title' => 'Good one',
            'slug' => 'good-one',
            'youtube_url' => 'https://www.youtube.com/watch?v=abcdefghijk',
        ]);

        Livewire::actingAs($admin)
            ->test(ListTutorials::class)
            ->callAction(TestAction::make('publish')->table($good))
            ->assertHasNoActionErrors();

        $this->assertSame(Tutorial::STATUS_PUBLISHED, $good->fresh()->status);
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
