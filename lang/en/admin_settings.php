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

    // The Settings overlay (App\Livewire\SettingsPanel): Profile and
    // Integrations save together from here; Mail, Translations and
    // Languages are summarised with a button to their own page.
    'overlay' => [
        'profile' => 'Profile',
        'name' => 'Name',
        'email' => 'Email address',
        'password' => 'New password',
        'password_confirmation' => 'Confirm password',
        'current_password' => 'Current password',
        'current_password_help' => 'Needed to change your email or password.',
        'save' => 'Save settings',
        'saved' => 'Settings saved',
        'mail_description' => 'Whether the academy is really sending email, and a way to test it.',
        'translations_description' => 'Correct the wording students see, in every language, without a deploy.',
        'languages_description' => 'Add a language, or change which ones are offered.',
        'open' => 'Open :page',
    ],
];
