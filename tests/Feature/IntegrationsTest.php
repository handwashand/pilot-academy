<?php

namespace Tests\Feature;

use App\Filament\Pages\Integrations;
use App\Filament\Resources\Lessons\Pages\EditLesson;
use App\Models\AiProvider;
use App\Models\Course;
use App\Models\Product;
use App\Models\User;
use App\Services\Descript\DescriptClient;
use Database\Seeders\LanguageSeeder;
use Database\Seeders\PilotQuickStartSeeder;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Settings → Integrations, and the ChatGPT / DeepSeek drafts it switches on in the
 * Translate dialog.
 */
class IntegrationsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(LanguageSeeder::class);
        $this->seed(PilotQuickStartSeeder::class);

        Http::fake(function (Request $request) {
            if (str_contains($request->url(), 'deepl.com')) {
                if (str_contains($request->url(), '/v3/languages')) {
                    $features = ['tag_handling' => ['status' => 'stable']];

                    return Http::response(collect(['en', 'ru', 'es', 'fr', 'ar', 'en-US', 'pt-BR', 'pt'])->map(fn (string $lang): array => [
                        'lang' => $lang, 'usable_as_source' => true, 'usable_as_target' => true, 'features' => $features,
                    ])->all());
                }

                return Http::response(['translations' => collect($request->data()['text'])->map(fn (string $text): array => [
                    'text' => '[deepl] '.$text,
                ])->all()]);
            }

            // Claude's Messages API: the text is the one user message, the
            // answer a text block after a (empty) thinking block.
            if (str_contains($request->url(), 'anthropic.com')) {
                $texts = json_decode($request->data()['messages'][0]['content'], true);

                return Http::response([
                    'stop_reason' => 'end_turn',
                    'content' => [
                        ['type' => 'thinking', 'thinking' => ''],
                        ['type' => 'text', 'text' => json_encode(collect($texts)->map(fn (string $text): string => '[claude] '.$text)->all(), JSON_UNESCAPED_UNICODE)],
                    ],
                ]);
            }

            $texts = json_decode($request->data()['messages'][1]['content'], true);

            return Http::response(['choices' => [['message' => ['content' => json_encode(
                collect($texts)->map(fn (string $text): string => '[llm] '.$text)->all(),
                JSON_UNESCAPED_UNICODE,
            )]]]]);
        });
    }

    private function admin(string ...$rights): User
    {
        $admin = User::firstOrCreate(['email' => 'admin@pilot.local'], [
            'name' => 'Pilot Admin',
            'password' => 'password',
            'role' => User::ROLE_ADMIN,
        ]);

        foreach ($rights as $right) {
            $admin->permissions()->firstOrCreate(['permission' => $right]);
        }

        return $admin;
    }

    private function enable(string $provider, string $key = 'sk-secret-token'): void
    {
        $row = AiProvider::for($provider);
        $row->fill(['enabled' => true, 'api_key' => $key])->save();
    }

    public function test_only_admins_can_open_integrations(): void
    {
        $creator = User::create([
            'name' => 'Creator',
            'email' => 'creator@pilot.local',
            'password' => 'password',
            'role' => User::ROLE_CREATOR,
        ]);

        $this->actingAs($creator)->get(Integrations::getUrl())->assertForbidden();
    }

    public function test_an_admin_can_open_integrations(): void
    {
        $this->actingAs($this->admin())->get(Integrations::getUrl())->assertOk();
    }

    public function test_each_card_says_whether_it_is_on_and_reads_env_for_descript(): void
    {
        config(['services.deepl.enabled' => false, 'services.deepl.key' => null]);
        config(['services.descript.enabled' => true, 'services.descript.token' => 'from-env']);
        AiProvider::for('chatgpt')->fill(['enabled' => true, 'api_key' => 'sk-on'])->save();
        AiProvider::for('deepseek')->fill(['enabled' => false, 'api_key' => 'sk-off'])->save();

        $page = Livewire::actingAs($this->admin())->test(Integrations::class)->html();

        $status = fn (string $provider): string => preg_match('/'.$provider.'\s*<\/h3>.*?<p[^>]*fi-section-header-description[^>]*>\s*([^<]+?)\s*</s', $page, $m) ? $m[1] : '';

        $this->assertSame('On', $status('ChatGPT'));
        $this->assertSame('Off — token saved', $status('DeepSeek'));
        $this->assertSame('Not connected', $status('DeepL'));
        // Switched on in the server's .env, with nothing saved here: still on.
        $this->assertSame('On', $status('Descript'));
    }

    public function test_a_deepl_key_saved_here_is_used_on_the_host_its_key_belongs_to(): void
    {
        config(['services.deepl.enabled' => false, 'services.deepl.key' => null]);

        Livewire::actingAs($this->admin())
            ->test(Integrations::class)
            ->fillForm(['deepl' => ['enabled' => true, 'api_key' => 'abc-123:fx']])
            ->call('save');

        $this->assertSame('abc-123:fx', AiProvider::for('deepl')->api_key);

        Livewire::actingAs($this->admin(User::PERMISSION_DEEPL_TRANSLATE))
            ->test(EditLesson::class, ['record' => Course::first()->lessons()->first()->getRouteKey()])
            ->callAction('translateWithDeepL')
            ->assertHasNoActionErrors()
            ->assertSet('mountedActions.0.data.fr.title', '[deepl] '.Course::first()->lessons()->first()->title);

        Http::assertSent(fn (Request $request): bool => str_starts_with($request->url(), 'https://api-free.deepl.com/v2/translate')
            && $request->hasHeader('Authorization', 'DeepL-Auth-Key abc-123:fx'));
    }

    public function test_a_descript_token_saved_here_connects_descript_and_its_switch_decides(): void
    {
        config(['services.descript.enabled' => false, 'services.descript.token' => null]);
        $this->assertFalse((new DescriptClient)->enabled(), 'Nothing saved and nothing in .env: off.');

        Livewire::actingAs($this->admin())
            ->test(Integrations::class)
            ->fillForm(['descript' => ['enabled' => true, 'api_key' => 'dsc-secret']])
            ->call('save');

        $this->assertSame('dsc-secret', AiProvider::for('descript')->api_key);
        $this->assertTrue((new DescriptClient)->enabled(), 'A saved, enabled token connects Descript.');

        AiProvider::for('descript')->update(['enabled' => false]);
        config(['services.descript.enabled' => true, 'services.descript.token' => 'env-token']);

        $this->assertFalse((new DescriptClient)->enabled(), 'Once saved, the page’s switch beats .env.');
    }

    public function test_descript_still_works_from_the_servers_env_when_nothing_is_saved(): void
    {
        config(['services.descript.enabled' => true, 'services.descript.token' => 'env-token']);

        $this->assertTrue((new DescriptClient)->enabled());
    }

    public function test_deepl_still_works_from_the_servers_env_when_nothing_is_saved(): void
    {
        config([
            'services.deepl.enabled' => true,
            'services.deepl.key' => 'env-key',
            'services.deepl.base_url' => 'https://api.deepl.com',
        ]);

        Livewire::actingAs($this->admin(User::PERMISSION_DEEPL_TRANSLATE))
            ->test(EditLesson::class, ['record' => Course::first()->lessons()->first()->getRouteKey()])
            ->callAction('translateWithDeepL')
            ->assertHasNoActionErrors();

        Http::assertSent(fn (Request $request): bool => $request->hasHeader('Authorization', 'DeepL-Auth-Key env-key'));
    }

    public function test_a_token_is_stored_encrypted_and_never_shown_again(): void
    {
        Livewire::actingAs($this->admin())
            ->test(Integrations::class)
            ->fillForm(['chatgpt' => ['enabled' => true, 'api_key' => 'sk-secret-token', 'model' => 'gpt-4o']])
            ->call('save')
            ->assertHasNoFormErrors()
            ->assertSet('data.chatgpt.api_key', '')
            ->assertDontSee('sk-secret-token');

        $raw = DB::table('ai_providers')->where('provider', 'chatgpt')->value('api_key');
        $this->assertNotSame('sk-secret-token', $raw);
        $this->assertSame('sk-secret-token', AiProvider::for('chatgpt')->api_key);
        $this->assertSame('gpt-4o', AiProvider::for('chatgpt')->modelName());
    }

    public function test_a_blank_token_keeps_the_saved_one_and_remove_clears_it(): void
    {
        $this->enable('deepseek');

        Livewire::actingAs($this->admin())
            ->test(Integrations::class)
            ->fillForm(['deepseek' => ['enabled' => true, 'api_key' => '']])
            ->call('save');

        $this->assertSame('sk-secret-token', AiProvider::for('deepseek')->api_key);

        // Remove, on the line under the token box, takes it away at once.
        Livewire::actingAs($this->admin())
            ->test(Integrations::class)
            ->assertSee(__t('admin_integrations.token_saved', ['ending' => 'oken']), false)
            ->callAction(TestAction::make('remove_shared')->schemaComponent('form.deepseek.api_key'));

        $this->assertNull(AiProvider::for('deepseek')->api_key);
    }

    public function test_claude_drafts_with_its_own_api_and_a_schema_for_the_answer(): void
    {
        $this->enable('claude', 'sk-ant-token');
        $lesson = Course::first()->lessons()->first();

        Livewire::actingAs($this->admin(User::PERMISSION_AI_TRANSLATE))
            ->test(EditLesson::class, ['record' => $lesson->getRouteKey()])
            ->callAction('translateWithClaude')
            ->assertHasNoActionErrors()
            ->assertSet('mountedActions.0.data.fr.title', '[claude] '.$lesson->title);

        Http::assertSent(function (Request $request): bool {
            $body = $request->data();
            $keys = array_keys(json_decode($body['messages'][0]['content'], true));

            return $request->url() === 'https://api.anthropic.com/v1/messages'
                && $request->hasHeader('x-api-key', 'sk-ant-token')
                && $request->hasHeader('anthropic-version', '2026-10-21')
                && ! $request->hasHeader('Authorization')
                // The middle model by default, with no temperature.
                && $body['model'] === 'claude-sonnet-5-5'
                && ! array_key_exists('temperature', $body)
                && $body['output_config']['format']['type'] === 'json_schema'
                && $body['output_config']['format']['schema']['required'] === $keys
                && $body['output_config']['format']['schema']['additionalProperties'] === false;
        });
        $this->assertSame(0, $lesson->contentTranslations()->count(), 'Drafting stores nothing.');
    }

    public function test_a_declined_claude_request_stores_nothing_and_says_so(): void
    {
        $this->enable('claude', 'sk-ant-token');
        Http::fake(['*' => Http::response(['stop_reason' => 'refusal', 'content' => []])]);
        $lesson = Course::first()->lessons()->first();

        Livewire::actingAs($this->admin())
            ->test(EditLesson::class, ['record' => $lesson->getRouteKey()])
            ->callAction('translateWithClaude')
            ->assertSet('mountedActions.0.data.fr.title', '');

        $this->assertSame(0, $lesson->contentTranslations()->count());
    }

    public function test_every_ai_provider_is_offered_wherever_translate_is(): void
    {
        foreach (['chatgpt', 'deepseek', 'claude'] as $provider) {
            $this->enable($provider);
        }

        Livewire::actingAs($this->admin())
            ->test(EditLesson::class, ['record' => Course::first()->lessons()->first()->getRouteKey()])
            ->assertActionVisible('translateWithChatgpt')
            ->assertActionVisible('translateWithDeepseek')
            ->assertActionVisible('translateWithClaude');
    }

    public function test_a_dearer_model_than_the_default_says_how_much_more_it_costs(): void
    {
        $this->assertNull(AiProvider::costMultiple('claude', null));
        $this->assertNull(AiProvider::costMultiple('claude', 'claude-sonnet-5-5'));
        $this->assertNull(AiProvider::costMultiple('claude', 'claude-haiku-5-5'));
        $this->assertSame(2, AiProvider::costMultiple('claude', 'claude-opus-5-5'));
        $this->assertSame(4, AiProvider::costMultiple('claude', 'claude-fable-5-2'));
        $this->assertSame(4, AiProvider::costMultiple('chatgpt', 'gpt-6.2-astra'));
        $this->assertNull(AiProvider::costMultiple('chatgpt', 'gpt-4o-mini'));

        $warning = __t('admin_integrations.model_warning', ['model' => 'Claude Opus 5.5', 'times' => 2, 'default' => 'Claude Sonnet 5.5']);

        Livewire::actingAs($this->admin())
            ->test(Integrations::class)
            ->assertDontSee($warning)
            ->fillForm(['claude' => ['model' => 'claude-opus-5-5']])
            ->assertSee($warning)
            ->fillForm(['claude' => ['model' => 'claude-haiku-5-5']])
            ->assertDontSee($warning);
    }

    public function test_a_provider_cannot_be_enabled_without_a_token(): void
    {
        Livewire::actingAs($this->admin())
            ->test(Integrations::class)
            ->fillForm(['chatgpt' => ['enabled' => true, 'api_key' => '']])
            ->call('save');

        $this->assertNull(AiProvider::usable('chatgpt'));
        $this->assertFalse((bool) AiProvider::for('chatgpt')->enabled);
    }

    public function test_typed_tokens_leave_the_page_when_a_save_is_refused(): void
    {
        Livewire::actingAs($this->admin())
            ->test(Integrations::class)
            ->fillForm([
                'deepl' => ['enabled' => true, 'api_key' => 'deepl-typed-secret'],
                'chatgpt' => ['enabled' => true, 'api_key' => ''],
                'deepseek' => ['enabled' => true, 'api_key' => 'ds-typed-secret'],
            ])
            ->call('save')
            ->assertSet('data.deepl.api_key', '')
            ->assertSet('data.deepseek.api_key', '')
            ->assertDontSee('deepl-typed-secret')
            ->assertDontSee('ds-typed-secret');

        // DeepL came before the refused card, so it was saved.
        $this->assertSame('deepl-typed-secret', AiProvider::for('deepl')->api_key);
    }

    public function test_translating_with_a_reasoning_model_sends_no_temperature(): void
    {
        $this->enable('chatgpt', 'sk-secret-token');
        AiProvider::for('chatgpt')->update(['model' => 'gpt-6.2-sol']);

        Livewire::actingAs($this->admin(User::PERMISSION_AI_TRANSLATE))
            ->test(EditLesson::class, ['record' => Course::first()->lessons()->first()->getRouteKey()])
            ->callAction('translateWithChatgpt')
            ->assertHasNoActionErrors();

        Http::assertSent(fn (Request $request): bool => $request->data()['model'] === 'gpt-6.2-sol'
            && $request->data()['reasoning_effort'] === 'low'
            && ! array_key_exists('temperature', $request->data()));
    }

    public function test_the_token_is_not_serialised(): void
    {
        $this->enable('chatgpt');

        $this->assertStringNotContainsString('sk-secret-token', AiProvider::for('chatgpt')->toJson());
    }

    public function test_chatgpt_drafts_the_dialog_with_its_own_address_and_token(): void
    {
        $this->enable('chatgpt');
        $course = Course::first()->lessons()->first();

        Livewire::actingAs($this->admin(User::PERMISSION_AI_TRANSLATE))
            ->test(EditLesson::class, ['record' => $course->getRouteKey()])
            ->callAction('translateWithChatgpt')
            ->assertHasNoActionErrors()
            ->assertSet('mountedActions.0.data.fr.title', '[llm] '.$course->title);

        Http::assertSent(fn (Request $request): bool => $request->url() === 'https://api.openai.com/v1/chat/completions'
            && $request->hasHeader('Authorization', 'Bearer sk-secret-token')
            && $request->data()['model'] === 'gpt-4o');
        $this->assertSame(0, $course->contentTranslations()->count(), 'Drafting stores nothing.');
    }

    public function test_deepseek_uses_its_own_address(): void
    {
        $this->enable('deepseek', 'ds-token');

        Livewire::actingAs($this->admin(User::PERMISSION_AI_TRANSLATE))
            ->test(EditLesson::class, ['record' => Course::first()->lessons()->first()->getRouteKey()])
            ->callAction('translateWithDeepseek')
            ->assertHasNoActionErrors();

        Http::assertSent(fn (Request $request): bool => $request->url() === 'https://api.deepseek.com/chat/completions'
            && $request->hasHeader('Authorization', 'Bearer ds-token'));
        Http::assertNotSent(fn (Request $request): bool => str_contains($request->url(), 'openai.com'));
    }

    public function test_the_buttons_need_an_enabled_provider_and_the_right(): void
    {
        // Enabled, but this creator was not given the right. (An admin needs none.)
        $this->enable('chatgpt');
        $creator = User::create(['name' => 'Creator', 'email' => 'creator@pilot.local', 'password' => 'password', 'role' => User::ROLE_CREATOR]);
        $product = Product::create(['name' => 'Pilot', 'slug' => 'pilot']);
        Course::first()->update(['product_id' => $product->id]);
        $creator->products()->attach($product);

        Livewire::actingAs($creator)
            ->test(EditLesson::class, ['record' => Course::first()->lessons()->first()->getRouteKey()])
            ->assertActionDoesNotExist('translateWithChatgpt');
    }

    public function test_an_admin_needs_no_extra_right_for_a_text_engine(): void
    {
        $this->enable('deepseek');

        Livewire::actingAs($this->admin())
            ->test(EditLesson::class, ['record' => Course::first()->lessons()->first()->getRouteKey()])
            ->assertActionVisible('translateWithDeepseek');
    }

    public function test_a_token_saved_under_another_app_key_reads_as_missing_instead_of_breaking_the_page(): void
    {
        $this->enable('deepseek');
        // What a rebuilt server with a new APP_KEY finds in the column.
        DB::table('ai_providers')->where('provider', 'deepseek')->update(['api_key' => 'not-decryptable']);

        $this->assertNull(AiProvider::for('deepseek')->api_key);
        $this->assertNull(AiProvider::usable('deepseek'));
        $this->actingAs($this->admin())->get(Integrations::getUrl())->assertOk();
    }

    public function test_existing_translations_are_kept_unless_the_editor_chooses_to_replace_them(): void
    {
        $this->enable('chatgpt');
        $course = Course::first()->lessons()->first();
        $course->setTranslation('title', 'fr', 'Écrit par une personne');

        // Default: kept, and offered for editing.
        Livewire::actingAs($this->admin())
            ->test(EditLesson::class, ['record' => $course->getRouteKey()])
            ->callAction('translateWithChatgpt')
            ->assertSet('mountedActions.0.data.fr.title', 'Écrit par une personne');

        // Chosen: a new draft replaces it in the window — still unsaved.
        Livewire::actingAs($this->admin())
            ->test(EditLesson::class, ['record' => $course->getRouteKey()])
            ->callAction('translateWithChatgpt', data: ['overwrite' => true])
            ->assertSet('mountedActions.0.data.fr.title', '[llm] '.$course->title);

        $this->assertSame('Écrit par une personne', $course->fresh()->translated('title', 'fr'), 'Nothing is replaced until Save translations.');
    }

    public function test_a_switched_off_provider_sends_nothing(): void
    {
        $this->enable('chatgpt');
        AiProvider::for('chatgpt')->update(['enabled' => false]);

        $this->assertNull(AiProvider::usable('chatgpt'));
        Http::assertNothingSent();
    }
}
