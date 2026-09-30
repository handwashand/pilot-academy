<?php

namespace App\Mail;

use App\Models\User;
use App\Services\Translator;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/**
 * "Forgot password?" — the one-use link, written in the language the person
 * chose, like every other email the academy sends.
 */
class PasswordResetLink extends Mailable
{
    public function __construct(
        public User $user,
        public string $resetUrl,
        public int $expiresInMinutes,
    ) {
        $this->locale(app(Translator::class)->localeFor($user));
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: __t('mail.password_reset.subject'));
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.password-reset',
            with: [
                'name' => $this->user->name,
                'url' => $this->resetUrl,
                'minutes' => $this->expiresInMinutes,
            ],
        );
    }
}
