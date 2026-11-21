<?php

// Portuguese (Brazil). Keys mirror lang/en/admin_settings.php — see the note there.

return [
    'languages' => [
        'code' => 'Código ISO 639-1',
        'native_name' => 'Nome nativo',
        'direction' => 'Direção',
        'position' => 'Posição',
        'active' => 'Ativo',
        'default' => 'Idioma padrão',
        'default_help' => 'Alterar isto muda o idioma reserva para todos.',
        'language' => 'Idioma',
        'code_column' => 'Código',
        'default_column' => 'Padrão',
        'coverage' => 'Cobertura',
        'use' => 'Usar este idioma',
    ],

    'translations' => [
        'key' => 'Chave',
        'language' => 'Idioma',
        'english' => 'Inglês',
        'shipped' => 'Texto incluído',
        'shipped_help' => 'O que os alunos veem neste idioma quando o campo abaixo está vazio.',
        'none_shipped' => '— nada incluído —',
        'correction' => 'Correção',
        'correction_help' => 'Deixe vazio para usar o texto incluído. Mantenha como estão as palavras que começam com dois-pontos, como :name, e mantenha o | entre as formas de uma contagem ("1 aula|2 aulas").',
        'notes' => 'Notas',
        'text' => 'Texto que os alunos veem',
        'missing' => 'faltando',
        'module' => 'Módulo',
        'corrected' => 'Corrigido',
        'correct' => 'Corrigir',
        'correct_in' => 'Corrigir a linha em :language',
        'click_hint' => 'Uma linha por chave, em todos os idiomas. Clique em qualquer célula para corrigir esse idioma — os alunos veem a mudança na hora.',
        'save_correction' => 'Salvar a correção',
        'states' => [
            'corrected' => 'Corrigido',
            'shipped' => 'Incluído',
            'missing' => 'Faltando',
        ],
    ],

    // Settings → Certificate (App\Filament\Pages\SettingsCertificate).
    'certificate' => [
        'nav' => 'Certificado',
        'subheading' => 'O certificado padrão usado por todos os cursos. Envie um fundo para substituir o design integrado; um curso com fundo próprio o mantém. Remova a imagem para voltar ao design integrado.',
        'background' => 'Fundo padrão do certificado',
        'background_help' => 'Fundo de página inteira A4 paisagem (≈3508×2480 px). Deixe vazio para usar o layout com moldura embutido. Nome, curso, data, número e QR são sobrepostos automaticamente.',
    ],

    'overlay' => [
        'profile' => 'Perfil',
        'photo' => 'Foto',
        'name' => 'Nome',
        'email' => 'E-mail',
        'password' => 'Nova senha',
        'password_confirmation' => 'Confirmar senha',
        'current_password' => 'Senha atual',
        'current_password_help' => 'Necessária para alterar seu e-mail ou sua senha.',
        'save' => 'Salvar configurações',
        'saved' => 'Configurações salvas',
    ],
];
