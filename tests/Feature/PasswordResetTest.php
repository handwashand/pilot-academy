<?php

namespace Tests\Feature;

use App\Mail\PasswordResetLink;
use App\Models\User;
use Database\Seeders\LanguageSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * "Forgot password?", from both login pages.
 *
 * The page must never say whether an address has an account: otherwise anyone
 * could type addresses one at a time and learn which partners are customers.
 */
class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // throttle:6,1 counts in the cache, which outlives RefreshDatabase —
        // without this, one throttling test would lock out the rest.
        Cache::flush();
    }

    private function user(array $overrides = []): User
    {
        return User::create([
            'name' => 'Ana Pereira',
            'email' => 'ana@partner.test',
            'password' => 'old-password',
            'role' => User::ROLE_LEARNER,
            ...$overrides,
        ]);
    }

    public function test_both_login_pages_offer_it(): void
    {
        $this->get(route('login'))
            ->assertOk()
            ->assertSee('Forgot password?')
            ->assertSee(route('password.request'), false);

        $this->get('/admin/login')
            ->assertOk()
            ->assertSee(route('password.request'), false);
    }

    public function test_a_link_is_emailed_and_sets_a_new_password(): void
    {
        Mail::fake();
        $user = $this->user();

        $this->post(route('password.email'), ['email' => $user->email])
            ->assertSessionHas('status');

        $token = null;
        Mail::assertSent(PasswordResetLink::class, function (PasswordResetLink $mail) use ($user, &$token): bool {
            $token = $mail->resetUrl;

            return $mail->hasTo($user->email);
        });

        $this->assertNotNull($token, 'No reset link was emailed.');

        // Follow the link the same way the person would.
        preg_match('#/reset-password/([^?]+)#', $token, $found);
        $reset = $found[1] ?? '';

        $this->get(route('password.reset', ['token' => $reset, 'email' => $user->email]))
            ->assertOk()
            ->assertSee('Set a new password');

        $this->post(route('password.update'), [
            'token' => $reset,
            'email' => $user->email,
            'password' => 'a-brand-new-one',
            'password_confirmation' => 'a-brand-new-one',
        ])->assertRedirect(route('login'));

        $this->assertTrue(Hash::check('a-brand-new-one', $user->fresh()->password));
        $this->assertNotNull($user->fresh()->password_set_at);

        // One use only: the same link cannot be played again.
        $this->post(route('password.update'), [
            'token' => $reset,
            'email' => $user->email,
            'password' => 'another-attempt',
            'password_confirmation' => 'another-attempt',
        ])->assertSessionHasErrors('email');

        $this->assertTrue(Hash::check('a-brand-new-one', $user->fresh()->password));
    }

    /** The whole point: an unknown address must look exactly like a known one. */
    public function test_an_unknown_address_gets_the_same_answer_and_no_email(): void
    {
        Mail::fake();
        $this->user();

        $known = $this->post(route('password.email'), ['email' => 'ana@partner.test']);
        $unknown = $this->post(route('password.email'), ['email' => 'nobody@partner.test']);

        $this->assertSame($known->getSession()->get('status'), $unknown->getSession()->get('status'));
        $unknown->assertSessionHasNoErrors();

        Mail::assertSent(PasswordResetLink::class, 1);
    }

    public function test_a_tampered_or_expired_link_says_only_that_it_is_no_longer_valid(): void
    {
        $user = $this->user();

        $this->post(route('password.update'), [
            'token' => 'not-a-real-token',
            'email' => $user->email,
            'password' => 'a-brand-new-one',
            'password_confirmation' => 'a-brand-new-one',
        ])->assertSessionHasErrors(['email' => 'That link is no longer valid — they last an hour and work once. Ask for a new one.']);

        $this->assertTrue(Hash::check('old-password', $user->fresh()->password));
    }

    public function test_the_email_is_written_in_the_persons_language(): void
    {
        $this->seed(LanguageSeeder::class);
        // The array mailer, not a fake: the language is applied as the mailer
        // builds the message, so only a real build proves it.
        config(['mail.default' => 'array']);

        $user = $this->user();
        $user->forceFill(['locale' => 'ru'])->save();

        $this->post(route('password.email'), ['email' => $user->email]);

        $email = Mail::getSymfonyTransport()->messages()->last()->getOriginalMessage();

        $this->assertStringContainsString('Новый пароль', $email->getSubject());
        $this->assertStringContainsString('Установить новый пароль', $email->getHtmlBody());
        $this->assertStringContainsString('Ссылка действует', $email->getHtmlBody());
    }

    public function test_asking_over_and_over_is_throttled(): void
    {
        Mail::fake();
        $user = $this->user();

        for ($attempt = 0; $attempt < 6; $attempt++) {
            $this->post(route('password.email'), ['email' => $user->email]);
        }

        $this->post(route('password.email'), ['email' => $user->email])
            ->assertStatus(429);
    }

    public function test_an_admin_can_use_it_too(): void
    {
        Mail::fake();
        $admin = $this->user(['email' => 'admin@pilot.local', 'role' => User::ROLE_ADMIN]);

        $this->post(route('password.email'), ['email' => $admin->email])
            ->assertSessionHas('status');

        Mail::assertSent(PasswordResetLink::class, fn (PasswordResetLink $mail): bool => $mail->hasTo($admin->email));
    }
}
