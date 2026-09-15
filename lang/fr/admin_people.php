<?php

// French. Keys mirror lang/en/admin_people.php — see the note there.

return [
    'users' => [
        'password' => 'Mot de passe',
        'password_help' => 'Laissez vide pour conserver le mot de passe actuel lors de la modification.',
        'company_help' => 'Entreprise partenaire de cet utilisateur (laissez vide pour les administrateurs).',
        'role' => 'Rôle',
        'role_help' => 'Les administrateurs gèrent la plateforme. Les créateurs gèrent uniquement la formation de leurs propres produits. Les apprenants suivent les cours.',
        'products' => 'Produits / modules',
        'products_help' => 'Les produits dont ce créateur gère la formation. Il ne voit les cours d’aucun autre produit.',
        'product_filter' => 'Produit / module',
        'all_users' => 'Tous les utilisateurs',
        'admins' => 'Administrateurs',
        'creators' => 'Créateurs',
        'learners' => 'Apprenants',
        'permissions' => 'Autorisations supplémentaires',
        'permissions_help' => 'Accordées compte par compte. Elles ne sont pas incluses par défaut dans les rôles Administrateur ou Créateur.',
        'manage_languages' => 'Gérer les langues',
        'manage_translations' => 'Gérer les traductions',
        'lessons_done' => 'Leçons terminées',
        'last_login' => 'Dernière connexion',
        'export' => 'Exporter la progression des apprenants',
        'access_link' => 'Lien d’accès',
        'access_link_heading' => 'Lien d’accès personnel',
        'copy_link' => 'Copier le lien',
        'copied' => 'Copié !',
        'access_link_help' => 'Envoyez ce lien à l’utilisateur. En l’ouvrant, il se connecte sans mot de passe, et sa progression est enregistrée sur ce compte.',
        'csv' => [
            'name' => 'Nom',
            'email' => 'E-mail',
            'partner' => 'Partenaire',
            'lessons_completed' => 'Leçons terminées',
            'certificates' => 'Certificats',
            'last_activity' => 'Dernière activité',
            'last_login' => 'Dernière connexion',
            'joined' => 'Inscription',
        ],
    ],

    'tabs' => [
        'started' => 'Commencé',
        'completed' => 'Terminée',
        'action' => 'Action',
        'details' => 'Détails',
    ],

    'companies' => [
        'name' => 'Nom de l’entreprise',
        'region' => 'Région',
        'region_help' => 'par ex. EMEA, LATAM, CIS',
        'industry' => 'Secteur',
        'industry_help' => 'par ex. Logistique, Construction',
        'members' => 'Membres',
        'certified' => 'Certifiés',
        'certified_tip' => 'Apprenants ayant au moins un certificat valide, sur le total des membres.',
    ],
];
