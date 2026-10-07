<?php

namespace Tests\Feature;

use App\Models\DescriptImport;
use App\Models\Lesson;
use App\Models\VideoTranslation;
use Database\Seeders\LanguageSeeder;
use Database\Seeders\PilotQuickStartSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Descript's stored subtitles, offered on the lesson player as captions.
 *
 * Browsers only read WebVTT in a track, while Descript exports SRT, so the
 * stored file is converted when it is served. Only a finished translation of a
 * video on a visible lesson is ever offered.
 */
class VideoCaptionsTest extends TestCase
{
    use RefreshDatabase;

    private const VIDEO = 'lesson-videos/intro.mp4';

    private const SRT = "1\n00:00:01,000 --> 00:00:03,500\nBonjour à tous\n\n2\n00:00:04,000 --> 00:00:06,250\nBienvenue\n";

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(LanguageSeeder::class);
        $this->seed(PilotQuickStartSeeder::class);
        Storage::fake('public');
        Storage::disk('public')->put(self::VIDEO, 'video');
    }

    private function lesson(): Lesson
    {
        $lesson = Lesson::first();
        $lesson->update(['video_sources' => [['type' => 'upload', 'video_path' => self::VIDEO]]]);

        return $lesson->fresh();
    }

    private function translation(Lesson $lesson, string $language, string $status = VideoTranslation::STATUS_DONE, bool $withFile = true): VideoTranslation
    {
        $import = DescriptImport::firstOrCreate(
            ['lesson_id' => $lesson->id, 'video_path' => self::VIDEO],
            ['source_language' => 'en', 'status' => DescriptImport::STATUS_READY],
        );

        $path = "video-translations/lesson-{$lesson->id}/{$import->id}-{$language}.srt";

        if ($withFile) {
            Storage::disk('public')->put($path, self::SRT);
        }

        return VideoTranslation::create([
            'descript_import_id' => $import->id,
            'lesson_id' => $lesson->id,
            'language' => $language,
            'kind' => VideoTranslation::KIND_TRANSCRIPT,
            'status' => $status,
            'subtitle_path' => $path,
        ]);
    }

    private function captionsUrl(Lesson $lesson, string $language, int $video = 0): string
    {
        return route('academy.lesson.captions', [$lesson->course, $lesson, $video, $language]);
    }

    public function test_the_stored_srt_is_served_as_webvtt(): void
    {
        $lesson = $this->lesson();
        $this->translation($lesson, 'fr');

        $response = $this->get($this->captionsUrl($lesson, 'fr'))->assertOk();

        $this->assertStringStartsWith('text/vtt', $response->headers->get('Content-Type'));
        $body = $response->getContent();
        $this->assertStringStartsWith("WEBVTT\n\n", $body);
        $this->assertStringContainsString('00:00:01.000 --> 00:00:03.500', $body);
        $this->assertStringNotContainsString('00:00:01,000', $body, 'Commas become dots, as WebVTT requires.');
        $this->assertStringContainsString('Bonjour à tous', $body);
    }

    public function test_only_a_finished_translation_with_its_file_is_offered(): void
    {
        $lesson = $this->lesson();
        $this->translation($lesson, 'fr', VideoTranslation::STATUS_TRANSLATING);
        $this->translation($lesson, 'es', VideoTranslation::STATUS_FAILED);
        $this->translation($lesson, 'ru', VideoTranslation::STATUS_DONE, withFile: false);

        foreach (['fr', 'es', 'ru', 'pt'] as $language) {
            $this->get($this->captionsUrl($lesson, $language))->assertNotFound();
        }

        $this->get($this->captionsUrl($lesson, '../../etc'))->assertNotFound();
    }

    public function test_a_draft_lessons_captions_do_not_reach_students(): void
    {
        $lesson = $this->lesson();
        $this->translation($lesson, 'fr');
        $lesson->update(['status' => 'draft']);

        $this->get($this->captionsUrl($lesson, 'fr'))->assertNotFound();
    }

    public function test_a_video_that_does_not_exist_has_no_captions(): void
    {
        $lesson = $this->lesson();
        $this->translation($lesson, 'fr');

        $this->get($this->captionsUrl($lesson, 'fr', 3))->assertNotFound();
    }

    public function test_the_player_offers_a_track_for_each_finished_language_only(): void
    {
        $lesson = $this->lesson();
        $this->translation($lesson, 'fr');
        $this->translation($lesson, 'es', VideoTranslation::STATUS_TRANSLATING);

        $html = $this->get(route('academy.lesson', [$lesson->course, $lesson]))->assertOk()->getContent();

        $this->assertStringContainsString('<track kind="captions"', $html);
        $this->assertStringContainsString('srclang="fr"', $html);
        $this->assertStringContainsString($this->captionsUrl($lesson, 'fr'), $html);
        $this->assertStringNotContainsString('srclang="es"', $html);
    }

    public function test_a_lesson_without_translations_has_no_tracks(): void
    {
        $lesson = $this->lesson();

        $this->get(route('academy.lesson', [$lesson->course, $lesson]))
            ->assertOk()
            ->assertDontSee('<track', false);
    }
}
