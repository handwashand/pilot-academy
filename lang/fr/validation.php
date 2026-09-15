<?php

return [
    'required' => 'Le champ :attribute est obligatoire.',
    'email' => 'Le champ :attribute doit être une adresse e-mail valide.',
    'max' => [
        'string' => 'Le champ :attribute ne doit pas dépasser :max caractères.',
    ],
    'min' => [
        'string' => 'Le champ :attribute doit contenir au moins :min caractères.',
    ],
    'unique' => 'La valeur du champ :attribute est déjà utilisée.',
    'confirmed' => 'La confirmation du champ :attribute ne correspond pas.',
    'boolean' => 'Le champ :attribute doit être vrai ou faux.',
    'integer' => 'Le champ :attribute doit être un entier.',
    'attributes' => [
        'name' => 'nom',
        'email' => 'e-mail',
        'password' => 'mot de passe',
        'course_id' => 'cours',
        'lesson_id' => 'leçon',
        'seconds' => 'position dans la vidéo',
        'is_positive' => 'avis',
        'comment' => 'commentaire',
        'locale' => 'langue',
    ],
];
