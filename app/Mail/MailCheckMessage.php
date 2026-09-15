<?php

namespace App\Mail;

use App\Models\User;
use App\Services\Translator;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\App;

/**
 * The test email from Settings → Mail. Sent straight away, not queued: the
 * admin who pressed the button is waiting to hear whether it worked. It goes to
 * that admin, so it is in their language.
 */
class MailCheckMessage extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public User $sentBy)
    {
        $this->locale(app(Translator::class)->localeFor($sentBy));
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: __t('mail.mail_check.subject'));
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.mail-check',
            with: [
                'name' => $this->sentBy->name,
                'appUrl' => config('app.url'),
                'sentAt' => now()->locale(App::getLocale())->isoFormat('LLL'),
            ],
        );
    }
}
