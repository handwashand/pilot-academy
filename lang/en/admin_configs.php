<?php

// Settings → Configs. Shipped with the code; a row in the translations table
// with the same key overrides a line.

return [
    'subheading' => 'Switch on a translation provider and add its API token. Once enabled, people who have been given the right see a button to draft translations with it.',
    'provider_description' => 'Translate course and lesson text with :provider. Text is sent to :provider only when someone presses the button and confirms.',
    'enabled' => 'Enable :provider',
    'enabled_help' => 'Needs a token. Switching it off hides the button but keeps the token.',
    'token' => 'API token',
    'token_saved' => 'A token is saved — leave blank to keep it',
    'token_empty' => 'Paste the API token',
    'token_help' => 'Stored encrypted and never shown again. Create a key with a spending limit on the provider’s own site.',
    'clear_token' => 'Remove the saved token',
    'model' => 'Model',
    'model_help' => 'Leave blank to use :model.',
    'needs_token' => 'Add an API token before enabling :provider',
    'save' => 'Save',
    'saved' => 'Configs saved',
];
