<?php

namespace Tests\Feature;

use App\Filament\Resources\Lessons\Pages\EditLesson;
use App\Filament\Widgets\ContentNeedingAttention;
use App\Models\Course;
use App\Models\Lesson;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * A YouTube link that does not point at one video used to save without a word
 * and leave the lesson with no video at all.
 */
class YoutubeLinkTest extends TestCase
{
    use RefreshDatabase;

    public function test_every_kind_of_single_video_link_is_understood(): void
    {
        foreach ([
            'https://www.youtube.com/watch?v=aqz-KE-bpKQ',
            'https://www.youtube.com/watch?v=aqz-KE-bpKQ&t=42s',
            'https://www.youtube.com/watch?feature=share&v=aqz-KE-bpKQ',
            'https://m.youtube.com/watch?v=aqz-KE-bpKQ',
            'https://youtu.be/aqz-KE-bpKQ',
            'https://youtu.be/aqz-KE-bpKQ?si=abc123',
            'https://www.youtube.com/shorts/aqz-KE-bpKQ',
            'https://www.youtube.com/live/aqz-KE-bpKQ',
            'https://www.youtube.com/embed/aqz-KE-bpKQ',
            'https://www.youtube-nocookie.com/embed/aqz-KE-bpKQ?rel=0',
            'aqz-KE-bpKQ',
        ] as $link) {
            $this->assertSame('aqz-KE-bpKQ', Lesson::youtubeIdFrom($link), $link);
        }
    }

    public function test_links_that_are_not_one_video_are_not_understood(): void
    {
        foreach ([
            'https://www.youtube.com/playlist?list=PLx0sYbCqOb8TBPRdmBHs5Iftvv9TPboYG',
            'https://www.youtube.com/@PilotTelematics',
            'https://vimeo.com/76979871',
            'https://www.youtube.com/watch?v=short',
            'https://www.youtube.com/watch?v=aqz-KE-bpKQextra',
            'https://evil.example/youtube.com/watch?v=aqz-KE-bpKQ',
            '',
            null,
        ] as $link) {
            $this->assertNull(Lesson::youtubeIdFrom($link), (string) $link);
        }
    }

    public function test_the_lesson_form_refuses_a_playlist_link(): void
    {
        [$lesson, $admin] = $this->lessonAndAdmin();

        Livewire::actingAs($admin)
            ->test(EditLesson::class, ['record' => $lesson->getRouteKey()])
            ->fillForm(['youtube_url' => 'https://www.youtube.com/playlist?list=PLx0sYbCqOb8TBPRdmBHs5Iftvv9TPboYG'])
            ->call('save')
            ->assertHasFormErrors(['youtube_url']);

        $this->assertNotSame('https://www.youtube.com/playlist?list=PLx0sYbCqOb8TBPRdmBHs5Iftvv9TPboYG', $lesson->fresh()->youtube_url);
    }

    public function test_a_live_link_saves_and_plays_on_the_lesson_page(): void
    {
        [$lesson, $admin] = $this->lessonAndAdmin();

        Livewire::actingAs($admin)
            ->test(EditLesson::class, ['record' => $lesson->getRouteKey()])
            ->fillForm(['youtube_url' => 'https://www.youtube.com/live/aqz-KE-bpKQ'])
            ->call('save')
            ->assertHasNoFormErrors();

        $lesson->refresh();
        $this->assertSame('https://www.youtube.com/live/aqz-KE-bpKQ', $lesson->youtube_url);

        $this->get(route('academy.lesson', [$lesson->course, $lesson]))
            ->assertSee('youtube-nocookie.com/embed/aqz-KE-bpKQ?rel=0', false);
    }

    public function test_a_stored_link_that_cannot_play_is_flagged_on_the_dashboard(): void
    {
        [$lesson, $admin] = $this->lessonAndAdmin();

        // Saved before the form checked links.
        $lesson->forceFill(['youtube_url' => 'https://www.youtube.com/@PilotTelematics'])->save();

        $this->actingAs($admin);
        $this->assertContains(
            'YouTube link that is not a playable video',
            (new ContentNeedingAttention)->getProblems()->pluck('what'),
        );

        // An uploaded video plays instead of the link, so it is not a problem.
        $lesson->forceFill(['video_path' => 'lesson-videos/sample.mp4'])->save();
        $this->assertNotContains(
            'YouTube link that is not a playable video',
            (new ContentNeedingAttention)->getProblems()->pluck('what'),
        );
    }

    /** @return array{0: Lesson, 1: User} */
    private function lessonAndAdmin(): array
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

        $admin = User::create([
            'name' => 'Pilot Admin',
            'email' => 'admin@pilot.local',
            'password' => bcrypt('password'),
            'role' => User::ROLE_ADMIN,
        ]);

        return [$lesson->fresh(), $admin];
    }
}
