<?php

/*
 * The Webinars screens in the admin panel. Keys mirror in every language
 * (see StudentSiteTranslationTest).
 */

return [
    'sections' => [
        'main' => 'الندوة',
        'when' => 'الموعد والمكان',
        'when_hint' => 'تُحفظ المواعيد وتُعرض بتوقيت UTC مع ذكر المنطقة الزمنية، حتى لا ينضم أحد متأخرًا بساعة.',
        'about' => 'عن هذه الجلسة',
    ],
    'form' => [
        'summary' => 'ملخّص قصير',
        'presenter' => 'المقدّم',
        'starts_at' => 'تبدأ في',
        'starts_at_hint' => 'بتوقيت UTC.',
        'join_url' => 'رابط الانضمام',
        'recording_url' => 'رابط التسجيل',
        'recording_url_hint' => 'أضف هذا بعد الجلسة؛ فهو يحل محل زر الانضمام لمن فاتته.',
        'description' => 'الوصف',
        'before_publishing' => 'أضف موعدًا ورابط انضمام أو تسجيلًا قبل النشر.',
    ],
    'table' => [
        'upcoming' => 'قادمة',
        'past' => 'سابقة',
        'recording' => 'التسجيل',
    ],
    'notify' => [
        'published' => 'نُشرت الندوة',
        'drafted' => 'أُعيدت الندوة إلى المسودات',
        'incomplete' => 'أكمل تفاصيل الجلسة أولًا',
        'incomplete_body' => 'يلزم تحديد موعد ورابط انضمام أو رابط تسجيل قبل النشر.',
    ],
];
