<?php

/*
 * The Results screens: certificates, quiz attempts and student feedback. Keys
 * mirror in every language (see StudentSiteTranslationTest).
 */

return [
    'certificates' => [
        'export' => 'تصدير CSV',
        'resend' => 'إعادة إرسال البريد',
        'resend_description' => 'أرسل الشهادة إلى :email.',
        'emailed' => 'أُرسلت الشهادة بالبريد',
        'regenerate' => 'إعادة توليد ملف PDF',
        'regenerated' => 'أُعيد توليد ملف PDF',
        'edit_name' => 'تعديل الاسم',
        'edit_name_heading' => 'صحّح الاسم على هذه الشهادة',
        'edit_name_description' => 'يُعاد طبع ملف PDF بالاسم الجديد، وتظهر صفحة التحقق العامة الاسم فورًا. أما الرقم والتاريخ والنتيجة فتبقى كما هي. استخدم «إعادة إرسال البريد» بعد ذلك إن كان على الطالب أن يستلم النسخة المصحّحة.',
        'name_on_certificate' => 'الاسم على الشهادة',
        'update_profile' => 'استخدم هذا الاسم أيضًا في شهادات الطالب المستقبلية',
        'update_profile_help' => 'يحفظه بوصفه اسم الشهادة في ملفه الشخصي، حيث يمكنه تغييره أيضًا.',
        'name_corrected' => 'صُحّح الاسم وأُعيد طبع ملف PDF',
        'revoke' => 'إلغاء',
        'revoke_description' => 'ستظهر الشهادة ملغاة في صفحة التحقق العامة. والشهادات دائمة — فلا تستخدم هذا إلا عند إصدار خاطئ.',
        'revoked' => 'أُلغيت الشهادة',
        'restore' => 'استعادة',
        'restored' => 'استُعيدت الشهادة',
        'score_percent' => 'النتيجة %',
    ],

    'attempts' => [
        'quiz' => 'الاختبار',
        'not_submitted' => 'لم يُرسل',
        'out_of_attempts' => 'استنفد المحاولات ولم ينجح',
        'quiz_type' => 'نوع الاختبار',
        'final_quizzes' => 'الاختبارات النهائية',
        'lesson_checks' => 'اختبارات فهم الدروس',
        'grant' => 'منح محاولة إضافية',
        'grant_description' => 'يحصل :name على محاولة إضافية واحدة في :quiz. ولا يتأثر أحد غيره: يبقى «الحد الأقصى للمحاولات» كما هو للجميع.',
        'reason' => 'السبب (اختياري)',
        'reason_placeholder' => 'مثلًا: انقطع الاتصال أثناء الاختبار',
        'granted' => 'مُنحت محاولة إضافية',
        'granted_body' => 'يستطيع :name أداء :quiz مرة أخرى.',
        'empty' => 'لا محاولات اختبار بعد',
        'empty_description' => 'تُسجَّل المحاولات للاختبارات النهائية، ولاختبارات فهم الدروس التي لها حد زمني أو عدد محاولات.',
        'final_quiz' => 'الاختبار النهائي',
        'a_lesson' => 'أحد الدروس',
    ],

    'feedback' => [
        'empty_description' => 'يُسأل الطلاب عن رأيهم بمجرد إنهائهم دورة.',
    ],
];
