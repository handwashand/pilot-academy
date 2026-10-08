<?php

/*
 * Settings: languages and translations. Keys mirror in every language (see
 * StudentSiteTranslationTest).
 */

return [
    'languages' => [
        'code' => 'رمز ISO 639-1',
        'native_name' => 'الاسم بلغتها',
        'direction' => 'اتجاه الكتابة',
        'position' => 'الترتيب',
        'active' => 'مفعّلة',
        'default' => 'اللغة الافتراضية',
        'default_help' => 'تغيير هذا ينقل لغة الاحتياط للجميع.',
        'language' => 'اللغة',
        'code_column' => 'الرمز',
        'default_column' => 'افتراضية',
        'coverage' => 'نسبة التغطية',
        'use' => 'استخدم هذه اللغة',
    ],

    'translations' => [
        'key' => 'المفتاح',
        'language' => 'اللغة',
        'english' => 'الإنجليزية',
        'shipped' => 'النص المرفق',
        'shipped_help' => 'ما يراه الطلاب بهذه اللغة حين يكون الحقل أدناه فارغًا.',
        'none_shipped' => '— لا نص مرفق —',
        'correction' => 'التصحيح',
        'correction_help' => 'اتركه فارغًا لاستخدام النص المرفق. أبقِ الكلمات التي تبدأ بنقطتين، مثل :name، كما هي تمامًا، وأبقِ الفاصل | بين صيغ العدد («درس واحد|درسان»).',
        'notes' => 'ملاحظات',
        'text' => 'النص الذي يراه الطلاب',
        'missing' => 'ناقص',
        'module' => 'الوحدة',
        'corrected' => 'مصحّح',
        'correct' => 'صحّح',
        'correct_in' => 'صحّح سطر :language',
        'click_hint' => 'سطر واحد لكل مفتاح، في كل اللغات. انقر أي خلية لتصحيح تلك اللغة — ويرى الطلاب التغيير فورًا.',
        'save_correction' => 'حفظ التصحيح',
        'states' => [
            'corrected' => 'مصحّح',
            'shipped' => 'مرفق',
            'missing' => 'ناقص',
        ],
    ],

    'overlay' => [
        'profile' => 'الملف الشخصي',
        'photo' => 'الصورة',
        'name' => 'الاسم',
        'email' => 'البريد الإلكتروني',
        'password' => 'كلمة مرور جديدة',
        'password_confirmation' => 'تأكيد كلمة المرور',
        'current_password' => 'كلمة المرور الحالية',
        'current_password_help' => 'لازمة لتغيير بريدك الإلكتروني أو كلمة مرورك.',
        'save' => 'حفظ الإعدادات',
        'saved' => 'تم حفظ الإعدادات',
    ],
];
