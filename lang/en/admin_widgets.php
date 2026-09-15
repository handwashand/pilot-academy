<?php

/*
 * The dashboard: figures, charts and the tables under them. Keys mirror in
 * every language (see StudentSiteTranslationTest).
 */

return [
    'overview' => [
        'students' => 'Students',
        'students_help' => 'Partner accounts',
        'active' => 'Active students',
        'active_help' => ':percent% started at least one lesson',
        'completions' => 'Lesson completions',
        'completions_help' => 'Across all students',
        'published_courses' => 'Published courses',
        'published_courses_help' => ':count in total, drafts included',
        'published_lessons' => 'Published lessons',
        'published_lessons_help' => 'Available to learn',
        'certificates' => 'Certificates issued',
        'no_passes' => 'No passes yet',
        'average_score' => 'Average score :score%',
    ],

    'companies' => [
        'heading' => 'Progress by partner company',
        'description' => 'Share of all published lessons completed by each company\'s students.',
        'dataset' => '% of published lessons completed',
    ],

    'certificates' => [
        'heading' => 'Certificates issued by course',
    ],

    'stalled' => [
        'heading' => 'Students who have gone quiet',
        'description' => 'Started a course, nothing completed in the last :days days, no certificate yet.',
        'lessons_done' => 'Lessons done',
        'last_activity' => 'Last activity',
        'reminded' => 'Reminded',
        'remind' => 'Send reminder',
        'remind_heading' => 'Send a reminder',
        'remind_description' => 'Emails :email a personal link straight back to their next lesson.',
        'send_it' => 'Send it',
        'sent' => 'Reminder sent to :name',
        'not_sent' => 'Not sent',
        'cooldown' => 'Already reminded in the last :days days.',
        'open' => 'Open',
        'remind_all' => 'Send reminders',
        'remind_all_description' => 'Each student gets a personal link back to their next lesson. Anyone reminded in the last :days days is skipped.',
        'sent_count' => ':count reminder sent|:count reminders sent',
        'skipped_count' => ':count skipped',
        'empty' => 'Nobody has gone quiet',
        'empty_description' => 'Every student who started a course is either still working through it or has finished.',
    ],

    'hardest' => [
        'heading' => 'Lessons students struggle with',
        'description' => 'Graded attempts by students, worst pass rate first. A hard lesson is often an unclear question.',
        'failed' => 'Failed',
        'fail_rate' => 'Fail rate',
        'review' => 'Review questions',
        'empty' => 'No struggles to report',
        'empty_description' => 'Once students have made a few graded attempts, the toughest lessons show up here.',
    ],

    'activity' => [
        'heading' => 'Student activity',
        'description' => 'Lessons finished and sign-ins per day, students only.',
        'lessons_finished' => 'Lessons finished',
        'sign_ins' => 'Sign-ins',
    ],

    'opened' => [
        'heading' => 'Most opened courses',
        'description' => 'Times students opened each course in the last :days days.',
        'times_opened' => 'Times opened',
    ],
];
