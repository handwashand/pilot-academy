<?php

// As chaves espelham lang/en/admin_webinars.php.

return [
    'sections' => [
        'main' => 'Webinar',
        'when' => 'Quando e onde',
        'when_hint' => 'Os horários são guardados e exibidos em UTC, com o fuso indicado, para ninguém chegar uma hora atrasado.',
        'about' => 'Sobre esta sessão',
    ],
    'form' => [
        'summary' => 'Resumo breve',
        'presenter' => 'Apresentador',
        'starts_at' => 'Começa',
        'starts_at_hint' => 'Em UTC.',
        'join_url' => 'Link para participar',
        'recording_url' => 'Link da gravação',
        'recording_url_hint' => 'Adicione depois da sessão: substitui o botão de participar para quem perdeu.',
        'description' => 'Descrição',
        'before_publishing' => 'Adicione uma data e um link para participar ou uma gravação antes de publicar.',
    ],
    'table' => [
        'upcoming' => 'Em breve',
        'past' => 'Passado',
        'recording' => 'Gravação',
    ],
    'notify' => [
        'published' => 'Webinar publicado',
        'drafted' => 'Webinar devolvido a rascunho',
        'incomplete' => 'Preencha primeiro os dados da sessão',
        'incomplete_body' => 'Antes de publicar são obrigatórios uma data e um link para participar ou uma gravação.',
    ],
];
