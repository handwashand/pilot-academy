<?php

// Settings → Integrations. Shipped with the code; a row in the translations table
// with the same key overrides a line.

return [
    'groups' => [
        'text' => 'Tradução de texto',
        'text_help' => 'Qualquer provedor ativado aqui gera rascunhos de tradução em todos os lugares onde a academia tem Traduzir: cursos, aulas, testes, estudos de caso, tutoriais e webinars. Administradores podem usar imediatamente; qualquer outra pessoa precisa da permissão em Pessoas → Usuários → Permissões extras.',
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
        'deepl' => 'Gera rascunhos de tradução onde a academia tem Traduzir. O texto das caixas vazias só é enviado ao DeepL quando alguém clica no botão e confirma. Uma chave que termina em :fx é uma chave DeepL API Free.',
        'chatgpt' => 'Gera rascunhos de tradução onde a academia tem Traduzir. O texto só é enviado ao ChatGPT quando alguém clica no botão e confirma.',
        'deepseek' => 'Gera rascunhos de tradução onde a academia tem Traduzir. O texto só é enviado ao DeepSeek quando alguém clica no botão e confirma.',
        'claude' => 'Gera rascunhos de tradução onde a academia tem Traduzir. O texto só é enviado ao Claude quando alguém clica no botão e confirma.',
    ],
    'on' => 'Ativado',
    'token' => 'Token',
    'token_saved' => '••••:ending salvo — digite um novo para substituí-lo',
    'token_empty' => 'Cole um token',
    'token_help' => 'Guardado criptografado e nunca mais exibido; só os quatro últimos caracteres aparecem. Deixe a caixa vazia para manter o salvo. Crie uma chave com limite de gasto no site do provedor.',
    'scope' => 'Quem pode usar',
    'scope_help' => 'Para um token digitado agora. Com a caixa vazia, muda quem pode usar o token compartilhado salvo. Só eu: só você usa, antes de qualquer token compartilhado.',
    'scopes' => [
        'personal' => 'Só eu',
        'admins' => 'Todos os administradores',
        'everyone' => 'Todos com a permissão',
    ],
    'shared_line' => ':scope · adicionado por :name',
    'own_line' => 'Seu próprio token ••••:ending, usado primeiro para você',
    'remove' => 'Remover',
    'remove_shared_heading' => 'Remover o token compartilhado do :provider?',
    'remove_own_heading' => 'Remover seu próprio token do :provider?',
    'remove_description' => 'Quem o usa perde os botões do :provider até que um token seja adicionado novamente.',
    'removed' => 'Token removido',
    'added_by_unknown' => 'uma conta excluída',
    'model' => 'Modelo',
    'model_help' => ':model (padrão)',
    'model_warning' => ':model custa cerca de :times vezes mais por tradução que o padrão, :default. Traduzir um curso inteiro consome muitos tokens.',
    'needs_token' => 'Adicione um token de API antes de ativar o :provider',
    'save' => 'Salvar',
    'saved' => 'Integrações salvas',
];
