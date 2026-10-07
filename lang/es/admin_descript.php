<?php

/*
 * Translating lesson videos with Descript. Keys mirror in every language (see
 * StudentSiteTranslationTest). See docs/descript-integration.md.
 */

return [
    'action' => [
        'button' => 'Traducir vídeo',
        'heading' => 'Traducir el vídeo con Descript',
        'description' => 'Descript traduce lo que se dice en el vídeo. Cada idioma se traduce una sola vez y se guarda aquí, así que volver a pedirlo nunca gasta créditos dos veces. Solo se pueden enviar vídeos subidos, no enlaces de YouTube.',
        'video' => 'Vídeo',
        'languages' => 'Idiomas',
        'languages_help' => 'Los idiomas ya traducidos o en curso se muestran, pero no se pueden volver a enviar.',
        'confirmation' => 'Entiendo que esto envía el vídeo subido seleccionado a Descript y puede consumir minutos de contenido y créditos de IA.',
        'confirmation_required' => 'Confirma que quieres enviar este vídeo a Descript y consumir créditos.',
        'submit' => 'Iniciar traducción',
        'requested' => 'Traducción iniciada',
        'requested_body' => 'Descript está trabajando en: :languages. Traducir lleva unos minutos — usa Comprobar progreso.',
        'nothing_new' => 'Nada nuevo que traducir',
        'nothing_new_body' => 'Todos los idiomas elegidos ya están traducidos o en curso.',
        'check' => 'Comprobar progreso',
        'checked' => 'Progreso comprobado',
        'checked_body' => ':done terminados, :running en curso, :failed con error.',
        'option' => ':language — :status',
    ],

    'status' => [
        'pending' => 'en espera',
        'translating' => 'traduciendo',
        'exporting' => 'guardando',
        'done' => 'listo',
        'failed' => 'error — márcalo para reintentar',
    ],

    'errors' => [
        'unreachable' => 'No se pudo contactar con Descript. Inténtalo de nuevo en unos minutos.',
        'out_of_credits' => 'El plan de Descript se ha quedado sin créditos de IA o minutos de medios.',
        'auth' => 'Descript rechazó el token de la API. Revisa DESCRIPT_API_TOKEN en el servidor.',
        'busy' => 'Descript está ocupado. Se volverá a intentar en la próxima comprobación.',
        'unavailable' => 'Descript tiene problemas ahora mismo. Se volverá a intentar en la próxima comprobación.',
        'rejected' => 'Descript rechazó la solicitud.',
        'translation_failed' => 'Descript no pudo traducir este vídeo.',
        'composition_not_found' => 'Descript terminó, pero no se pudo identificar su versión traducida.',
        'no_subtitles' => 'Descript terminó, pero los subtítulos traducidos llegaron vacíos.',
        'file_missing' => 'El archivo de vídeo subido no está en el almacenamiento.',
        'import_failed' => 'Descript no pudo importar este vídeo.',
    ],
];
