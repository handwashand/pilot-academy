<?php

/*
 * Translating lesson videos with Descript. Keys mirror in every language (see
 * StudentSiteTranslationTest). See docs/descript-integration.md.
 */

return [
    'action' => [
        'button' => 'Traduire la vidéo',
        'heading' => 'Traduire la vidéo avec Descript',
        'description' => 'Descript traduit ce qui est dit dans la vidéo. Chaque langue est traduite une seule fois et conservée ici : redemander ne dépense donc jamais de crédits deux fois. Seules les vidéos téléversées peuvent être envoyées, pas les liens YouTube.',
        'video' => 'Vidéo',
        'languages' => 'Langues',
        'languages_help' => 'Les langues déjà traduites ou en cours sont affichées, mais ne peuvent pas être renvoyées.',
        'confirmation' => 'Je comprends que cette action envoie la vidéo importée sélectionnée à Descript et peut consommer des minutes de média et des crédits IA.',
        'confirmation_required' => 'Confirmez que vous souhaitez envoyer cette vidéo à Descript et utiliser des crédits.',
        'submit' => 'Démarrer la traduction',
        'requested' => 'Traduction lancée',
        'requested_body' => 'Descript travaille sur : :languages. La traduction prend quelques minutes — utilisez Vérifier l’avancement.',
        'nothing_new' => 'Rien de nouveau à traduire',
        'nothing_new_body' => 'Toutes les langues choisies sont déjà traduites ou en cours.',
        'check' => 'Vérifier l’avancement',
        'checked' => 'Avancement vérifié',
        'checked_body' => ':done terminées, :running en cours, :failed en échec.',
        'option' => ':language — :status',
    ],

    'status' => [
        'pending' => 'en attente',
        'translating' => 'en traduction',
        'exporting' => 'enregistrement',
        'done' => 'terminé',
        'failed' => 'échec — cochez pour réessayer',
    ],

    'errors' => [
        'unreachable' => 'Impossible de joindre Descript. Réessayez dans quelques minutes.',
        'out_of_credits' => 'Le forfait Descript n’a plus de crédits IA ou de minutes média.',
        'auth' => 'Descript a refusé le jeton d’API. Vérifiez DESCRIPT_API_TOKEN sur le serveur.',
        'busy' => 'Descript est occupé. Un nouvel essai aura lieu à la prochaine vérification.',
        'unavailable' => 'Descript rencontre des difficultés. Un nouvel essai aura lieu à la prochaine vérification.',
        'rejected' => 'Descript a refusé la demande.',
        'translation_failed' => 'Descript n’a pas pu traduire cette vidéo.',
        'composition_not_found' => 'Descript a terminé, mais sa version traduite n’a pas pu être identifiée.',
        'no_subtitles' => 'Descript a terminé, mais les sous-titres traduits sont revenus vides.',
        'file_missing' => 'Le fichier vidéo téléversé est introuvable dans le stockage.',
        'import_failed' => 'Descript n’a pas pu importer cette vidéo.',
    ],
    'course' => [
        'button' => 'Traduire les vidéos des leçons',
        'heading' => 'Traduire toutes les vidéos des leçons de ce cours',
        'description' => ':videos vidéos téléversées dans :lessons leçons seront envoyées à Descript pour les langues cochées. Une vidéo ou une langue déjà traduite ou en cours est ignorée et ne coûte rien. Cela utilise des minutes de média et des crédits IA de Descript.',
        'submit' => 'Mettre en file',
        'queued' => 'Traductions mises en file',
        'queued_body' => ':count traductions mises en file. Cliquez sur Vérifier l’avancement pour les lancer — chacune prend quelques minutes.',
    ],
];
