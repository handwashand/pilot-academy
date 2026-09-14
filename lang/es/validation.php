<?php

return [
    'required' => 'El campo :attribute es obligatorio.',
    'email' => 'El campo :attribute debe ser una dirección de correo válida.',
    'max' => [
        'string' => 'El campo :attribute no debe superar :max caracteres.',
    ],
    'min' => [
        'string' => 'El campo :attribute debe tener al menos :min caracteres.',
    ],
    'unique' => 'El valor de :attribute ya está en uso.',
    'confirmed' => 'La confirmación de :attribute no coincide.',
    'boolean' => 'El campo :attribute debe ser verdadero o falso.',
    'integer' => 'El campo :attribute debe ser un número entero.',
    'attributes' => [
        'name' => 'nombre',
        'email' => 'correo electrónico',
        'password' => 'contraseña',
        'course_id' => 'curso',
        'lesson_id' => 'lección',
        'seconds' => 'posición del video',
        'is_positive' => 'valoración',
        'comment' => 'comentario',
        'locale' => 'idioma',
    ],
];
