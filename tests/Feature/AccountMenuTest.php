<?php

namespace Tests\Feature;

use App\Filament\Pages\AdminGuide;
use App\Models\User;
use Database\Seeders\LanguageSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The account menu in the student site's header, the logo beside it, and the
 * Guide item in the admin panel's own account menu.
 */
class AccountMenuTest extends TestCase
{
    use RefreshDatabase;

    private function user(string $role): User
    {
        return User::create([
            'name' => 'Ana Pereira',
            'email' => "{$role}@partner.com",
            'password' => 'secret123',
            'role' => $role,
        ]);
    }

    public function test_a_signed_in_student_gets_an_account_menu(): void
    {
        $this->actingAs($this->user(User::ROLE_LEARNER))
            ->get(route('academy.home'))
            ->assertStatus(200)
            ->assertSee('data-account-menu', false)
            // Who you are signed in as.
            ->assertSee('Ana Pereira')
            ->assertSee('learner@partner.com')
            ->assertSeeInOrder([
                'data-account-menu',
                'href="'.route('academy.profile').'"',
                'href="'.route('certificates.index').'"',
            ], false)
            ->assertSee('action="'.route('logout').'"', false)
            // Students have no panel to go to.
            ->assertDontSee('href="'.url('/admin').'"', false);
    }

    public function test_only_admins_get_a_link_to_the_admin_panel(): void
    {
        $this->actingAs($this->user(User::ROLE_ADMIN))
            ->get(route('academy.home'))
            ->assertSee('href="'.url('/admin').'"', false);

        $this->actingAs($this->user(User::ROLE_CREATOR))
            ->get(route('academy.home'))
            ->assertDontSee('href="'.url('/admin').'"', false);
    }

    public function test_a_guest_has_no_account_menu(): void
    {
        $this->get(route('academy.home'))
            ->assertDontSee('<details class="relative flex-none" data-account-menu>', false)
            ->assertSee('href="'.route('login').'"', false);
    }

    public function test_the_header_uses_the_same_logo_as_the_admin_panel(): void
    {
        $this->get(route('academy.home'))
            ->assertSee('img/pilot-logo.png', false)
            ->assertSee('alt="Pilot Academy"', false);
    }

    public function test_the_admin_account_menu_links_to_the_guide(): void
    {
        $this->actingAs($this->user(User::ROLE_ADMIN));

        $panel = Filament::getPanel('admin');
        Filament::setCurrentPanel($panel);

        $items = collect($panel->getUserMenuItems());

        $this->assertTrue($items->has('guide'), 'The admin account menu should have a Guide item.');
        $this->assertSame(AdminGuide::getUrl(), $items->get('guide')->getUrl());
    }

    public function test_the_admin_account_menu_links_to_the_student_site(): void
    {
        $this->actingAs($this->user(User::ROLE_CREATOR));

        $panel = Filament::getPanel('admin');
        Filament::setCurrentPanel($panel);

        $items = collect($panel->getUserMenuItems());

        // Filament keys menu items by action name.
        $this->assertTrue($items->has('studentSite'), 'The admin account menu should link to the student site.');
        $this->assertSame(route('academy.home'), $items->get('studentSite')->getUrl());
    }

    public function test_the_admin_top_bar_has_a_compact_language_button(): void
    {
        $this->seed(LanguageSeeder::class);
        $admin = $this->user(User::ROLE_ADMIN);

        $this->actingAs($admin)
            ->get('/admin')
            ->assertOk()
            ->assertSee('data-language-switcher', false)
            // A button with the current code, not a <select> of every name.
            ->assertDontSee('<select id="locale-switcher"', false)
            ->assertSee('action="'.route('locale.switch').'"', false)
            ->assertSee('Русский')
            ->assertSee('Português (Brasil)');

        // Choosing a language from the panel returns to the panel, in it.
        $this->from('/admin')
            ->post(route('locale.switch'), ['locale' => 'fr'])
            ->assertRedirect('/admin');

        $this->assertSame('fr', $admin->fresh()->locale);
    }

    public function test_one_language_shows_no_language_button(): void
    {
        $this->actingAs($this->user(User::ROLE_ADMIN))
            ->get('/admin')
            ->assertOk()
            ->assertDontSee('data-language-switcher', false);
    }
}
