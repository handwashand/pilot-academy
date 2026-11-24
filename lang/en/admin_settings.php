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
        'subheading' => 'The standard certificate every course uses. Upload a background to replace the built-in design; a course with its own background keeps it. Remove the upload to go back to the built-in design.',
        'background' => 'Standard certificate background',
        'background_help' => 'Full-page A4 landscape background (≈3508×2480 px). Leave empty to use the built-in framed layout. Name, course, date, number and QR are overlaid automatically.',
        'signatory' => 'Signatory',
        'signatory_help' => 'Printed on every certificate. Leave the name empty to print no signature block.',
        'signatory_name' => 'Signatory name',
        'signatory_title' => 'Title',
        'signatory_title_placeholder' => 'Head of Training, Pilot Telematics',
        'signature' => 'Signature image',
        'signature_help' => 'A PNG with a transparent background, about 600×200 px. Optional.',
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
