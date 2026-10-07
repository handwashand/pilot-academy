<?php

// Paramètres → Intégrations. Fourni avec le code ; une ligne de la table
// translations portant la même clé remplace celle-ci.

return [
    'subheading' => 'Activez une intégration et ajoutez son jeton d’API. Qui peut utiliser chacune se règle compte par compte sous Personnes → Utilisateurs → Autorisations supplémentaires.',
    'description' => [
        'descript' => 'Traduit la parole des vidéos de leçon téléversées (bouton Traduire la vidéo d’une leçon). Chaque vidéo est envoyée une seule fois à Descript et chaque traduction est conservée ici. Utilise vos minutes de média et crédits IA Descript.',
        'deepl' => 'Génère des brouillons de traduction du texte des cours et des leçons dans la fenêtre Traduire. Le texte des champs vides n’est envoyé à DeepL que lorsque quelqu’un clique sur le bouton et confirme. Une clé se terminant par :fx est une clé DeepL API Free.',
        'chatgpt' => 'Génère des brouillons de traduction du texte des cours et des leçons dans la fenêtre Traduire. Le texte n’est envoyé à ChatGPT que lorsque quelqu’un clique sur le bouton et confirme.',
        'deepseek' => 'Génère des brouillons de traduction du texte des cours et des leçons dans la fenêtre Traduire. Le texte n’est envoyé à DeepSeek que lorsque quelqu’un clique sur le bouton et confirme.',
    ],
    'enabled' => 'Activer :provider',
    'enabled_help' => 'Nécessite un jeton. Le désactiver masque les boutons mais conserve le jeton.',
    'token' => 'Jeton d’API',
    'token_saved' => 'Un jeton est enregistré — laissez vide pour le conserver',
    'token_empty' => 'Collez le jeton d’API',
    'token_help' => 'Stocké chiffré et jamais réaffiché. Créez une clé avec une limite de dépense sur le site du fournisseur.',
    'clear_token' => 'Supprimer le jeton enregistré',
    'model' => 'Modèle',
    'model_help' => 'Laissez vide pour utiliser :model.',
    'needs_token' => 'Ajoutez un jeton d’API avant d’activer :provider',
    'save' => 'Enregistrer',
    'saved' => 'Intégrations enregistrées',
];
