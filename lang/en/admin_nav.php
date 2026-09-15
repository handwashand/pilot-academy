<?php

/*
 * The admin panel's menu, page titles and record names. Keys mirror in every
 * language (see StudentSiteTranslationTest). Record names are lower case —
 * Filament capitalises them where a title needs it ("New course").
 */

return [
    'groups' => [
        'content' => 'Content',
        'people' => 'People',
        'results' => 'Results',
        'docs' => 'Docs',
        'settings' => 'Settings',
    ],

    'account' => [
        'guide' => 'Guide',
        'student_site' => 'Student site',
    ],

    'courses' => [
        'nav' => 'Courses',
        'one' => 'course',
        'many' => 'courses',
        'badge' => 'Courses still in draft — students cannot see them yet',
    ],
    'lessons' => [
        'nav' => 'Lessons',
        'one' => 'lesson',
        'many' => 'lessons',
        'badge' => 'Lessons still in draft — hidden even in a published course',
    ],
    'products' => [
        'nav' => 'Products',
        'one' => 'product',
        'many' => 'products',
    ],
    'media' => [
        'nav' => 'Media Items',
        'one' => 'media item',
        'many' => 'media items',
    ],
    'content_health' => [
        'nav' => 'Content health',
        'badge' => 'Broken for students right now',
    ],
    'users' => [
        'nav' => 'Users',
        'one' => 'user',
        'many' => 'users',
    ],
    'companies' => [
        'nav' => 'Companies',
        'one' => 'company',
        'many' => 'companies',
    ],
    'certificates' => [
        'nav' => 'Certificates',
        'one' => 'certificate',
        'many' => 'certificates',
    ],
    'final_quiz_health' => [
        'nav' => 'Final quiz health',
    ],
    'quiz_attempts' => [
        'nav' => 'Quiz attempts',
        'one' => 'quiz attempt',
        'many' => 'quiz attempts',
        'badge' => 'Students out of attempts at a quiz they have not passed',
    ],
    'feedback' => [
        'nav' => 'Student feedback',
        'one' => 'feedback',
        'many' => 'student feedback',
    ],
    'guide' => [
        'nav' => 'Guide',
        'title' => 'Admin guide',
    ],
    'whats_new' => [
        'nav' => "What's new",
    ],
    'questions' => [
        'one' => 'question',
        'many' => 'questions',
    ],
    'activities' => [
        'one' => 'activity',
        'many' => 'activities',
    ],
    'translations' => [
        'one' => 'translation',
        'many' => 'translations',
    ],
    'languages' => [
        'one' => 'language',
        'many' => 'languages',
    ],
    'mail' => [
        'nav' => 'Mail',
    ],

    'tabs' => [
        'lessons' => 'Lessons',
        'final_questions' => 'Final questions',
        'feedback' => 'Student feedback',
        'completed_lessons' => 'Completed lessons',
        'quiz_attempts' => 'Quiz attempts',
        'certificates' => 'Certificates',
        'activity' => 'Activity',
    ],
];
