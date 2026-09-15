<?php

// Spanish. Keys mirror lang/en/admin_people.php — see the note there.

return [
    'users' => [
        'password' => 'Contraseña',
        'password_help' => 'Déjalo vacío para mantener la contraseña actual al editar.',
        'company_help' => 'Empresa socia a la que pertenece este usuario (déjalo vacío para administradores).',
        'role' => 'Rol',
        'role_help' => 'Los administradores gestionan la plataforma. Los creadores gestionan solo la formación de sus propios productos. Los estudiantes hacen los cursos.',
        'products' => 'Productos / módulos',
        'products_help' => 'Los productos cuya formación gestiona este creador. No puede ver los cursos de ningún otro producto.',
        'product_filter' => 'Producto / módulo',
        'all_users' => 'Todos los usuarios',
        'admins' => 'Administradores',
        'creators' => 'Creadores',
        'learners' => 'Estudiantes',
        'permissions' => 'Permisos adicionales',
        'permissions_help' => 'Se conceden por cuenta. No están incluidos por defecto en los roles Administrador o Creador.',
        'manage_languages' => 'Gestionar idiomas',
        'manage_translations' => 'Gestionar traducciones',
        'lessons_done' => 'Lecciones hechas',
        'last_login' => 'Último acceso',
        'export' => 'Exportar progreso de estudiantes',
        'access_link' => 'Enlace de acceso',
        'access_link_heading' => 'Enlace de acceso personal',
        'copy_link' => 'Copiar enlace',
        'copied' => '¡Copiado!',
        'access_link_help' => 'Envía este enlace al usuario. Al abrirlo inicia sesión sin contraseña, y su progreso se guarda en esta cuenta.',
        'csv' => [
            'name' => 'Nombre',
            'email' => 'Correo electrónico',
            'partner' => 'Socio',
            'lessons_completed' => 'Lecciones completadas',
            'certificates' => 'Certificados',
            'last_activity' => 'Última actividad',
            'last_login' => 'Último acceso',
            'joined' => 'Alta',
        ],
    ],

    'tabs' => [
        'started' => 'Iniciado',
        'completed' => 'Completada',
        'action' => 'Acción',
        'details' => 'Detalles',
    ],

    'companies' => [
        'name' => 'Nombre de la empresa',
        'region' => 'Región',
        'region_help' => 'p. ej. EMEA, LATAM, CIS',
        'industry' => 'Sector',
        'industry_help' => 'p. ej. Logística, Construcción',
        'members' => 'Miembros',
        'certified' => 'Certificados',
        'certified_tip' => 'Estudiantes con al menos un certificado válido, sobre el total de miembros.',
    ],
];
