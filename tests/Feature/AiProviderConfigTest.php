<?php

namespace Tests\Feature;

use App\Filament\Pages\Configs;
use App\Filament\Resources\Courses\Pages\EditCourse;
use App\Models\AiProvider;
use App\Models\Course;
use App\Models\User;
use Database\Seeders\LanguageSeeder;
use Database\Seeders\PilotQuickStartSeeder;
use Filament\Actions\Exceptions\ActionNotResolvableException;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Settings → Configs, and the ChatGPT / DeepSeek drafts it switches on in the
 * Translate dialog.
 */
class AiProviderConfigTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(LanguageSeeder::class);
        $this->seed(PilotQuickStartSeeder::class);

        Http::fake(function (Request $request) {
            $texts = json_decode($request->data()['messages'][1]['content'], true);

            return Http::response(['choices' => [['message' => ['content' => json_encode(
                collect($texts)->map(fn (string $text): string => '[llm] '.$text)->all(),
                JSON_UNESCAPED_UNICODE,
            )]]]]);
        });
    }

    private function admin(bool $withRight = false): User
    {
        $admin = User::create([
            'name' => 'Pilot Admin',
            'email' => 'admin@pilot.local',
            'password' => 'password',
            'role' => User::ROLE_ADMIN,
        ]);

        if ($withRight) {
            $admin->permissions()->create(['permission' => User::PERMISSION_AI_TRANSLATE]);
        }

        return $admin;
    }

    private function enable(string $provider, string $key = 'sk-secret-token'): void
    {
        $row = AiProvider::for($provider);
        $row->fill(['enabled' => true, 'api_key' => $key])->save();
    }

    public function test_only_admins_can_open_configs(): void
    {
        $creator = User::create([
            'name' => 'Creator',
            'email' => 'creator@pilot.local',
            'password' => 'password',
            'role' => User::ROLE_CREATOR,
        ]);

        $this->actingAs($creator)->get('/admin/configs')->assertForbidden();
        $this->actingAs($this->admin())->get('/admin/configs')->assertOk();
    }

    public function test_a_token_is_stored_encrypted_and_never_shown_again(): void
    {
        Livewire::actingAs($this->admin())
            ->test(Configs::class)
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
            ->test(Configs::class)
            ->fillForm(['deepseek' => ['enabled' => true, 'api_key' => '']])
            ->call('save');

        $this->assertSame('sk-secret-token', AiProvider::for('deepseek')->api_key);

        Livewire::actingAs($this->admin())
            ->test(Configs::class)
            ->fillForm(['deepseek' => ['enabled' => false, 'clear_key' => true]])
            ->call('save');

        $this->assertNull(AiProvider::for('deepseek')->api_key);
    }

    public function test_a_provider_cannot_be_enabled_without_a_token(): void
    {
        Livewire::actingAs($this->admin())
            ->test(Configs::class)
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

        Livewire::actingAs($this->admin(true))
            ->test(EditCourse::class, ['record' => $course->getRouteKey()])
            ->mountAction('translateContent')
            ->callAction(TestAction::make('draftWithChatgpt')->schemaComponent('draftActions'))
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

        Livewire::actingAs($this->admin(true))
            ->test(EditCourse::class, ['record' => Course::first()->getRouteKey()])
            ->mountAction('translateContent')
            ->callAction(TestAction::make('draftWithDeepseek')->schemaComponent('draftActions'))
            ->assertHasNoActionErrors();

        Http::assertSent(fn (Request $request): bool => $request->url() === 'https://api.deepseek.com/chat/completions'
            && $request->hasHeader('Authorization', 'Bearer ds-token'));
        Http::assertNotSent(fn (Request $request): bool => str_contains($request->url(), 'openai.com'));
    }

    public function test_the_buttons_need_an_enabled_provider_and_the_right(): void
    {
        // Enabled, but this admin was not given the right.
        $this->enable('chatgpt');
        $this->expectException(ActionNotResolvableException::class);

        Livewire::actingAs($this->admin(false))
            ->test(EditCourse::class, ['record' => Course::first()->getRouteKey()])
            ->mountAction('translateContent')
            ->callAction(TestAction::make('draftWithChatgpt')->schemaComponent('draftActions'));
    }

    public function test_a_switched_off_provider_sends_nothing(): void
    {
        $this->enable('chatgpt');
        AiProvider::for('chatgpt')->update(['enabled' => false]);

        $this->assertNull(AiProvider::usable('chatgpt'));
        Http::assertNothingSent();
    }
}
