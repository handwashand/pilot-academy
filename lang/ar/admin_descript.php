<?php

/*
 * Translating lesson videos with Descript. Keys mirror in every language (see
 * StudentSiteTranslationTest). See docs/descript-integration.md.
 */

return [
    'action' => [
        'button' => 'ترجمة الفيديو',
        'heading' => 'ترجمة الفيديو عبر Descript',
        'description' => 'يترجم Descript ما يُقال في الفيديو. وتُترجم كل لغة مرة واحدة وتُحفظ هنا، فلا يستهلك الطلب مجددًا الأرصدة مرتين أبدًا. ولا يمكن إرسال إلا الفيديوهات المرفوعة — لا روابط يوتيوب.',
        'video' => 'الفيديو',
        'languages' => 'اللغات',
        'languages_help' => 'اللغات المترجمة أو الجارية ترجمتها تظهر، لكن لا يمكن إرسالها مرة أخرى.',
        'confirmation' => 'أفهم أن هذا يرسل ملف الفيديو المرفوع المحدد إلى Descript وقد يستهلك دقائق الوسائط وأرصدة الذكاء الاصطناعي.',
        'confirmation_required' => 'أكّد أنك تقصد إرسال هذا الفيديو إلى Descript واستخدام الأرصدة.',
        'submit' => 'بدء الترجمة',
        'requested' => 'بدأت الترجمة',
        'requested_body' => 'يعمل Descript على: :languages. تستغرق الترجمة بضع دقائق — استخدم «تحقّق من التقدّم».',
        'nothing_new' => 'لا جديد للترجمة',
        'nothing_new_body' => 'كل اللغات التي اخترتها مترجمة أو جارية ترجمتها.',
        'check' => 'تحقّق من التقدّم',
        'checked' => 'تم التحقّق من التقدّم',
        'checked_body' => 'مكتملة: :done، جارية: :running، فاشلة: :failed.',
        'option' => ':language — :status',
    ],

    'status' => [
        'pending' => 'في الانتظار',
        'translating' => 'جارية الترجمة',
        'exporting' => 'جارٍ الحفظ',
        'done' => 'مكتملة',
        'failed' => 'فشلت — علّمها لإعادة المحاولة',
    ],

    'errors' => [
        'unreachable' => 'تعذّر الوصول إلى Descript. أعد المحاولة بعد بضع دقائق.',
        'out_of_credits' => 'نفدت أرصدة الذكاء الاصطناعي أو دقائق الوسائط في خطة Descript.',
        'auth' => 'رفض Descript رمز الواجهة البرمجية. تحقّق من DESCRIPT_API_TOKEN على الخادم.',
        'busy' => 'Descript مشغول. ستُعاد المحاولة عند التحقّق التالي.',
        'unavailable' => 'يواجه Descript مشكلات الآن. ستُعاد المحاولة عند التحقّق التالي.',
        'rejected' => 'رفض Descript الطلب.',
        'translation_failed' => 'تعذّر على Descript ترجمة هذا الفيديو.',
        'composition_not_found' => 'أنهى Descript عمله، لكن تعذّر تحديد نسخته المترجمة.',
        'file_missing' => 'ملف الفيديو المرفوع غير موجود في التخزين.',
        'import_failed' => 'تعذّر على Descript استيراد هذا الفيديو.',
    ],
];
