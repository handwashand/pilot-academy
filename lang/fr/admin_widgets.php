<?php

// French. Keys mirror lang/en/admin_widgets.php — see the note there.

return [
    'filters' => [
        'heading' => 'Filtres du tableau de bord',
        'description' => 'Appliqués à l’activité et aux résultats ; videz un champ pour tout inclure.',
        'start_date' => 'Du',
        'end_date' => 'Au',
        'partner' => 'Partenaire',
        'all_partners' => 'Tous les partenaires',
        'product' => 'Produit',
        'all_products' => 'Tous les produits',
        'course' => 'Cours',
        'all_courses' => 'Tous les cours',
    ],

    'overview' => [
        'students' => 'Apprenants',
        'students_help' => 'Comptes partenaires',
        'active' => 'Apprenants actifs',
        'active_help' => ':percent % actifs sur la période sélectionnée',
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

    'creator' => [
        'status' => ':published publiés · :drafts brouillons · :archived archivés',
    ],

    'companies' => [
        'heading' => 'Engagement des partenaires',
        'description' => 'Portée et résultats des apprenants sur la période sélectionnée.',
        'learners' => 'Apprenants',
        'active' => 'Actifs',
        'completions' => 'Leçons terminées',
        'certificates' => 'Certificats',
        'last_activity' => 'Dernière activité',
        'open' => 'Ouvrir le partenaire',
        'empty' => 'Aucun partenaire ne correspond à ces filtres',
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
        'description' => 'Apprenants actifs uniques et leçons terminées par jour sur la période sélectionnée.',
        'active_learners' => 'Apprenants actifs',
        'lessons_finished' => 'Leçons terminées',
    ],

    'journey' => [
        'heading' => 'Parcours apprenant',
        'description' => 'Apprenants uniques ayant atteint chaque étape sur la période sélectionnée. Terminer compte les étapes précédentes.',
        'learners' => 'Apprenants',
        'course_opened' => 'Cours ouvert',
        'lesson_opened' => 'Leçon ouverte',
        'lesson_completed' => 'Leçon terminée',
        'course_completed' => 'Cours terminé',
        'certified' => 'Certificat obtenu',
    ],

    'resources' => [
        'heading' => 'Engagement avec les ressources',
        'description' => 'Actions des partenaires connectés sur la période sélectionnée.',
        'opens' => 'Actions',
        'case_studies' => 'Études de cas ouvertes',
        'tutorials' => 'Tutoriels ouverts',
        'webinars' => 'Webinaires ouverts',
        'joins' => 'Participations',
        'recordings' => 'Enregistrements ouverts',
    ],

    'opened' => [
        'heading' => 'Cours les plus ouverts',
        'description' => 'Nombre d’ouvertures de chaque cours par les apprenants sur la période sélectionnée.',
        'times_opened' => 'Ouvertures',
        'removed_course' => 'Cours supprimé',
    ],
];
