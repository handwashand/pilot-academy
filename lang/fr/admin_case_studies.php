<?php

// Les clés reprennent lang/en/admin_case_studies.php.

return [
    'sections' => [
        'main' => 'Étude de cas',
        'privacy' => 'Confidentialité et vérification',
        'privacy_hint' => 'Par défaut, n’indiquez aucune donnée permettant d’identifier le client. Conservez une note de vérification pour toute affirmation chiffrée.',
        'media' => 'Médias',
        'media_hint' => 'Utilisez uniquement des schémas, captures d’écran ou pièces jointes nettoyés. Évitez les noms de clients, les lieux, les données en temps réel, les identifiants et les images reconnaissables.',
        'study' => 'Sections de l’étude',
        'related' => 'Contenu associé',
    ],

    'form' => [
        'short_problem' => 'Énoncé court du problème',
        'industry' => 'Secteur',
        'features' => 'Fonctions Pilot concernées',
        'add_feature' => 'Ajouter une fonction',
        'difficulty' => 'Difficulté',
        'implementation_time' => 'Temps de mise en œuvre estimé',
        'anonymized' => 'Anonymisé',
        'customer_approved' => 'Approuvé par le client',
        'source_note' => 'Source ou note de vérification',
        'performance_note' => 'Note sur les affirmations chiffrées',
        'performance_note_hint' => 'Obligatoire si l’étude mentionne des économies mesurées, des réductions, une disponibilité ou d’autres résultats chiffrés.',
        'cover' => 'Image de couverture',
        'diagram' => 'Schéma ou capture d’écran',
        'related_lessons' => 'Leçons associées de l’Académie',
        'links' => 'Liens vers la documentation',
        'add_link' => 'Ajouter un lien',
        'link_url' => 'URL',
        'own_product' => 'Choisissez l’un des produits qui vous sont attribués.',
        'before_publishing' => 'Complétez les sections obligatoires et la note de source avant de publier.',
    ],

    'table' => [
        'approved' => 'Approuvé',
        'preview' => 'Aperçu',
    ],

    'notify' => [
        'published' => 'Étude de cas publiée',
        'drafted' => 'Étude de cas repassée en brouillon',
        'drafted_many' => 'Études de cas repassées en brouillon',
        'published_count' => ':count étude de cas publiée|:count études de cas publiées',
        'skipped_count' => ':count étude de cas ignorée|:count études de cas ignorées',
        'incomplete' => 'Complétez d’abord les sections obligatoires',
        'incomplete_body' => 'Avant publication, le titre, le résumé, le scénario, le résultat souhaité, la configuration, la vérification et la note de source sont obligatoires.',
        'skipped_body' => 'Les enregistrements ignorés n’ont pas toutes les sections obligatoires ou la note de source.',
    ],
];
