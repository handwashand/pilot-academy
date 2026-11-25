<?php

// Settings → Integrations. Shipped with the code; a row in the translations table
// with the same key overrides a line.

return [
    'groups' => [
        'text' => 'Traduction de texte',
        'text_help' => 'Tout fournisseur activé ici prépare des brouillons de traduction partout où l’académie propose Traduire : cours, leçons, quiz, études de cas, tutoriels et webinaires. Les administrateurs peuvent les utiliser tout de suite ; toute autre personne a besoin du droit sous Personnes → Utilisateurs → Autorisations supplémentaires.',
        'video' => 'Traduction vidéo',
        'video_help' => 'Traduit la voix des vidéos de leçon importées. Tout le monde, administrateurs compris, a besoin du droit sous Personnes → Utilisateurs → Autorisations supplémentaires.',
    ],
    'status' => [
        'on' => 'Activé',
        'off' => 'Désactivé — jeton enregistré',
        'not_connected' => 'Non connecté',
    ],
    'description' => [
        'descript' => 'Traduit la parole des vidéos de leçon téléversées (bouton Traduire la vidéo d’une leçon). Chaque vidéo est envoyée une seule fois à Descript et chaque traduction est conservée ici. Utilise vos minutes de média et crédits IA Descript.',
        'deepl' => 'Prépare des brouillons de traduction partout où l’académie propose Traduire. Le texte des cases vides n’est envoyé à DeepL que lorsque quelqu’un appuie sur le bouton et confirme. Une clé se terminant par :fx est une clé DeepL API Free.',
        'chatgpt' => 'Prépare des brouillons de traduction partout où l’académie propose Traduire. Le texte n’est envoyé à ChatGPT que lorsque quelqu’un appuie sur le bouton et confirme.',
        'deepseek' => 'Prépare des brouillons de traduction partout où l’académie propose Traduire. Le texte n’est envoyé à DeepSeek que lorsque quelqu’un appuie sur le bouton et confirme.',
        'claude' => 'Prépare des brouillons de traduction partout où l’académie propose Traduire. Le texte n’est envoyé à Claude que lorsque quelqu’un appuie sur le bouton et confirme.',
    ],
    'on' => 'Activé',
    'token' => 'Jeton',
    'token_saved' => '••••:ending enregistré — saisissez-en un nouveau pour le remplacer',
    'token_empty' => 'Collez un jeton',
    'token_help' => 'Stocké chiffré et jamais réaffiché ; seuls ses quatre derniers caractères le sont. Laissez la case vide pour garder celui enregistré. Créez une clé avec une limite de dépense sur le site du fournisseur.',
    'scope' => 'Qui peut l’utiliser',
    'scope_help' => 'Pour un jeton saisi maintenant. Case vide : change qui peut utiliser le jeton partagé enregistré. Moi seulement : vous seul l’utilisez, avant tout jeton partagé.',
    'scopes' => [
        'personal' => 'Moi seulement',
        'admins' => 'Tous les administrateurs',
        'everyone' => 'Toutes les personnes ayant le droit',
    ],
    'shared_line' => ':scope · ajouté par :name',
    'own_line' => 'Votre propre jeton ••••:ending, utilisé en premier pour vous',
    'remove' => 'Retirer',
    'remove_shared_heading' => 'Retirer le jeton partagé :provider ?',
    'remove_own_heading' => 'Retirer votre propre jeton :provider ?',
    'remove_description' => 'Les personnes qui l’utilisent perdent les boutons :provider jusqu’à l’ajout d’un nouveau jeton.',
    'removed' => 'Jeton retiré',
    'added_by_unknown' => 'un compte supprimé',
    'model' => 'Modèle',
    'model_help' => ':model (par défaut)',
    'model_warning' => ':model coûte environ :times fois plus par traduction que le modèle par défaut, :default. Traduire un cours entier consomme beaucoup de jetons.',
    'needs_token' => 'Ajoutez un jeton d’API avant d’activer :provider',
    'save' => 'Enregistrer',
    'saved' => 'Intégrations enregistrées',
];
