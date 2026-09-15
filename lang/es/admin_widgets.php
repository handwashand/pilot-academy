<?php

// Spanish. Keys mirror lang/en/admin_widgets.php — see the note there.

return [
    'overview' => [
        'students' => 'Estudiantes',
        'students_help' => 'Cuentas de socios',
        'active' => 'Estudiantes activos',
        'active_help' => ':percent% empezó al menos una lección',
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

    'companies' => [
        'heading' => 'Progreso por empresa socia',
        'description' => 'Proporción de todas las lecciones publicadas completadas por los estudiantes de cada empresa.',
        'dataset' => '% de lecciones publicadas completadas',
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
        'description' => 'Lecciones terminadas e inicios de sesión por día, solo estudiantes.',
        'lessons_finished' => 'Lecciones terminadas',
        'sign_ins' => 'Inicios de sesión',
    ],

    'opened' => [
        'heading' => 'Cursos más abiertos',
        'description' => 'Veces que los estudiantes abrieron cada curso en los últimos :days días.',
        'times_opened' => 'Veces abierto',
    ],
];
