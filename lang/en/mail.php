<?php

// Shipped with the code — see the note in lang/en/academy.php. A row in
// the translations table with the same key overrides a line.
//
// Emails and the certificate PDF, written in the recipient's language (see
// Translator::localeFor). Lines are Markdown: **bold** stays bold.

return [
    'common' => [
        'thanks' => 'Thanks,',
    ],

    'course_reminder' => [
        'subject' => 'Pick up where you left off - Pilot Academy',
        'heading' => 'Still with us, :name?',
        'intro' => 'You made a start on Pilot Academy, and there is not much left to pick up.',
        'intro_progress' => 'You made a start on Pilot Academy — :count lesson finished so far — and there is not much left to pick up.|You made a start on Pilot Academy — :count lessons finished so far — and there is not much left to pick up.',
        'button' => 'Continue where you left off',
        'personal' => 'That link signs you straight in and takes you to the next lesson, so there is no password to remember. It is personal to you — please don\'t forward it.',
    ],

    'certificate_issued' => [
        'subject' => 'Your :course certificate',
        'heading' => 'Congratulations, :name!',
        'passed' => 'You passed the final quiz for **:course** with a score of **:score%** and earned your certificate.',
        'attached' => 'Your certificate is attached to this email as a PDF. Its unique number is **:number**.',
        'button' => 'Verify certificate',
        'anyone' => 'Anyone can confirm this certificate is genuine at the link above.',
    ],

    'mail_check' => [
        'subject' => 'Pilot Academy: test email',
        'heading' => 'This is a test email',
        'sent_by' => ':name sent it from **Settings → Mail** in the Pilot Academy admin panel on :date.',
        'works' => 'If you are reading this, the academy can send email: certificates and reminders will reach students.',
        'links' => 'Links in academy emails start with :url. If that is not the address people use to open the academy, the logo and links in certificate emails will be broken.',
    ],

    'certificate_pdf' => [
        'title' => 'Certificate of Completion',
        'certifies' => 'This certifies that',
        'completed' => 'has successfully completed the course',
        'scan' => 'Scan to verify',
        'number' => 'Certificate No.',
        'issued' => 'Issued',
    ],
];
