<?php

// Settings → Integrations. Shipped with the code; a row in the translations table
// with the same key overrides a line.

return [
    'groups' => [
        'text' => 'Text translation',
        'text_help' => 'Drafts course, lesson and quiz translations in the Translate window. Admins can use these straight away; anyone else needs the right under People → Users → Extra permissions.',
        'video' => 'Video translation',
        'video_help' => 'Translates the speech in uploaded lesson videos. Everyone, admins included, needs the right under People → Users → Extra permissions.',
        'course' => 'Other providers',
        'course_help' => 'Optional. A Claude token can be saved here for later use. No academy feature uses it at the moment.',
    ],
    'status' => [
        'on' => 'On',
        'off' => 'Off — token saved',
        'not_connected' => 'Not connected',
    ],
    'description' => [
        'descript' => 'Translates the speech in uploaded lesson videos (a lesson’s Translate video button). Each video is sent to Descript once and every translation is kept here. Uses your Descript media minutes and AI credits.',
        'deepl' => 'Drafts course and lesson text translations in the Translate window. The text of empty boxes is sent to DeepL only when someone presses the button and confirms. A key ending in :fx is a DeepL API Free key.',
        'chatgpt' => 'Drafts course and lesson text translations in the Translate window. Text is sent to ChatGPT only when someone presses the button and confirms.',
        'deepseek' => 'Drafts course and lesson text translations in the Translate window. Text is sent to DeepSeek only when someone presses the button and confirms.',
        'claude' => 'Saved for later use. No academy feature sends anything to Claude at the moment.',
    ],
    'enabled' => 'Enable :provider',
    'enabled_help' => 'Needs a token. Switching it off hides the buttons but keeps the token.',
    'token' => 'API token',
    'token_saved' => 'A token is saved — leave blank to keep it',
    'token_empty' => 'Paste the API token',
    'token_help' => 'Stored encrypted and never shown again. Create a key with a spending limit on the provider’s own site.',
    'clear_token' => 'Remove the saved token',
    'scope' => 'Who can use this token',
    'scope_help' => 'A new token goes where you choose. Your own token is used for you instead of the shared one.',
    'scopes' => [
        'personal' => 'Just me',
        'admins' => 'All admins',
        'everyone' => 'Everyone with the right',
    ],
    'shared_token' => 'Shared token',
    'shared_none' => 'No shared token',
    'shared_value' => 'Ends in :ending · :scope · added by :name',
    'shared_scope' => 'Who can use the shared token',
    'clear_shared' => 'Remove the shared token',
    'own_token' => 'Your own token',
    'own_none' => 'None',
    'own_value' => 'Ends in :ending · used for you instead of the shared token',
    'clear_own' => 'Remove your own token',
    'added_by_unknown' => 'a deleted account',
    'model' => 'Model',
    'model_help' => 'Leave blank to use :model.',
    'needs_token' => 'Add an API token before enabling :provider',
    'save' => 'Save',
    'saved' => 'Integrations saved',
];
