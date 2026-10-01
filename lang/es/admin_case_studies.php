<?php

// Las claves reflejan lang/en/admin_case_studies.php.

return [
    'sections' => [
        'main' => 'Caso práctico',
        'privacy' => 'Privacidad y verificación',
        'privacy_hint' => 'De forma predeterminada, no incluyas datos que identifiquen al cliente. Guarda una nota de verificación para cualquier afirmación cuantificada.',
        'media' => 'Medios',
        'media_hint' => 'Usa solo diagramas, capturas o adjuntos depurados. Evita nombres de clientes, ubicaciones, datos en vivo, credenciales e imágenes identificables.',
        'study' => 'Secciones del caso',
        'related' => 'Contenido relacionado',
    ],

    'form' => [
        'step_images' => 'Imágenes para «:step»',
        'step_images_help' => 'Pega una captura, suelta un archivo aquí o elige uno. Aparecen bajo este paso en la página del socio. Depúralas antes: sin nombres de clientes, ubicaciones exactas, credenciales ni datos de vehículos reales.',
        'images_help' => 'Añade una imagen a cualquier paso: el clip de la barra, o pega o arrastra un archivo directamente. Depura las capturas: sin nombres de clientes, ubicaciones ni datos reales.',
        'short_problem' => 'Descripción breve del problema',
        'industry' => 'Sector',
        'features' => 'Funciones de Pilot relevantes',
        'add_feature' => 'Añadir una función',
        'difficulty' => 'Dificultad',
        'implementation_time' => 'Tiempo estimado de implementación',
        'anonymized' => 'Anonimizado',
        'customer_approved' => 'Aprobado por el cliente',
        'source_note' => 'Fuente o nota de verificación',
        'performance_note' => 'Nota sobre afirmaciones cuantificadas',
        'performance_note_hint' => 'Obligatorio si el caso menciona ahorros medidos, reducciones, disponibilidad u otros resultados cuantificados.',
        'cover' => 'Imagen de portada',
        'diagram' => 'Diagrama o captura de pantalla',
        'related_lessons' => 'Lecciones relacionadas de la Academia',
        'links' => 'Enlaces a la documentación',
        'add_link' => 'Añadir enlace',
        'link_url' => 'URL',
        'own_product' => 'Elige uno de los productos asignados a ti.',
        'before_publishing' => 'Completa las secciones obligatorias y la nota de fuente antes de publicar.',
    ],

    'table' => [
        'approved' => 'Aprobado',
        'preview' => 'Vista previa',
    ],

    'notify' => [
        'published' => 'Caso práctico publicado',
        'drafted' => 'Caso práctico devuelto a borrador',
        'drafted_many' => 'Casos prácticos devueltos a borrador',
        'published_count' => ':count caso práctico publicado|:count casos prácticos publicados',
        'skipped_count' => ':count caso práctico omitido|:count casos prácticos omitidos',
        'incomplete' => 'Completa primero las secciones obligatorias',
        'incomplete_body' => 'Antes de publicar se requieren el título, el resumen, el escenario, el resultado deseado, la configuración, la verificación y la nota de fuente.',
        'skipped_body' => 'A los registros omitidos les faltan secciones obligatorias o la nota de fuente.',
    ],
];
