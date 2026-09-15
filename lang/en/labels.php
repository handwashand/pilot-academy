<?php

/*
 * Names for the values a record can hold — publish status, attempt status,
 * question type, activity. Keys are the values stored in the database, so
 * they mirror in every language (see StudentSiteTranslationTest).
 */

return [
    'publish_status' => [
        'draft' => 'Draft',
        'published' => 'Published',
        'archived' => 'Archived',
    ],

    'attempt_status' => [
        'in_progress' => 'In progress',
        'passed' => 'Passed',
        'failed' => 'Failed',
        'expired' => 'Time expired',
    ],

    'question_type' => [
        'single' => 'Single choice',
        'multiple' => 'Multiple select',
    ],

    'activity' => [
        'login' => 'Logged in',
        'course_opened' => 'Opened course',
        'lesson_opened' => 'Opened lesson',
        'lesson_completed' => 'Completed lesson',
        'course_completed' => 'Completed course',
        'reminder_sent' => 'Sent a reminder',
    ],

    // Standalone names, for a select or a badge. The student site words these
    // inside a sentence instead ("For :audience", academy.common.audience).
    'audience' => [
        'all' => 'Everyone',
        'sales' => 'Sales',
        'technical' => 'Technical',
        'support' => 'Support',
    ],

    'role' => [
        'admin' => 'Admin',
        'creator' => 'Creator',
        'learner' => 'Learner',
    ],

    'certificate' => [
        'valid' => 'Valid',
        'revoked' => 'Revoked',
    ],
];
