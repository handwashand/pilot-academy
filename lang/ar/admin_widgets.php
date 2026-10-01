<?php

/*
 * The dashboard: figures, charts and the tables under them. Keys mirror in
 * every language (see StudentSiteTranslationTest).
 */

return [
    'filters' => [
        'heading' => 'عوامل تصفية لوحة المعلومات',
        'description' => 'تنطبق على نشاط المتعلّمين ونتائجهم؛ أفرغ أي حقل لتشمل كل شيء.',
        'start_date' => 'من',
        'end_date' => 'إلى',
        'partner' => 'الشريك',
        'all_partners' => 'كل الشركاء',
        'product' => 'المنتج',
        'all_products' => 'كل المنتجات',
        'course' => 'الدورة',
        'all_courses' => 'كل الدورات',
    ],

    'overview' => [
        'students' => 'الطلاب',
        'students_help' => 'حسابات الشركاء',
        'active' => 'الطلاب النشطون',
        'active_help' => ':percent% نشطون في الفترة المحددة',
        'completions' => 'الدروس المكتملة',
        'completions_help' => 'لدى جميع الطلاب',
        'published_courses' => 'الدورات المنشورة',
        'published_courses_help' => ':count في المجموع، بما فيها المسودات',
        'published_lessons' => 'الدروس المنشورة',
        'published_lessons_help' => 'متاحة للتعلّم',
        'certificates' => 'الشهادات الصادرة',
        'no_passes' => 'لا نجاحات بعد',
        'average_score' => 'متوسط النتيجة :score%',
    ],

    'creator' => [
        'status' => ':published منشور · :drafts مسودات · :archived مؤرشف',
    ],

    'companies' => [
        'heading' => 'تفاعل الشركاء',
        'description' => 'عدد المتعلّمين ونتائجهم في الفترة المحددة.',
        'learners' => 'المتعلّمون',
        'active' => 'النشطون',
        'completions' => 'الدروس المنجزة',
        'certificates' => 'الشهادات',
        'last_activity' => 'آخر نشاط',
        'open' => 'افتح الشريك',
        'empty' => 'لا شركاء يطابقون هذه العوامل',
    ],

    'certificates' => [
        'heading' => 'الشهادات الصادرة حسب الدورة',
    ],

    'stalled' => [
        'heading' => 'طلاب انقطعوا',
        'description' => 'بدأوا دورة، ولم يكملوا شيئًا خلال :days يومًا الماضية، وليست لديهم شهادة بعد.',
        'lessons_done' => 'الدروس المنجزة',
        'last_activity' => 'آخر نشاط',
        'reminded' => 'ذُكّر',
        'remind' => 'أرسل تذكيرًا',
        'remind_heading' => 'إرسال تذكير',
        'remind_description' => 'يرسل إلى :email رابطًا شخصيًا يعود به مباشرة إلى درسه التالي.',
        'send_it' => 'أرسله',
        'sent' => 'أُرسل التذكير إلى :name',
        'not_sent' => 'لم يُرسل',
        'cooldown' => 'ذُكّر بالفعل خلال :days يومًا الماضية.',
        'open' => 'افتح',
        'remind_all' => 'أرسل التذكيرات',
        'remind_all_description' => 'يحصل كل طالب على رابط شخصي يعود به إلى درسه التالي. ويُتخطّى كل من ذُكّر خلال :days يومًا الماضية.',
        'sent_count' => 'لم يُرسل أي تذكير|أُرسل تذكير واحد|أُرسل تذكيران|أُرسلت :count تذكيرات|أُرسل :count تذكيرًا|أُرسل :count تذكير',
        'skipped_count' => 'تُخطّي :count',
        'empty' => 'لم ينقطع أحد',
        'empty_description' => 'كل طالب بدأ دورة إما ما زال يتابعها أو أنهاها.',
    ],

    'hardest' => [
        'heading' => 'دروس يتعثّر فيها الطلاب',
        'description' => 'المحاولات المقيَّمة للطلاب، الأسوأ نجاحًا أولًا. والدرس الصعب كثيرًا ما يكون سؤالًا غير واضح.',
        'failed' => 'الرسوب',
        'fail_rate' => 'نسبة الرسوب',
        'review' => 'راجع الأسئلة',
        'empty' => 'لا تعثّر يُذكر',
        'empty_description' => 'بمجرد أن يؤدي الطلاب بضع محاولات مقيَّمة، تظهر هنا أصعب الدروس.',
    ],

    'activity' => [
        'heading' => 'نشاط الطلاب',
        'description' => 'عدد الطلاب النشطين والدروس المنجزة يوميًا في الفترة المحددة.',
        'active_learners' => 'الطلاب النشطون',
        'lessons_finished' => 'الدروس المنجزة',
    ],

    'journey' => [
        'heading' => 'رحلة المتعلّم',
        'description' => 'عدد المتعلّمين الذين بلغوا كل مرحلة في الفترة المحددة. والإنجاز يحتسب الخطوات التي قبله.',
        'learners' => 'المتعلّمون',
        'course_opened' => 'فتح دورة',
        'lesson_opened' => 'فتح درسًا',
        'lesson_completed' => 'أنهى درسًا',
        'course_completed' => 'أنهى دورة',
        'certified' => 'حصل على شهادة',
    ],

    'resources' => [
        'heading' => 'التفاعل مع الموارد',
        'description' => 'إجراءات الشركاء المسجّل دخولهم في الفترة المحددة.',
        'opens' => 'الإجراءات',
        'case_studies' => 'فتح دراسات الحالة',
        'tutorials' => 'فتح الشروحات',
        'webinars' => 'فتح الندوات',
        'joins' => 'الانضمام إلى الندوات',
        'recordings' => 'فتح التسجيلات',
    ],

    'opened' => [
        'heading' => 'أكثر الدورات فتحًا',
        'description' => 'عدد مرات فتح الطلاب لكل دورة في الفترة المحددة.',
        'times_opened' => 'مرات الفتح',
        'removed_course' => 'دورة محذوفة',
    ],
];
