<?php

// Portuguese (Brazil). Keys mirror lang/en/admin_results.php — see the note there.

return [
    'certificates' => [
        'export' => 'Exportar CSV',
        'resend' => 'Reenviar e-mail',
        'resend_description' => 'Enviar o certificado por e-mail para :email.',
        'emailed' => 'Certificado enviado',
        'regenerate' => 'Gerar PDF novamente',
        'regenerated' => 'PDF gerado novamente',
        'edit_name' => 'Editar nome',
        'edit_name_heading' => 'Corrigir o nome neste certificado',
        'edit_name_description' => 'O PDF é reimpresso com o novo nome, e a página pública de verificação o mostra imediatamente. Número, data e pontuação continuam iguais. Use Reenviar e-mail depois se o aluno precisar receber a cópia corrigida.',
        'name_on_certificate' => 'Nome no certificado',
        'update_profile' => 'Usar este nome também nos próximos certificados do aluno',
        'update_profile_help' => 'Salva como nome do certificado no perfil dele, onde ele também pode alterá-lo.',
        'name_corrected' => 'Nome corrigido e PDF reimpresso',
        'revoke' => 'Revogar',
        'revoke_description' => 'O certificado aparecerá como revogado na página pública de verificação. Certificados são permanentes — use apenas para uma emissão incorreta.',
        'revoked' => 'Certificado revogado',
        'restore' => 'Restaurar',
        'restored' => 'Certificado restaurado',
        'score_percent' => 'Pontuação %',
    ],

    'attempts' => [
        'quiz' => 'Teste',
        'not_submitted' => 'Não enviado',
        'out_of_attempts' => 'Sem tentativas, não aprovado',
        'quiz_type' => 'Tipo de teste',
        'final_quizzes' => 'Testes finais',
        'lesson_checks' => 'Verificações das aulas',
        'grant' => 'Conceder outra tentativa',
        'grant_description' => ':name ganha mais uma tentativa em :quiz. Ninguém mais é afetado: o máximo de tentativas continua igual para os demais.',
        'reason' => 'Motivo (opcional)',
        'reason_placeholder' => 'Por exemplo: a conexão caiu durante o teste',
        'granted' => 'Tentativa extra concedida',
        'granted_body' => ':name pode tentar :quiz mais uma vez.',
        'empty' => 'Nenhuma tentativa ainda',
        'empty_description' => 'As tentativas são registradas para testes finais e para verificações de aula que têm limite de tempo ou de tentativas.',
        'final_quiz' => 'o teste final',
        'a_lesson' => 'uma aula',
    ],

    'feedback' => [
        'empty_description' => 'Os alunos são convidados a opinar quando concluem um curso.',
    ],
];
