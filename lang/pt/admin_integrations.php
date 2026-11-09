<?php

// Configurações → Integrações. Enviado com o código; uma linha da tabela
// translations com a mesma chave substitui uma linha.

return [
    'groups' => [
        'text' => 'Tradução de texto',
        'text_help' => 'Gera rascunhos de tradução de cursos, aulas e testes na janela Traduzir. Administradores podem usar imediatamente; qualquer outra pessoa precisa da permissão em Pessoas → Usuários → Permissões extras.',
        'video' => 'Tradução de vídeo',
        'video_help' => 'Traduz a fala dos vídeos enviados às aulas. Todos, inclusive administradores, precisam da permissão em Pessoas → Usuários → Permissões extras.',
    ],
    'status' => [
        'on' => 'Ativado',
        'off' => 'Desativado — token salvo',
        'not_connected' => 'Não conectado',
    ],
    'description' => [
        'descript' => 'Traduz a fala dos vídeos de aula enviados (botão Traduzir vídeo de uma aula). Cada vídeo é enviado uma única vez ao Descript e cada tradução fica guardada aqui. Usa seus minutos de mídia e créditos de IA do Descript.',
        'deepl' => 'Gera rascunhos de tradução do texto de cursos e aulas na janela Traduzir. O texto dos campos vazios só é enviado ao DeepL quando alguém clica no botão e confirma. Uma chave terminada em :fx é uma chave DeepL API Free.',
        'chatgpt' => 'Gera rascunhos de tradução do texto de cursos e aulas na janela Traduzir. O texto só é enviado ao ChatGPT quando alguém clica no botão e confirma.',
        'deepseek' => 'Gera rascunhos de tradução do texto de cursos e aulas na janela Traduzir. O texto só é enviado ao DeepSeek quando alguém clica no botão e confirma.',
    ],
    'enabled' => 'Ativar o :provider',
    'enabled_help' => 'Precisa de um token. Desativar oculta os botões, mas mantém o token.',
    'token' => 'Token de API',
    'token_saved' => 'Há um token salvo — deixe em branco para mantê-lo',
    'token_empty' => 'Cole o token de API',
    'token_help' => 'Guardado criptografado e nunca mais exibido. Crie uma chave com limite de gasto no site do provedor.',
    'clear_token' => 'Remover o token salvo',
    'model' => 'Modelo',
    'model_help' => 'Deixe em branco para usar :model.',
    'needs_token' => 'Adicione um token de API antes de ativar o :provider',
    'save' => 'Salvar',
    'saved' => 'Integrações salvas',
    'generation' => [
        'heading' => 'Geração de cursos',
        'help' => 'Usa o token do ChatGPT ou do DeepSeek salvo acima. Não há uma chave separada. Os preços do orçamento são estimativas.',
        'default_provider' => 'Provedor padrão',
        'default_provider_help' => 'Já vem escolhido em Gerar curso. Um provedor desligado não pode ser usado até ser ativado.',
        'none' => 'Sem padrão',
        'fallback' => 'Permitir o outro provedor como reserva',
        'fallback_help' => 'Somente quando isto está ligado e quem gera marca a opção. As notas então vão para o outro provedor. Um trabalho não troca de provedor sozinho.',
        'daily_limit' => 'Gerações diárias por pessoa',
        'daily_limit_help' => 'Deixe vazio se não houver limite. Conta cada resposta do provedor, inclusive uma que não pôde ser salva.',
        'budget' => 'Orçamento mensal (USD)',
        'budget_help' => 'Deixe vazio se não houver teto. Estimativa pelo preço dos tokens, não uma fatura do provedor.',
    ],
];
