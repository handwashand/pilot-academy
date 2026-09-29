<?php

// Las claves reflejan lang/en/admin_webinars.php.

return [
    'sections' => [
        'main' => 'Seminario web',
        'when' => 'Cuándo y dónde',
        'when_hint' => 'Las horas se guardan y se muestran en UTC, con la zona indicada, para que nadie llegue una hora tarde.',
        'about' => 'Sobre esta sesión',
    ],
    'form' => [
        'summary' => 'Resumen breve',
        'presenter' => 'Ponente',
        'starts_at' => 'Comienza',
        'starts_at_hint' => 'En UTC.',
        'join_url' => 'Enlace para unirse',
        'recording_url' => 'Enlace de la grabación',
        'recording_url_hint' => 'Añádelo después de la sesión: sustituye al botón de unirse para quien se la perdió.',
        'description' => 'Descripción',
        'before_publishing' => 'Añade una fecha y un enlace para unirse o una grabación antes de publicar.',
    ],
    'table' => [
        'upcoming' => 'Próximo',
        'past' => 'Pasado',
        'recording' => 'Grabación',
    ],
    'notify' => [
        'published' => 'Seminario web publicado',
        'drafted' => 'Seminario web devuelto a borrador',
        'incomplete' => 'Completa primero los datos de la sesión',
        'incomplete_body' => 'Antes de publicar se requieren una fecha y un enlace para unirse o una grabación.',
    ],
];
