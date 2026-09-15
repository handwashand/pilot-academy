<?php

// Shipped with the code — see the note in lang/en/academy.php. A row in
// the translations table with the same key overrides a line.

return [
    'common' => [
        'thanks' => 'Obrigado,',
    ],

    'course_reminder' => [
        'subject' => 'Continue de onde parou - Pilot Academy',
        'heading' => 'Ainda com a gente, :name?',
        'intro' => 'Você começou na Pilot Academy e falta pouco para retomar.',
        'intro_progress' => 'Você começou na Pilot Academy (:count aula concluída até agora) e falta pouco para retomar.|Você começou na Pilot Academy (:count aulas concluídas até agora) e falta pouco para retomar.',
        'button' => 'Continuar de onde parou',
        'personal' => 'Esse link faz o login direto e leva você à próxima aula, então não há senha para lembrar. Ele é pessoal — por favor, não o encaminhe.',
    ],

    'certificate_issued' => [
        'subject' => 'Seu certificado de :course',
        'heading' => 'Parabéns, :name!',
        'passed' => 'Você foi aprovado no teste final de **:course** com pontuação de **:score%** e conquistou seu certificado.',
        'attached' => 'Seu certificado está anexado a este e-mail em PDF. O número único dele é **:number**.',
        'button' => 'Verificar certificado',
        'anyone' => 'Qualquer pessoa pode confirmar que este certificado é autêntico no link acima.',
    ],

    'mail_check' => [
        'subject' => 'Pilot Academy: e-mail de teste',
        'heading' => 'Este é um e-mail de teste',
        'sent_by' => ':name enviou pelo menu **Configurações → E-mail** no painel de administração da Pilot Academy em :date.',
        'works' => 'Se você está lendo isto, a academia consegue enviar e-mails: certificados e lembretes chegarão aos alunos.',
        'links' => 'Os links nos e-mails da academia começam com :url. Se esse não é o endereço usado para abrir a academia, o logotipo e os links dos e-mails de certificado vão quebrar.',
    ],

    'certificate_pdf' => [
        'title' => 'Certificado de conclusão',
        'certifies' => 'Certificamos que',
        'completed' => 'concluiu com êxito o curso',
        'scan' => 'Escaneie para verificar',
        'number' => 'Certificado nº',
        'issued' => 'Emitido em',
    ],
];
