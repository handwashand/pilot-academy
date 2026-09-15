<?php

// Shipped with the code — see the note in lang/en/academy.php. A row in
// the translations table with the same key overrides a line.

return [
    'common' => [
        'thanks' => 'Gracias,',
    ],

    'course_reminder' => [
        'subject' => 'Continúa donde lo dejaste - Pilot Academy',
        'heading' => '¿Sigues con nosotros, :name?',
        'intro' => 'Empezaste en Pilot Academy y no te queda mucho por retomar.',
        'intro_progress' => 'Empezaste en Pilot Academy (:count lección terminada hasta ahora) y no te queda mucho por retomar.|Empezaste en Pilot Academy (:count lecciones terminadas hasta ahora) y no te queda mucho por retomar.',
        'button' => 'Continuar donde lo dejaste',
        'personal' => 'Ese enlace inicia tu sesión directamente y te lleva a la siguiente lección, así que no hay contraseña que recordar. Es personal: por favor, no lo reenvíes.',
    ],

    'certificate_issued' => [
        'subject' => 'Tu certificado de :course',
        'heading' => '¡Enhorabuena, :name!',
        'passed' => 'Aprobaste el examen final de **:course** con una puntuación de **:score%** y obtuviste tu certificado.',
        'attached' => 'Tu certificado va adjunto a este correo en PDF. Su número único es **:number**.',
        'button' => 'Verificar certificado',
        'anyone' => 'Cualquiera puede confirmar que este certificado es auténtico en el enlace de arriba.',
    ],

    'mail_check' => [
        'subject' => 'Pilot Academy: correo de prueba',
        'heading' => 'Este es un correo de prueba',
        'sent_by' => ':name lo envió desde **Ajustes → Correo** en el panel de administración de Pilot Academy el :date.',
        'works' => 'Si estás leyendo esto, la academia puede enviar correos: los certificados y recordatorios llegarán a los estudiantes.',
        'links' => 'Los enlaces de los correos de la academia empiezan por :url. Si esa no es la dirección con la que se abre la academia, el logotipo y los enlaces de los correos de certificados no funcionarán.',
    ],

    'certificate_pdf' => [
        'title' => 'Certificado de finalización',
        'certifies' => 'Se certifica que',
        'completed' => 'ha completado con éxito el curso',
        'scan' => 'Escanea para verificar',
        'number' => 'Certificado n.º',
        'issued' => 'Emitido el',
    ],
];
