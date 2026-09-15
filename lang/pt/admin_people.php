<?php

// Portuguese (Brazil). Keys mirror lang/en/admin_people.php — see the note there.

return [
    'users' => [
        'password' => 'Senha',
        'password_help' => 'Deixe em branco para manter a senha atual ao editar.',
        'company_help' => 'Empresa parceira a que este usuário pertence (deixe vazio para administradores).',
        'role' => 'Função',
        'role_help' => 'Administradores gerenciam a plataforma. Criadores gerenciam apenas o treinamento dos próprios produtos. Alunos fazem os cursos.',
        'products' => 'Produtos / módulos',
        'products_help' => 'Os produtos cujo treinamento este criador gerencia. Ele não vê cursos de nenhum outro produto.',
        'product_filter' => 'Produto / módulo',
        'all_users' => 'Todos os usuários',
        'admins' => 'Administradores',
        'creators' => 'Criadores',
        'learners' => 'Alunos',
        'permissions' => 'Permissões extras',
        'permissions_help' => 'Concedidas por conta. Não estão incluídas por padrão nas funções Administrador ou Criador.',
        'manage_languages' => 'Gerenciar idiomas',
        'manage_translations' => 'Gerenciar traduções',
        'lessons_done' => 'Aulas concluídas',
        'last_login' => 'Último acesso',
        'export' => 'Exportar progresso dos alunos',
        'access_link' => 'Link de acesso',
        'access_link_heading' => 'Link de acesso pessoal',
        'copy_link' => 'Copiar link',
        'copied' => 'Copiado!',
        'access_link_help' => 'Envie este link ao usuário. Ao abri-lo, ele entra sem senha, e o progresso é salvo nesta conta.',
        'csv' => [
            'name' => 'Nome',
            'email' => 'E-mail',
            'partner' => 'Parceiro',
            'lessons_completed' => 'Aulas concluídas',
            'certificates' => 'Certificados',
            'last_activity' => 'Última atividade',
            'last_login' => 'Último acesso',
            'joined' => 'Cadastro',
        ],
    ],

    'tabs' => [
        'started' => 'Iniciada',
        'completed' => 'Concluída',
        'action' => 'Ação',
        'details' => 'Detalhes',
    ],

    'companies' => [
        'name' => 'Nome da empresa',
        'region' => 'Região',
        'region_help' => 'por ex. EMEA, LATAM, CIS',
        'industry' => 'Setor',
        'industry_help' => 'por ex. Logística, Construção',
        'members' => 'Membros',
        'certified' => 'Certificados',
        'certified_tip' => 'Alunos com pelo menos um certificado válido, do total de membros.',
    ],
];
