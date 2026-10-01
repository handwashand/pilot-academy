<?php

/*
 * The Case Studies screens in the admin panel. The ten study headings are not
 * here: editors and partners read the same names, so they live in
 * academy.case_studies.sections. Keys mirror in every language (see
 * StudentSiteTranslationTest).
 */

return [
    'sections' => [
        'main' => 'Case study',
        'privacy' => 'Privacy and verification',
        'privacy_hint' => 'Keep identifying customer details out by default. Store verification notes for any performance claim.',
        'media' => 'Media',
        'media_hint' => 'Use sanitized diagrams, screenshots, or attachments. Avoid customer names, locations, live data, credentials, and identifying images.',
        'study' => 'Study sections',
        'related' => 'Related content',
    ],

    'form' => [
        'step_images' => 'Pictures for :step',
        'step_images_help' => 'Paste a screenshot, drop a file here, or choose one. They appear under this step on the partner\'s page. Sanitize them first: no customer names, exact locations, credentials or live vehicle data.',
        'images_help' => 'Add a picture to any step: the paperclip in the toolbar, or paste or drag one straight in. Keep screenshots sanitized — no customer names, locations or live data.',
        'short_problem' => 'Short problem statement',
        'industry' => 'Industry',
        'features' => 'Relevant Pilot features',
        'add_feature' => 'Add a feature',
        'difficulty' => 'Difficulty',
        'implementation_time' => 'Estimated implementation time',
        'anonymized' => 'Anonymized',
        'customer_approved' => 'Customer approved',
        'source_note' => 'Source or verification note',
        'performance_note' => 'Performance claim note',
        'performance_note_hint' => 'Required when the study mentions measured savings, reductions, uptime, or other quantified outcomes.',
        'cover' => 'Cover image',
        'diagram' => 'Diagram or screenshot',
        'related_lessons' => 'Related Academy lessons',
        'links' => 'Documentation links',
        'add_link' => 'Add link',
        'link_url' => 'URL',
        'own_product' => 'Choose one of your assigned products.',
        'before_publishing' => 'Add the required sections and source note before publishing.',
    ],

    'table' => [
        'approved' => 'Approved',
        'preview' => 'Preview',
    ],

    'notify' => [
        'published' => 'Case study published',
        'drafted' => 'Case study returned to draft',
        'drafted_many' => 'Case studies returned to draft',
        'published_count' => ':count case study published|:count case studies published',
        'skipped_count' => ':count case study skipped|:count case studies skipped',
        'incomplete' => 'Finish the required sections first',
        'incomplete_body' => 'Title, summary, scenario, desired outcome, configuration, verification, and source note are required before publishing.',
        'skipped_body' => 'Skipped records are missing required sections or source notes.',
    ],
];
