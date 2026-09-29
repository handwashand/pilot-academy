<?php

// Les clés reprennent lang/en/admin_webinars.php.

return [
    'sections' => [
        'main' => 'Webinaire',
        'when' => 'Quand et où',
        'when_hint' => 'Les heures sont enregistrées et affichées en UTC, avec le fuseau nommé, pour que personne n’arrive une heure trop tard.',
        'about' => 'À propos de cette session',
    ],
    'form' => [
        'summary' => 'Résumé court',
        'presenter' => 'Intervenant',
        'starts_at' => 'Début',
        'starts_at_hint' => 'En UTC.',
        'join_url' => 'Lien pour rejoindre',
        'recording_url' => 'Lien de l’enregistrement',
        'recording_url_hint' => 'À ajouter après la session : il remplace le bouton pour rejoindre.',
        'description' => 'Description',
        'before_publishing' => 'Ajoutez une date et un lien pour rejoindre ou un enregistrement avant de publier.',
    ],
    'table' => [
        'upcoming' => 'À venir',
        'past' => 'Passé',
        'recording' => 'Enregistrement',
    ],
    'notify' => [
        'published' => 'Webinaire publié',
        'drafted' => 'Webinaire repassé en brouillon',
        'incomplete' => 'Complétez d’abord les détails de la session',
        'incomplete_body' => 'Une date et un lien pour rejoindre ou un enregistrement sont obligatoires avant publication.',
    ],
];
