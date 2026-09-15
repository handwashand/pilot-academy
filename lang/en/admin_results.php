<?php

/*
 * The Results screens: certificates, quiz attempts and student feedback. Keys
 * mirror in every language (see StudentSiteTranslationTest).
 */

return [
    'certificates' => [
        'export' => 'Export CSV',
        'resend' => 'Resend email',
        'resend_description' => 'Email the certificate to :email.',
        'emailed' => 'Certificate emailed',
        'regenerate' => 'Regenerate PDF',
        'regenerated' => 'PDF regenerated',
        'edit_name' => 'Edit name',
        'edit_name_heading' => 'Correct the name on this certificate',
        'edit_name_description' => 'The PDF is reprinted with the new name, and the public verification page shows it straight away. The number, date and score stay the same. Use Resend email afterwards if the student should get the corrected copy.',
        'name_on_certificate' => 'Name on the certificate',
        'update_profile' => 'Also use this name on the student\'s future certificates',
        'update_profile_help' => 'Saves it as the certificate name on their profile, where they can change it too.',
        'name_corrected' => 'Name corrected and PDF reprinted',
        'revoke' => 'Revoke',
        'revoke_description' => 'The certificate will show as revoked on the public verification page. Certificates are permanent — use this only for an incorrect issue.',
        'revoked' => 'Certificate revoked',
        'restore' => 'Restore',
        'restored' => 'Certificate restored',
        'score_percent' => 'Score %',
    ],

    'attempts' => [
        'quiz' => 'Quiz',
        'not_submitted' => 'Not submitted',
        'out_of_attempts' => 'Out of attempts, not passed',
        'quiz_type' => 'Quiz type',
        'final_quizzes' => 'Final quizzes',
        'lesson_checks' => 'Lesson knowledge checks',
        'grant' => 'Grant another attempt',
        'grant_description' => ':name gets one more attempt at :quiz. Nobody else is affected: Max attempts stays as it is for everyone else.',
        'reason' => 'Reason (optional)',
        'reason_placeholder' => 'For example: the connection dropped during the quiz',
        'granted' => 'Another attempt granted',
        'granted_body' => ':name can try :quiz once more.',
        'empty' => 'No quiz attempts yet',
        'empty_description' => 'Attempts are recorded for final quizzes, and for lesson knowledge checks that have a time limit or a number of attempts.',
        'final_quiz' => 'the final quiz',
        'a_lesson' => 'a lesson',
    ],

    'feedback' => [
        'empty_description' => 'Students are asked what they thought once they finish a course.',
    ],
];
