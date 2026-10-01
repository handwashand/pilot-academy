<?php

/*
 * Names for the values a record can hold — publish status, attempt status,
 * question type, activity. Keys are the values stored in the database, so
 * they mirror in every language (see StudentSiteTranslationTest).
 */

return [
    'publish_status' => [
        'draft' => 'مسودة',
        'published' => 'منشور',
        'archived' => 'مؤرشف',
    ],

    'attempt_status' => [
        'in_progress' => 'قيد التقدّم',
        'passed' => 'ناجح',
        'failed' => 'راسب',
        'expired' => 'انتهى الوقت',
    ],

    'question_type' => [
        'single' => 'اختيار واحد',
        'multiple' => 'اختيار متعدد',
    ],

    'activity' => [
        'login' => 'سجّل الدخول',
        'course_opened' => 'فتح دورة',
        'lesson_opened' => 'فتح درسًا',
        'lesson_completed' => 'أكمل درسًا',
        'course_completed' => 'أكمل دورة',
        'reminder_sent' => 'أرسل تذكيرًا',
        'case_study_opened' => 'فتح دراسة حالة',
        'tutorial_opened' => 'فتح شرحًا',
        'webinar_opened' => 'فتح ندوة',
        'webinar_joined' => 'انضم إلى ندوة',
        'webinar_recording_opened' => 'فتح تسجيل ندوة',
    ],

    // Standalone names, for a select or a badge. The student site words these
    // inside a sentence instead ("For :audience", academy.common.audience).
    'audience' => [
        'all' => 'الجميع',
        'sales' => 'المبيعات',
        'technical' => 'الفريق التقني',
        'support' => 'الدعم',
    ],

    'role' => [
        'admin' => 'مسؤول',
        'creator' => 'منشئ محتوى',
        'learner' => 'متعلّم',
    ],

    'certificate' => [
        'valid' => 'سارية',
        'revoked' => 'ملغاة',
    ],
];
