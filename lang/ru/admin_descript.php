<?php

/*
 * Translating lesson videos with Descript. Keys mirror in every language (see
 * StudentSiteTranslationTest). See docs/descript-integration.md.
 */

return [
    'action' => [
        'button' => 'Перевести видео',
        'heading' => 'Перевести видео через Descript',
        'description' => 'Descript переводит то, что звучит в видео. Каждый язык переводится один раз и хранится здесь, поэтому повторный запрос никогда не тратит кредиты дважды. Отправить можно только загруженные видео — не ссылки YouTube.',
        'video' => 'Видео',
        'languages' => 'Языки',
        'languages_help' => 'Языки, которые уже переведены или переводятся, показаны, но отправить их повторно нельзя.',
        'confirmation' => 'Я понимаю, что выбранное загруженное видео будет отправлено в Descript и могут быть использованы минуты обработки и кредиты ИИ.',
        'confirmation_required' => 'Подтвердите намерение отправить это видео в Descript и использовать кредиты.',
        'submit' => 'Начать перевод',
        'requested' => 'Перевод запущен',
        'requested_body' => 'Descript переводит: :languages. Это занимает несколько минут — нажмите «Проверить ход».',
        'nothing_new' => 'Нечего переводить',
        'nothing_new_body' => 'Все выбранные языки уже переведены или переводятся.',
        'check' => 'Проверить ход',
        'checked' => 'Ход проверен',
        'checked_body' => 'Готово: :done, в работе: :running, с ошибкой: :failed.',
        'option' => ':language — :status',
    ],

    'status' => [
        'pending' => 'в очереди',
        'translating' => 'переводится',
        'exporting' => 'сохраняется',
        'done' => 'готово',
        'failed' => 'ошибка — отметьте, чтобы повторить',
    ],

    'errors' => [
        'unreachable' => 'Не удалось связаться с Descript. Повторите через несколько минут.',
        'out_of_credits' => 'В тарифе Descript закончились AI-кредиты или минуты медиа.',
        'auth' => 'Descript отклонил API-токен. Проверьте DESCRIPT_API_TOKEN на сервере.',
        'busy' => 'Descript занят. Попытка повторится при следующей проверке.',
        'unavailable' => 'У Descript сейчас неполадки. Попытка повторится при следующей проверке.',
        'rejected' => 'Descript отклонил запрос.',
        'translation_failed' => 'Descript не смог перевести это видео.',
        'composition_not_found' => 'Descript закончил работу, но его переведённую версию не удалось определить.',
        'no_subtitles' => 'Descript завершил работу, но переведённые субтитры оказались пустыми.',
        'file_missing' => 'Загруженный файл видео отсутствует в хранилище.',
        'import_failed' => 'Descript не смог импортировать это видео.',
    ],
];
