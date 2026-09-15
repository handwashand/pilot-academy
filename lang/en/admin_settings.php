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
        'states' => [
            'corrected' => 'Corrected',
            'shipped' => 'Shipped',
            'missing' => 'Missing',
        ],
    ],
];
