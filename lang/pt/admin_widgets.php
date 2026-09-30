<?php

// Portuguese (Brazil). Keys mirror lang/en/admin_widgets.php — see the note there.

return [
    'filters' => [
        'heading' => 'Filtros do painel',
        'description' => 'Aplicados à atividade e aos resultados; limpe um campo para incluir tudo.',
        'start_date' => 'De',
        'end_date' => 'Até',
        'partner' => 'Parceiro',
        'all_partners' => 'Todos os parceiros',
        'product' => 'Produto',
        'all_products' => 'Todos os produtos',
        'course' => 'Curso',
        'all_courses' => 'Todos os cursos',
    ],

    'overview' => [
        'students' => 'Alunos',
        'students_help' => 'Contas de parceiros',
        'active' => 'Alunos ativos',
        'active_help' => ':percent% tiveram atividade no período selecionado',
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

    'creator' => [
        'status' => ':published publicados · :drafts rascunhos · :archived arquivados',
    ],

    'companies' => [
        'heading' => 'Engajamento dos parceiros',
        'description' => 'Alcance e resultados dos alunos no período selecionado.',
        'learners' => 'Alunos',
        'active' => 'Ativos',
        'completions' => 'Aulas concluídas',
        'certificates' => 'Certificados',
        'last_activity' => 'Última atividade',
        'open' => 'Abrir parceiro',
        'empty' => 'Nenhum parceiro corresponde a estes filtros',
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
        'description' => 'Alunos ativos únicos e aulas concluídas por dia no período selecionado.',
        'active_learners' => 'Alunos ativos',
        'lessons_finished' => 'Aulas concluídas',
    ],

    'journey' => [
        'heading' => 'Jornada do aluno',
        'description' => 'Alunos únicos registrados em cada etapa no período selecionado.',
        'learners' => 'Alunos',
        'course_opened' => 'Abriu um curso',
        'lesson_opened' => 'Abriu uma aula',
        'lesson_completed' => 'Concluiu uma aula',
        'course_completed' => 'Concluiu um curso',
        'certified' => 'Obteve um certificado',
    ],

    'resources' => [
        'heading' => 'Engajamento com recursos',
        'description' => 'Ações de parceiros conectados no período selecionado.',
        'opens' => 'Ações',
        'case_studies' => 'Estudos de caso abertos',
        'tutorials' => 'Tutoriais abertos',
        'webinars' => 'Webinars abertos',
        'joins' => 'Entradas em webinars',
        'recordings' => 'Gravações abertas',
    ],

    'opened' => [
        'heading' => 'Cursos mais abertos',
        'description' => 'Vezes que os alunos abriram cada curso no período selecionado.',
        'times_opened' => 'Vezes aberto',
        'removed_course' => 'Curso removido',
    ],
];
