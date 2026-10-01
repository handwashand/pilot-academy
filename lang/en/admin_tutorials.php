<?php

/*
 * The Tutorials screens in the admin panel: standalone videos that belong to
 * no course. Keys mirror in every language (see StudentSiteTranslationTest).
 */

return [
    'sections' => [
        'main' => 'Tutorial',
        'video' => 'The video',
        'video_hint' => 'A YouTube link or a file you upload — the same two sources a lesson video takes.',
    ],
    'form' => [
        'summary' => 'Short summary',
        'before_publishing' => 'Add a working video before publishing.',
    ],
    'notify' => [
        'published' => 'Tutorial published',
        'drafted' => 'Tutorial returned to draft',
        'incomplete' => 'Add the video first',
        'incomplete_body' => 'A tutorial needs a YouTube link that points at one video, or an uploaded file, before it can be published.',
    ],
];
