<?php

/*
 * The People screens: users (form, list, their tabs, the progress export) and
 * partner companies. Keys mirror in every language (see
 * StudentSiteTranslationTest).
 */

return [
    'users' => [
        'password' => 'كلمة المرور',
        'password_help' => 'اتركه فارغًا للإبقاء على كلمة المرور الحالية عند التعديل.',
        'company_help' => 'الشركة الشريكة التي ينتمي إليها هذا المستخدم (اتركه فارغًا للمسؤولين).',
        'role' => 'الدور',
        'role_help' => 'المسؤولون يديرون المنصة. ومنشئو المحتوى يديرون تدريب منتجاتهم وحدها. والمتعلّمون يحضرون الدورات.',
        'products' => 'المنتجات / الوحدات',
        'products_help' => 'المنتجات التي يملك هذا المنشئ تدريبها. ولا يستطيع رؤية دورات أي منتج آخر.',
        'product_filter' => 'المنتج / الوحدة',
        'all_users' => 'كل المستخدمين',
        'admins' => 'المسؤولون',
        'creators' => 'منشئو المحتوى',
        'learners' => 'المتعلّمون',
        'permissions' => 'صلاحيات إضافية',
        'permissions_help' => 'تُمنح لكل حساب على حدة. وهي غير مشمولة بدور المسؤول أو منشئ المحتوى افتراضيًا.',
        'manage_languages' => 'إدارة اللغات',
        'manage_translations' => 'إدارة الترجمات',
        'check_mail' => 'فحص تسليم البريد',
        'lessons_done' => 'الدروس المنجزة',
        'last_login' => 'آخر تسجيل دخول',
        'export' => 'تصدير تقدّم المتعلّمين',
        'access_link' => 'رابط الدخول',
        'access_link_heading' => 'رابط الدخول الشخصي',
        'copy_link' => 'نسخ الرابط',
        'copied' => 'تم النسخ!',
        'access_link_help' => 'أرسل هذا الرابط إلى المستخدم. فتحه يسجّل دخوله دون كلمة مرور، ويُحفظ تقدّمه في هذا الحساب.',
        'csv' => [
            'name' => 'الاسم',
            'email' => 'البريد الإلكتروني',
            'partner' => 'الشريك',
            'lessons_completed' => 'الدروس المكتملة',
            'certificates' => 'الشهادات',
            'last_activity' => 'آخر نشاط',
            'last_login' => 'آخر تسجيل دخول',
            'joined' => 'تاريخ الانضمام',
        ],
    ],

    'tabs' => [
        'started' => 'بدأ',
        'completed' => 'أكمل',
        'action' => 'الإجراء',
        'details' => 'التفاصيل',
    ],

    'companies' => [
        'name' => 'اسم الشركة',
        'region' => 'المنطقة',
        'region_help' => 'مثل EMEA أو LATAM أو CIS',
        'industry' => 'القطاع',
        'industry_help' => 'مثل الخدمات اللوجستية أو الإنشاءات',
        'members' => 'الأعضاء',
        'certified' => 'الحاصلون على شهادات',
        'certified_tip' => 'الطلاب الذين لديهم شهادة سارية واحدة على الأقل، من إجمالي الأعضاء.',
    ],
];
