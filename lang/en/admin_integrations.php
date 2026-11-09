<?php

// Settings → Integrations. Shipped with the code; a row in the translations table
// with the same key overrides a line.

return [
    'groups' => [
        'text' => 'Text translation',
        'text_help' => 'Drafts course, lesson and quiz translations in the Translate window. Admins can use these straight away; anyone else needs the right under People → Users → Extra permissions.',
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
        'deepl' => 'Drafts course and lesson text translations in the Translate window. The text of empty boxes is sent to DeepL only when someone presses the button and confirms. A key ending in :fx is a DeepL API Free key.',
        'chatgpt' => 'Drafts course and lesson text translations in the Translate window. Text is sent to ChatGPT only when someone presses the button and confirms.',
        'deepseek' => 'Drafts course and lesson text translations in the Translate window. Text is sent to DeepSeek only when someone presses the button and confirms.',
    ],
    'enabled' => 'Enable :provider',
    'enabled_help' => 'Needs a token. Switching it off hides the buttons but keeps the token.',
    'token' => 'API token',
    'token_saved' => 'A token is saved — leave blank to keep it',
    'token_empty' => 'Paste the API token',
    'token_help' => 'Stored encrypted and never shown again. Create a key with a spending limit on the provider’s own site.',
    'clear_token' => 'Remove the saved token',
    'model' => 'Model',
    'model_help' => 'Leave blank to use :model.',
    'needs_token' => 'Add an API token before enabling :provider',
    'save' => 'Save',
    'saved' => 'Integrations saved',
    'generation' => [
        'heading' => 'Course generation',
        'help' => 'Uses the ChatGPT or DeepSeek token saved above. There is no separate key. Prices used for the budget are estimates.',
        'default_provider' => 'Default provider',
        'default_provider_help' => 'Pre-selected on Generate course. A provider that is off cannot be used until it is switched on.',
        'none' => 'No default',
        'fallback' => 'Allow fallback to the other provider',
        'fallback_help' => 'Only when this is on, and the person generating ticks it. The notes are then sent to the other provider. A job never switches provider on its own.',
        'daily_limit' => 'Daily generations per person',
        'daily_limit_help' => 'Leave empty for no limit. Counts each request the provider answered, including one that could not be saved.',
        'budget' => 'Monthly budget (USD)',
        'budget_help' => 'Leave empty for no cap. Estimated from token prices, not an invoice from the provider.',
    ],
];
