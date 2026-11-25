<?php

// Settings → Integrations. Shipped with the code; a row in the translations table
// with the same key overrides a line.

return [
    'groups' => [
        'text' => 'Traducción de texto',
        'text_help' => 'Cualquier proveedor activado aquí genera borradores de traducción en todos los lugares donde la academia tiene Traducir: cursos, lecciones, cuestionarios, casos de estudio, tutoriales y webinars. Los administradores pueden usarlos de inmediato; cualquier otra persona necesita el permiso en Personas → Usuarios → Permisos adicionales.',
        'video' => 'Traducción de vídeo',
        'video_help' => 'Traduce la voz de los vídeos subidos a las lecciones. Todos, administradores incluidos, necesitan el permiso en Personas → Usuarios → Permisos adicionales.',
    ],
    'status' => [
        'on' => 'Activado',
        'off' => 'Desactivado — token guardado',
        'not_connected' => 'Sin conectar',
    ],
    'description' => [
        'descript' => 'Traduce el habla de los vídeos de lección subidos (botón Traducir vídeo de una lección). Cada vídeo se envía una sola vez a Descript y cada traducción se guarda aquí. Usa tus minutos de contenido y créditos de IA de Descript.',
        'deepl' => 'Genera borradores de traducción donde la academia tiene Traducir. El texto de las casillas vacías se envía a DeepL solo cuando alguien pulsa el botón y lo confirma. Una clave que termina en :fx es una clave de DeepL API Free.',
        'chatgpt' => 'Genera borradores de traducción donde la academia tiene Traducir. El texto se envía a ChatGPT solo cuando alguien pulsa el botón y lo confirma.',
        'deepseek' => 'Genera borradores de traducción donde la academia tiene Traducir. El texto se envía a DeepSeek solo cuando alguien pulsa el botón y lo confirma.',
        'claude' => 'Genera borradores de traducción donde la academia tiene Traducir. El texto se envía a Claude solo cuando alguien pulsa el botón y lo confirma.',
    ],
    'on' => 'Activado',
    'token' => 'Token',
    'token_saved' => '••••:ending guardado — escribe uno nuevo para sustituirlo',
    'token_empty' => 'Pega un token',
    'token_help' => 'Se guarda cifrado y no se vuelve a mostrar; solo se ven sus cuatro últimos caracteres. Deja la casilla vacía para conservar el guardado. Crea una clave con límite de gasto en el sitio del proveedor.',
    'scope' => 'Quién puede usarlo',
    'scope_help' => 'Para un token que escribas ahora. Con la casilla vacía, cambia quién puede usar el token compartido guardado. Solo yo: solo tú lo usas, antes que cualquier token compartido.',
    'scopes' => [
        'personal' => 'Solo yo',
        'admins' => 'Todos los administradores',
        'everyone' => 'Todos los que tienen el permiso',
    ],
    'shared_line' => ':scope · añadido por :name',
    'own_line' => 'Tu propio token ••••:ending, que se usa primero para ti',
    'remove' => 'Quitar',
    'remove_shared_heading' => '¿Quitar el token compartido de :provider?',
    'remove_own_heading' => '¿Quitar tu propio token de :provider?',
    'remove_description' => 'Quien lo use pierde los botones de :provider hasta que se añada otro token.',
    'removed' => 'Token quitado',
    'added_by_unknown' => 'una cuenta eliminada',
    'model' => 'Modelo',
    'model_help' => ':model (predeterminado)',
    'model_warning' => ':model cuesta unas :times veces más por traducción que el predeterminado, :default. Traducir un curso completo consume muchos tokens.',
    'needs_token' => 'Añade un token de API antes de activar :provider',
    'save' => 'Guardar',
    'saved' => 'Integraciones guardadas',
];
