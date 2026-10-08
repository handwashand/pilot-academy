<?php

namespace Tests\Feature;

use App\Filament\Clusters\Settings;
use App\Filament\Pages\Integrations;
use App\Filament\Pages\MailCheck;
use App\Filament\Pages\SettingsProfile;
use App\Filament\Resources\Languages\LanguageResource;
use App\Filament\Resources\Translations\TranslationResource;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Settings is one sidebar entry (App\Filament\Clusters\Settings) with a tab
 * strip across the top of Profile, Integrations, Mail, Translations and
 * Languages — Filament's own Cluster mechanism, in SubNavigationPosition::Top
 * mode — rather than five separate sidebar links or a modal.
 */
class SettingsClusterTest extends TestCase
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

    public function test_opening_settings_lands_on_profile(): void
    {
        $this->actingAs($this->user(User::ROLE_ADMIN))
            ->get(Settings::getUrl())
            ->assertRedirect(SettingsProfile::getUrl());
    }

    public function test_the_sidebar_has_one_settings_row(): void
    {
        $this->actingAs($this->user(User::ROLE_ADMIN))
            ->get('/admin')
            ->assertOk()
            ->assertSee(__t('admin_nav.settings.nav'))
            ->assertSee(Settings::getUrl(), false)
            ->assertDontSee(Integrations::getUrl(), false)
            ->assertDontSee(MailCheck::getUrl(), false)
            ->assertDontSee(TranslationResource::getUrl('index'), false)
            ->assertDontSee(LanguageResource::getUrl('index'), false);
    }

    public function test_an_admin_sees_every_tab_but_languages_needs_its_own_permission(): void
    {
        $response = $this->actingAs($this->user(User::ROLE_ADMIN))
            ->get(SettingsProfile::getUrl())
            ->assertOk()
            ->assertSee(__t('admin_settings.overlay.profile'))
            ->assertSee(__t('admin_nav.integrations.nav'))
            ->assertSee(__t('admin_nav.mail.nav'))
            ->assertSee(__t('admin.translations'));

        $response->assertDontSee(__t('admin.languages'));
    }

    public function test_a_creator_with_the_languages_permission_sees_that_tab_too(): void
    {
        $creator = $this->user(User::ROLE_CREATOR, User::PERMISSION_LANGUAGES_MANAGE);

        $this->actingAs($creator)
            ->get(SettingsProfile::getUrl())
            ->assertOk()
            ->assertSee(__t('admin.languages'))
            // No admin, so Integrations stays off the tab strip.
            ->assertDontSee(__t('admin_nav.integrations.nav'));
    }

    public function test_saving_the_profile_tab_updates_the_signed_in_user(): void
    {
        $admin = $this->user(User::ROLE_ADMIN);

        Livewire::actingAs($admin)
            ->test(SettingsProfile::class)
            ->fillForm(['name' => 'New Name', 'email' => $admin->email])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame('New Name', $admin->fresh()->name);
    }

    public function test_a_wrong_current_password_blocks_a_new_one(): void
    {
        $admin = $this->user(User::ROLE_ADMIN);

        Livewire::actingAs($admin)
            ->test(SettingsProfile::class)
            ->fillForm([
                'name' => $admin->name,
                'email' => $admin->email,
                'password' => 'a-new-long-password',
                'passwordConfirmation' => 'a-new-long-password',
                'currentPassword' => 'not-my-password',
            ])
            ->call('save')
            ->assertHasFormErrors(['currentPassword']);

        $this->assertTrue(Hash::check('password', $admin->fresh()->password));
    }

    public function test_a_non_admin_still_cannot_open_integrations_directly(): void
    {
        $this->actingAs($this->user(User::ROLE_CREATOR))
            ->get(Integrations::getUrl())
            ->assertForbidden();
    }
}
