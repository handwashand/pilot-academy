<?php

/*
 * The Tutorials screens in the admin panel: standalone videos that belong to
 * no course. Keys mirror in every language (see StudentSiteTranslationTest).
 */

return [
    'sections' => [
        'main' => 'الشرح',
        'video' => 'الفيديو',
        'video_hint' => 'رابط يوتيوب أو ملف ترفعه — المصدران نفسهما اللذان يقبلهما فيديو الدرس.',
    ],
    'form' => [
        'summary' => 'ملخّص قصير',
        'before_publishing' => 'أضف فيديو يعمل قبل النشر.',
    ],
    'notify' => [
        'published' => 'نُشر الشرح',
        'drafted' => 'أُعيد الشرح إلى المسودات',
        'incomplete' => 'أضف الفيديو أولًا',
        'incomplete_body' => 'يحتاج الشرح إلى رابط يوتيوب يشير إلى فيديو واحد، أو إلى ملف مرفوع، قبل أن يمكن نشره.',
    ],
];
