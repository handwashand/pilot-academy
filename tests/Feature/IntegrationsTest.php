<?php

namespace Tests\Feature;

use App\Filament\Pages\Integrations;
use App\Filament\Resources\Courses\Pages\EditCourse;
use App\Models\AiProvider;
use App\Models\Course;
use App\Models\Product;
use App\Models\User;
use App\Services\Descript\DescriptClient;
use Database\Seeders\LanguageSeeder;
use Database\Seeders\PilotQuickStartSeeder;
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

    public function test_a_deepl_key_saved_here_is_used_on_the_host_its_key_belongs_to(): void
    {
        config(['services.deepl.enabled' => false, 'services.deepl.key' => null]);

        Livewire::actingAs($this->admin())
            ->test(Integrations::class)
            ->fillForm(['deepl' => ['enabled' => true, 'api_key' => 'abc-123:fx']])
            ->call('save');

        $this->assertSame('abc-123:fx', AiProvider::for('deepl')->api_key);

        Livewire::actingAs($this->admin(User::PERMISSION_DEEPL_TRANSLATE))
            ->test(EditCourse::class, ['record' => Course::first()->getRouteKey()])
            ->callAction('translateWithDeepL')
            ->assertHasNoActionErrors()
            ->assertSet('mountedActions.0.data.fr.title', '[deepl] '.Course::first()->title);

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
            ->test(EditCourse::class, ['record' => Course::first()->getRouteKey()])
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

        Livewire::actingAs($this->admin())
            ->test(Integrations::class)
            ->fillForm(['deepseek' => ['enabled' => false, 'clear_key' => true]])
            ->call('save');

        $this->assertNull(AiProvider::for('deepseek')->api_key);
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

    public function test_the_token_is_not_serialised(): void
    {
        $this->enable('chatgpt');

        $this->assertStringNotContainsString('sk-secret-token', AiProvider::for('chatgpt')->toJson());
    }

    public function test_chatgpt_drafts_the_dialog_with_its_own_address_and_token(): void
    {
        $this->enable('chatgpt');
        $course = Course::first();

        Livewire::actingAs($this->admin(User::PERMISSION_AI_TRANSLATE))
            ->test(EditCourse::class, ['record' => $course->getRouteKey()])
            ->callAction('translateWithChatgpt')
            ->assertHasNoActionErrors()
            ->assertSet('mountedActions.0.data.fr.title', '[llm] '.$course->title);

        Http::assertSent(fn (Request $request): bool => $request->url() === 'https://api.openai.com/v1/chat/completions'
            && $request->hasHeader('Authorization', 'Bearer sk-secret-token')
            && $request->data()['model'] === 'gpt-4o-mini');
        $this->assertSame(0, $course->contentTranslations()->count(), 'Drafting stores nothing.');
    }

    public function test_deepseek_uses_its_own_address(): void
    {
        $this->enable('deepseek', 'ds-token');

        Livewire::actingAs($this->admin(User::PERMISSION_AI_TRANSLATE))
            ->test(EditCourse::class, ['record' => Course::first()->getRouteKey()])
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
            ->test(EditCourse::class, ['record' => Course::first()->getRouteKey()])
            ->assertActionDoesNotExist('translateWithChatgpt');
    }

    public function test_an_admin_needs_no_extra_right_for_a_text_engine(): void
    {
        $this->enable('deepseek');

        Livewire::actingAs($this->admin())
            ->test(EditCourse::class, ['record' => Course::first()->getRouteKey()])
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
        $course = Course::first();
        $course->setTranslation('title', 'fr', 'Écrit par une personne');

        // Default: kept, and offered for editing.
        Livewire::actingAs($this->admin())
            ->test(EditCourse::class, ['record' => $course->getRouteKey()])
            ->callAction('translateWithChatgpt')
            ->assertSet('mountedActions.0.data.fr.title', 'Écrit par une personne');

        // Chosen: a new draft replaces it in the window — still unsaved.
        Livewire::actingAs($this->admin())
            ->test(EditCourse::class, ['record' => $course->getRouteKey()])
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
