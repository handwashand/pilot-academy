<?php

namespace Tests\Feature;

use App\Filament\Pages\Integrations;
use App\Filament\Pages\MailCheck;
use App\Filament\Resources\Languages\LanguageResource;
use App\Filament\Resources\Translations\TranslationResource;
use App\Livewire\SettingsPanel;
use App\Models\AiProvider;
use App\Models\User;
use Database\Seeders\LanguageSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * The Settings overlay: one sidebar row ("Settings → General" in the guide)
 * opens a dialog with Profile, Integrations, Mail, Translations and Languages
 * down the side, instead of four separate sidebar links to four separate
 * pages. Profile and Integrations save together from here; Mail,
 * Translations and Languages are a summary with a button to their own page
 * (see App\Livewire\SettingsPanel for why).
 */
class SettingsPanelTest extends TestCase
{
    use RefreshDatabase;

    private function user(string $role, string ...$rights): User
    {
        $user = User::create([
            'name' => ucfirst($role),
            'email' => "{$role}@pilot.local",
            'password' => 'password',
            'role' => $role,
        ]);

        foreach ($rights as $right) {
            $user->permissions()->create(['permission' => $right]);
        }

        return $user;
    }

    public function test_the_sidebar_has_one_settings_row_that_is_not_a_link(): void
    {
        $this->actingAs($this->user(User::ROLE_ADMIN))
            ->get('/admin')
            ->assertOk()
            ->assertSee(__t('admin_nav.settings.nav'))
            // Reached by the overlay only — the old separate pages' routes
            // are no longer advertised in the sidebar HTML.
            ->assertDontSee(Integrations::getUrl(), false)
            ->assertDontSee(MailCheck::getUrl(), false)
            ->assertDontSee(TranslationResource::getUrl('index'), false);
    }

    public function test_an_admin_can_open_it_and_sees_their_own_profile_prefilled(): void
    {
        $admin = $this->user(User::ROLE_ADMIN);

        Livewire::actingAs($admin)
            ->test(SettingsPanel::class)
            ->mountAction('settings')
            ->assertActionDataSet([
                'profile' => [
                    'name' => $admin->name,
                    'email' => $admin->email,
                    'password' => null,
                    'passwordConfirmation' => null,
                    'currentPassword' => null,
                ],
            ]);
    }

    public function test_saving_the_profile_tab_updates_the_signed_in_user(): void
    {
        $admin = $this->user(User::ROLE_ADMIN);

        Livewire::actingAs($admin)
            ->test(SettingsPanel::class)
            ->callAction('settings', data: [
                'profile' => ['name' => 'New Name', 'email' => $admin->email],
            ])
            ->assertHasNoActionErrors();

        $this->assertSame('New Name', $admin->fresh()->name);
    }

    public function test_an_admin_can_save_integrations_from_the_overlay(): void
    {
        $admin = $this->user(User::ROLE_ADMIN);

        Livewire::actingAs($admin)
            ->test(SettingsPanel::class)
            ->callAction('settings', data: [
                'profile' => ['name' => $admin->name, 'email' => $admin->email],
                'integrations' => ['chatgpt' => ['enabled' => true, 'api_key' => 'sk-secret-token']],
            ])
            ->assertHasNoActionErrors();

        $this->assertSame('sk-secret-token', AiProvider::for('chatgpt')->api_key);

        $raw = DB::table('ai_providers')->where('provider', 'chatgpt')->value('api_key');
        $this->assertNotSame('sk-secret-token', $raw, 'Stored encrypted, same as the standalone Integrations page.');
    }

    public function test_a_creator_with_no_rights_can_still_save_their_own_profile(): void
    {
        $creator = $this->user(User::ROLE_CREATOR);

        Livewire::actingAs($creator)
            ->test(SettingsPanel::class)
            ->callAction('settings', data: [
                'profile' => ['name' => 'Creator Renamed', 'email' => $creator->email],
            ])
            ->assertHasNoActionErrors();

        $this->assertSame('Creator Renamed', $creator->fresh()->name);
        $this->assertNull(AiProvider::for('chatgpt')->api_key, 'A creator has no Integrations tab, so nothing there was ever submitted.');
    }

    public function test_the_standalone_pages_are_unaffected_by_moving_behind_the_overlay(): void
    {
        $this->seed(LanguageSeeder::class);
        $admin = $this->user(User::ROLE_ADMIN, User::PERMISSION_TRANSLATIONS_MANAGE, User::PERMISSION_LANGUAGES_MANAGE);

        $this->actingAs($admin)->get(Integrations::getUrl())->assertOk();
        $this->actingAs($admin)->get(MailCheck::getUrl())->assertOk();
        $this->actingAs($admin)->get(TranslationResource::getUrl('index'))->assertOk();
        $this->actingAs($admin)->get(LanguageResource::getUrl('index'))->assertOk();

        $this->assertFalse(Integrations::shouldRegisterNavigation());
        $this->assertFalse(MailCheck::shouldRegisterNavigation());
        $this->assertFalse(TranslationResource::shouldRegisterNavigation());
        $this->assertFalse(LanguageResource::shouldRegisterNavigation());
    }
}
