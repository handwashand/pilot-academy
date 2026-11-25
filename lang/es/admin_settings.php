<?php

// Spanish. Keys mirror lang/en/admin_settings.php — see the note there.

return [
    'languages' => [
        'code' => 'Código ISO 639-1',
        'native_name' => 'Nombre nativo',
        'direction' => 'Dirección',
        'position' => 'Posición',
        'active' => 'Activo',
        'default' => 'Idioma predeterminado',
        'default_help' => 'Cambiarlo mueve el idioma de reserva para todos.',
        'language' => 'Idioma',
        'code_column' => 'Código',
        'default_column' => 'Predeterminado',
        'coverage' => 'Cobertura',
        'use' => 'Usar este idioma',
    ],

    'translations' => [
        'key' => 'Clave',
        'language' => 'Idioma',
        'english' => 'Inglés',
        'shipped' => 'Texto incluido',
        'shipped_help' => 'Lo que ven los estudiantes en este idioma cuando el campo de abajo está vacío.',
        'none_shipped' => '— no incluido —',
        'correction' => 'Corrección',
        'correction_help' => 'Déjalo vacío para usar el texto incluido. Mantén tal cual las palabras que empiezan por dos puntos, como :name, y conserva la | entre las formas de un número («1 lección|2 lecciones»).',
        'notes' => 'Notas',
        'text' => 'Texto que ven los estudiantes',
        'missing' => 'falta',
        'module' => 'Módulo',
        'corrected' => 'Corregido',
        'correct' => 'Corregir',
        'correct_in' => 'Corregir la línea en :language',
        'click_hint' => 'Una línea por clave, en todos los idiomas. Haz clic en cualquier celda para corregir ese idioma: los estudiantes ven el cambio enseguida.',
        'save_correction' => 'Guardar la corrección',
        'states' => [
            'corrected' => 'Corregido',
            'shipped' => 'Incluido',
            'missing' => 'Falta',
        ],
    ],

    // Settings → Certificate (App\Filament\Pages\SettingsCertificate).
    'certificate' => [
        'nav' => 'Certificado',
        'subheading' => 'El certificado estándar que usan todos los cursos. Sube un fondo para reemplazar el diseño integrado; un curso con su propio fondo lo conserva. Quita la imagen para volver al diseño integrado.',
        'background' => 'Fondo estándar del certificado',
        'background_help' => 'Fondo a página completa A4 horizontal (≈3508×2480 px). Déjalo vacío para usar el diseño con marco integrado. El nombre, el curso, la fecha, el número y el QR se superponen automáticamente.',
    ],

    'overlay' => [
        'profile' => 'Perfil',
        'photo' => 'Foto',
        'name' => 'Nombre',
        'email' => 'Correo electrónico',
        'password' => 'Nueva contraseña',
        'password_confirmation' => 'Confirmar contraseña',
        'current_password' => 'Contraseña actual',
        'current_password_help' => 'Necesaria para cambiar tu correo o tu contraseña.',
        'save' => 'Guardar ajustes',
        'saved' => 'Ajustes guardados',
    ],
];
