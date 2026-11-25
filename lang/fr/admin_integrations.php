<?php

// Paramètres → Intégrations. Fourni avec le code ; une ligne de la table
// translations portant la même clé remplace celle-ci.

return [
    'groups' => [
        'text' => 'Traduction de texte',
        'text_help' => 'Prépare des brouillons de traduction des cours, leçons et quiz dans la fenêtre Traduire. Les administrateurs peuvent les utiliser tout de suite ; toute autre personne a besoin du droit sous Personnes → Utilisateurs → Autorisations supplémentaires.',
        'video' => 'Traduction vidéo',
        'video_help' => 'Traduit la voix des vidéos de leçon importées. Tout le monde, administrateurs compris, a besoin du droit sous Personnes → Utilisateurs → Autorisations supplémentaires.',
        'course' => 'Autres fournisseurs',
        'course_help' => 'Facultatif. Un jeton Claude peut être enregistré ici pour plus tard. Pour le moment, aucune fonction de la plateforme ne fait appel à lui.',
    ],
    'status' => [
        'on' => 'Activé',
        'off' => 'Désactivé — jeton enregistré',
        'not_connected' => 'Non connecté',
    ],
    'description' => [
        'descript' => 'Traduit la parole des vidéos de leçon téléversées (bouton Traduire la vidéo d’une leçon). Chaque vidéo est envoyée une seule fois à Descript et chaque traduction est conservée ici. Utilise vos minutes de média et crédits IA Descript.',
        'deepl' => 'Génère des brouillons de traduction du texte des cours et des leçons dans la fenêtre Traduire. Le texte des champs vides n’est envoyé à DeepL que lorsque quelqu’un clique sur le bouton et confirme. Une clé se terminant par :fx est une clé DeepL API Free.',
        'chatgpt' => 'Génère des brouillons de traduction du texte des cours et des leçons dans la fenêtre Traduire. Le texte n’est envoyé à ChatGPT que lorsque quelqu’un clique sur le bouton et confirme.',
        'deepseek' => 'Génère des brouillons de traduction du texte des cours et des leçons dans la fenêtre Traduire. Le texte n’est envoyé à DeepSeek que lorsque quelqu’un clique sur le bouton et confirme.',
        'claude' => 'Enregistré pour plus tard. Pour le moment, la plateforme ne transmet rien à Claude.',
    ],
    'enabled' => 'Activer :provider',
    'enabled_help' => 'Nécessite un jeton. Le désactiver masque les boutons mais conserve le jeton.',
    'token' => 'Jeton d’API',
    'token_saved' => 'Un jeton est enregistré — laissez vide pour le conserver',
    'token_empty' => 'Collez le jeton d’API',
    'token_help' => 'Stocké chiffré et jamais réaffiché. Créez une clé avec une limite de dépense sur le site du fournisseur.',
    'clear_token' => 'Supprimer le jeton enregistré',
    'scope' => 'Qui peut utiliser ce jeton',
    'scope_help' => 'Le nouveau jeton est enregistré là où vous le choisissez. Votre propre jeton est utilisé pour vous à la place du jeton partagé.',
    'scopes' => [
        'personal' => 'Moi seulement',
        'admins' => 'Tous les administrateurs',
        'everyone' => 'Toutes les personnes ayant le droit',
    ],
    'shared_token' => 'Jeton partagé',
    'shared_none' => 'Aucun jeton partagé',
    'shared_value' => 'Se termine par :ending · :scope · ajouté par :name',
    'shared_scope' => 'Qui peut utiliser le jeton partagé',
    'clear_shared' => 'Supprimer le jeton partagé',
    'own_token' => 'Votre propre jeton',
    'own_none' => 'Aucun',
    'own_value' => 'Se termine par :ending · utilisé pour vous à la place du jeton partagé',
    'clear_own' => 'Supprimer votre propre jeton',
    'added_by_unknown' => 'un compte supprimé',
    'model' => 'Modèle',
    'model_help' => 'Laissez vide pour utiliser :model.',
    'needs_token' => 'Ajoutez un jeton d’API avant d’activer :provider',
    'save' => 'Enregistrer',
    'saved' => 'Intégrations enregistrées',
];
