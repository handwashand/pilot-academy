<?php

namespace Tests\Feature;

use App\Models\User;
use Filament\Auth\Pages\EditProfile;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\TestCase;

class ProfilePageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Filament::setCurrentPanel(Filament::getPanel('admin'));
    }

    // One user per test: AuthenticateSession pins the session to the first
    // user's password hash, so switching users mid-test reads as a hijack.
    public function test_an_admin_can_open_their_profile(): void
    {
        $this->actingAs($this->user('admin@pilot.local', 'admin'))
            ->get('/admin/profile')
            ->assertStatus(200)
            ->assertSee('Test admin');
    }

    public function test_a_creator_can_open_their_profile(): void
    {
        $this->actingAs($this->user('creator@pilot.local', 'creator'))
            ->get('/admin/profile')
            ->assertStatus(200);
    }

    public function test_students_cannot_open_the_panel_profile(): void
    {
        $this->actingAs($this->user('student@example.com', 'learner'))
            ->get('/admin/profile')
            ->assertStatus(403);
    }

    /**
     * Submitted, not just rendered: a form can show at 200 and fail on save.
     * Creators have no Users access, so this page is their only way to change
     * a password — it has to actually work.
     */
    public function test_a_creator_can_change_their_own_password(): void
    {
        $creator = $this->user('creator@pilot.local', 'creator');

        Livewire::actingAs($creator)
            ->test(EditProfile::class)
            ->fillForm([
                'password' => 'a-new-long-password',
                'passwordConfirmation' => 'a-new-long-password',
                'currentPassword' => 'password',
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertTrue(Hash::check('a-new-long-password', $creator->fresh()->password));
    }

    public function test_the_current_password_is_required_to_set_a_new_one(): void
    {
        $creator = $this->user('creator@pilot.local', 'creator');

        Livewire::actingAs($creator)
            ->test(EditProfile::class)
            ->fillForm([
                'password' => 'a-new-long-password',
                'passwordConfirmation' => 'a-new-long-password',
                'currentPassword' => 'not-my-password',
            ])
            ->call('save')
            ->assertHasFormErrors(['currentPassword']);

        $this->assertTrue(Hash::check('password', $creator->fresh()->password));
    }

    public function test_saving_a_name_with_a_blank_password_keeps_the_old_password(): void
    {
        $admin = $this->user('admin@pilot.local', 'admin');

        Livewire::actingAs($admin)
            ->test(EditProfile::class)
            ->fillForm(['name' => 'Renamed Admin', 'password' => '', 'passwordConfirmation' => ''])
            ->call('save')
            ->assertHasNoFormErrors();

        $admin->refresh();
        $this->assertSame('Renamed Admin', $admin->name);
        $this->assertTrue(Hash::check('password', $admin->password));
    }

    private function user(string $email, string $role): User
    {
        return User::create([
            'name' => 'Test '.$role,
            'email' => $email,
            'password' => bcrypt('password'),
            'role' => $role,
        ]);
    }
}
