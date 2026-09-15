<?php

// French. Keys mirror lang/en/admin_widgets.php — see the note there.

return [
    'overview' => [
        'students' => 'Apprenants',
        'students_help' => 'Comptes partenaires',
        'active' => 'Apprenants actifs',
        'active_help' => ':percent % ont commencé au moins une leçon',
        'completions' => 'Leçons terminées',
        'completions_help' => 'Tous apprenants confondus',
        'published_courses' => 'Cours publiés',
        'published_courses_help' => ':count au total, brouillons compris',
        'published_lessons' => 'Leçons publiées',
        'published_lessons_help' => 'Disponibles',
        'certificates' => 'Certificats délivrés',
        'no_passes' => 'Aucune réussite pour l’instant',
        'average_score' => 'Score moyen :score %',
    ],

    'companies' => [
        'heading' => 'Progression par entreprise partenaire',
        'description' => 'Part de toutes les leçons publiées terminées par les apprenants de chaque entreprise.',
        'dataset' => '% des leçons publiées terminées',
    ],

    'certificates' => [
        'heading' => 'Certificats délivrés par cours',
    ],

    'stalled' => [
        'heading' => 'Apprenants devenus inactifs',
        'description' => 'Ont commencé un cours, rien terminé ces :days derniers jours, pas encore de certificat.',
        'lessons_done' => 'Leçons terminées',
        'last_activity' => 'Dernière activité',
        'reminded' => 'Relancé',
        'remind' => 'Envoyer un rappel',
        'remind_heading' => 'Envoyer un rappel',
        'remind_description' => 'Envoie à :email un lien personnel menant directement à sa prochaine leçon.',
        'send_it' => 'Envoyer',
        'sent' => 'Rappel envoyé à :name',
        'not_sent' => 'Non envoyé',
        'cooldown' => 'Déjà relancé ces :days derniers jours.',
        'open' => 'Ouvrir',
        'remind_all' => 'Envoyer des rappels',
        'remind_all_description' => 'Chaque apprenant reçoit un lien personnel vers sa prochaine leçon. Toute personne relancée ces :days derniers jours est ignorée.',
        'sent_count' => ':count rappel envoyé|:count rappels envoyés',
        'skipped_count' => 'Ignorés : :count',
        'empty' => 'Personne n’est inactif',
        'empty_description' => 'Chaque apprenant ayant commencé un cours le suit encore ou l’a terminé.',
    ],

    'hardest' => [
        'heading' => 'Leçons qui posent problème',
        'description' => 'Tentatives notées des apprenants, pire taux de réussite d’abord. Une leçon difficile cache souvent une question floue.',
        'failed' => 'Échouées',
        'fail_rate' => 'Taux d’échec',
        'review' => 'Revoir les questions',
        'empty' => 'Aucune difficulté à signaler',
        'empty_description' => 'Dès que les apprenants auront fait quelques tentatives notées, les leçons les plus difficiles apparaîtront ici.',
    ],

    'activity' => [
        'heading' => 'Activité des apprenants',
        'description' => 'Leçons terminées et connexions par jour, apprenants uniquement.',
        'lessons_finished' => 'Leçons terminées',
        'sign_ins' => 'Connexions',
    ],

    'opened' => [
        'heading' => 'Cours les plus ouverts',
        'description' => 'Nombre d’ouvertures de chaque cours par les apprenants ces :days derniers jours.',
        'times_opened' => 'Ouvertures',
    ],
];
