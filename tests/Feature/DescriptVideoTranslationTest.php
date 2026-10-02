<?php

namespace Tests\Feature;

use App\Actions\TranslateLessonVideo;
use App\Filament\Resources\Lessons\Pages\EditLesson;
use App\Models\DescriptImport;
use App\Models\Lesson;
use App\Models\User;
use App\Models\VideoTranslation;
use Database\Seeders\LanguageSeeder;
use Database\Seeders\PilotQuickStartSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Sleep;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Lesson videos translated by Descript, and kept.
 *
 * The owner's goal: anything translated is stored in the app and never
 * requested from the API twice. The tests that hold that are the ones counting
 * calls — see docs/descript-integration.md, rules 1–5.
 *
 * Descript is faked statefully: a translation's composition only appears in
 * the project once its agent job has finished, as the real API should behave.
 * No test here talks to Descript.
 */
class DescriptVideoTranslationTest extends TestCase
{
    use RefreshDatabase;

    private const VIDEO = 'lesson-videos/intro.mp4';

    /** @var array<int, array{0: string, 1: string}> method + path of every call */
    private array $calls = [];

    /** @var array<int, array{id: string, name: string}> */
    private array $compositions = [['id' => 'comp-orig', 'name' => 'Lesson video']];

    /** @var array<string, string> agent job id => language */
    private array $agentJobs = [];

    /** Status the next agent call answers with; 201 is a normal start. */
    private int $agentStatus = 201;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(LanguageSeeder::class);
        $this->seed(PilotQuickStartSeeder::class);

        Sleep::fake();
        Storage::fake('public');
        Storage::disk('public')->put(self::VIDEO, str_repeat('x', 2048));

        config([
            'services.descript.enabled' => true,
            'services.descript.token' => 'secret-test-token',
            'services.descript.base_url' => 'https://descriptapi.test/v1',
            'app.url' => 'https://academy.example.com',
        ]);

        $this->fakeDescript();
    }

    private function lesson(): Lesson
    {
        $lesson = Lesson::first();
        $lesson->update(['video_sources' => [['type' => 'upload', 'video_path' => self::VIDEO]]]);

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

    private function descriptEditor(): User
    {
        $admin = $this->admin();
        $admin->permissions()->create(['permission' => User::PERMISSION_DESCRIPT_TRANSLATE]);

        return $admin;
    }

    private function translator(): TranslateLessonVideo
    {
        return app(TranslateLessonVideo::class);
    }

    /** Advance until nothing is in flight, as the Check progress button would. */
    private function settle(Lesson $lesson): void
    {
        for ($i = 0; $i < 8 && VideoTranslation::query()->where('lesson_id', $lesson->id)->inFlight()->exists(); $i++) {
            $this->translator()->advanceLesson($lesson);
        }
    }

    private function callsTo(string $method, string $path): int
    {
        return collect($this->calls)->filter(fn (array $call): bool => $call[0] === $method && Str::is($path, $call[1]))->count();
    }

    public function test_descript_supplements_the_existing_manual_translation_action(): void
    {
        $lesson = $this->lesson();
        $admin = $this->descriptEditor();

        Livewire::actingAs($admin)
            ->test(EditLesson::class, ['record' => $lesson->getRouteKey()])
            ->assertActionVisible('translateContent')
            ->assertActionHasLabel('translateContent', __t('admin_common.translate.button'))
            ->assertActionVisible('translateVideoWithDescript')
            ->assertActionHasLabel('translateVideoWithDescript', __t('admin_descript.action.button'))
            ->callAction('translateContent', data: ['fr' => ['title' => 'Prise en main']])
            ->assertHasNoActionErrors();

        $this->assertSame('Prise en main', $lesson->fresh()->translated('title', 'fr'));
        $this->assertSame([], $this->calls, 'Manual translation must not call Descript.');

        config(['services.descript.enabled' => false]);

        Livewire::actingAs($admin)
            ->test(EditLesson::class, ['record' => $lesson->getRouteKey()])
            ->assertActionVisible('translateContent')
            ->assertActionHidden('translateVideoWithDescript');
    }

    public function test_a_language_is_translated_and_what_comes_back_is_stored(): void
    {
        $lesson = $this->lesson();

        $this->translator()->request($lesson, self::VIDEO, ['fr']);
        $this->settle($lesson);

        $row = VideoTranslation::sole();
        $this->assertSame(VideoTranslation::STATUS_DONE, $row->status);
        $this->assertSame('comp-fr', $row->composition_id);
        $this->assertSame('Texte traduit (fr)', $row->transcript, 'The transcript is kept on the row.');
        $this->assertSame(7, $row->ai_credits_used);
        Storage::disk('public')->assertExists($row->subtitle_path);
        $this->assertStringContainsString('-->', Storage::disk('public')->get($row->subtitle_path), 'The .srt is a real subtitle file.');
        $this->assertSame('Texte traduit (fr)', $lesson->fresh()->translated('transcript', 'fr'), 'Partners read it as the French transcript.');
    }

    /** The owner's goal, in one test. */
    public function test_asking_again_for_a_done_language_spends_nothing(): void
    {
        $lesson = $this->lesson();
        $this->translator()->request($lesson, self::VIDEO, ['fr']);
        $this->settle($lesson);

        $before = count($this->calls);
        $outcome = $this->translator()->request($lesson, self::VIDEO, ['fr']);
        $this->translator()->advanceAll();

        $this->assertSame(['fr'], $outcome['done']);
        $this->assertSame([], $outcome['requested']);
        $this->assertSame($before, count($this->calls), 'Not one call to Descript.');
        $this->assertSame(1, VideoTranslation::count());
    }

    public function test_many_languages_share_one_import(): void
    {
        $lesson = $this->lesson();

        $this->translator()->request($lesson, self::VIDEO, ['fr', 'es', 'ru']);
        $this->settle($lesson);

        $this->assertSame(1, $this->callsTo('POST', 'jobs/import/project_media'), 'The video is sent once.');
        $this->assertSame(1, DescriptImport::count());
        $this->assertSame(3, $this->callsTo('POST', 'jobs/agent'), 'One translation per language.');
        $this->assertSame(3, VideoTranslation::where('status', VideoTranslation::STATUS_DONE)->count());
        $this->assertSame('Texto traducido (es)', $lesson->fresh()->translated('transcript', 'es'));
    }

    public function test_a_transcript_somebody_wrote_is_never_overwritten(): void
    {
        $lesson = $this->lesson();
        $lesson->setTranslation('transcript', 'fr', 'Transcription écrite à la main.');

        $this->translator()->request($lesson, self::VIDEO, ['fr']);
        $this->settle($lesson);

        $this->assertSame('Transcription écrite à la main.', $lesson->fresh()->translated('transcript', 'fr'));
        $this->assertSame('Texte traduit (fr)', VideoTranslation::sole()->transcript, 'Descript\'s version is still kept, on the row.');
    }

    public function test_running_out_of_credits_fails_readably_and_stores_nothing(): void
    {
        $lesson = $this->lesson();
        $this->agentStatus = 402;

        $this->translator()->request($lesson, self::VIDEO, ['fr']);
        $this->settle($lesson);

        $row = VideoTranslation::sole();
        $this->assertSame(VideoTranslation::STATUS_FAILED, $row->status);
        $this->assertSame(__t('admin_descript.errors.out_of_credits'), $row->error);
        $this->assertNull($row->transcript);
        $this->assertNull($row->subtitle_path);
        $this->assertSame($lesson->transcript, $lesson->fresh()->translated('transcript', 'fr'), 'Nothing was written for partners.');
    }

    public function test_a_failed_language_is_tried_again_only_when_someone_asks(): void
    {
        $lesson = $this->lesson();
        $this->agentStatus = 402;
        $this->translator()->request($lesson, self::VIDEO, ['fr']);
        $this->settle($lesson);

        $agentCalls = $this->callsTo('POST', 'jobs/agent');
        $this->translator()->advanceAll();
        $this->assertSame($agentCalls, $this->callsTo('POST', 'jobs/agent'), 'A failed row is not retried on its own.');

        $this->agentStatus = 201;
        $outcome = $this->translator()->request($lesson, self::VIDEO, ['fr']);
        $this->settle($lesson);

        $this->assertSame(['fr'], $outcome['requested']);
        $this->assertSame(VideoTranslation::STATUS_DONE, VideoTranslation::sole()->status);
    }

    public function test_nothing_is_sent_when_descript_is_switched_off(): void
    {
        config(['services.descript.enabled' => false]);
        $lesson = $this->lesson();

        $this->translator()->request($lesson, self::VIDEO, ['fr']);
        $this->translator()->advanceAll();

        Http::assertNothingSent();
        $this->assertSame(VideoTranslation::STATUS_PENDING, VideoTranslation::sole()->status);
    }

    public function test_without_a_token_it_counts_as_switched_off(): void
    {
        config(['services.descript.token' => '']);

        $this->assertFalse($this->translator()->enabled());
    }

    public function test_a_video_that_is_not_one_of_the_lessons_uploads_is_refused(): void
    {
        $lesson = $this->lesson();

        $this->expectException(\InvalidArgumentException::class);
        $this->translator()->request($lesson, '../../.env', ['fr']);
    }

    public function test_a_local_machine_uploads_and_the_token_never_goes_to_the_upload_host(): void
    {
        config(['app.url' => 'http://localhost']);
        $lesson = $this->lesson();

        $this->translator()->request($lesson, self::VIDEO, ['fr']);

        Http::assertSent(fn (Request $request): bool => Str::startsWith($request->url(), 'https://storage.test/')
            && $request->method() === 'PUT'
            && ! $request->hasHeader('Authorization'));

        Http::assertSent(fn (Request $request): bool => Str::endsWith($request->url(), 'jobs/import/project_media')
            && isset($request->data()['add_media']['intro.mp4']['file_size'])
            && ! isset($request->data()['add_media']['intro.mp4']['url']));
    }

    public function test_a_public_server_lets_descript_fetch_the_file_itself(): void
    {
        $lesson = $this->lesson();

        $this->translator()->request($lesson, self::VIDEO, ['fr']);

        Http::assertSent(fn (Request $request): bool => Str::endsWith($request->url(), 'jobs/import/project_media')
            && Str::endsWith((string) ($request->data()['add_media']['intro.mp4']['url'] ?? ''), self::VIDEO)
            && $request->data()['folder_name'] === config('services.descript.project_folder'));

        Http::assertNotSent(fn (Request $request): bool => Str::startsWith($request->url(), 'https://storage.test/'));
    }

    public function test_the_sync_command_finishes_work_and_then_has_nothing_to_spend(): void
    {
        $lesson = $this->lesson();
        $this->translator()->request($lesson, self::VIDEO, ['fr']);

        for ($i = 0; $i < 4; $i++) {
            $this->artisan('descript:sync')->assertSuccessful();
        }

        $this->assertSame(VideoTranslation::STATUS_DONE, VideoTranslation::sole()->status);

        $before = count($this->calls);
        $this->artisan('descript:sync')->assertSuccessful();
        $this->assertSame($before, count($this->calls));
    }

    public function test_the_button_shows_only_for_a_lesson_with_an_uploaded_video(): void
    {
        $admin = $this->descriptEditor();
        $withUpload = $this->lesson();
        $youtubeOnly = Lesson::query()->whereKeyNot($withUpload->id)->first();
        $youtubeOnly->update(['video_sources' => [['type' => 'youtube', 'youtube_url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ']]]);

        Livewire::actingAs($admin)
            ->test(EditLesson::class, ['record' => $withUpload->getRouteKey()])
            ->assertActionVisible('translateVideoWithDescript');

        Livewire::actingAs($admin)
            ->test(EditLesson::class, ['record' => $youtubeOnly->getRouteKey()])
            ->assertActionHidden('translateVideoWithDescript');

        config(['services.descript.enabled' => false]);

        Livewire::actingAs($admin)
            ->test(EditLesson::class, ['record' => $withUpload->getRouteKey()])
            ->assertActionHidden('translateVideoWithDescript');
    }

    public function test_descript_actions_require_an_explicit_account_permission(): void
    {
        $lesson = $this->lesson();
        $admin = $this->admin();
        $this->translator()->request($lesson, self::VIDEO, ['fr']);

        Livewire::actingAs($admin)
            ->test(EditLesson::class, ['record' => $lesson->getRouteKey()])
            ->assertActionHidden('translateVideoWithDescript')
            ->assertActionHidden('checkDescriptProgress');

        $admin->permissions()->create(['permission' => User::PERMISSION_DESCRIPT_TRANSLATE]);

        Livewire::actingAs($admin)
            ->test(EditLesson::class, ['record' => $lesson->getRouteKey()])
            ->assertActionVisible('translateVideoWithDescript')
            ->assertActionVisible('checkDescriptProgress');
    }

    public function test_starting_a_translation_requires_an_intentional_confirmation(): void
    {
        $lesson = $this->lesson();

        Livewire::actingAs($this->descriptEditor())
            ->test(EditLesson::class, ['record' => $lesson->getRouteKey()])
            ->callAction('translateVideoWithDescript', data: [
                'video' => self::VIDEO,
                'languages' => ['fr'],
                'confirmed' => false,
            ])
            ->assertHasActionErrors(['confirmed' => 'accepted']);

        $this->assertDatabaseCount('video_translations', 0);
        $this->assertSame([], $this->calls, 'Validation must stop the request before Descript is called.');
    }

    public function test_an_editor_starts_a_translation_from_the_lesson(): void
    {
        $lesson = $this->lesson();

        Livewire::actingAs($this->descriptEditor())
            ->test(EditLesson::class, ['record' => $lesson->getRouteKey()])
            ->callAction('translateVideoWithDescript', data: [
                'video' => self::VIDEO,
                'languages' => ['fr', 'es'],
                'confirmed' => true,
            ])
            ->assertHasNoActionErrors()
            ->assertNotified(__t('admin_descript.action.requested'));

        $this->assertSame(['es', 'fr'], VideoTranslation::orderBy('language')->pluck('language')->all());
        $this->assertSame(1, $this->callsTo('POST', 'jobs/import/project_media'));
    }

    public function test_check_progress_finishes_the_work(): void
    {
        $lesson = $this->lesson();
        $this->translator()->request($lesson, self::VIDEO, ['fr']);

        $page = Livewire::actingAs($this->descriptEditor())->test(EditLesson::class, ['record' => $lesson->getRouteKey()]);

        for ($i = 0; $i < 4 && VideoTranslation::sole()->isInFlight(); $i++) {
            $page->callAction('checkDescriptProgress');
        }

        $this->assertSame(VideoTranslation::STATUS_DONE, VideoTranslation::sole()->status);
    }

    /**
     * A stand-in for Descript's API that remembers what happened: an import
     * finishes, an agent job adds its composition to the project, an export
     * returns that composition's words.
     */
    private function fakeDescript(): void
    {
        Http::fake(function (Request $request) {
            $url = $request->url();
            $method = $request->method();

            if (Str::startsWith($url, 'https://storage.test/')) {
                $this->calls[] = [$method, 'upload'];

                return Http::response('', 200);
            }

            $path = Str::after($url, 'descriptapi.test/v1/');
            $this->calls[] = [$method, $path];

            if ($method === 'POST' && $path === 'jobs/import/project_media') {
                return Http::response([
                    'job_id' => 'job-import',
                    'drive_id' => 'drive-1',
                    'project_id' => 'proj-1',
                    'project_url' => 'https://web.descript.com/proj-1',
                    'upload_urls' => isset($request->data()['add_media']['intro.mp4']['file_size'])
                        ? ['intro.mp4' => ['upload_url' => 'https://storage.test/upload/intro', 'asset_id' => 'a1', 'artifact_id' => 'b1']]
                        : null,
                ], 201);
            }

            if ($method === 'GET' && $path === 'jobs/job-import') {
                return Http::response([
                    'job_id' => 'job-import',
                    'job_type' => 'import',
                    'job_state' => 'stopped',
                    'result' => [
                        'status' => 'success',
                        'media_status' => ['intro.mp4' => ['status' => 'success', 'duration_seconds' => 42]],
                        'media_seconds_used' => 42,
                    ],
                ]);
            }

            if ($method === 'GET' && $path === 'projects/proj-1') {
                return Http::response(['id' => 'proj-1', 'compositions' => $this->compositions]);
            }

            if ($method === 'POST' && $path === 'jobs/agent') {
                if ($this->agentStatus !== 201) {
                    return Http::response(['error' => 'insufficient_credits', 'message' => 'Not enough AI credits'], $this->agentStatus);
                }

                preg_match('/Pilot Academy — ([a-z]+)/u', (string) $request->data()['prompt'], $m);
                $jobId = 'job-agent-'.($m[1] ?? 'x');
                $this->agentJobs[$jobId] = $m[1] ?? 'x';

                return Http::response(['job_id' => $jobId, 'project_id' => 'proj-1', 'conversation_id' => 'c1'], 201);
            }

            if ($method === 'GET' && Str::startsWith($path, 'jobs/job-agent-')) {
                $language = $this->agentJobs[Str::after($path, 'jobs/')] ?? 'x';

                // The translation's composition exists from the moment it finishes.
                if (! collect($this->compositions)->contains('id', 'comp-'.$language)) {
                    $this->compositions[] = ['id' => 'comp-'.$language, 'name' => 'Pilot Academy — '.$language];
                }

                return Http::response([
                    'job_state' => 'stopped',
                    'result' => ['status' => 'success', 'agent_response' => 'Translated.', 'project_changed' => true, 'ai_credits_used' => 7],
                ]);
            }

            if ($method === 'POST' && $path === 'export/transcript') {
                $language = Str::after((string) $request->data()['composition_id'], 'comp-');
                $text = match ($language) {
                    'es' => 'Texto traducido (es)',
                    'ru' => 'Переведённый текст (ru)',
                    default => 'Texte traduit ('.$language.')',
                };

                return $request->data()['format'] === 'srt'
                    ? Http::response("1\n00:00:00,000 --> 00:00:02,000\n{$text}\n", 200, ['Content-Type' => 'application/x-subrip'])
                    : Http::response($text, 200, ['Content-Type' => 'text/plain']);
            }

            return Http::response(['error' => 'not_found', 'message' => 'Unknown fake route '.$method.' '.$path], 404);
        });
    }
}
