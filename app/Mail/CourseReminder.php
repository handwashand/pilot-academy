<?php

namespace App\Mail;

use App\Models\User;
use App\Services\Translator;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * A nudge for a student who started a course and then stopped.
 *
 * The link is their personal access link, which signs them in and lands them on
 * the academy home page — where "Continue where you left off" already offers the
 * next unfinished lesson. Nothing about which lesson that is needs working out
 * here.
 *
 * Written in the student's language, not the admin's who pressed Send.
 */
class CourseReminder extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public User $student)
    {
        $this->locale(app(Translator::class)->localeFor($student));
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __t('mail.course_reminder.subject'),
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.course-reminder',
            with: [
                'name' => $this->student->name,
                // Creates the token on first use, so a student invited long ago
                // still gets a working link.
                'url' => $this->student->accessUrl(),
                'lessonsDone' => $this->student->completedLessons()->count(),
            ],
        );
    }
}
