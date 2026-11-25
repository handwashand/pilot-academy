<?php

// Settings → Integrations. Shipped with the code; a row in the translations table
// with the same key overrides a line.

return [
    'groups' => [
        'text' => 'Text translation',
        'text_help' => 'Any provider switched on here drafts translations everywhere the academy has Translate: courses, lessons, quizzes, case studies, tutorials and webinars. Admins can use them straight away; anyone else needs the right under People → Users → Extra permissions.',
        'video' => 'Video translation',
        'video_help' => 'Translates the speech in uploaded lesson videos. Everyone, admins included, needs the right under People → Users → Extra permissions.',
    ],
    'status' => [
        'on' => 'On',
        'off' => 'Off — token saved',
        'not_connected' => 'Not connected',
    ],
    'description' => [
        'descript' => 'Translates the speech in uploaded lesson videos (a lesson’s Translate video button). Each video is sent to Descript once and every translation is kept here. Uses your Descript media minutes and AI credits.',
        'deepl' => 'Drafts translations wherever the academy has Translate. The text of empty boxes is sent to DeepL only when someone presses the button and confirms. A key ending in :fx is a DeepL API Free key.',
        'chatgpt' => 'Drafts translations wherever the academy has Translate. Text is sent to ChatGPT only when someone presses the button and confirms.',
        'deepseek' => 'Drafts translations wherever the academy has Translate. Text is sent to DeepSeek only when someone presses the button and confirms.',
        'claude' => 'Drafts translations wherever the academy has Translate. Text is sent to Claude only when someone presses the button and confirms.',
    ],
    'on' => 'On',
    'token' => 'Token',
    'token_saved' => '••••:ending saved — type a new one to replace it',
    'token_empty' => 'Paste a token',
    'token_help' => 'Stored encrypted and never shown again; only its last four characters are. Leave the box empty to keep the saved one. Create a key with a spending limit on the provider’s own site.',
    'scope' => 'Who can use it',
    'scope_help' => 'For a token you type now. With the box empty, it changes who can use the saved shared token. Just me: only you use it, before any shared token.',
    'scopes' => [
        'personal' => 'Just me',
        'admins' => 'All admins',
        'everyone' => 'Everyone with the right',
    ],
    'shared_line' => ':scope · added by :name',
    'own_line' => 'Your own token ••••:ending, used for you first',
    'remove' => 'Remove',
    'remove_shared_heading' => 'Remove the shared :provider token?',
    'remove_own_heading' => 'Remove your own :provider token?',
    'remove_description' => 'Whoever uses it loses the :provider buttons until a token is added again.',
    'removed' => 'Token removed',
    'added_by_unknown' => 'a deleted account',
    'model' => 'Model',
    'model_help' => ':model (default)',
    'model_warning' => ':model costs about :times× as much per translation as the default, :default. Translating a whole course uses many tokens.',
    'needs_token' => 'Add an API token before enabling :provider',
    'save' => 'Save',
    'saved' => 'Integrations saved',
];
