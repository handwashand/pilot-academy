<?php

namespace Tests\Feature;

use App\Filament\Resources\Lessons\Pages\EditLesson;
use App\Models\DescriptImport;
use App\Models\Lesson;
use App\Models\User;
use App\Models\VideoTranslation;
use Database\Seeders\LanguageSeeder;
use Database\Seeders\PilotQuickStartSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * A lesson video's own version for a language, added by hand on the lesson
 * form ("Other languages"): a reader in that language gets it, everyone else
 * the original, and it wins over a Descript dub (owner, 2026-11-25).
 */
class LessonLanguageVideoTest extends TestCase
{
    use RefreshDatabase;

    private const VIDEO = 'lesson-videos/intro.mp4';

    private const PORTUGUESE = 'lesson-videos/intro-pt.mp4';

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(LanguageSeeder::class);
        $this->seed(PilotQuickStartSeeder::class);
        Storage::fake('public');
        Storage::disk('public')->put(self::VIDEO, 'original');
        Storage::disk('public')->put(self::PORTUGUESE, 'portuguese');
    }

    private function lesson(array $entry): Lesson
    {
        $lesson = Lesson::first();
        $lesson->update(['video_sources' => [$entry]]);

        return $lesson->fresh();
    }

    private function page(Lesson $lesson): string
    {
        return route('academy.lesson', [$lesson->course, $lesson]);
    }

    private function source(string $path): string
    {
        return '<source src="'.Storage::disk('public')->url($path).'"';
    }

    public function test_a_portuguese_reader_gets_the_portuguese_upload_and_others_the_original(): void
    {
        $lesson = $this->lesson(['type' => 'upload', 'video_path' => self::VIDEO, 'language_versions' => [
            ['language' => 'pt', 'video_path' => self::PORTUGUESE],
        ]]);

        $this->withSession(['locale' => 'pt'])->get($this->page($lesson))
            ->assertOk()
            ->assertSee($this->source(self::PORTUGUESE), false)
            ->assertSee('?audio=original', false);

        $this->withSession(['locale' => 'pt'])->get($this->page($lesson).'?audio=original')
            ->assertSee($this->source(self::VIDEO), false);

        $this->withSession(['locale' => 'fr'])->get($this->page($lesson))
            ->assertSee($this->source(self::VIDEO), false)
            ->assertDontSee($this->source(self::PORTUGUESE), false);
    }

    public function test_a_version_added_by_hand_wins_over_a_descript_dub(): void
    {
        $lesson = $this->lesson(['type' => 'upload', 'video_path' => self::VIDEO, 'language_versions' => [
            ['language' => 'pt', 'video_path' => self::PORTUGUESE],
        ]]);
        $import = DescriptImport::create(['lesson_id' => $lesson->id, 'video_path' => self::VIDEO, 'source_language' => 'en', 'status' => DescriptImport::STATUS_READY]);
        VideoTranslation::create([
            'descript_import_id' => $import->id, 'lesson_id' => $lesson->id, 'language' => 'pt',
            'kind' => VideoTranslation::KIND_DUB, 'status' => VideoTranslation::STATUS_DONE, 'dub_path' => 'video-translations/dub-pt.mp4',
        ]);

        $this->withSession(['locale' => 'pt'])->get($this->page($lesson))
            ->assertSee($this->source(self::PORTUGUESE), false)
            ->assertDontSee('dub-pt.mp4', false);
    }

    public function test_a_youtube_video_can_have_a_link_per_language(): void
    {
        $lesson = $this->lesson(['type' => 'youtube', 'youtube_url' => 'https://www.youtube.com/watch?v=aaaaaaaaaaa', 'language_versions' => [
            ['language' => 'pt', 'youtube_url' => 'https://youtu.be/bbbbbbbbbbb'],
        ]]);

        $this->withSession(['locale' => 'pt'])->get($this->page($lesson))
            ->assertSee('youtube-nocookie.com/embed/bbbbbbbbbbb', false)
            ->assertDontSee('embed/aaaaaaaaaaa', false);

        $this->withSession(['locale' => 'pt'])->get($this->page($lesson).'?audio=original')
            ->assertSee('youtube-nocookie.com/embed/aaaaaaaaaaa', false);

        $this->withSession(['locale' => 'es'])->get($this->page($lesson))
            ->assertSee('youtube-nocookie.com/embed/aaaaaaaaaaa', false);
    }

    public function test_the_lesson_form_saves_a_language_version(): void
    {
        $lesson = $this->lesson(['type' => 'youtube', 'youtube_url' => 'https://www.youtube.com/watch?v=aaaaaaaaaaa']);
        $admin = User::create(['name' => 'A', 'email' => 'a@pilot.local', 'password' => 'password', 'role' => User::ROLE_ADMIN]);

        $page = Livewire::actingAs($admin)->test(EditLesson::class, ['record' => $lesson->getRouteKey()]);
        $key = array_key_first($page->get('data.video_sources'));

        $page->set("data.video_sources.{$key}.language_versions", ['v1' => ['language' => 'pt', 'youtube_url' => 'https://youtu.be/bbbbbbbbbbb']])
            ->call('save')
            ->assertHasNoFormErrors();

        $saved = $lesson->fresh()->videoEntries()[0];
        $this->assertSame('pt', $saved['language_versions'][0]['language'] ?? $saved['language_versions']['v1']['language'] ?? null);
        $this->assertNotNull(Lesson::languageVersion($saved, 'pt'));
        $this->assertNull(Lesson::languageVersion($saved, 'fr'));
    }

    public function test_a_broken_youtube_link_for_a_language_is_refused(): void
    {
        $lesson = $this->lesson(['type' => 'youtube', 'youtube_url' => 'https://www.youtube.com/watch?v=aaaaaaaaaaa']);
        $admin = User::create(['name' => 'A', 'email' => 'a@pilot.local', 'password' => 'password', 'role' => User::ROLE_ADMIN]);

        $page = Livewire::actingAs($admin)->test(EditLesson::class, ['record' => $lesson->getRouteKey()]);
        $key = array_key_first($page->get('data.video_sources'));

        $page->set("data.video_sources.{$key}.language_versions", ['v1' => ['language' => 'pt', 'youtube_url' => 'https://www.youtube.com/playlist?list=PL123']])
            ->call('save')
            ->assertHasFormErrors(["video_sources.{$key}.language_versions.v1.youtube_url"]);
    }
}
