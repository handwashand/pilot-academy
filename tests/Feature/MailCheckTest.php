<?php

namespace Tests\Feature;

use App\Filament\Pages\MailCheck;
use App\Mail\MailCheckMessage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;
use Tests\TestCase;

/** Settings → Mail: is the academy really sending certificate emails? */
class MailCheckTest extends TestCase
{
    use RefreshDatabase;

    private function user(string $role): User
    {
        return User::create(['name' => 'Test '.$role, 'email' => "{$role}@pilot.local", 'password' => 'password', 'role' => $role]);
    }

    public function test_it_says_plainly_when_emails_are_only_written_to_the_log(): void
    {
        config(['mail.default' => 'log']);

        $this->actingAs($this->user(User::ROLE_ADMIN))
            ->get(MailCheck::getUrl())
            ->assertOk()
            ->assertSee('Emails are not being delivered.');
    }

    public function test_it_shows_the_mail_server_when_one_is_set(): void
    {
        config([
            'mail.default' => 'smtp',
            'mail.mailers.smtp.host' => 'smtp.pilot-mail.example',
            'mail.mailers.smtp.port' => 587,
            'app.url' => 'https://academy.pilot-gps.com',
        ]);

        $this->actingAs($this->user(User::ROLE_ADMIN))
            ->get(MailCheck::getUrl())
            ->assertSee('The academy is set to send email.')
            ->assertSee('smtp.pilot-mail.example:587')
            ->assertDontSee('Not a public address');
    }

    public function test_a_test_email_goes_to_the_admin_who_asked(): void
    {
        Mail::fake();
        $admin = $this->user(User::ROLE_ADMIN);

        Livewire::actingAs($admin)
            ->test(MailCheck::class)
            ->callAction('sendTest');

        Mail::assertSent(MailCheckMessage::class, fn (MailCheckMessage $mail): bool => $mail->hasTo($admin->email));
    }

    public function test_only_admins_can_open_it(): void
    {
        $this->actingAs($this->user(User::ROLE_CREATOR))
            ->get(MailCheck::getUrl())
            ->assertForbidden();
    }
}
