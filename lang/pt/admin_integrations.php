<?php

// Configurações → Integrações. Enviado com o código; uma linha da tabela
// translations com a mesma chave substitui uma linha.

return [
    'subheading' => 'Ative uma integração e adicione o token de API. Administradores podem usar os tradutores de texto imediatamente; qualquer outra pessoa, e o Descript para todos, precisa da permissão marcada em Pessoas → Usuários → Permissões extras.',
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
];
