<?php

/*
 * Translating lesson videos with Descript. Keys mirror in every language (see
 * StudentSiteTranslationTest). See docs/descript-integration.md.
 */

return [
    'action' => [
        'button' => 'Translate video',
        'heading' => 'Translate the video with Descript',
        'description' => 'Descript translates what is said in the video. Each language is translated once and kept here, so asking again never spends credits twice. Only uploaded videos can be sent — not YouTube links.',
        'video' => 'Video',
        'languages' => 'Languages',
        'languages_help' => 'Languages already translated or under way are shown but cannot be sent again.',
        'kind' => 'What to make',
        'kind_dub' => 'Voice — the video dubbed in that language',
        'kind_transcript' => 'Subtitles and transcript only',
        'kind_help' => 'A dubbed voice uses more AI credits than subtitles. Each is made once and kept here.',
        'confirmation' => 'I understand that this sends the selected uploaded video to Descript and may use media minutes and AI credits.',
        'confirmation_required' => 'Confirm that you intend to send this video to Descript and use credits.',
        'submit' => 'Start translation',
        'requested' => 'Translation started',
        'requested_body' => 'Descript is working on: :languages. Translating takes a few minutes — use Check progress.',
        'nothing_new' => 'Nothing new to translate',
        'nothing_new_body' => 'Every language you chose is already translated or under way.',
        'check' => 'Check progress',
        'checked' => 'Progress checked',
        'checked_body' => ':done done, :running still working, :failed failed.',
        'option' => ':language — :status',
    ],

    'status' => [
        'pending' => 'waiting',
        'translating' => 'translating',
        'dubbing' => 'adding the voice',
        'exporting' => 'saving',
        'done' => 'done',
        'failed' => 'failed — tick to try again',
    ],

    'errors' => [
        'unreachable' => 'Descript could not be reached. Try again in a few minutes.',
        'out_of_credits' => 'Descript has run out of AI credits or media minutes on this plan.',
        'auth' => 'Descript refused the API token. Check DESCRIPT_API_TOKEN on the server.',
        'busy' => 'Descript is busy. It will be tried again on the next check.',
        'unavailable' => 'Descript is having trouble right now. It will be tried again on the next check.',
        'rejected' => 'Descript turned the request down.',
        'translation_failed' => 'Descript could not translate this video.',
        'composition_not_found' => 'Descript finished, but its translated version could not be identified.',
        'no_subtitles' => 'Descript finished, but its translated subtitles came back empty.',
        'dub_failed' => 'Descript could not dub this video.',
        'file_missing' => 'The uploaded video file is missing from storage.',
        'import_failed' => 'Descript could not import this video.',
    ],
    'course' => [
        'button' => 'Translate lesson videos',
        'heading' => 'Translate every lesson video in this course',
        'description' => ':videos uploaded videos in :lessons lessons will be sent to Descript for the languages you tick. A video or language already translated or under way is skipped and costs nothing. This uses Descript media minutes and AI credits.',
        'submit' => 'Queue translations',
        'queued' => 'Translations queued',
        'queued_body' => ':count translations queued. Press Check progress to start them — each takes a few minutes.',
    ],
];
