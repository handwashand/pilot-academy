<?php

/*
 * The Webinars screens in the admin panel. Keys mirror in every language
 * (see StudentSiteTranslationTest).
 */

return [
    'sections' => [
        'main' => 'Webinar',
        'when' => 'When and where',
        'when_hint' => 'Times are stored and shown in UTC, with the zone named, so nobody joins an hour late.',
        'about' => 'About this session',
    ],
    'form' => [
        'summary' => 'Short summary',
        'presenter' => 'Presenter',
        'starts_at' => 'Starts at',
        'starts_at_hint' => 'In UTC.',
        'join_url' => 'Join link',
        'recording_url' => 'Recording link',
        'recording_url_hint' => 'Add this after the session; it replaces the join button for anyone who missed it.',
        'description' => 'Description',
        'before_publishing' => 'Add a date and either a join link or a recording before publishing.',
    ],
    'table' => [
        'upcoming' => 'Upcoming',
        'past' => 'Past',
        'recording' => 'Recording',
    ],
    'notify' => [
        'published' => 'Webinar published',
        'drafted' => 'Webinar returned to draft',
        'incomplete' => 'Finish the session details first',
        'incomplete_body' => 'A date and either a join link or a recording link are required before publishing.',
    ],
];
