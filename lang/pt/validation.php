<?php

return [
    'required' => 'O campo :attribute é obrigatório.',
    'email' => 'O campo :attribute deve ser um endereço de e-mail válido.',
    'max' => [
        'string' => 'O campo :attribute não deve ter mais que :max caracteres.',
    ],
    'min' => [
        'string' => 'O campo :attribute deve ter pelo menos :min caracteres.',
    ],
    'unique' => 'O valor do campo :attribute já está em uso.',
    'confirmed' => 'A confirmação do campo :attribute não confere.',
    'boolean' => 'O campo :attribute deve ser verdadeiro ou falso.',
    'integer' => 'O campo :attribute deve ser um número inteiro.',
    'attributes' => [
        'name' => 'nome',
        'email' => 'e-mail',
        'password' => 'senha',
        'course_id' => 'curso',
        'lesson_id' => 'aula',
        'seconds' => 'posição do vídeo',
        'is_positive' => 'avaliação',
        'comment' => 'comentário',
        'locale' => 'idioma',
    ],
];
