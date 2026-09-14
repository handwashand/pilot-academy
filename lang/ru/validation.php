<?php

return [
    'required' => 'Поле :attribute обязательно.',
    'email' => 'Поле :attribute должно быть действительным адресом эл. почты.',
    'max' => [
        'string' => 'Поле :attribute не должно быть длиннее :max символов.',
    ],
    'min' => [
        'string' => 'Поле :attribute должно быть не короче :min символов.',
    ],
    'unique' => 'Такое значение поля :attribute уже используется.',
    'confirmed' => 'Подтверждение поля :attribute не совпадает.',
    'boolean' => 'Поле :attribute должно быть истинным или ложным.',
    'integer' => 'Поле :attribute должно быть целым числом.',
    'attributes' => [
        'name' => 'имя',
        'email' => 'эл. почта',
        'password' => 'пароль',
        'course_id' => 'курс',
        'lesson_id' => 'урок',
        'seconds' => 'позиция видео',
        'is_positive' => 'оценка отзыва',
        'comment' => 'комментарий',
        'locale' => 'язык',
    ],
];
