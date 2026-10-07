<?php

namespace Tests\Feature;

use App\Models\DescriptImport;
use App\Models\Lesson;
use App\Models\User;
use App\Models\VideoTranslation;
use Database\Seeders\LanguageSeeder;
use Database\Seeders\PilotQuickStartSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * A lesson video Descript has dubbed: someone reading the academy in that
 * language hears the dubbed file; everyone else, and anyone who asks for it,
 * the original.
 */
class DubbedVideoTest extends TestCase
{
    use RefreshDatabase;

    private const VIDEO = 'lesson-videos/intro.mp4';

    private const DUB = 'video-translations/lesson-1/1-fr-dub.mp4';

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(LanguageSeeder::class);
        $this->seed(PilotQuickStartSeeder::class);
        Storage::fake('public');
        Storage::disk('public')->put(self::VIDEO, 'original');
        Storage::disk('public')->put(self::DUB, 'french-voice');
    }

    private function lessonWithFrenchDub(): Lesson
    {
        $lesson = Lesson::first();
        $lesson->update(['video_sources' => [['type' => 'upload', 'video_path' => self::VIDEO]]]);

        $import = DescriptImport::create([
            'lesson_id' => $lesson->id, 'video_path' => self::VIDEO, 'source_language' => 'en', 'status' => DescriptImport::STATUS_READY,
        ]);

        VideoTranslation::create([
            'descript_import_id' => $import->id,
            'lesson_id' => $lesson->id,
            'language' => 'fr',
            'kind' => VideoTranslation::KIND_DUB,
            'status' => VideoTranslation::STATUS_DONE,
            'dub_path' => self::DUB,
        ]);

        return $lesson->fresh();
    }

    private function page(Lesson $lesson): string
    {
        return route('academy.lesson', [$lesson->course, $lesson]);
    }

    public function test_a_french_reader_hears_the_dub(): void
    {
        $lesson = $this->lessonWithFrenchDub();

        $this->withSession(['locale' => 'fr'])->get($this->page($lesson))
            ->assertOk()
            ->assertSee(Storage::disk('public')->url(self::DUB), false)
            ->assertDontSee('<source src="'.Storage::disk('public')->url(self::VIDEO).'"', false)
            ->assertSee('?audio=original', false);
    }

    public function test_the_original_is_one_click_away(): void
    {
        $lesson = $this->lessonWithFrenchDub();

        $this->withSession(['locale' => 'fr'])->get($this->page($lesson).'?audio=original')
            ->assertOk()
            ->assertSee('<source src="'.Storage::disk('public')->url(self::VIDEO).'"', false);
    }

    public function test_a_reader_in_another_language_gets_the_original(): void
    {
        $lesson = $this->lessonWithFrenchDub();

        $this->get($this->page($lesson))
            ->assertOk()
            ->assertSee('<source src="'.Storage::disk('public')->url(self::VIDEO).'"', false)
            ->assertDontSee(self::DUB, false);
    }

    public function test_downloading_gives_the_version_being_watched(): void
    {
        $lesson = $this->lessonWithFrenchDub();
        $admin = User::create(['name' => 'Admin', 'email' => 'admin@pilot.local', 'password' => 'password', 'role' => User::ROLE_ADMIN, 'locale' => 'fr']);
        $url = route('academy.lesson.video.download', [$lesson->course, $lesson, 0]);

        $this->assertSame('french-voice', $this->actingAs($admin)->get($url)->assertOk()->streamedContent());
        $this->assertSame('original', $this->actingAs($admin)->get($url.'?audio=original')->assertOk()->streamedContent());
    }
}
