<?php

/*
 * The admin panel's menu, page titles and record names. Keys mirror in every
 * language (see StudentSiteTranslationTest). Record names are lower case —
 * Filament capitalises them where a title needs it ("New course"). Arabic has
 * no letter case, so these read the same wherever they are used.
 */

return [
    'groups' => [
        'content' => 'المحتوى',
        'people' => 'الأشخاص',
        'results' => 'النتائج',
        'docs' => 'الوثائق',
        'settings' => 'الإعدادات',
    ],

    'account' => [
        'guide' => 'الدليل',
        'student_site' => 'موقع الطلاب',
    ],

    'courses' => [
        'nav' => 'الدورات',
        'one' => 'دورة',
        'many' => 'دورات',
        'badge' => 'دورات ما زالت مسودات — لا يراها الطلاب بعد',
    ],
    'lessons' => [
        'nav' => 'الدروس',
        'one' => 'درس',
        'many' => 'دروس',
        'badge' => 'دروس ما زالت مسودات — مخفية حتى داخل دورة منشورة',
    ],
    'products' => [
        'nav' => 'المنتجات',
        'one' => 'منتج',
        'many' => 'منتجات',
    ],
    'media' => [
        'nav' => 'عناصر الوسائط',
        'one' => 'عنصر وسائط',
        'many' => 'عناصر وسائط',
    ],
    'content_health' => [
        'nav' => 'سلامة المحتوى',
        'badge' => 'معطّل أمام الطلاب الآن',
    ],
    'users' => [
        'nav' => 'المستخدمون',
        'one' => 'مستخدم',
        'many' => 'مستخدمون',
    ],
    'companies' => [
        'nav' => 'الشركات',
        'one' => 'شركة',
        'many' => 'شركات',
    ],
    'certificates' => [
        'nav' => 'الشهادات',
        'one' => 'شهادة',
        'many' => 'شهادات',
    ],
    'final_quiz_health' => [
        'nav' => 'أداء الاختبار النهائي',
    ],
    'quiz_attempts' => [
        'nav' => 'محاولات الاختبار',
        'one' => 'محاولة اختبار',
        'many' => 'محاولات الاختبار',
        'badge' => 'طلاب استنفدوا محاولات اختبار لم ينجحوا فيه',
    ],
    'feedback' => [
        'nav' => 'آراء الطلاب',
        'one' => 'رأي',
        'many' => 'آراء الطلاب',
    ],
    'guide' => [
        'nav' => 'الدليل',
        'title' => 'دليل المسؤول',
    ],
    'whats_new' => [
        'nav' => 'ما الجديد',
    ],
    'questions' => [
        'one' => 'سؤال',
        'many' => 'أسئلة',
    ],
    'activities' => [
        'one' => 'نشاط',
        'many' => 'أنشطة',
    ],
    'translations' => [
        'one' => 'ترجمة',
        'many' => 'ترجمات',
    ],
    'languages' => [
        'one' => 'لغة',
        'many' => 'لغات',
    ],
    'integrations' => [
        'nav' => 'التكاملات',
    ],
    'mail' => [
        'nav' => 'البريد',
    ],

    'tabs' => [
        'lessons' => 'الدروس',
        'final_questions' => 'أسئلة الاختبار النهائي',
        'feedback' => 'آراء الطلاب',
        'completed_lessons' => 'الدروس المكتملة',
        'quiz_attempts' => 'محاولات الاختبار',
        'certificates' => 'الشهادات',
        'activity' => 'النشاط',
    ],

    'tutorials' => [
        'nav' => 'الشروحات',
        'one' => 'شرح',
        'many' => 'شروحات',
        'badge' => 'شروحات ما زالت مسودات — لا يراها الشركاء بعد',
    ],

    'webinars' => [
        'nav' => 'الندوات',
        'one' => 'ندوة',
        'many' => 'ندوات',
        'badge' => 'ندوات ما زالت مسودات — لا يراها الشركاء بعد',
    ],

    'case_studies' => [
        'nav' => 'دراسات الحالة',
        'one' => 'دراسة حالة',
        'many' => 'دراسات حالة',
        'badge' => 'دراسات حالة ما زالت مسودات — لا يراها الشركاء بعد',
    ],
];
