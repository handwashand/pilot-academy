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
        // Descript refuses a project in a folder unless it says what the
        // drive's other members may do with it: edit, comment or view.
        'team_access' => env('DESCRIPT_TEAM_ACCESS', 'view'),
        'timeout' => (int) env('DESCRIPT_TIMEOUT_SECONDS', 30),
        // Descript has no translate endpoint: translation is an instruction to
        // its AI editor. {source} is the composition the video was placed in, {language}
        // the target's English name, {name} the
        // composition to create — the app finds the result by that name.
        'translate_prompt' => env(
            'DESCRIPT_TRANSLATE_PROMPT',
            'Translate the captions of the composition named "{source}" into {language}. Create the translation as a new composition named "{name}". Do not change the original composition.',
        ),
        // Dubbing is a second instruction, on the translated composition. Proven
        // live 2026-10-07: without an assigned speaker Descript picks a stock voice.
        'dub_prompt' => env(
            'DESCRIPT_DUB_PROMPT',
            'Dub the speech of the composition named "{name}" into {language} with an AI voice, keeping the original speaker\'s voice if you can. Change only that composition. Do not change the composition named "{source}". Do not ask me any questions; choose sensible defaults.',
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
