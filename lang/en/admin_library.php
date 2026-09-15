<?php

/*
 * Products and the media library. Keys mirror in every language (see
 * StudentSiteTranslationTest).
 */

return [
    'products' => [
        'name' => 'Product / module',
        'name_help' => 'The product this training is about, e.g. GARM or PTM.',
        'creators' => 'Creators',
        'creators_help' => 'Creators who own this product\'s training. Only users whose role is already Creator appear here.',
        'none_assigned' => 'none assigned',
        'courses' => 'Courses',
    ],

    'media' => [
        'name_help' => 'A label to find this image later (e.g. "Map screenshot").',
        'image' => 'Image',
        'used_by' => 'Used by',
        'lessons' => ':count lesson|:count lessons',
    ],
];
