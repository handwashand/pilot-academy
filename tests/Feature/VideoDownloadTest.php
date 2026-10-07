<?php

namespace Tests\Feature;

use App\Models\Lesson;
use App\Models\User;
use Database\Seeders\LanguageSeeder;
use Database\Seeders\PilotQuickStartSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Downloading an uploaded lesson video: admins always, a learner only when an
 * admin gave them the right, nobody who is not signed in.
 */
class VideoDownloadTest extends TestCase
{
    use RefreshDatabase;

    private const VIDEO = 'lesson-videos/intro.mp4';

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(LanguageSeeder::class);
        $this->seed(PilotQuickStartSeeder::class);
        Storage::fake('public');
        Storage::disk('public')->put(self::VIDEO, 'video-bytes');
    }

    private function lesson(): Lesson
    {
        $lesson = Lesson::first();
        $lesson->update(['video_sources' => [
            ['type' => 'upload', 'video_path' => self::VIDEO],
            ['type' => 'youtube', 'youtube_url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ'],
        ]]);

        return $lesson->fresh();
    }

    private function user(string $role, bool $withRight = false): User
    {
        $user = User::create(['name' => 'Someone', 'email' => $role.'@pilot.local', 'password' => 'password', 'role' => $role]);

        if ($withRight) {
            $user->permissions()->create(['permission' => User::PERMISSION_VIDEO_DOWNLOAD]);
        }

        return $user;
    }

    private function url(Lesson $lesson, int $video = 0): string
    {
        return route('academy.lesson.video.download', [$lesson->course, $lesson, $video]);
    }

    public function test_an_admin_downloads_the_file_under_a_readable_name(): void
    {
        $lesson = $this->lesson();

        $response = $this->actingAs($this->user(User::ROLE_ADMIN))->get($this->url($lesson))->assertOk();

        $this->assertStringContainsString('attachment', (string) $response->headers->get('Content-Disposition'));
        $this->assertStringContainsString('.mp4', (string) $response->headers->get('Content-Disposition'));
    }

    public function test_a_learner_needs_the_right(): void
    {
        $lesson = $this->lesson();

        $this->actingAs($this->user(User::ROLE_LEARNER))->get($this->url($lesson))->assertForbidden();
    }

    public function test_a_learner_with_the_right_downloads(): void
    {
        $lesson = $this->lesson();

        $this->actingAs($this->user(User::ROLE_LEARNER, withRight: true))->get($this->url($lesson))->assertOk();
    }

    public function test_a_guest_is_sent_to_sign_in(): void
    {
        $this->get($this->url($this->lesson()))->assertRedirect();
    }

    public function test_a_youtube_video_or_one_that_does_not_exist_cannot_be_downloaded(): void
    {
        $lesson = $this->lesson();
        $admin = $this->user(User::ROLE_ADMIN);

        $this->actingAs($admin)->get($this->url($lesson, 1))->assertNotFound();
        $this->actingAs($admin)->get($this->url($lesson, 7))->assertNotFound();
    }

    public function test_a_draft_lesson_is_not_downloadable_by_a_learner_with_the_right(): void
    {
        $lesson = $this->lesson();
        $lesson->update(['status' => 'draft']);

        $this->actingAs($this->user(User::ROLE_LEARNER, withRight: true))->get($this->url($lesson))->assertNotFound();
    }

    public function test_the_button_shows_only_to_those_who_may_download(): void
    {
        $lesson = $this->lesson();
        $page = route('academy.lesson', [$lesson->course, $lesson]);

        $this->actingAs($this->user(User::ROLE_LEARNER, withRight: true))->get($page)
            ->assertOk()
            ->assertSee($this->url($lesson), false)
            ->assertDontSee('controlsList="nodownload"', false);
    }

    public function test_without_the_right_there_is_no_button_and_the_player_menu_hides_download(): void
    {
        $lesson = $this->lesson();

        $this->actingAs($this->user(User::ROLE_LEARNER))->get(route('academy.lesson', [$lesson->course, $lesson]))
            ->assertOk()
            ->assertDontSee($this->url($lesson), false)
            ->assertSee('controlsList="nodownload"', false);
    }
}
