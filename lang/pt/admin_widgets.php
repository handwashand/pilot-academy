<?php

// Portuguese (Brazil). Keys mirror lang/en/admin_widgets.php — see the note there.

return [
    'overview' => [
        'students' => 'Alunos',
        'students_help' => 'Contas de parceiros',
        'active' => 'Alunos ativos',
        'active_help' => ':percent% começaram pelo menos uma aula',
        'completions' => 'Aulas concluídas',
        'completions_help' => 'Entre todos os alunos',
        'published_courses' => 'Cursos publicados',
        'published_courses_help' => ':count no total, incluindo rascunhos',
        'published_lessons' => 'Aulas publicadas',
        'published_lessons_help' => 'Disponíveis para estudar',
        'certificates' => 'Certificados emitidos',
        'no_passes' => 'Nenhuma aprovação ainda',
        'average_score' => 'Pontuação média :score%',
    ],

    'companies' => [
        'heading' => 'Progresso por empresa parceira',
        'description' => 'Proporção de todas as aulas publicadas concluídas pelos alunos de cada empresa.',
        'dataset' => '% das aulas publicadas concluídas',
    ],

    'certificates' => [
        'heading' => 'Certificados emitidos por curso',
    ],

    'stalled' => [
        'heading' => 'Alunos que pararam',
        'description' => 'Começaram um curso, não concluíram nada nos últimos :days dias e ainda não têm certificado.',
        'lessons_done' => 'Aulas concluídas',
        'last_activity' => 'Última atividade',
        'reminded' => 'Lembrado',
        'remind' => 'Enviar lembrete',
        'remind_heading' => 'Enviar um lembrete',
        'remind_description' => 'Envia para :email um link pessoal direto para a próxima aula.',
        'send_it' => 'Enviar',
        'sent' => 'Lembrete enviado para :name',
        'not_sent' => 'Não enviado',
        'cooldown' => 'Já foi lembrado nos últimos :days dias.',
        'open' => 'Abrir',
        'remind_all' => 'Enviar lembretes',
        'remind_all_description' => 'Cada aluno recebe um link pessoal para a próxima aula. Quem já foi lembrado nos últimos :days dias é ignorado.',
        'sent_count' => ':count lembrete enviado|:count lembretes enviados',
        'skipped_count' => 'Ignorados: :count',
        'empty' => 'Ninguém parou',
        'empty_description' => 'Todo aluno que começou um curso ainda está nele ou já terminou.',
    ],

    'hardest' => [
        'heading' => 'Aulas mais difíceis para os alunos',
        'description' => 'Tentativas avaliadas dos alunos, pior taxa de aprovação primeiro. Uma aula difícil muitas vezes é uma pergunta confusa.',
        'failed' => 'Reprovações',
        'fail_rate' => 'Taxa de reprovação',
        'review' => 'Revisar perguntas',
        'empty' => 'Nenhuma dificuldade para mostrar',
        'empty_description' => 'Quando os alunos fizerem algumas tentativas avaliadas, as aulas mais difíceis aparecerão aqui.',
    ],

    'activity' => [
        'heading' => 'Atividade dos alunos',
        'description' => 'Aulas concluídas e acessos por dia, apenas alunos.',
        'lessons_finished' => 'Aulas concluídas',
        'sign_ins' => 'Acessos',
    ],

    'opened' => [
        'heading' => 'Cursos mais abertos',
        'description' => 'Vezes que os alunos abriram cada curso nos últimos :days dias.',
        'times_opened' => 'Vezes aberto',
    ],
];
