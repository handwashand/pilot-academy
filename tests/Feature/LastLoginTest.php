<?php

namespace Tests\Feature;

use App\Models\ActivityEvent;
use App\Models\User;
use Filament\Auth\Pages\Login;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Last login is stamped by every sign-in, once: the admin panel's own login
 * page too, which went through no controller of ours and left admins and
 * creators with no Last login at all (2026-11-25).
 */
class LastLoginTest extends TestCase
{
    use RefreshDatabase;

    private function person(string $role): User
    {
        return User::create(['name' => 'Ana', 'email' => "{$role}@pilot.local", 'password' => 'secret-pass-123', 'role' => $role]);
    }

    public function test_signing_in_to_the_admin_panel_stamps_last_login(): void
    {
        $admin = $this->person(User::ROLE_ADMIN);
        $this->assertNull($admin->last_login_at);

        Livewire::test(Login::class)
            ->fillForm(['email' => $admin->email, 'password' => 'secret-pass-123'])
            ->call('authenticate')
            ->assertHasNoFormErrors();

        $this->assertNotNull($admin->fresh()->last_login_at);
        $this->assertSame(1, ActivityEvent::where('user_id', $admin->id)->where('type', ActivityEvent::TYPE_LOGIN)->count());
    }

    public function test_signing_in_on_the_student_site_stamps_it_once(): void
    {
        $learner = $this->person(User::ROLE_LEARNER);

        $this->post(route('login'), ['email' => $learner->email, 'password' => 'secret-pass-123'])->assertRedirect();

        $this->assertNotNull($learner->fresh()->last_login_at);
        $this->assertSame(1, ActivityEvent::where('user_id', $learner->id)->where('type', ActivityEvent::TYPE_LOGIN)->count());
    }
}
