# Pilot Academy — Guide d'administration

Guide pour créer des cours, gérer le quiz final et délivrer des certificats.

## 1. Se connecter et se repérer

Ouvrez l'adresse du site et ajoutez `/admin`. Connectez-vous avec votre e-mail et mot de passe d'administrateur.

Vous arrivez sur le **Tableau**, qui résume les étudiants, la progression, les cours publiés et les certificats. Le menu de gauche donne accès à toute l'académie.

Modifiez votre nom, e-mail ou mot de passe depuis le menu du compte, en haut à droite, puis **Profil**.

## 2. Le menu en bref

| Élément | Utilité |
|---|---|
| **Tableau** | Indicateurs, progression par entreprise et étudiants inactifs. |
| **Content → Courses** | Créer les cours, activer quiz final et certificat. |
| **Content → Lessons** | Ajouter vidéo, texte et questions. |
| **Content → Products** | Produits ou modules et leurs responsables. |
| **Content → Media Items** | Bibliothèque d'images réutilisables. |
| **People → Users** | Comptes, rôles, progression et certificats. |
| **People → Companies** | Entreprises partenaires et étudiants. |
| **Results → Certificates** | Télécharger, renvoyer, révoquer ou restaurer. |
| **Results → Final quiz health** | Voir si le quiz final est trop facile ou difficile. |
| **Docs → Guide** | Ce guide. |
| **Docs → What's new** | Changements récents et notes de version. |

Les étudiants ont leur guide dans **Aide**, le bouton **?** de la barre supérieure.

## 3. Démarrage rapide : du cours vide au certificat

Le chemin est toujours : **créer un cours → ajouter les leçons → activer le quiz final → publier → l'étudiant réussit → il reçoit un certificat**.

### Étape 1 · Créer un cours

Dans **Courses**, cliquez **New course**. Renseignez titre, description courte, durée éventuelle, niveau et produit/module. Un nouveau cours commence en **Draft**.

### Étape 2 · Ajouter des leçons

Dans **Lessons**, créez une leçon, choisissez le cours, ajoutez vidéo, texte, durée, transcription et questions. Une leçon doit avoir un contrôle de connaissances pour être terminée.

Réordonnez les leçons depuis l'onglet **Lessons** du cours. Déplacer une leçon existante la retire de son ancien cours ; pour copier, utilisez **Duplicate** sur le cours.

### Étape 3 · Activer le quiz final

Dans le cours, activez **Final quiz & certificate**, définissez le score requis, les questions par essai et le nombre maximal d'essais. Ajoutez ensuite les questions dans **Final questions**.

### Étape 4 · Publier le cours

Dans la liste des cours, utilisez **Publish**. Un cours doit contenir au moins une leçon publiée. **Unpublish** le masque sans supprimer contenu, progression ni certificats.

### Étape 5 · Tester comme étudiant

Ouvrez le site public, terminez les leçons et vérifiez que le quiz final se débloque.

### Étape 6 · Le certificat

Après réussite, le certificat est généré automatiquement en PDF avec nom, cours, date, numéro unique et QR code.

## 4. Paramétrer

Modifiez les questions du quiz final depuis l'onglet **Final questions** du cours. Une question venue d'une leçon reste la même question ; la modifier change aussi la leçon.

Ajoutez un fond de certificat depuis **Final quiz & certificate → Certificate background**. Utilisez une image A4 paysage ou laissez vide pour le modèle intégré.

Dupliquez un cours depuis sa ligne pour réutiliser une structure. La copie est un brouillon indépendant et ne copie ni progression ni certificats.

Pour qu'un responsable produit crée sa formation, donnez-lui le rôle **Creator** et cochez ses produits dans **Users**. Il ne verra que les cours et leçons de ces produits.

Pour ajouter une entreprise et un étudiant, créez d'abord l'entreprise dans **Companies**, puis l'utilisateur avec son entreprise et son rôle.

## 5. Contrôler les résultats

Utilisez **Users** pour filtrer par rôle et ouvrir la progression d'un étudiant.

Le **Tableau** montre les contenus à corriger, les indicateurs, la progression par entreprise, les étudiants inactifs, les leçons difficiles, l'activité et les cours les plus ouverts.

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
