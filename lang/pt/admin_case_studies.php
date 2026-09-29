<?php

// As chaves espelham lang/en/admin_case_studies.php.

return [
    'sections' => [
        'main' => 'Estudo de caso',
        'privacy' => 'Privacidade e verificação',
        'privacy_hint' => 'Por padrão, não inclua dados que identifiquem o cliente. Guarde uma nota de verificação para qualquer afirmação quantificada.',
        'media' => 'Mídia',
        'media_hint' => 'Use apenas diagramas, capturas de tela ou anexos higienizados. Evite nomes de clientes, localizações, dados em tempo real, credenciais e imagens identificáveis.',
        'study' => 'Seções do estudo',
        'related' => 'Conteúdo relacionado',
    ],

    'form' => [
        'short_problem' => 'Descrição breve do problema',
        'industry' => 'Setor',
        'features' => 'Funções do Pilot relevantes',
        'add_feature' => 'Adicionar uma função',
        'difficulty' => 'Dificuldade',
        'implementation_time' => 'Tempo estimado de implementação',
        'anonymized' => 'Anonimizado',
        'customer_approved' => 'Aprovado pelo cliente',
        'source_note' => 'Fonte ou nota de verificação',
        'performance_note' => 'Nota sobre afirmações quantificadas',
        'performance_note_hint' => 'Obrigatório se o estudo mencionar economia medida, reduções, disponibilidade ou outros resultados quantificados.',
        'cover' => 'Imagem de capa',
        'diagram' => 'Diagrama ou captura de tela',
        'related_lessons' => 'Aulas relacionadas da Academia',
        'links' => 'Links para a documentação',
        'add_link' => 'Adicionar link',
        'link_url' => 'URL',
        'own_product' => 'Escolha um dos produtos atribuídos a você.',
        'before_publishing' => 'Preencha as seções obrigatórias e a nota de fonte antes de publicar.',
    ],

    'table' => [
        'approved' => 'Aprovado',
        'preview' => 'Pré-visualizar',
    ],

    'notify' => [
        'published' => 'Estudo de caso publicado',
        'drafted' => 'Estudo de caso devolvido a rascunho',
        'drafted_many' => 'Estudos de caso devolvidos a rascunho',
        'published_count' => ':count estudo de caso publicado|:count estudos de caso publicados',
        'skipped_count' => ':count estudo de caso ignorado|:count estudos de caso ignorados',
        'incomplete' => 'Preencha primeiro as seções obrigatórias',
        'incomplete_body' => 'Antes de publicar são obrigatórios o título, o resumo, o cenário, o resultado desejado, a configuração, a verificação e a nota de fonte.',
        'skipped_body' => 'Nos registros ignorados faltam seções obrigatórias ou a nota de fonte.',
    ],
];
