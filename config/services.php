<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    /*
     * Descript translates the speech in uploaded lesson videos. Off until it is
     * switched on and a token is set. See docs/descript-integration.md.
     */
    'descript' => [
        'enabled' => (bool) env('DESCRIPT_ENABLED', false),
        'token' => env('DESCRIPT_API_TOKEN'),
        'base_url' => env('DESCRIPT_API_BASE_URL', 'https://descriptapi.com/v1'),
        // Every project the academy creates goes in here; nested with "/".
        'project_folder' => env('DESCRIPT_PROJECT_FOLDER', 'Pilot Academy/Transcriptions'),
        'timeout' => (int) env('DESCRIPT_TIMEOUT_SECONDS', 30),
        // Descript has no translate endpoint: translation is an instruction to
        // its AI editor. {language} is the target's English name, {name} the
        // composition to create — the app finds the result by that name.
        'translate_prompt' => env(
            'DESCRIPT_TRANSLATE_PROMPT',
            'Translate the captions of this composition into {language}. Create the translation as a new composition named "{name}". Do not change the original composition.',
        ),
    ],

    /*
     * DeepL drafts translations for editor review inside the existing
     * Translate action. It remains inert until both the flag and key are set.
     */
    'deepl' => [
        'enabled' => (bool) env('DEEPL_ENABLED', false),
        'key' => env('DEEPL_API_KEY'),
        'base_url' => env('DEEPL_API_BASE_URL', 'https://api-free.deepl.com'),
        'english_target' => env('DEEPL_ENGLISH_TARGET', 'en-US'),
        'timeout' => (int) env('DEEPL_TIMEOUT_SECONDS', 30),
        'reporting_tag' => env('DEEPL_REPORTING_TAG', 'pilot-academy'),
    ],

];
