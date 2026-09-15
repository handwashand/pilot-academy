<?php

// Spanish. Keys mirror lang/en/admin_results.php — see the note there.

return [
    'certificates' => [
        'export' => 'Exportar CSV',
        'resend' => 'Reenviar correo',
        'resend_description' => 'Enviar el certificado por correo a :email.',
        'emailed' => 'Certificado enviado',
        'regenerate' => 'Regenerar PDF',
        'regenerated' => 'PDF regenerado',
        'edit_name' => 'Editar nombre',
        'edit_name_heading' => 'Corregir el nombre de este certificado',
        'edit_name_description' => 'El PDF se vuelve a generar con el nuevo nombre y la página pública de verificación lo muestra de inmediato. El número, la fecha y la puntuación no cambian. Usa después Reenviar correo si el estudiante debe recibir la copia corregida.',
        'name_on_certificate' => 'Nombre en el certificado',
        'update_profile' => 'Usar también este nombre en los futuros certificados del estudiante',
        'update_profile_help' => 'Lo guarda como nombre de certificado en su perfil, donde también puede cambiarlo.',
        'name_corrected' => 'Nombre corregido y PDF regenerado',
        'revoke' => 'Revocar',
        'revoke_description' => 'El certificado aparecerá como revocado en la página pública de verificación. Los certificados son permanentes: úsalo solo si se emitió por error.',
        'revoked' => 'Certificado revocado',
        'restore' => 'Restaurar',
        'restored' => 'Certificado restaurado',
        'score_percent' => 'Puntuación %',
    ],

    'attempts' => [
        'quiz' => 'Cuestionario',
        'not_submitted' => 'Sin enviar',
        'out_of_attempts' => 'Sin intentos, no aprobado',
        'quiz_type' => 'Tipo de cuestionario',
        'final_quizzes' => 'Exámenes finales',
        'lesson_checks' => 'Comprobaciones de las lecciones',
        'grant' => 'Conceder otro intento',
        'grant_description' => ':name recibe un intento más en :quiz. No afecta a nadie más: los intentos máximos siguen igual para los demás.',
        'reason' => 'Motivo (opcional)',
        'reason_placeholder' => 'Por ejemplo: se cortó la conexión durante el cuestionario',
        'granted' => 'Intento adicional concedido',
        'granted_body' => ':name puede intentar :quiz una vez más.',
        'empty' => 'Aún no hay intentos',
        'empty_description' => 'Se registran intentos de los exámenes finales y de las comprobaciones de lección que tienen límite de tiempo o de intentos.',
        'final_quiz' => 'el examen final',
        'a_lesson' => 'una lección',
    ],

    'feedback' => [
        'empty_description' => 'Se pregunta a los estudiantes su opinión cuando terminan un curso.',
    ],
];
