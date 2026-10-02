<?php

/*
 * Translating lesson videos with Descript. Keys mirror in every language (see
 * StudentSiteTranslationTest). See docs/descript-integration.md.
 */

return [
    'action' => [
        'button' => 'Traduzir vídeo',
        'heading' => 'Traduzir o vídeo com o Descript',
        'description' => 'O Descript traduz o que é dito no vídeo. Cada idioma é traduzido uma única vez e guardado aqui, então pedir de novo nunca gasta créditos duas vezes. Só é possível enviar vídeos enviados por upload, não links do YouTube.',
        'video' => 'Vídeo',
        'languages' => 'Idiomas',
        'languages_help' => 'Os idiomas já traduzidos ou em andamento aparecem, mas não podem ser enviados de novo.',
        'confirmation' => 'Compreendo que isto envia o vídeo carregado selecionado para o Descript e pode consumir minutos de multimédia e créditos de IA.',
        'confirmation_required' => 'Confirme que pretende enviar este vídeo para o Descript e utilizar créditos.',
        'submit' => 'Iniciar tradução',
        'requested' => 'Tradução iniciada',
        'requested_body' => 'O Descript está trabalhando em: :languages. A tradução leva alguns minutos — use Verificar andamento.',
        'nothing_new' => 'Nada novo para traduzir',
        'nothing_new_body' => 'Todos os idiomas escolhidos já estão traduzidos ou em andamento.',
        'check' => 'Verificar andamento',
        'checked' => 'Andamento verificado',
        'checked_body' => ':done concluídos, :running em andamento, :failed com erro.',
        'option' => ':language — :status',
    ],

    'status' => [
        'pending' => 'aguardando',
        'translating' => 'traduzindo',
        'exporting' => 'salvando',
        'done' => 'concluído',
        'failed' => 'erro — marque para tentar de novo',
    ],

    'errors' => [
        'unreachable' => 'Não foi possível contatar o Descript. Tente de novo em alguns minutos.',
        'out_of_credits' => 'O plano do Descript ficou sem créditos de IA ou minutos de mídia.',
        'auth' => 'O Descript recusou o token da API. Verifique DESCRIPT_API_TOKEN no servidor.',
        'busy' => 'O Descript está ocupado. Haverá uma nova tentativa na próxima verificação.',
        'unavailable' => 'O Descript está com problemas agora. Haverá uma nova tentativa na próxima verificação.',
        'rejected' => 'O Descript recusou o pedido.',
        'translation_failed' => 'O Descript não conseguiu traduzir este vídeo.',
        'composition_not_found' => 'O Descript terminou, mas a versão traduzida não pôde ser identificada.',
        'file_missing' => 'O arquivo de vídeo enviado não está no armazenamento.',
        'import_failed' => 'O Descript não conseguiu importar este vídeo.',
    ],
];
