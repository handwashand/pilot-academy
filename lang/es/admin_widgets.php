<?php

// Spanish. Keys mirror lang/en/admin_widgets.php — see the note there.

return [
    'filters' => [
        'heading' => 'Filtros del panel',
        'description' => 'Se aplican a la actividad y los resultados; vacía un campo para incluir todo.',
        'start_date' => 'Desde',
        'end_date' => 'Hasta',
        'partner' => 'Socio',
        'all_partners' => 'Todos los socios',
        'product' => 'Producto',
        'all_products' => 'Todos los productos',
        'course' => 'Curso',
        'all_courses' => 'Todos los cursos',
    ],

    'overview' => [
        'students' => 'Estudiantes',
        'students_help' => 'Cuentas de socios',
        'active' => 'Estudiantes activos',
        'active_help' => ':percent% tuvo actividad en el período seleccionado',
        'completions' => 'Lecciones completadas',
        'completions_help' => 'Entre todos los estudiantes',
        'published_courses' => 'Cursos publicados',
        'published_courses_help' => ':count en total, borradores incluidos',
        'published_lessons' => 'Lecciones publicadas',
        'published_lessons_help' => 'Disponibles para aprender',
        'certificates' => 'Certificados emitidos',
        'no_passes' => 'Aún no hay aprobados',
        'average_score' => 'Puntuación media :score%',
    ],

    'creator' => [
        'status' => ':published publicados · :drafts borradores · :archived archivados',
    ],

    'companies' => [
        'heading' => 'Participación de socios',
        'description' => 'Alcance y resultados de estudiantes en el período seleccionado.',
        'learners' => 'Estudiantes',
        'active' => 'Activos',
        'completions' => 'Lecciones terminadas',
        'certificates' => 'Certificados',
        'last_activity' => 'Última actividad',
        'open' => 'Abrir socio',
        'empty' => 'Ningún socio coincide con estos filtros',
    ],

    'certificates' => [
        'heading' => 'Certificados emitidos por curso',
    ],

    'stalled' => [
        'heading' => 'Estudiantes que se han quedado parados',
        'description' => 'Empezaron un curso, no completaron nada en los últimos :days días y aún no tienen certificado.',
        'lessons_done' => 'Lecciones hechas',
        'last_activity' => 'Última actividad',
        'reminded' => 'Recordado',
        'remind' => 'Enviar recordatorio',
        'remind_heading' => 'Enviar un recordatorio',
        'remind_description' => 'Envía a :email un enlace personal directo a su siguiente lección.',
        'send_it' => 'Enviar',
        'sent' => 'Recordatorio enviado a :name',
        'not_sent' => 'No enviado',
        'cooldown' => 'Ya se le recordó en los últimos :days días.',
        'open' => 'Abrir',
        'remind_all' => 'Enviar recordatorios',
        'remind_all_description' => 'Cada estudiante recibe un enlace personal a su siguiente lección. Se omite a quien ya se recordó en los últimos :days días.',
        'sent_count' => ':count recordatorio enviado|:count recordatorios enviados',
        'skipped_count' => 'Omitidos: :count',
        'empty' => 'Nadie se ha quedado parado',
        'empty_description' => 'Todos los que empezaron un curso lo siguen haciendo o ya lo terminaron.',
    ],

    'hardest' => [
        'heading' => 'Lecciones que más cuestan',
        'description' => 'Intentos calificados de estudiantes, peor tasa de aprobados primero. Una lección difícil suele ser una pregunta poco clara.',
        'failed' => 'Suspendidos',
        'fail_rate' => 'Tasa de suspensos',
        'review' => 'Revisar preguntas',
        'empty' => 'No hay dificultades que mostrar',
        'empty_description' => 'Cuando los estudiantes hagan algunos intentos calificados, aquí aparecerán las lecciones más difíciles.',
    ],

    'activity' => [
        'heading' => 'Actividad de estudiantes',
        'description' => 'Estudiantes activos únicos y lecciones terminadas por día en el período seleccionado.',
        'active_learners' => 'Estudiantes activos',
        'lessons_finished' => 'Lecciones terminadas',
    ],

    'journey' => [
        'heading' => 'Recorrido del estudiante',
        'description' => 'Estudiantes únicos registrados en cada etapa durante el período seleccionado.',
        'learners' => 'Estudiantes',
        'course_opened' => 'Abrió un curso',
        'lesson_opened' => 'Abrió una lección',
        'lesson_completed' => 'Terminó una lección',
        'course_completed' => 'Terminó un curso',
        'certified' => 'Obtuvo un certificado',
    ],

    'resources' => [
        'heading' => 'Participación con recursos',
        'description' => 'Acciones de socios con sesión iniciada durante el período seleccionado.',
        'opens' => 'Acciones',
        'case_studies' => 'Casos prácticos abiertos',
        'tutorials' => 'Tutoriales abiertos',
        'webinars' => 'Seminarios abiertos',
        'joins' => 'Uniones a seminarios',
        'recordings' => 'Grabaciones abiertas',
    ],

    'opened' => [
        'heading' => 'Cursos más abiertos',
        'description' => 'Veces que los estudiantes abrieron cada curso en el período seleccionado.',
        'times_opened' => 'Veces abierto',
        'removed_course' => 'Curso eliminado',
    ],
];
