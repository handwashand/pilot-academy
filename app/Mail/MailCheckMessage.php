<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * The test email from Settings → Mail. Sent straight away, not queued: the
 * admin who pressed the button is waiting to hear whether it worked.
 */
class MailCheckMessage extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public User $sentBy) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Pilot Academy: test email');
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.mail-check',
            with: [
                'name' => $this->sentBy->name,
                'appUrl' => config('app.url'),
                'sentAt' => now()->format('j M Y, H:i'),
            ],
        );
    }
}
