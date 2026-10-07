<?php

namespace Tests\Feature;

use App\Actions\DraftTranslationsWithDeepL;
use App\Filament\Resources\Courses\Pages\EditCourse;
use App\Filament\Resources\Lessons\Pages\EditLesson;
use App\Models\Course;
use App\Models\Language;
use App\Models\User;
use Database\Seeders\LanguageSeeder;
use Database\Seeders\PilotQuickStartSeeder;
use Filament\Actions\Exceptions\ActionNotResolvableException;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Sleep;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * "Generate missing with DeepL" inside the Translate dialog: it fills empty
 * boxes for an editor to review, never writes over text, never saves, and is
 * there only for someone who was given the right while DeepL is connected.
 */
class DeepLDraftTranslationTest extends TestCase
{
    use RefreshDatabase;

    private int $translateCalls = 0;

    private bool $quotaOnSecondCall = false;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::clear();
        Sleep::fake();
        $this->seed(LanguageSeeder::class);
        $this->seed(PilotQuickStartSeeder::class);

        config([
            'services.deepl.enabled' => true,
            'services.deepl.key' => 'test-deepl-secret',
            'services.deepl.base_url' => 'https://api-free.deepl.com',
        ]);

        Http::fake(function (Request $request) {
            if (str_contains($request->url(), '/v3/languages')) {
                $features = ['tag_handling' => ['status' => 'stable']];

                return Http::response(collect(['en', 'ru', 'es', 'fr', 'ar', 'en-US', 'pt-BR', 'pt'])->map(fn (string $lang): array => [
                    'lang' => $lang, 'usable_as_source' => true, 'usable_as_target' => true, 'features' => $features,
                ])->all());
            }

            $this->translateCalls++;

            if ($this->quotaOnSecondCall && $this->translateCalls >= 2) {
                return Http::response(['message' => 'Quota exceeded', 'code' => 'quota_exceeded'], 456);
            }

            $body = $request->data();

            return Http::response(['translations' => collect($body['text'])->map(fn (string $text): array => [
                'text' => '['.$body['target_lang'].'] '.$text,
            ])->all()]);
        });
    }

    private function editor(bool $withRight = true): User
    {
        $admin = User::create([
            'name' => 'Pilot Admin',
            'email' => 'admin@pilot.local',
            'password' => 'password',
            'role' => User::ROLE_ADMIN,
        ]);

        if ($withRight) {
            $admin->permissions()->create(['permission' => User::PERMISSION_DEEPL_TRANSLATE]);
        }

        return $admin;
    }

    public function test_only_empty_boxes_are_drafted_and_filled_ones_are_left_alone(): void
    {
        $course = Course::first();
        $targets = Language::whereIn('code', ['fr', 'ru'])->orderBy('code')->get();

        $result = app(DraftTranslationsWithDeepL::class)->handle($course, $targets, [
            'fr' => ['title' => '', 'description' => null],
            'ru' => ['title' => 'Уже написано человеком', 'description' => ''],
        ]);

        $this->assertNull($result['error']);
        $this->assertSame('[fr] '.$course->title, $result['drafts']['fr']['title']);
        $this->assertArrayHasKey('description', $result['drafts']['fr']);
        $this->assertArrayNotHasKey('title', $result['drafts']['ru'], 'A box with text is never asked for again.');
        $this->assertArrayHasKey('description', $result['drafts']['ru']);
        $this->assertSame(0, $course->contentTranslations()->count(), 'Drafting stores nothing.');
    }

    public function test_html_goes_to_deepl_as_html_and_plain_text_does_not(): void
    {
        $lesson = Course::first()->lessons()->first();
        $lesson->update(['content' => '<p>Lesson <strong>text</strong>.</p>']);

        app(DraftTranslationsWithDeepL::class)->handle($lesson, Language::where('code', 'fr')->get(), []);

        Http::assertSent(fn (Request $request): bool => str_contains($request->url(), 'v2/translate')
            && ($request->data()['tag_handling'] ?? null) === 'html'
            && $request->data()['text'] === ['<p>Lesson <strong>text</strong>.</p>']);
        Http::assertSent(fn (Request $request): bool => str_contains($request->url(), 'v2/translate')
            && ! isset($request->data()['tag_handling'])
            && in_array($lesson->title, $request->data()['text'], true));
    }

    public function test_a_failure_keeps_the_drafts_already_made_and_reports_the_error(): void
    {
        $this->quotaOnSecondCall = true;
        $course = Course::first();

        $result = app(DraftTranslationsWithDeepL::class)->handle(
            $course,
            Language::whereIn('code', ['fr', 'ru'])->orderBy('code')->get(),
            [],
        );

        $this->assertTrue($result['error']->isQuotaProblem());
        $this->assertArrayHasKey('fr', $result['drafts']);
        $this->assertArrayNotHasKey('ru', $result['drafts']);
    }

    public function test_the_button_shows_for_someone_with_the_right(): void
    {
        Livewire::actingAs($this->editor())
            ->test(EditCourse::class, ['record' => Course::first()->getRouteKey()])
            ->mountAction('translateContent')
            ->assertActionVisible(TestAction::make('draftWithDeepL')->schemaComponent('deeplActions'));
    }

    public function test_the_button_is_hidden_without_the_right_even_for_an_admin(): void
    {
        // A hidden component's action cannot be resolved, so it cannot be called
        // by a hand-made Livewire request either.
        $this->expectException(ActionNotResolvableException::class);

        Livewire::actingAs($this->editor(false))
            ->test(EditCourse::class, ['record' => Course::first()->getRouteKey()])
            ->mountAction('translateContent')
            ->callAction(TestAction::make('draftWithDeepL')->schemaComponent('deeplActions'));
    }

    public function test_the_button_is_hidden_when_deepl_is_switched_off(): void
    {
        config(['services.deepl.enabled' => false]);

        $this->expectException(ActionNotResolvableException::class);

        Livewire::actingAs($this->editor())
            ->test(EditCourse::class, ['record' => Course::first()->getRouteKey()])
            ->mountAction('translateContent')
            ->callAction(TestAction::make('draftWithDeepL')->schemaComponent('deeplActions'));
    }

    public function test_generating_fills_the_dialog_but_saves_nothing(): void
    {
        $lesson = Course::first()->lessons()->first();

        Livewire::actingAs($this->editor())
            ->test(EditLesson::class, ['record' => $lesson->getRouteKey()])
            ->mountAction('translateContent')
            ->callAction(TestAction::make('draftWithDeepL')->schemaComponent('deeplActions'))
            ->assertHasNoActionErrors()
            ->assertSet('mountedActions.0.data.fr.title', '[fr] '.$lesson->title);

        $this->assertSame(0, $lesson->contentTranslations()->count());
        $this->assertGreaterThan(0, $this->translateCalls);
    }

    public function test_manual_translation_still_works_with_deepl_off(): void
    {
        config(['services.deepl.enabled' => false]);
        $course = Course::first();

        Livewire::actingAs($this->editor())
            ->test(EditCourse::class, ['record' => $course->getRouteKey()])
            ->callAction('translateContent', data: ['ru' => ['title' => 'Быстрый старт']])
            ->assertHasNoActionErrors();

        $this->assertSame('Быстрый старт', $course->fresh()->translated('title', 'ru'));
        $this->assertSame(0, $this->translateCalls);
    }
}
