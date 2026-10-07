<?php

// Settings → Integrations. Shipped with the code; a row in the translations table
// with the same key overrides a line.

return [
    'subheading' => 'Switch on an integration and add its API token. Who may use each one is set per account under People → Users → Extra permissions.',
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
];
