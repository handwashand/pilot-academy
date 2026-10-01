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

    public function test_without_the_permission_nobody_but_an_admin_gets_in(): void
    {
        $this->actingAs($this->user(User::ROLE_CREATOR))
            ->get(MailCheck::getUrl())
            ->assertForbidden();
    }

    /**
     * An admin can hand the page to a colleague — answering "did that
     * certificate email go out?" should not need running the whole academy.
     */
    public function test_the_permission_opens_it_for_someone_who_is_not_an_admin(): void
    {
        $creator = $this->user(User::ROLE_CREATOR);
        $creator->permissions()->create(['permission' => User::PERMISSION_MAIL_CHECK]);

        $this->actingAs($creator)
            ->get(MailCheck::getUrl())
            ->assertOk()
            // The question the page opens with.
            ->assertSee('Not getting emails?');

        // And it is in their sidebar, not just reachable by its address.
        $this->actingAs($creator)
            ->get('/admin')
            ->assertOk()
            ->assertSee(MailCheck::getUrl(), false);

        // Taking it away closes the page again.
        $creator->permissions()->where('permission', User::PERMISSION_MAIL_CHECK)->delete();

        $this->actingAs($creator)
            ->get(MailCheck::getUrl())
            ->assertForbidden();
    }

    public function test_an_admin_still_sees_it_in_the_sidebar(): void
    {
        $this->actingAs($this->user(User::ROLE_ADMIN))
            ->get('/admin')
            ->assertOk()
            ->assertSee(MailCheck::getUrl(), false);
    }
}
