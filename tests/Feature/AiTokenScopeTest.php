<?php

namespace Tests\Feature;

use App\Filament\Pages\Integrations;
use App\Filament\Resources\Lessons\Pages\EditLesson;
use App\Models\AiProvider;
use App\Models\Course;
use App\Models\User;
use Database\Seeders\LanguageSeeder;
use Database\Seeders\PilotQuickStartSeeder;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Who may use a token saved under Settings → Integrations: just the admin who
 * added it, all admins, or everyone with the right. A person's own token is
 * used before the shared one.
 */
class AiTokenScopeTest extends TestCase
{
    use RefreshDatabase;

    private function person(string $email, string $role, string ...$rights): User
    {
        $user = User::create(['name' => ucfirst(strtok($email, '@')), 'email' => $email, 'password' => 'password', 'role' => $role]);

        foreach ($rights as $right) {
            $user->permissions()->create(['permission' => $right]);
        }

        return $user;
    }

    private function shared(string $provider, string $token, string $scope, bool $enabled = true): AiProvider
    {
        $row = AiProvider::for($provider);
        $row->fill(['enabled' => $enabled, 'api_key' => $token, 'scope' => $scope, 'model' => null])->save();

        return $row;
    }

    public function test_an_existing_shared_token_still_reaches_everyone_with_the_right(): void
    {
        AiProvider::for('chatgpt')->fill(['enabled' => true, 'api_key' => 'sk-shared'])->save();
        $creator = $this->person('creator@pilot.local', User::ROLE_CREATOR, User::PERMISSION_AI_TRANSLATE);

        $this->assertSame(AiProvider::SCOPE_EVERYONE, AiProvider::for('chatgpt')->fresh()->scope);
        $this->assertSame('sk-shared', AiProvider::usable('chatgpt', $creator)?->api_key);
    }

    public function test_a_token_for_all_admins_is_not_used_for_a_creator(): void
    {
        $this->shared('chatgpt', 'sk-admins', AiProvider::SCOPE_ADMINS);
        $admin = $this->person('admin@pilot.local', User::ROLE_ADMIN);
        $creator = $this->person('creator@pilot.local', User::ROLE_CREATOR, User::PERMISSION_AI_TRANSLATE);

        $this->assertSame('sk-admins', AiProvider::usable('chatgpt', $admin)?->api_key);
        $this->assertNull(AiProvider::usable('chatgpt', $creator));
    }

    public function test_a_personal_token_is_used_only_by_its_owner_and_before_the_shared_one(): void
    {
        $this->shared('chatgpt', 'sk-shared', AiProvider::SCOPE_EVERYONE)->update(['model' => 'gpt-4o']);
        $owner = $this->person('owner@pilot.local', User::ROLE_ADMIN);
        $other = $this->person('other@pilot.local', User::ROLE_ADMIN);
        AiProvider::personalFor('chatgpt', $owner)->fill(['api_key' => 'sk-mine', 'added_by' => $owner->id])->save();

        $mine = AiProvider::usable('chatgpt', $owner);

        $this->assertSame('sk-mine', $mine?->api_key);
        // The provider's model and switch apply to a personal token too.
        $this->assertSame('gpt-4o', $mine->modelName());
        $this->assertSame('sk-shared', AiProvider::usable('chatgpt', $other)?->api_key);
    }

    public function test_a_personal_token_follows_the_providers_switch(): void
    {
        $owner = $this->person('owner@pilot.local', User::ROLE_ADMIN);
        AiProvider::for('chatgpt')->fill(['enabled' => false])->save();
        AiProvider::personalFor('chatgpt', $owner)->fill(['api_key' => 'sk-mine'])->save();

        $this->assertNull(AiProvider::usable('chatgpt', $owner));

        AiProvider::for('chatgpt')->update(['enabled' => true]);

        $this->assertSame('sk-mine', AiProvider::usable('chatgpt', $owner)?->api_key);
    }

    public function test_saving_a_token_just_for_me_keeps_it_out_of_the_shared_row(): void
    {
        $owner = $this->person('owner@pilot.local', User::ROLE_ADMIN);

        Livewire::actingAs($owner)
            ->test(Integrations::class)
            ->fillForm(['chatgpt' => ['enabled' => true, 'api_key' => 'sk-personal-1234', 'scope' => AiProvider::SCOPE_PERSONAL]])
            ->call('save')
            ->assertHasNoFormErrors()
            ->assertSet('data.chatgpt.api_key', '')
            ->assertDontSee('sk-personal-1234');

        $this->assertNull(AiProvider::for('chatgpt')->api_key);
        $this->assertTrue((bool) AiProvider::for('chatgpt')->enabled);
        $this->assertSame('sk-personal-1234', AiProvider::personalFor('chatgpt', $owner)->api_key);

        // Its owner sees the last four characters, never the token.
        Livewire::actingAs($owner)->test(Integrations::class)
            ->assertSee(__t('admin_integrations.own_line', ['ending' => '1234']))
            ->assertDontSee('sk-personal-1234');

        // Another admin sees nothing of it.
        Livewire::actingAs($this->person('other@pilot.local', User::ROLE_ADMIN))->test(Integrations::class)
            ->assertDontSee(__t('admin_integrations.own_line', ['ending' => '1234']));
    }

    public function test_a_shared_token_records_its_reach_and_who_added_it(): void
    {
        $admin = $this->person('admin@pilot.local', User::ROLE_ADMIN);

        Livewire::actingAs($admin)
            ->test(Integrations::class)
            ->fillForm(['deepseek' => ['enabled' => true, 'api_key' => 'ds-shared-9876', 'scope' => AiProvider::SCOPE_ADMINS]])
            ->call('save')
            ->assertHasNoFormErrors();

        $row = AiProvider::for('deepseek');
        $this->assertSame(AiProvider::SCOPE_ADMINS, $row->scope);
        $this->assertSame($admin->id, $row->added_by);

        // The token box hints at its ending; the line under it says who can use it.
        Livewire::actingAs($admin)->test(Integrations::class)
            ->assertSee(__t('admin_integrations.token_saved', ['ending' => '9876']), false)
            ->assertSee(__t('admin_integrations.shared_line', [
                'scope' => __t('admin_integrations.scopes.admins'),
                'name' => $admin->name,
            ]))
            ->assertSchemaStateSet(['deepseek.scope' => AiProvider::SCOPE_ADMINS])
            // With the box empty, Who can use it changes the saved token's reach.
            ->fillForm(['deepseek' => ['scope' => AiProvider::SCOPE_EVERYONE]])
            ->call('save');

        $this->assertSame(AiProvider::SCOPE_EVERYONE, AiProvider::for('deepseek')->scope);
        $this->assertSame('ds-shared-9876', AiProvider::for('deepseek')->api_key);
    }

    public function test_switching_on_with_only_your_own_token_is_allowed_and_removing_it_works(): void
    {
        $owner = $this->person('owner@pilot.local', User::ROLE_ADMIN);
        AiProvider::personalFor('deepseek', $owner)->fill(['api_key' => 'ds-mine'])->save();

        Livewire::actingAs($owner)->test(Integrations::class)
            ->fillForm(['deepseek' => ['enabled' => true]])
            ->call('save');

        $this->assertTrue((bool) AiProvider::for('deepseek')->enabled);

        Livewire::actingAs($owner)->test(Integrations::class)
            ->callAction(TestAction::make('remove_own')->schemaComponent());

        $this->assertNull(AiProvider::personalFor('deepseek', $owner)->api_key);
    }

    public function test_descript_has_no_personal_tokens(): void
    {
        $admin = $this->person('admin@pilot.local', User::ROLE_ADMIN);

        // "Just me" is not offered, and a forged one is refused.
        Livewire::actingAs($admin)->test(Integrations::class)
            ->fillForm(['descript' => ['enabled' => true, 'api_key' => 'dsc-token', 'scope' => AiProvider::SCOPE_PERSONAL]])
            ->call('save')
            ->assertHasFormErrors(['descript.scope']);

        $this->assertNull(AiProvider::for('descript')->api_key);
        $this->assertSame(0, AiProvider::query()->whereNotNull('user_id')->count());

        Livewire::actingAs($admin)->test(Integrations::class)
            ->fillForm(['descript' => ['enabled' => true, 'api_key' => 'dsc-token', 'scope' => AiProvider::SCOPE_ADMINS]])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame(AiProvider::SCOPE_ADMINS, AiProvider::for('descript')->scope);
    }

    public function test_deleting_an_account_removes_its_own_tokens_but_not_the_shared_one_it_added(): void
    {
        $admin = $this->person('admin@pilot.local', User::ROLE_ADMIN);
        $this->shared('chatgpt', 'sk-shared', AiProvider::SCOPE_EVERYONE)->update(['added_by' => $admin->id]);
        AiProvider::personalFor('deepseek', $admin)->fill(['api_key' => 'ds-mine'])->save();

        $admin->delete();

        $this->assertSame('sk-shared', AiProvider::for('chatgpt')->api_key);
        $this->assertNull(AiProvider::for('chatgpt')->added_by);
        $this->assertSame(0, AiProvider::query()->whereNotNull('user_id')->count());
    }

    public function test_translate_drafts_with_the_admins_own_token(): void
    {
        $this->seed(LanguageSeeder::class);
        $this->seed(PilotQuickStartSeeder::class);
        Http::fake(fn (Request $request) => Http::response(['choices' => [['message' => ['content' => json_encode(
            collect(json_decode($request->data()['messages'][1]['content'], true))->map(fn (string $text): string => '[llm] '.$text)->all(),
        )]]]]));

        $owner = $this->person('owner@pilot.local', User::ROLE_ADMIN);
        $this->shared('chatgpt', 'sk-shared', AiProvider::SCOPE_EVERYONE);
        AiProvider::personalFor('chatgpt', $owner)->fill(['api_key' => 'sk-mine'])->save();

        Livewire::actingAs($owner)
            ->test(EditLesson::class, ['record' => Course::first()->lessons()->first()->getRouteKey()])
            ->callAction('translateWithChatgpt')
            ->assertHasNoActionErrors();

        Http::assertSent(fn (Request $request): bool => $request->hasHeader('Authorization', 'Bearer sk-mine'));
        Http::assertNotSent(fn (Request $request): bool => $request->hasHeader('Authorization', 'Bearer sk-shared'));
    }
}
