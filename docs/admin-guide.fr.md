# Pilot Academy — Guide d'administration

Guide pour créer des cours, gérer le quiz final et délivrer des certificats.

## 1. Se connecter et se repérer

Ouvrez l'adresse du site et ajoutez `/admin`. Connectez-vous avec votre e-mail et mot de passe d'administrateur.

Vous arrivez sur le **Tableau**, qui résume les étudiants, la progression, les cours publiés et les certificats. Le menu de gauche donne accès à toute l'académie.

Modifiez votre nom, e-mail ou mot de passe depuis le menu du compte, en haut à droite, puis **Profil**.

Les administrateurs voient l'activité des étudiants, leur progression et les certificats. **Étudiants actifs** compte ceux qui se sont connectés ou ont travaillé dans l'académie au cours des 30 derniers jours ; les rappels et l'activité du personnel ne comptent pas. Le graphique affiche les apprenants actifs uniques sous forme de ligne et les leçons terminées sous forme de barres ; plusieurs actions d'une même personne dans la journée ne comptent qu'une fois. Les leçons publiées et la progression par entreprise comprennent uniquement les leçons accessibles dans un cours publié. Les créateurs voient des cartes liées aux Cours, Études de cas, Tutoriels et Webinaires de leurs produits attribués, réparties entre publiés, brouillons et archivés, sans données sur les étudiants ni les entreprises.

## 2. Le menu en bref

| Élément | Utilité |
|---|---|
| **Tableau de bord** | Indicateurs, progression par entreprise et étudiants inactifs. |
| **Contenu → Cours** | Créer les cours, activer quiz final et certificat. |
| **Contenu → Leçons** | Ajouter vidéo, texte et questions. |
| **Contenu → Études de cas** | Créer, prévisualiser et publier des guides pratiques de déploiement pour les partenaires. |
| **Contenu → Webinaires** | Programmer des sessions en direct, partager le lien pour rejoindre et ajouter l’enregistrement ensuite. |
| **Contenu → Tutoriels** | Vidéos pratiques indépendantes, par lien YouTube ou fichier téléversé. |
| **Contenu → Produits** | Produits ou modules et leurs responsables. |
| **Contenu → Médias** | Bibliothèque d'images réutilisables. |
| **Personnes → Utilisateurs** | Comptes, rôles, progression et certificats. |
| **Personnes → Entreprises** | Entreprises partenaires et étudiants. |
| **Résultats → Certificats** | Télécharger, renvoyer, révoquer ou restaurer. |
| **Résultats → Qualité du quiz final** | Voir si le quiz final est trop facile ou difficile. |
| **Documentation → Guide** | Ce guide. |
| **Documentation → Nouveautés** | Changements récents et notes de version. |

Les étudiants ont leur guide dans **Aide**, le bouton **?** de la barre supérieure.

## 3. Démarrage rapide : du cours vide au certificat

Le chemin est toujours : **créer un cours → ajouter les leçons → activer le quiz final → publier → l'étudiant réussit → il reçoit un certificat**.

### Étape 1 · Créer un cours

Dans **Courses**, cliquez **New course**. Renseignez titre, description courte, durée éventuelle, niveau et produit/module. Un nouveau cours commence en **Draft**.

### Étape 2 · Ajouter des leçons

Dans **Lessons**, créez une leçon, choisissez le cours, ajoutez vidéo, texte, durée, transcription et questions. Une leçon doit avoir un contrôle de connaissances pour être terminée.

Réordonnez les leçons depuis l'onglet **Lessons** du cours. Au même endroit, **Nouvelle leçon** crée une leçon directement dans ce cours, et **Ajouter une leçon existante** ouvre la liste des leçons des autres cours : la leçon est partagée, elle reste aussi dans son cours d'origine. Pour une copie distincte, utilisez **Duplicate** sur le cours.

### Étape 3 · Activer le quiz final

Dans le cours, activez **Final quiz & certificate**, définissez le score requis, les questions par essai et le nombre maximal d'essais. Ajoutez ensuite les questions dans **Final questions**.

### Étape 4 · Publier le cours

Dans la liste des cours, utilisez **Publish**. Un cours doit contenir au moins une leçon publiée. **Unpublish** le masque sans supprimer contenu, progression ni certificats.

### Étape 5 · Tester comme étudiant

Ouvrez le site public, terminez les leçons et vérifiez que le quiz final se débloque.

### Étape 6 · Le certificat

Après réussite, le certificat est généré automatiquement en PDF avec nom, cours, date, numéro unique et QR code.

## 4. Paramétrer

**Études de cas.** Dans **Contenu → Études de cas**, cliquez sur **New case study**, puis indiquez le problème en bref, le secteur, les fonctionnalités Pilot, la difficulté et le temps estimé. Complétez le scénario, le résultat recherché, les étapes de configuration et la vérification. Une note **Source or verification note** est obligatoire avant publication ; utilisez **Performance claim note** pour conserver la preuve de tout résultat chiffré. Laissez **Anonymized** activé sauf accord explicite du client et n'activez **Customer approved** que lorsque cet accord est consigné. Chaque étape accepte des images depuis le trombone de sa barre d’outils ou depuis la zone située dessous, où vous pouvez coller, déposer ou choisir des fichiers ; les images de cette zone apparaissent sous l’étape. Utilisez uniquement des images nettoyées, sans noms, lieux précis, identifiants ni données de véhicules réels. Enregistrez le brouillon, contrôlez-le avec **Preview**, puis utilisez **Publish** dans la liste. Les partenaires ordinaires ne peuvent pas ouvrir un brouillon. **Written in** est la langue de l’étude elle-même : rédigez dans la vôtre, puis ajoutez les autres langues avec **Translate** sur la page d’édition. Les notes de source et d’affirmation chiffrée sont réservées aux éditeurs : les partenaires ne les voient pas. Les creators ne gèrent que les études des produits qui leur sont attribués.

Modifiez les questions du quiz final depuis l'onglet **Final questions** du cours. Une question venue d'une leçon reste la même question ; la modifier change aussi la leçon.

Ajoutez un fond de certificat depuis **Final quiz & certificate → Certificate background**. Utilisez une image A4 paysage ou laissez vide pour le modèle intégré.

Dupliquez un cours depuis sa ligne pour réutiliser une structure. La copie est un brouillon indépendant et ne copie ni progression ni certificats.

Pour qu'un responsable produit crée sa formation, donnez-lui le rôle **Creator** et cochez ses produits dans **Users**. Il ne verra que les cours et leçons de ces produits.

Pour ajouter une entreprise et un étudiant, créez d'abord l'entreprise dans **Companies**, puis l'utilisateur avec son entreprise et son rôle.

## 5. Contrôler les résultats

Utilisez **Users** pour filtrer par rôle et ouvrir la progression d'un étudiant.

Les **Filtres du tableau de bord** permettent de choisir une période, un partenaire, un produit ou un cours ; la période par défaut est de 30 jours. Les créateurs ne voient ni ces filtres ni les données des apprenants.

Le **Tableau** montre l’**Engagement des partenaires**, le **Parcours apprenant**, l’activité quotidienne, les ressources ouvertes et les cours les plus ouverts. La liste des apprenants inactifs est calculée par cours : terminer ou certifier un autre cours ne masque plus un cours abandonné. L’engagement avec les études de cas, les tutoriels et les webinaires ne compte que les utilisateurs connectés ; les visites anonymes ne sont pas enregistrées.

Dans **Student feedback**, sur le cours, lisez les avis privés des étudiants.

Dans **Students who have gone quiet**, utilisez **Send reminder**. Personne ne reçoit deux rappels en moins de 7 jours.

Utilisez **Ctrl+K** ou **⌘K** pour rechercher rapidement cours, leçons et personnes.

Dans **Final quiz health**, suivez le taux de réussite au premier essai et le délai jusqu'au certificat.

Dans **What's new**, consultez les changements par version ou téléchargez un PDF.

Dans **Certificates**, filtrez par cours, entreprise ou statut. La page publique `/certificates/{number}` vérifie un certificat.

## 6. Que faire si…

| Situation | Action |
|---|---|
| L'étudiant n'a pas reçu l'e-mail | Renvoyer depuis **Certificates**. |
| Un certificat a été émis par erreur | Utiliser **Revoke**. |
| Le PDF est vide ou échoue | Utiliser **Regenerate PDF**, puis télécharger. |
| Il faut un rapport | Exporter CSV depuis **Certificates** ou **Users**. |
| Plus d'essais | Augmenter ou vider le maximum d'essais du cours. |
| Un cours est introuvable | Vérifier qu'il est **Published**. |
| Une leçon manque | Vérifier que le cours et la leçon sont publiés. |
| Un creator ne voit pas un cours | Vérifier le **Product / module** du cours. |
| Mettre hors ligne | Utiliser **Unpublish**. |

## 7. Questions fréquentes

**Les certificats expirent-ils ?** Non.

**Peut-on refaire le quiz final après réussite ?** Non.

**D'où vient le nom du certificat ?** Du nom saisi par l'étudiant avant le quiz final.

**Et si l'e-mail n'est pas configuré ?** Le certificat est quand même créé et téléchargeable.

**Puis-je changer le score requis ?** Oui, par cours.

**Combien de certificats par étudiant et par cours ?** Un certificat valide.

**Puis-je tester le quiz sans finir les leçons ?** Oui, les administrateurs peuvent le prévisualiser puis révoquer le certificat de test.

## 8. Termes utilisés ici

| Terme | Sens |
|---|---|
| Quiz final | Test du cours complet. |
| Banque de questions | Questions disponibles pour le quiz final. |
| Score requis | Pourcentage nécessaire pour réussir. |
| Essai | Une tentative de quiz. |
| Certificat | PDF prouvant la réussite. |
| Révoquer | Invalider un certificat. |
| Partenaire | Entreprise des étudiants. |
| Admin | Gère toute la plateforme. |
| Creator | Responsable qui crée la formation de ses produits. |
| Learner | Étudiant qui suit les cours. |
| Draft | Non visible pour les étudiants. |
| Published | Visible sur le site étudiant. |
| Archived | Retiré, mais conservé. |

## Traduction vidéo avec Descript

La traduction Descript est distincte du bouton **Traduire** normal, qui reste
le moyen de traduire manuellement les titres, résumés et textes des cours et
leçons.

Un administrateur ouvre d’abord **Personnes → Utilisateurs**, ouvre le compte
du collaborateur, coche **Utiliser la traduction vidéo de Descript** dans
**Autorisations supplémentaires**, puis enregistre. L’intégration facultative
doit aussi être activée sur le serveur. Ce droit n’est pas inclus
automatiquement dans les rôles Admin ou Creator.

Dans une leçon avec une vidéo téléversée, la personne autorisée clique sur
**Traduire la vidéo**, choisit le fichier et les langues, puis confirme que la
vidéo sera envoyée à Descript et pourra consommer des minutes média et des
crédits IA. Rien n’est envoyé avant **Démarrer la traduction**. Les liens
YouTube ne peuvent pas être envoyés. Utilisez **Vérifier la progression**
jusqu’à l’enregistrement du résultat.
