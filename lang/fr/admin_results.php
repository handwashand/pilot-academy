<?php

// French. Keys mirror lang/en/admin_results.php — see the note there.

return [
    'certificates' => [
        'export' => 'Exporter en CSV',
        'resend' => 'Renvoyer l’e-mail',
        'resend_description' => 'Envoyer le certificat par e-mail à :email.',
        'emailed' => 'Certificat envoyé',
        'regenerate' => 'Régénérer le PDF',
        'regenerated' => 'PDF régénéré',
        'edit_name' => 'Modifier le nom',
        'edit_name_heading' => 'Corriger le nom sur ce certificat',
        'edit_name_description' => 'Le PDF est réimprimé avec le nouveau nom, et la page de vérification publique l’affiche immédiatement. Le numéro, la date et le score restent identiques. Utilisez ensuite Renvoyer l’e-mail si l’apprenant doit recevoir la copie corrigée.',
        'name_on_certificate' => 'Nom sur le certificat',
        'update_profile' => 'Utiliser aussi ce nom sur les futurs certificats de l’apprenant',
        'update_profile_help' => 'L’enregistre comme nom de certificat sur son profil, où il peut aussi le modifier.',
        'name_corrected' => 'Nom corrigé et PDF réimprimé',
        'revoke' => 'Révoquer',
        'revoke_description' => 'Le certificat apparaîtra comme révoqué sur la page de vérification publique. Les certificats sont permanents — à n’utiliser qu’en cas d’émission erronée.',
        'revoked' => 'Certificat révoqué',
        'restore' => 'Restaurer',
        'restored' => 'Certificat restauré',
        'score_percent' => 'Score %',
    ],

    'attempts' => [
        'quiz' => 'Quiz',
        'not_submitted' => 'Non soumis',
        'out_of_attempts' => 'Plus de tentatives, non réussi',
        'quiz_type' => 'Type de quiz',
        'final_quizzes' => 'Quiz finaux',
        'lesson_checks' => 'Vérifications de connaissances des leçons',
        'grant' => 'Accorder une autre tentative',
        'grant_description' => ':name obtient une tentative de plus pour :quiz. Personne d’autre n’est concerné : Tentatives max. reste inchangé pour tous les autres.',
        'reason' => 'Motif (facultatif)',
        'reason_placeholder' => 'Par exemple : la connexion a été coupée pendant le quiz',
        'granted' => 'Tentative supplémentaire accordée',
        'granted_body' => ':name peut retenter :quiz une fois.',
        'empty' => 'Aucune tentative de quiz pour l’instant',
        'empty_description' => 'Les tentatives sont enregistrées pour les quiz finaux, et pour les vérifications de leçon ayant une limite de temps ou un nombre de tentatives.',
        'final_quiz' => 'le quiz final',
        'a_lesson' => 'une leçon',
    ],

    'feedback' => [
        'empty_description' => 'On demande leur avis aux apprenants une fois un cours terminé.',
    ],
];
