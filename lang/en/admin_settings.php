<?php

/*
 * Settings: languages and translations. Keys mirror in every language (see
 * StudentSiteTranslationTest).
 */

return [
    'languages' => [
        'code' => 'ISO 639-1 code',
        'native_name' => 'Native name',
        'direction' => 'Direction',
        'position' => 'Position',
        'active' => 'Active',
        'default' => 'Default language',
        'default_help' => 'Changing this moves the fallback language for everyone.',
        'language' => 'Language',
        'code_column' => 'Code',
        'default_column' => 'Default',
        'coverage' => 'Coverage',
        'use' => 'Use this language',
    ],

    'translations' => [
        'key' => 'Key',
        'language' => 'Language',
        'english' => 'English',
        'shipped' => 'Shipped text',
        'shipped_help' => 'What students see in this language when the box below is empty.',
        'none_shipped' => '— none shipped —',
        'correction' => 'Correction',
        'correction_help' => 'Leave empty to use the shipped text. Keep words that start with a colon, such as :name, exactly as they are, and keep the | between the forms of a count ("1 lesson|2 lessons").',
        'notes' => 'Notes',
        'text' => 'Text students see',
        'missing' => 'missing',
        'module' => 'Module',
        'corrected' => 'Corrected',
        'correct' => 'Correct',
        'correct_in' => 'Correct the :language line',
        'click_hint' => 'One line per key, in every language. Click any cell to correct that language — students see the change straight away.',
        'save_correction' => 'Save the correction',
        'states' => [
            'corrected' => 'Corrected',
            'shipped' => 'Shipped',
            'missing' => 'Missing',
        ],
    ],

    // Settings → Certificate (App\Filament\Pages\SettingsCertificate).
    'certificate' => [
        'nav' => 'Certificate',
        'subheading' => 'The standard certificate every course uses: its background, its words and an optional signature. A course with its own background keeps it.',
        'background' => 'Standard certificate background',
        'background_help' => 'Full-page A4 landscape background (≈3508×2480 px). Leave empty to use the built-in framed layout. Name, course, date, number and QR are overlaid automatically.',
        'background_section' => 'Background',
        'text_section' => 'Certificate text',
        'text_help' => 'Type the words yourself, per language; each student gets theirs. An empty box uses the standard text shown in it. Click a placeholder to copy it, then paste it where you want it.',
        'lines' => [
            'issuer' => 'Top line',
            'title' => 'Title',
            'certifies' => 'Line above the name',
            'achievement_course' => 'Achievement',
            'issued_by' => 'Bottom line',
        ],
        'placeholders' => 'Placeholders',
        'placeholder' => [
            'learner' => 'The student’s name. It is always printed large in the middle too.',
            'course' => 'The course title, in the student’s language',
            'lessons' => 'How many lessons the course has, e.g. “8 lessons”',
            'score' => 'The final quiz score, with its % sign',
            'date' => 'The day it was issued',
            'number' => 'The certificate number',
            'verify_link' => 'The link that checks the certificate. It is also printed beside the QR code.',
            'partner' => 'The student’s partner company',
            'product' => 'The course’s product',
            'signature' => 'The signature image and the signer’s name',
            'signature_title' => 'The signer’s title',
        ],
        'reset' => 'Reset to the standard text',
        'reset_heading' => 'Reset the certificate text?',
        'reset_description' => 'Every language goes back to the standard text. Certificates already issued keep their PDF until someone presses Regenerate PDF.',
        'reset_done' => 'Certificate text reset',
        'signature_section' => 'Signature (optional)',
        'signature_help' => 'Not on the certificate until a line uses :signature or :signature_title.',
        'signature_name' => 'Signer’s name',
        'signature_title' => 'Title',
        'signature_image' => 'Signature image',
        'signature_image_help' => 'A PNG with a transparent background works best.',
        'preview' => 'Preview',
        'preview_help' => 'With sample details, in your panel language, as you type. Certificates already issued keep their PDF until someone presses Regenerate PDF.',
        'sample_name' => 'Sample Student',
        'sample_course' => 'Sample course',
    ],

    // Settings → Profile (App\Filament\Pages\SettingsProfile), one of the
    // tabs in the Settings cluster.
    'overlay' => [
        'profile' => 'Profile',
        'photo' => 'Photo',
        'name' => 'Name',
        'email' => 'Email address',
        'password' => 'New password',
        'password_confirmation' => 'Confirm password',
        'current_password' => 'Current password',
        'current_password_help' => 'Needed to change your email or password.',
        'save' => 'Save settings',
        'saved' => 'Settings saved',
    ],
];
