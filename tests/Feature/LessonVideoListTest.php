<?php

namespace Tests\Feature;

use App\Actions\FindContentProblems;
use App\Filament\Resources\Lessons\Pages\EditLesson;
use App\Models\Course;
use App\Models\Lesson;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * A lesson's Videos list: up to five, each a YouTube link or an uploaded file,
 * played in the order they are listed.
 */
class LessonVideoListTest extends TestCase
{
    use RefreshDatabase;

    private function lesson(array $videos = [], array $legacy = []): Lesson
    {
        $course = Course::create([
            'title' => 'Understanding GARM',
            'slug' => 'understanding-garm',
            'level' => 'beginner',
            'status' => Course::STATUS_PUBLISHED,
        ]);

        $lesson = $course->lessons()->create([
            'title' => 'Lesson one',
            'slug' => 'lesson-one',
            'content' => '<p>Body.</p>',
            'sort_order' => 1,
        ]);

        if ($videos !== []) {
            $lesson->forceFill(['video_sources' => $videos])->save();
        }

        if ($legacy !== []) {
            $lesson->forceFill($legacy)->save();
        }

        return $lesson->fresh();
    }

    private function admin(): User
    {
        return User::create([
            'name' => 'Pilot Admin',
            'email' => 'admin@pilot.local',
            'password' => 'password',
            'role' => User::ROLE_ADMIN,
        ]);
    }

    private function page(Lesson $lesson): string
    {
        return $this->get(route('academy.lesson', [$lesson->course, $lesson]))->assertOk()->getContent();
    }

    public function test_every_video_in_the_list_plays_on_the_lesson_page_in_order(): void
    {
        $lesson = $this->lesson([
            ['type' => 'youtube', 'youtube_url' => 'https://www.youtube.com/watch?v=aqz-KE-bpKQ'],
            ['type' => 'upload', 'video_path' => 'lesson-videos/sample.mp4'],
            ['type' => 'youtube', 'youtube_url' => 'https://youtu.be/dQw4w9WgXcQ'],
        ]);

        $html = $this->page($lesson);

        $this->assertSame(2, substr_count($html, 'youtube-nocookie.com/embed/'));
        $this->assertSame(1, substr_count($html, '<video'));
        $this->assertLessThan(strpos($html, '<video'), strpos($html, 'embed/aqz-KE-bpKQ'));
        $this->assertLessThan(strpos($html, 'embed/dQw4w9WgXcQ'), strpos($html, '<video'));
    }

    public function test_only_the_first_uploaded_video_remembers_its_place(): void
    {
        $lesson = $this->lesson([
            ['type' => 'upload', 'video_path' => 'lesson-videos/one.mp4'],
            ['type' => 'upload', 'video_path' => 'lesson-videos/two.mp4'],
        ]);

        $html = $this->page($lesson);

        // One saved position per lesson: two players writing it would undo each other.
        $this->assertSame(2, substr_count($html, '<video'));
        $this->assertSame(1, substr_count($html, json_encode(route('academy.lesson.position', [$lesson->course, $lesson]))));
    }

    public function test_each_video_needs_its_link_or_its_file(): void
    {
        $lesson = $this->lesson();
        $admin = $this->admin();

        Livewire::actingAs($admin)
            ->test(EditLesson::class, ['record' => $lesson->getRouteKey()])
            ->fillForm(['video_sources' => [['type' => 'youtube', 'youtube_url' => '']]])
            ->call('save')
            ->assertHasFormErrors();

        Livewire::actingAs($admin)
            ->test(EditLesson::class, ['record' => $lesson->getRouteKey()])
            ->fillForm(['video_sources' => [['type' => 'upload']]])
            ->call('save')
            ->assertHasFormErrors();
    }

    public function test_a_lesson_takes_no_more_than_five_videos(): void
    {
        $lesson = $this->lesson();

        Livewire::actingAs($this->admin())
            ->test(EditLesson::class, ['record' => $lesson->getRouteKey()])
            ->fillForm(['video_sources' => array_fill(0, 6, ['type' => 'youtube', 'youtube_url' => 'https://youtu.be/aqz-KE-bpKQ'])])
            ->call('save')
            ->assertHasFormErrors(['video_sources']);

        $this->assertSame([], $lesson->fresh()->videoEntries());
    }

    public function test_removing_every_video_leaves_none_behind(): void
    {
        // Saved before the Videos list, in the old single-video column.
        $lesson = $this->lesson(legacy: ['youtube_url' => 'https://www.youtube.com/watch?v=aqz-KE-bpKQ']);

        Livewire::actingAs($this->admin())
            ->test(EditLesson::class, ['record' => $lesson->getRouteKey()])
            ->fillForm(['video_sources' => []])
            ->call('save')
            ->assertHasNoFormErrors();

        $fresh = $lesson->fresh();
        $this->assertSame([], $fresh->videoEntries());
        $this->assertNull($fresh->getAttributes()['youtube_url']);
        $this->assertSame(0, substr_count($this->page($fresh), 'youtube-nocookie.com/embed/'));
    }

    public function test_an_older_lesson_shows_the_one_video_students_see_in_the_form(): void
    {
        // Before the list, an uploaded file played instead of the link.
        $lesson = $this->lesson(legacy: [
            'youtube_url' => 'https://www.youtube.com/watch?v=aqz-KE-bpKQ',
            'video_path' => 'lesson-videos/sample.mp4',
        ]);

        $state = Livewire::actingAs($this->admin())
            ->test(EditLesson::class, ['record' => $lesson->getRouteKey()])
            ->get('data.video_sources');

        $this->assertCount(1, $state);
        $this->assertSame('upload', array_values($state)[0]['type']);
    }

    public function test_a_broken_link_anywhere_in_the_list_is_reported(): void
    {
        $lesson = $this->lesson([
            ['type' => 'youtube', 'youtube_url' => 'https://www.youtube.com/watch?v=aqz-KE-bpKQ'],
            ['type' => 'youtube', 'youtube_url' => 'https://www.youtube.com/@PilotTelematics'],
        ]);

        $this->assertTrue($lesson->hasUnplayableYoutubeLink());
        $this->assertContains(
            'YouTube link that is not a playable video',
            app(FindContentProblems::class)->forViewer($this->admin())->pluck('what'),
        );
    }
}
