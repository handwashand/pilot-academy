<?php

return [
    'required' => 'The :attribute field is required.',
    'email' => 'The :attribute field must be a valid email address.',
    'max' => [
        'string' => 'The :attribute field must not be greater than :max characters.',
    ],
    'min' => [
        'string' => 'The :attribute field must be at least :min characters.',
    ],
    'unique' => 'The :attribute has already been taken.',
    'confirmed' => 'The :attribute confirmation does not match.',
    'boolean' => 'The :attribute field must be true or false.',
    'integer' => 'The :attribute field must be an integer.',
    'attributes' => [
        'name' => 'name',
        'email' => 'email',
        'password' => 'password',
        'course_id' => 'course',
        'lesson_id' => 'lesson',
        'seconds' => 'video position',
        'is_positive' => 'feedback rating',
        'comment' => 'comment',
        'locale' => 'language',
    ],
];
