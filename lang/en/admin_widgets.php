<?php

/*
 * The dashboard: figures, charts and the tables under them. Keys mirror in
 * every language (see StudentSiteTranslationTest).
 */

return [
    'filters' => [
        'heading' => 'Dashboard filters',
        'description' => 'Applied to learner activity and results; clear a field to include everything.',
        'start_date' => 'From',
        'end_date' => 'To',
        'partner' => 'Partner',
        'all_partners' => 'All partners',
        'product' => 'Product',
        'all_products' => 'All products',
        'course' => 'Course',
        'all_courses' => 'All courses',
    ],

    'overview' => [
        'students' => 'Students',
        'students_help' => 'Partner accounts',
        'active' => 'Active students',
        'active_help' => ':percent% active in the selected period',
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

    'creator' => [
        'status' => ':published published · :drafts drafts · :archived archived',
    ],

    'companies' => [
        'heading' => 'Partner engagement',
        'description' => 'Learner reach and outcomes in the selected period.',
        'learners' => 'Learners',
        'active' => 'Active',
        'completions' => 'Lessons finished',
        'certificates' => 'Certificates',
        'last_activity' => 'Last activity',
        'open' => 'Open partner',
        'empty' => 'No partners match these filters',
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
        'description' => 'Unique active students and lessons finished per day in the selected period.',
        'active_learners' => 'Active students',
        'lessons_finished' => 'Lessons finished',
    ],

    'journey' => [
        'heading' => 'Learner journey',
        'description' => 'Unique learners recorded at each stage in the selected period.',
        'learners' => 'Learners',
        'course_opened' => 'Opened a course',
        'lesson_opened' => 'Opened a lesson',
        'lesson_completed' => 'Finished a lesson',
        'course_completed' => 'Finished a course',
        'certified' => 'Earned a certificate',
    ],

    'resources' => [
        'heading' => 'Resource engagement',
        'description' => 'Signed-in partner actions in the selected period.',
        'opens' => 'Actions',
        'case_studies' => 'Case study opens',
        'tutorials' => 'Tutorial opens',
        'webinars' => 'Webinar opens',
        'joins' => 'Webinar joins',
        'recordings' => 'Recording opens',
    ],

    'opened' => [
        'heading' => 'Most opened courses',
        'description' => 'Times students opened each course in the selected period.',
        'times_opened' => 'Times opened',
        'removed_course' => 'Removed course',
    ],
];
