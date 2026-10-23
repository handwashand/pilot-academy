<?php

// Ajustes → Integraciones. Se incluye con el código; una fila de la tabla
// translations con la misma clave sustituye a una línea.

return [
    'groups' => [
        'text' => 'Traducción de texto',
        'text_help' => 'Genera borradores de traducción de cursos, lecciones y cuestionarios en la ventana Traducir. Los administradores pueden usarlos de inmediato; cualquier otra persona necesita el permiso en Personas → Usuarios → Permisos adicionales.',
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
        'deepl' => 'Genera borradores de traducción del texto de cursos y lecciones en la ventana Traducir. El texto de los campos vacíos solo se envía a DeepL cuando alguien pulsa el botón y confirma. Una clave que termina en :fx es una clave de DeepL API Free.',
        'chatgpt' => 'Genera borradores de traducción del texto de cursos y lecciones en la ventana Traducir. El texto solo se envía a ChatGPT cuando alguien pulsa el botón y confirma.',
        'deepseek' => 'Genera borradores de traducción del texto de cursos y lecciones en la ventana Traducir. El texto solo se envía a DeepSeek cuando alguien pulsa el botón y confirma.',
    ],
    'enabled' => 'Activar :provider',
    'enabled_help' => 'Necesita un token. Desactivarlo oculta los botones pero conserva el token.',
    'token' => 'Token de API',
    'token_saved' => 'Hay un token guardado — déjalo en blanco para conservarlo',
    'token_empty' => 'Pega el token de API',
    'token_help' => 'Se guarda cifrado y no se vuelve a mostrar. Crea una clave con límite de gasto en el sitio del proveedor.',
    'clear_token' => 'Eliminar el token guardado',
    'model' => 'Modelo',
    'model_help' => 'Déjalo en blanco para usar :model.',
    'needs_token' => 'Añade un token de API antes de activar :provider',
    'save' => 'Guardar',
    'saved' => 'Integraciones guardadas',
];
