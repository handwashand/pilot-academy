<?php

namespace App\Mail;

use App\Models\Certificate;
use App\Services\Translator;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;

/**
 * The certificate, emailed to the student in their own language — whoever
 * issued or resent it. Laravel renders the whole message, subject included,
 * inside the locale set here.
 */
class CertificateIssued extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Certificate $certificate)
    {
        $this->certificate->loadMissing('user', 'course');

        $this->locale(app(Translator::class)->localeFor($this->certificate->user));
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __t('mail.certificate_issued.subject', ['course' => $this->courseTitle()]),
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.certificate-issued',
            with: [
                'courseTitle' => $this->courseTitle(),
            ],
        );
    }

    /**
     * @return array<int, Attachment>
     */
    public function attachments(): array
    {
        if (! $this->certificate->pdf_path || ! Storage::disk('public')->exists($this->certificate->pdf_path)) {
            return [];
        }

        return [
            Attachment::fromStorageDisk('public', $this->certificate->pdf_path)
                ->as('certificate-'.$this->certificate->number.'.pdf')
                ->withMime('application/pdf'),
        ];
    }

    /** The course title in the student's language, where one has been written. */
    private function courseTitle(): string
    {
        return (string) $this->certificate->course->translated('title', $this->locale);
    }
}
