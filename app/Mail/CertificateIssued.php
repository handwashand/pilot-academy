<?php

namespace App\Mail;

use App\Models\Certificate;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;

class CertificateIssued extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Certificate $certificate)
    {
        $this->certificate->loadMissing('user', 'course');
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Your '.$this->certificate->course->title.' certificate',
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.certificate-issued',
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
}
