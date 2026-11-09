<?php

// Paramètres → Intégrations. Fourni avec le code ; une ligne de la table
// translations portant la même clé remplace celle-ci.

return [
    'groups' => [
        'text' => 'Traduction de texte',
        'text_help' => 'Prépare des brouillons de traduction des cours, leçons et quiz dans la fenêtre Traduire. Les administrateurs peuvent les utiliser tout de suite ; toute autre personne a besoin du droit sous Personnes → Utilisateurs → Autorisations supplémentaires.',
        'video' => 'Traduction vidéo',
        'video_help' => 'Traduit la voix des vidéos de leçon importées. Tout le monde, administrateurs compris, a besoin du droit sous Personnes → Utilisateurs → Autorisations supplémentaires.',
        'course' => 'Génération de cours',
        'course_help' => 'Facultatif. Un jeton Claude propose Claude sur Générer un cours. Il reste éteint tant que le jeton n\'est pas enregistré et l\'interrupteur allumé. Traduire ne l\'utilise pas.',
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
        'claude' => 'Rédige un cours à partir de notes sur Générer un cours. Les notes ne partent vers Claude que lorsque quelqu\'un clique sur Générer un brouillon. Traduire n\'utilise pas Claude.',
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
    'generation' => [
        'heading' => 'Génération de cours',
        'help' => 'Utilise un jeton ChatGPT, DeepSeek ou Claude enregistré sur cette page. Il n\'y a pas de seconde clé. Les prix du budget sont des estimations.',
        'default_provider' => 'Fournisseur par défaut',
        'default_provider_help' => 'Présélectionné sur Générer un cours. Un fournisseur désactivé ne peut pas servir tant qu\'il n\'est pas activé.',
        'none' => 'Aucun défaut',
        'fallback' => 'Autoriser l\'autre fournisseur en secours',
        'fallback_help' => 'Uniquement si ceci est activé et que la personne qui génère le coche. Les notes partent alors vers l\'autre fournisseur. Une tâche ne change pas de fournisseur toute seule.',
        'daily_limit' => 'Générations par jour et par personne',
        'daily_limit_help' => 'Laissez vide s\'il n\'y a pas de limite. Compte chaque réponse du fournisseur, y compris une réponse impossible à enregistrer.',
        'budget' => 'Budget mensuel (USD)',
        'budget_help' => 'Laissez vide s\'il n\'y a pas de plafond. Estimation d\'après le prix des jetons, pas une facture du fournisseur.',
    ],
];
