# Pilot Academy — Guide d'administration

*Comment créer un cours, gérer le quiz final et délivrer des certificats — étape par étape, en termes simples.*

**Pour :** responsables de cours · **Il vous faut :** un accès administrateur · **Où :** l’adresse de votre site + `/admin`

---

## 1. Se connecter et se repérer

Ouvrez l’adresse de votre site dans un navigateur et ajoutez `/admin` à la fin. Connectez-vous avec l’e-mail et le mot de passe administrateur.

Vous arrivez sur le **Tableau de bord**. Les administrateurs y voient l’activité des apprenants, la progression dans les leçons et
les certificats. Les créateurs y voient les cours, études de cas, tutoriels et webinaires
de leurs produits, répartis entre publiés, brouillons et archivés, avec
un lien vers chaque liste. Les créateurs ne voient jamais les rapports sur les
apprenants ni sur les partenaires. Le menu de gauche mène à chaque partie de l’académie.

**Modifiez votre nom, votre e-mail, votre photo ou votre mot de passe** depuis le menu du compte (cliquez sur vos initiales en haut à droite → **Profil**), ou depuis **Paramètres** dans le menu latéral, dont l’onglet **Profil** est le même formulaire. Une photo remplace vos initiales partout où elles apparaissent. Votre mot de passe actuel vous est demandé avant de changer d’e-mail ou de mot de passe. Cela fonctionne aussi pour les créateurs, qui ne peuvent pas ouvrir **Utilisateurs**.

**Voir l’académie comme un apprenant :** menu du compte → **Site des apprenants**.

**Changez de langue** avec le bouton de langue tout à droite de la barre supérieure — il affiche le code actuel, par exemple **FR**. Choisissez une langue dans son menu : tout le panneau se recharge dans cette langue — menus, formulaires, boutons, messages et tableau de bord — et votre choix est enregistré dans votre compte, si bien que le site des apprenants le suit aussi. Les alertes de la cloche de notifications sont elles aussi rédigées dans votre langue. Les e-mails et les certificats parviennent à chaque apprenant dans la langue qu’il a choisie, quel que soit l’expéditeur — un certificat que vous régénérez pour un apprenant français est donc toujours imprimé en français.

**Corrigez une traduction** dans **Paramètres → Traductions** (cliquez sur **Paramètres** dans le menu latéral, puis sur l’onglet **Traductions** en haut). Chaque ligne de texte est une ligne du tableau, avec une colonne par langue, pour lire une phrase dans toutes les langues à la fois. Cherchez les mots vus sur le site ou dans le panneau, dans n’importe quelle langue, cliquez sur la cellule de la langue à corriger, saisissez le nouveau texte dans **Correction** et enregistrez — tout le monde le voit aussitôt. Laissez tels quels les mots qui commencent par deux-points, comme `:name`. Pour revenir au texte d’origine, videz **Correction** et enregistrez.

**Écrivez dans votre propre langue.** Chaque cours et chaque leçon a un champ **Écrit en** — l’anglais, sauf si vous en choisissez une autre. Une nouvelle leçon prend la langue de son premier cours.

**Traduisez un cours ou une leçon** avec **Traduire** en haut de sa page de modification, pour qu’un cours écrit dans une langue serve aussi dans les autres. Sur une **leçon**, il y a un onglet pour chaque langue sauf celle d’origine : son titre, son résumé, son texte et la transcription de sa vidéo. Sur un **cours**, **Traduire** demande d’abord quoi traduire — **Le cours seul** (titre et description) ou **Le cours entier** (chaque leçon, quiz de leçon et le quiz final) —, puis comment (à la main ou avec un service de traduction s’il est activé) et dans quelle langue. L’étape suivante montre toutes les cases à relire et corriger avant **Enregistrer les traductions**. Avec un service de traduction, vous pouvez aussi choisir **Toutes les langues à la fois** : chaque langue est traduite et enregistrée aussitôt, dans les cases vides uniquement ; rouvrez **Traduire** et choisissez une langue pour les relire et les corriger. Les apprenants voient la traduction dans leur langue ; là où une case est vide, ils voient l’original. La recherche du site trouve aussi un cours par ses traductions.

**Traduisez le quiz d’une leçon ou le quiz final d’un cours** avec **Traduire le quiz** (l’icône du presse-papiers) — sur la page de modification de la leçon pour sa propre vérification des connaissances, ou dans l’onglet **Questions du quiz final** du cours pour sa banque de questions. Choisissez une langue, relisez chaque question et ses options de réponse (rédigées pour vous si DeepL, ChatGPT ou DeepSeek est activé), puis appuyez sur **Enregistrer les traductions**. Une question utilisée dans les quiz finaux de plusieurs cours se traduit une seule fois et se lit correctement dans chacun d’eux.

---

## 2. Le menu en bref

Chaque élément du menu de gauche a une fonction. Le menu est regroupé selon ce
que vous faites : construire la formation (**Contenu**), suivre les personnes qui
la suivent (**Personnes**), voir ce qu’elle a donné (**Résultats**), lire comment
tout fonctionne (**Documentation**) et la configuration de l’académie elle-même (**Paramètres**).

| Élément du menu | À quoi il sert |
|---|---|
| **Tableau de bord** | L’écran d’accueil : chiffres généraux, progression par partenaire et apprenants devenus inactifs. |
| **Contenu → Cours** | Créer des cours et activer le quiz final et le certificat. |
| **Contenu → Leçons** | Ajouter des leçons à un cours : une vidéo, du texte et des questions de quiz. |
| **Contenu → Études de cas** | Créer, prévisualiser et publier des guides de déploiement réutilisables pour les partenaires. |
| **Contenu → Webinaires** | Programmer des sessions en direct, partager le lien de connexion et ajouter l’enregistrement ensuite. |
| **Contenu → Tutoriels** | Des vidéos pratiques autonomes, par lien YouTube ou fichier importé. |
| **Contenu → Produits** | Les produits ou modules dont traite votre formation (GARM, PTM, …) et qui est responsable de chacun. |
| **Contenu → Médias** | Une bibliothèque d’images partagée, réutilisable comme couvertures de leçons. |
| **Contenu → État du contenu** | Tout ce qui est cassé pour les apprenants en ce moment, chaque élément avec un lien pour le corriger. Un nombre rouge à côté signale qu’il y a quelque chose à corriger. |
| **Personnes → Utilisateurs** | Toutes les personnes qui ont un compte, et leur rôle. La progression et les certificats de chaque apprenant. |
| **Personnes → Partenaires** | Les organisations qui intègrent et revendent le système Pilot. Regroupez les apprenants et voyez combien sont certifiés. |
| **Résultats → Certificats** | Tous les certificats délivrés. Les télécharger, les renvoyer, corriger le nom ou les révoquer. |
| **Résultats → Qualité du quiz final** | Si le quiz final est trop facile, trop difficile ou à peu près juste. Administrateurs uniquement. |
| **Résultats → Tentatives de quiz** | Toutes les tentatives de quiz de chaque apprenant. Accordez une tentative de plus à un apprenant qui n’en a plus. Administrateurs uniquement. |
| **Résultats → Avis des apprenants** | Ce que les apprenants ont dit de chaque cours, dans une seule liste. Administrateurs uniquement. |
| **Documentation → Guide** | Ce guide, avec une table des matières et une recherche. |
| **Documentation → Nouveautés** | Tout ce qui a changé dans l’académie, le plus récent en premier. Cherchez, filtrez par catégorie ou ouvrez-le en PDF. |
| **Paramètres → Général** | La configuration de l’académie elle-même : **Profil**, **Intégrations**, **E-mail**, **Traductions**, **Langues** et **Corbeille** (restaurer du contenu supprimé — nécessite sa propre autorisation), en onglets en haut de la page plutôt qu’en éléments de menu séparés. Profil : votre nom, votre e-mail et votre mot de passe. Intégrations : ajoutez le jeton d’API de Descript, DeepL, ChatGPT ou DeepSeek et activez chacun (administrateurs uniquement). Les personnes à qui vous accordez **Générer des brouillons de traduction avec DeepL** ou **… avec ChatGPT ou DeepSeek** (dans **Personnes → Utilisateurs → Autorisations supplémentaires**) voient alors **Traduire** devenir un menu — **Traduire à la main** ou traduire avec chaque fournisseur activé. Après une confirmation, la fenêtre de traduction s’ouvre avec les cases vides pré-rédigées pour que vous les relisiez ; rien n’est enregistré avant que vous appuyiez sur **Enregistrer les traductions**. E-mail : les e-mails arrivent-ils ? Les administrateurs et toute personne disposant de **Vérifier l’envoi des e-mails**. Traductions : les textes que voient les apprenants, dans toutes les langues — corrigez-en n’importe lequel sans attendre un développeur. Administrateurs uniquement. Langues : ajouter une langue ou changer celles qui sont proposées — nécessite sa propre autorisation. |

Les apprenants ont leur propre guide sur le site : **Aide**, dans le menu du compte et en bas de chaque page. Orientez-y d’abord un apprenant bloqué — il explique ce qui termine une leçon, quand le quiz final se débloque et où se trouvent les certificats.

---

## 3. Démarrage rapide : du cours vide au certificat

Le parcours est toujours le même : **créer un cours → le remplir de leçons → activer le quiz final → le publier → l’apprenant réussit → il reçoit un certificat.**

### Étape 1 · Créer un cours

1. Dans le menu de gauche, cliquez sur **Cours**.
2. En haut à droite, cliquez sur **Créer**.
3. Remplissez les champs principaux :
   - **Titre du cours** — le nom, par exemple « Les bases de Pilot ».
   - **Description courte** — une ou deux phrases. Les apprenants la voient sur la carte du cours.
   - **Durée (minutes)** — facultatif, mais **les apprenants la voient**, donc il vaut la peine
     de la remplir. Si vous laissez la durée du cours vide, l’académie additionne
     les durées de ses leçons. Si rien n’a de durée, aucun temps n’est affiché.
   - Les autres champs (niveau) sont facultatifs.
4. Cliquez sur **Créer**.

> Les nouveaux cours sont enregistrés en **Brouillon**. Les apprenants ne voient pas un brouillon — vous le publiez vous-même à l’étape 4, une fois les leçons en place.

> Laissez pour l’instant le bloc **Quiz final et certificat** désactivé. Nous y revenons à l’étape 3, quand le cours aura des leçons.

### Étape 2 · Ajouter des leçons

1. Dans le menu de gauche, cliquez sur **Leçons**, puis sur **Créer**.
2. Choisissez les **Cours** auxquels appartient la leçon — un ou plusieurs — et donnez-lui un titre.
3. Ajoutez des vidéos dans **Vidéos** : cliquez sur **Ajouter une vidéo**, puis choisissez la **Source** — **YouTube** pour coller un lien, ou **Import** pour ajouter un fichier vidéo. Cliquez de nouveau sur **Ajouter une vidéo** pour en ajouter une autre, jusqu’à cinq. Les apprenants les voient dans l’ordre de la liste ; faites-les glisser pour les réordonner, ou retirez-en une avec son icône de corbeille.
   Le lien doit pointer vers **une seule vidéo** — ouvrez-la sur YouTube et copiez la barre d’adresse.
   Les liens normaux, Shorts et de direct fonctionnent ; un lien de playlist ou de
   chaîne est refusé, car il n’y aurait aucune vidéo à afficher.
4. Rédigez le contenu de la leçon dans **Texte de la leçon**.
5. Indiquez la **Durée (minutes)** — le temps que prend la leçon. Les apprenants la voient sur
   la carte de la leçon, dans la liste des leçons et sur la page du cours (« 32 min restantes »),
   et c’est ainsi qu’ils décident de commencer maintenant ou de revenir plus tard. Elle
   s’ajoute aussi au total du cours quand le cours n’a pas de durée propre.
6. Collez une **Transcription de la vidéo** si la leçon a une vidéo. Cela vaut bien deux
   minutes : les apprenants peuvent la lire au lieu de regarder, quelqu’un qui ne
   peut pas utiliser le son peut tout de même terminer la formation, et c’est la **seule** façon
   pour que les mots prononcés dans une vidéo apparaissent dans la recherche du site. Les
   sous-titres de YouTube peuvent être copiés directement.
7. Descendez jusqu’à **Vérification des connaissances (quiz)**. Cliquez sur **Ajouter une question**, saisissez la question, ajoutez les options de réponse et activez **Correct** pour la bonne. Ajoutez autant de questions que vous voulez.
8. Laissez **Statut** sur **Publié** et cliquez sur **Créer**.
9. Recommencez pour chaque leçon du cours.

> Les nouvelles leçons sont **publiées** tout de suite — et c’est sans risque, car
> les apprenants ne voient toujours rien tant que le *cours* n’est pas publié à
> l’étape 4. Il n’y a donc pas de seconde étape à retenir.
>
> La liste **Leçons** a la même colonne **Statut** et les mêmes actions **Publier** /
> **Dépublier** que les cours, pour retenir une leçon pendant que le reste du
> cours est en ligne, ou retirer une ancienne leçon avec **Archivé**.
> Dépublier ne supprime jamais rien — le texte, la vidéo, les questions et la
> progression des apprenants restent.

#### Mettre les leçons dans le bon ordre et réutiliser celles que vous avez déjà

Ouvrez le cours (**Cours** → **Modifier**) et allez dans l’onglet **Leçons**. Il
liste toutes les leçons de ce cours.

- **Pour changer l’ordre**, faites glisser une ligne par la poignée à sa gauche.
  L’ordre défini ici est celui que suivent les apprenants : sur la page du cours,
  dans la barre latérale de la leçon et dans « Reprendre là où vous vous êtes
  arrêté ». Il n’y a rien à enregistrer.
- **Pour écrire une nouvelle leçon**, cliquez sur **Nouvelle leçon**. Le
  formulaire complet de la leçon s’ouvre — texte, vidéos et vérification des
  connaissances — avec ce cours déjà choisi et sa langue réglée. **Créer** l’ajoute
  à la fin de ce cours et vous ramène ici — inutile de passer d’abord par
  **Leçons** puis de choisir le cours.
- **Pour réutiliser une leçon qui existe déjà**, cliquez sur **Ajouter une leçon existante**.
  La liste s’ouvre avec toutes les leçons que vous pouvez ajouter, chacune avec le
  cours dont elle vient, et vous pouvez taper pour la filtrer. Les leçons déjà dans
  ce cours n’y figurent pas, puisqu’elles y sont.
- **Pour retirer une leçon de ce cours**, cliquez sur **Retirer du cours** sur sa
  ligne. Elle reste dans ses autres cours. Retirée de son seul cours, la leçon
  n’est plus dans aucun cours : les apprenants ne la voient plus, et elle reste
  dans **Contenu → Leçons** avec ses questions et la progression des apprenants.
  Retrouvez ces leçons avec le filtre **Dans aucun cours**, et remettez-en une avec
  **Ajouter une leçon existante** dans n’importe quel cours — ce cours en devient
  alors propriétaire.

> **Vous avez écrit la leçon ailleurs ?** Dans le formulaire de la leçon elle-même,
> le dernier bouton de la rangée de boutons de **Texte de la leçon**, **Importer un
> fichier Markdown**, accepte un fichier Markdown (`.md` ou `.txt`, jusqu’à 512 Ko)
> et remplit la case avec, en conservant titres, gras, listes, liens et tableaux.
> Une première ligne comme `# Titre` devient le titre de la leçon et une ligne `> `
> en dessous le résumé, s’ils sont vides. Les questions sous un titre `## Quiz` —
> `1. Question ?` puis des lignes `- [x] bonne réponse` et `- [ ] mauvaise
> réponse` — sont ajoutées à la vérification des connaissances. Chaque question
> demande au moins deux réponses et exactement une marquée `[x]` ; sinon rien
> n’est rempli et la fenêtre indique la question à corriger. **Télécharger le
> modèle**, dans la même fenêtre, donne un fichier dans ce format. Il remplace le
> texte de la case, s’ajoute aux questions existantes et n’enregistre rien avant
> l’enregistrement de la leçon. Ajoutez ensuite les vidéos et les images.

> **Une leçon n’a pas besoin de cours.** Dans **Contenu → Leçons**, **Créer** vous
> permet de laisser **Cours** vide et d’en choisir un plus tard ; les apprenants ne
> voient pas la leçon tant qu’elle n’est pas dans un cours publié. Un créateur voit
> les leçons sans cours qu’il a écrites lui-même. **Supprimer un cours ne supprime
> pas ses leçons** non plus : elles deviennent **Dans aucun cours**, et seul un
> administrateur peut les reprendre.

> **Une leçon peut faire partie de plusieurs cours.** Ajouter une leçon existante la
> partage : elle reste dans les cours où elle se trouvait déjà. C’est la même leçon
> partout — modifier son texte, sa vidéo ou ses questions la modifie dans tous les
> cours, et un apprenant qui l’a terminée dans un cours l’a terminée dans tous.
> Chaque cours garde son propre ordre. Le cours dans lequel la leçon a été écrite
> en premier en est propriétaire, donc seules les personnes qui peuvent modifier ce
> cours peuvent modifier la leçon. Vous pouvez aussi choisir plusieurs cours dans le
> champ **Cours** de la leçon elle-même. Si vous voulez une copie séparée à modifier
> indépendamment, utilisez plutôt **Dupliquer** sur le cours.

> Ce n’est possible qu’une fois le cours créé ; pour un tout nouveau cours, terminez
> d’abord avec **Créer** et l’onglet **Leçons** apparaît.

**Écran — Vérification des connaissances (quiz) :**

| Champ | Exemple |
|---|---|
| Question | Sur quel écran arrivez-vous après la connexion ? |
| Réponse + **Correct** | Le tableau de bord — ✅ Correct |
| Réponse | La page de facturation |

> Chaque leçon a son propre petit quiz. Un apprenant doit y répondre correctement pour terminer la leçon. Terminer **toutes** les leçons débloque le quiz final.

### Étape 3 · Activer le quiz final

1. Allez dans **Cours** et ouvrez votre cours (cliquez sur **Modifier**).
2. Trouvez **Quiz final et certificat** et activez **Activer le quiz final**.
3. Réglez la **Note de réussite (%)** — le score nécessaire pour réussir. Par défaut, 80 %.
4. Facultatif — **Questions par tentative** : combien de questions afficher, tirées au hasard à chaque essai. Laissez vide pour toutes les utiliser.
5. Facultatif — **Tentatives max.** : combien d’essais a un apprenant. Laissez vide pour un nombre illimité.
6. Cliquez sur **Sauvegarder les modifications**.
7. Ouvrez l’onglet **Questions du quiz final** et cliquez sur **Ajouter toutes les questions des leçons**. Le quiz se remplit avec toutes les questions de vos leçons.

**Écran — Quiz final et certificat (modification du cours) :**

| Champ | Exemple |
|---|---|
| Activer le quiz final | ✅ activé |
| Note de réussite (%) | 80 |
| Questions par tentative | vide = toutes |
| Tentatives max. | vide = illimité |
| Fond du certificat (facultatif) | votre image, ou vide |

**Écran — Questions du quiz final (banque) :**

| Question | Type | Source | Options |
|---|---|---|---|
| Sur quel écran arrivez-vous après la connexion ? | Choix unique | Premiers pas | 4 |
| Quelle zone affiche vos véhicules ? | Choix unique | La carte en direct | 4 |
| Choisissez les deux types de rapports que vous pouvez planifier | Choix multiple | Cours uniquement | 4 |

> Vous pouvez retirer une question dont vous ne voulez pas avec **Retirer de la banque**, ou en écrire une nouvelle avec **Nouvelle question finale** (une question finale peut avoir une *ou plusieurs* bonnes réponses).

### Étape 4 · Publier le cours

Jusqu’ici, le cours était un **Brouillon** — vous le voyez, les apprenants non.

1. Allez dans **Cours**. La colonne **Statut** indique où en est chaque cours :
   **Brouillon**, **Publié** ou **Archivé**.
2. Sur la ligne de votre cours, cliquez sur **Publier** et confirmez.

Le cours est aussitôt en ligne pour les apprenants. Deux choses à savoir :

- Un cours doit avoir au moins une **leçon publiée** pour pouvoir être publié —
  sinon les apprenants ouvriraient un cours vide. Si **Publier** vous demande
  d’ajouter d’abord une leçon publiée, allez dans **Leçons** et publiez-en une.
- **Publier vérifie aussi tout ce sur quoi les apprenants resteraient bloqués** — une
  leçon sans questions, une question sans bonne réponse, un quiz final sans
  questions, un lien YouTube qui ne se lit pas. La fenêtre de confirmation liste ce
  qui ne va pas, et le cours reste en brouillon tant que ce n’est pas corrigé.
- Vous changez d’avis ? Cliquez sur **Dépublier**. Le cours redevient un brouillon
  et disparaît du site. Rien n’est supprimé — leçons, questions, progression et
  certificats restent exactement comme ils étaient.

**Écran — Cours :**

| Cours | Statut | Actions |
|---|---|---|
| Démarrage rapide avec Pilot | Publié | Dépublier · Modifier |
| Introduction au leadership | Brouillon | Publier · Modifier |
| Ancienne intégration 2024 | Archivé | Modifier |

> Vous voulez retirer un cours pour de bon ? Ouvrez **Modifier** et mettez **Statut**
> sur **Archivé**. Comme un brouillon, il disparaît du site, mais ses certificats
> et son historique restent intacts.

> **Plusieurs à la fois :** cochez les cases à gauche dans **Cours** ou
> **Leçons**, puis choisissez **Publier** ou **Dépublier** dans le menu d’actions
> groupées qui apparaît. Ce sur quoi les apprenants resteraient bloqués est ignoré
> au lieu d’être publié, et on vous indique lesquels, par leur nom, avec la raison.

> **Les leçons passent le même contrôle.** Publier une leçon dans un cours déjà en
> ligne refuse une leçon que les apprenants ne pourraient pas terminer. Dépublier la
> seule leçon publiée d’un cours en ligne vous avertit d’abord, car les apprenants
> trouveraient le cours vide.

### Étape 5 · Tester comme étudiant

Ouvrez le site public (votre adresse *sans* `/admin`), allez au cours et terminez les leçons. Quand toutes les leçons sont faites, le bouton **Quiz final** se débloque. Réussissez-le pour voir tout le parcours par vous-même.

> En tant qu’administrateur, vous pouvez aussi ouvrir un cours en **brouillon** sur le
> site pour le prévisualiser — une barre jaune vous rappelle que les apprenants ne
> peuvent pas encore le voir.

### Étape 6 · Le certificat

Quand un apprenant réussit, le certificat est créé **automatiquement** : un PDF avec son nom, le cours, la date, un numéro unique et un QR code. L’apprenant le reçoit par e-mail et dans son compte. Vous trouvez tous les certificats dans **Certificats**.

---

## 4. Paramétrer

### Créer et publier une étude de cas

Utilisez **Contenu → Études de cas** pour un guide de déploiement pratique que les
partenaires peuvent reproduire. Une étude de cas est un document de référence et non
un cours : elle ne suit pas l’achèvement et ne délivre pas de certificat.
Le formulaire est un seul grand encadré pour les informations de base, les étapes et le contenu associé, puis, plus bas, **Confidentialité et vérification** et **Médias**.

1. Cliquez sur **Créer** et choisissez le **Produit** le cas échéant. Les créateurs
   doivent choisir l’un de leurs produits et ne peuvent gérer que les études de
   ces produits.
2. Ajoutez sur une seule ligne le **Produit**, le titre et le slug, le titre étant le champ le plus
   large, puis l’énoncé court du problème. Ensuite, le secteur, la difficulté, le temps de mise en
   œuvre estimé, l’ordre et le statut sur une seule ligne. Les fonctions Pilot concernées sont à
   côté des leçons associées de l’Académie, sous **Contenu associé**, en bas de l’encadré. Il n’y a
   pas de langue à choisir : une nouvelle étude prend automatiquement la langue de votre compte ou
   du panneau. Une fois rédigée, utilisez **Traduire** sur la page de modification pour ajouter les
   autres langues. Un partenaire lit la traduction là où elle existe, et l’original partout ailleurs.
3. Complétez les sections de l’étude, chacune dans son propre encadré numéroté (« Étape 1 · … »,
   « Étape 2 · … », etc.). Quatre sont toujours visibles, car l’étude ne peut pas être
   publiée sans elles : le scénario, le résultat souhaité, la configuration pas à pas, ainsi
   que les tests et la vérification. À côté, la **Source ou note de vérification** sous
   **Confidentialité et vérification**, plus bas.
4. Les cinq autres sections — prérequis, fonctions Pilot utilisées, résultats attendus,
   dépannage et façons d’adapter la solution — sont masquées au départ, pour qu’une étude
   rapide ne montre pas neuf encadrés vides. Ajoutez-en une avec **Ajouter une étape** en bas
   de la liste, qui ne propose que celles qui ne sont pas encore affichées. Chaque étape
   ajoutée a son propre lien **Retirer cette étape** dans son coin, qui ouvre une seule
   confirmation à deux choix : **Masquer** (par défaut) cache seulement l’encadré et conserve
   tout ce qui est écrit, et **Ajouter une étape** le ramène exactement tel quel ; **Supprimer**
   efface définitivement le texte et les images de l’étape, et c’est irréversible.
5. Dans **Confidentialité et vérification**, laissez **Anonymisé** activé sauf si le
   client a explicitement accepté d’être identifié. N’activez **Approuvé par le
   client** que lorsque cet accord est consigné. Utilisez **Note sur les affirmations
   chiffrées** pour consigner ce qui étaye toute économie, réduction, disponibilité
   ou autre résultat mesuré. Ces deux notes sont destinées aux éditeurs : les
   partenaires ne les voient jamais, écrivez-les donc pour vos collègues.
6. Ajoutez des images à n’importe quelle étape, de deux façons : le trombone de la
   barre d’outils de l’étape insère une image dans le texte, et la case sous l’étape
   accepte une capture collée, un fichier déposé ou un choix de fichier classique,
   affichés sous cette étape. Facultatif : ajoutez une image de couverture ou un
   schéma anonymisés, des leçons liées de l’académie et des liens de documentation.
   N’importez pas de noms de clients, d’adresses exactes, d’identifiants, de données
   réelles de véhicules ni de captures permettant d’identifier quelqu’un.
7. Enregistrez l’étude en **Brouillon**, puis utilisez **Aperçu** pour examiner sa page
   publique. L’aperçu est accessible aux éditeurs autorisés, mais les partenaires
   ordinaires ne peuvent pas ouvrir le brouillon.
8. Revenez à la liste des études de cas et cliquez sur **Publier**. Une étude à
   laquelle il manque une section obligatoire ou la note de source reste en
   brouillon, et le panneau explique ce qui doit être complété. **Dépublier** remet
   une étude en ligne en brouillon sans la supprimer.

Les études publiées apparaissent dans **Études de cas** sur l’académie publique.
Vérifiez que l’étude se trouve avec ses filtres de secteur, de fonction et de
difficulté, et que ses leçons et sa documentation liées s’ouvrent correctement.

### Modifier les questions du quiz final

Ouvrez le cours → onglet **Questions du quiz final**. Vous pouvez y **Joindre une question de leçon** (ajouter une question existante), **Ajouter toutes les questions des leçons** (l’icône plus dans un cercle : les ajouter toutes d’un coup) ou **Nouvelle question finale** (écrire une question de contrôle qui n’existe que dans le quiz final). Survolez une icône pour voir son nom.

**Pour charger beaucoup de questions d’un coup**, cliquez sur **Importer depuis Excel** (l’icône du tableau) :

1. Choisissez **Télécharger le fichier d’exemple**. C’est un fichier Excel avec une ligne d’en-têtes et deux questions d’exemple.
2. Remplacez les deux exemples par les vôtres. Une ligne est une question : la question, jusqu’à six réponses (colonnes **Réponse A** à **Réponse F**), puis, dans la dernière colonne, la lettre de la bonne réponse. S’il y a plusieurs bonnes réponses, écrivez les lettres séparées par des virgules — `A, C`.
3. Choisissez **Importer des questions** et sélectionnez le fichier.

Les questions sont ajoutées à la banque comme questions propres au cours, dans la langue du cours ; celles qui y étaient restent, et une question déjà présente dans la banque est ignorée. Si une ligne contient une erreur — pas de question, moins de deux réponses, pas de bonne réponse —, rien n’est ajouté et chaque erreur est indiquée avec son numéro de ligne, pour que vous puissiez corriger le fichier et l’importer de nouveau. Un fichier contient jusqu’à 300 questions.

> **Attention :** une question venant d’une leçon est la *même* question — la modifier ici la modifie aussi dans cette leçon. Pour seulement la retirer du quiz final, utilisez **Retirer de la banque** (cela ne la supprime pas).

### Importer votre propre fond de certificat

Ouvrez le cours → **Quiz final et certificat** → **Fond du certificat (facultatif)**. Importez une image pleine page (A4, paysage). Le nom, le cours, la date, le numéro et le QR code sont placés par-dessus automatiquement. Laissez vide pour utiliser le modèle encadré intégré.

### Créer un cours à partir d’un cours existant

Si le prochain cours ressemble beaucoup à un cours que vous avez déjà, ne partez
pas d’une page blanche. **Cours** → la ligne → **Dupliquer**.

Vous obtenez un nouveau **Brouillon** qui reprend tout ce qui compose le cours : les
leçons dans l’ordre avec leurs vidéos et leurs textes, chaque question et réponse,
ainsi que les réglages et la banque de questions du quiz final. Renommez-le,
modifiez ce qui diffère, publiez.

> La progression des apprenants et les certificats ne sont **pas** copiés — ils
> appartiennent au cours que les gens ont réellement suivi. Et la copie est
> entièrement indépendante : modifier une question dans la copie ne change jamais l’original.

### Laisser un responsable produit rédiger sa propre formation

Les responsables produit peuvent tenir à jour la formation de leur produit sans
que vous leur confiiez toute la plateforme. Ils reçoivent pour cela le rôle **Créateur**.

Il existe trois rôles :

| Rôle | Ce qu’il peut faire |
|---|---|
| **Administrateur** | Tout : tous les cours, utilisateurs, partenaires, certificats et paramètres. |
| **Créateur** | Les cours et leçons **de ses produits uniquement**. Rien d’autre. |
| **Apprenant** | Suit les cours sur le site. Aucun accès au panneau. |

Pour en configurer un :

1. **Produits** → **Créer**. Nommez-le d’après le produit, par exemple `GARM`.
2. **Utilisateurs** → ouvrez la personne → réglez **Rôle** sur **Créateur**.
3. Une case **Produits / modules** apparaît. Cochez les produits dont elle est
   responsable — un ou plusieurs — et enregistrez.

C’est tout. En se connectant à `/admin`, elle voit **Cours**, **Leçons** et
**Médias**, avec uniquement le contenu de ses produits. Elle peut créer des
cours et des leçons et les publier, exactement comme vous.

Ce qu’un Créateur **ne peut pas** faire :

- Voir ou modifier les cours ou leçons d’un autre produit — ces lignes ne figurent
  pas dans sa liste, et un lien direct ne fonctionne pas non plus.
- Ouvrir **Utilisateurs**, **Partenaires**, **Produits** ou **Certificats**.
- Voir les chiffres des apprenants ou le rapport des certificats sur le tableau de bord.
- Supprimer quoi que ce soit en masse, réordonner la liste des cours ou supprimer des médias partagés.

> C’est l’affectation d’un cours à un produit qui le confie au créateur. Un cours
> sans produit reste réservé aux administrateurs, et c’est le point de départ de
> chaque cours existant — renseignez le champ **Produit / module** d’un cours pour le confier.

> Besoin de retirer les droits ? Remettez son **Rôle** sur **Apprenant**, ou
> décochez le produit. Ses cours et leçons restent exactement tels quels.

### Ajouter un partenaire et un apprenant

1. Allez dans **Partenaires** → **Créer** → saisissez le nom → **Créer**.
2. Puis **Utilisateurs** → **Créer** → saisissez le nom et l’e-mail, choisissez le **Partenaire** et laissez **Rôle** sur **Apprenant** → **Créer**.

Les apprenants peuvent aussi s’inscrire eux-mêmes sur le site.

---

## 5. Contrôler les résultats

### Trouver des personnes par rôle

**Utilisateurs** s’ouvre sur des onglets en haut : **Tous les utilisateurs**, **Administrateurs**, **Créateurs**
et **Apprenants**, chacun avec un compteur. Cliquez sur l’un d’eux pour ne voir que ces comptes. Il y a
aussi des filtres **Rôle** et **Produit / module** si vous voulez les combiner avec un partenaire.

> **Apprenants** est l’onglet qui compte pour les rapports : chaque chiffre du
> tableau de bord et la colonne **Certifiés** dans Partenaires ne comptent que les
> apprenants. Les comptes administrateur et créateur ne sont jamais inclus — ni
> leurs leçons terminées, ni les certificats obtenus en prévisualisant un quiz final.

### Qui a terminé un cours

Allez dans **Utilisateurs** et ouvrez un apprenant. L’onglet **Leçons terminées** liste toutes les leçons qu’il a terminées. Le **Tableau de bord** montre l’activité générale de tous les apprenants.

### Ce que les apprenants pensent d’un cours

Ouvrez **Résultats → Avis des apprenants** pour lire les avis de tous les cours
dans une seule liste (administrateurs uniquement), ou ouvrez un cours (**Cours** →
**Modifier**) et allez dans son onglet **Avis des apprenants**. Quand un apprenant
termine un cours, on lui demande s’il a été utile, et il peut ajouter une phrase.
Vous voyez le verdict, qui l’a donné, son partenaire et son commentaire. Utilisez
le filtre **Verdict** pour lire seulement les plaintes, et **Cours** ou
**Partenaire** pour affiner.

> C’est le seul rapport qui vous dit qu’un cours est *mauvais* plutôt que *difficile*.
> **Leçons qui posent problème** sur le tableau de bord montre où les gens échouent ;
> celui-ci montre où ils ont eu le sentiment de perdre leur temps, et ce sont deux
> listes différentes.

> Les apprenants ne voient jamais les avis des autres, et il n’y a pas de notes
> publiques en étoiles — c’est une formation que votre entreprise attribue, pas une boutique.

### Lire le tableau de bord

Les administrateurs peuvent utiliser les **Filtres du tableau de bord** pour choisir
une période, un partenaire, un produit ou un cours. La période par défaut est les
30 derniers jours. Les dates s’appliquent à l’activité et aux résultats ; le produit
et le cours restreignent aussi les totaux de contenu. La liste des inactifs garde sa
règle fixe de 14 jours, mais ses filtres de partenaire, produit et cours
s’appliquent toujours. Les créateurs ne voient ni ces filtres ni aucune donnée sur
les apprenants.

L’écran d’accueil comporte ces blocs :

| Bloc | Ce qu’il vous dit |
|---|---|
| Les chiffres du haut | Apprenants, apprenants actifs et leçons terminées sur la période choisie, cours publiés et leçons disponibles, et certificats délivrés avec la note moyenne. Une connexion ou une activité d’apprenant compte ; les e-mails de rappel et l’activité du personnel non. |
| **Engagement des partenaires** | Apprenants, apprenants actifs, leçons terminées, certificats et dernière activité de chaque partenaire sur la période choisie. **Ouvrir le partenaire** mène directement à ce partenaire. Un partenaire sans activité reste visible avec des zéros. |
| Cartes de contenu du créateur | Les créateurs voient les totaux des cours, études de cas, tutoriels et webinaires de leurs produits, répartis entre publiés, brouillons et archivés. Cliquez sur une carte pour ouvrir cette liste. Les administrateurs ne voient pas ces cartes. |
| **Apprenants devenus inactifs** | Ont commencé le cours indiqué, n’y ont rien terminé depuis deux semaines et n’ont pas de certificat pour ce cours. Le travail ou un certificat dans un autre cours ne masque pas la ligne. **Envoyer un rappel** leur envoie un lien vers leur prochaine leçon, ou **Ouvrir** pour voir la personne. |
| **Leçons qui posent problème** | Les leçons classées selon la fréquence à laquelle les apprenants échouent au quiz. |
| **Parcours apprenant** | Les apprenants uniques connectés qui ont ouvert un cours, ouvert une leçon, terminé une leçon, terminé un cours et obtenu un certificat sur la période choisie. |
| **Activité des apprenants** | Les apprenants actifs uniques en courbe et les leçons terminées en barres, pour chaque jour de la période choisie. Plusieurs actions d’un même apprenant dans la journée comptent pour un seul apprenant actif, donc la courbe montre la portée et non les clics. |
| **Cours les plus ouverts** | Les cours ouverts par les apprenants connectés sur la période choisie, terminés ou non. |
| **Engagement avec les ressources** | Les actions des apprenants connectés sur les études de cas, tutoriels et webinaires, y compris la participation aux webinaires et l’ouverture des enregistrements. Les visites anonymes ne sont pas enregistrées. |

> **Leçons qui posent problème** vous signale généralement qu’une question n’est pas
> claire, pas que les apprenants sont faibles. Une leçon doit avoir au moins trois
> tentatives notées pour apparaître, et les tentatives encore en cours ne comptent
> pas contre elle.

### Corriger le contenu sur lequel les apprenants bloquent

Ouvrez **Contenu → État du contenu**. Il liste tout ce qui est cassé pour les
apprenants en ce moment, le plus grave en premier, chaque élément avec un lien
**Corriger**. Un nombre rouge à côté de **État du contenu** dans le menu vous indique
qu’il y a quelque chose à corriger, quel que soit l’écran. Les créateurs ne voient
que leurs propres produits.

**Ce qu’il détecte :**

| Problème | Ce que voit l’apprenant |
|---|---|
| Question sans bonne réponse | Un quiz qu’il ne peut jamais réussir, quoi qu’il réponde |
| Quiz final activé, banque de questions vide | Un bouton **Quiz final** qui ne mène nulle part |
| Cours publié sans leçon publiée | Une page de cours vide |
| Leçon publiée sans quiz | Une leçon qu’il ne peut jamais marquer comme terminée |
| Lien YouTube qui n’est pas une vidéo lisible | Une leçon sans vidéo là où il devait y en avoir une |

Vous voyez aussi ces problèmes là où vous travaillez sur le contenu :

- **Cours** et **Leçons** ont une colonne **Attention** et un filtre **À corriger**
  qui n’affiche que ce qui est cassé.
- Un cours ou une leçon cassé indique, sous son titre sur sa page de modification,
  ce sur quoi tombent les apprenants.
- **Publier** refuse un cours ou une leçon sur lequel les apprenants
  resteraient bloqués — voir l’étape 4.
- **La cloche** en haut à droite prévient les créateurs du produit — ou les
  administrateurs si personne n’est responsable du produit — quand un changement
  casse quelque chose. L’auteur du changement n’est pas prévenu : il le voit déjà.
  L’alerte disparaît d’elle-même une fois le problème corrigé.

> Une question sans bonne réponse était autrefois facile à créer par erreur —
> oubliez de cocher **Correct** et la leçon devient impossible. Le formulaire refuse
> désormais de l’enregistrer, et État du contenu liste celles passées avant.

### Relancer un apprenant qui s’est arrêté

Dans **Apprenants devenus inactifs**, cliquez sur **Envoyer un rappel** sur sa
ligne — ou cochez-en plusieurs et utilisez l’action groupée **Envoyer des rappels**.
Ils reçoivent un e-mail avec un lien personnel qui les connecte et ouvre leur prochaine leçon.

La colonne **Relancé** montre qui a déjà été relancé. Personne ne peut être relancé
deux fois en 7 jours, quel que soit celui qui clique, donc deux responsables
travaillant sur la même liste ne font pas doublon — un envoi groupé ignore
simplement toute personne dans ce délai et vous dit combien il en a ignoré.

> Un rappel n’est pas une progression. L’apprenant reste dans la liste jusqu’à ce
> qu’il revienne et termine une leçon, ce que vous vouliez vraiment.

> Les rappels sont consignés dans l’onglet **Activité** de l’apprenant, vous voyez
> donc tout l’historique : ce qu’il a fait et quand on l’a relancé.

> Chaque chiffre compte **uniquement les apprenants**. Votre propre compte et vos
> créateurs ne sont jamais inclus, même si vous terminez des leçons et obtenez des
> certificats en vérifiant un cours.

> Quelqu’un qui a terminé — même bien après avoir commencé — n’apparaît jamais dans
> la liste des inactifs. Elle concerne le travail en suspens, pas le travail lent.

### Trouver n’importe quoi rapidement

Appuyez sur **Ctrl+K** (**⌘K** sur Mac) n’importe où dans le panneau et commencez à
taper. La recherche porte sur les cours, les leçons et les personnes. Chaque
résultat affiche dessous son statut, son cours ou son partenaire, si bien que deux
enregistrements portant le même nom restent faciles à distinguer.

Un nombre à côté de **Cours** ou **Leçons** dans le menu de gauche signifie que
quelque chose est encore en brouillon et invisible pour les apprenants. Survolez-le
pour voir ce qu’il compte.

### Le quiz final fonctionne-t-il ?

Ouvrez **Résultats → Qualité du quiz final** (administrateurs uniquement). Un
certificat affirme que quelqu’un maîtrise la matière ; cette page vous dit si le quiz
qui le sous-tend est capable de le savoir. Seuls les apprenants sont comptés — vos
propres aperçus sont exclus.

| Carte | Ce qu’elle vous dit |
|---|---|
| **Taux de réussite au premier essai** | Parmi les *premiers* essais au quiz final, combien ont réussi. La fourchette conseillée est **65–80 %**. **Au-dessus de la fourchette** : le quiz est sans doute trop facile, donc réussir dit peu de chose. **En dessous de la fourchette** : les leçons n’enseignent probablement pas ce que demande le quiz, ou certaines questions manquent de clarté. Avec moins de 10 premiers essais, la carte affiche **Trop peu pour juger** au lieu de deviner. |
| **Jours jusqu’au certificat** | Le temps typique (médian) entre la première leçon terminée d’un cours et le certificat, avec le plus rapide et le plus lent. Aucun objectif n’a été fixé, regardez donc la tendance. |
| **Difficulté des questions** | Affiche **Non mesuré** : l’académie conserve le score total de chaque tentative, pas la réponse à chaque question, elle ne peut donc pas encore vous dire quelles questions posent problème. |

Sous les cartes, **Taux de réussite au premier essai par cours** donne le même
verdict par cours, et c’est là que vous agissez : un cours **En dessous de la
fourchette** est celui dont il faut relire les leçons ou les questions. **Leçons qui
posent problème** sur le tableau de bord montre le même type de problème pour les
quiz des leçons.

### Ce qui a changé, et quand

Ouvrez **Documentation → Nouveautés**, ou cliquez sur l’icône du mégaphone à côté du
champ de recherche en haut de n’importe quelle page. Chaque changement de l’académie
y figure, le mois le plus récent en premier, avec la version que vous utilisez
affichée en bas du menu de gauche — cliquez dessus pour arriver directement sur cette page.

Tapez dans le **champ de recherche** pour trouver un changement par n’importe quel
mot (« certificat », « transcription », un nom de cours). Cliquez sur un **bouton
de catégorie** — *Ajouté*, *Modifié*, *Corrigé*, *Limitations connues* — pour
n’afficher que ce type de changement ; cliquez de nouveau pour réafficher le reste.
Les mois qui ne contiennent plus rien disparaissent, vous ne voyez donc que ce qui
correspond.

**Vous envoyez les notes de version à quelqu’un qui n’a pas de compte ?** Cliquez
sur **PDF** dans l’en-tête d’une version pour cette version seule, ou sur **Toutes
les versions (PDF)** à côté des filtres pour tout. Le PDF s’ouvre dans un nouvel
onglet, où vous pouvez le lire, l’imprimer ou l’enregistrer. Le fichier contient
toujours des versions entières — les filtres à l’écran ne changent pas ce qui est imprimé.

> La page est le fichier du journal des modifications lui-même, relu à chaque
> ouverture. Quiconque livre un changement l’y écrit, c’est pourquoi cette page
> n’est jamais périmée.

### Qui a obtenu un certificat

Ouvrez **Certificats**. Chaque ligne indique l’apprenant, son partenaire, le cours, le score, la date, le numéro, et s’il est **Valide** ou **Révoqué**. Utilisez les filtres en haut pour affiner par cours, partenaire ou statut.

### Couverture par partenaire

Ouvrez **Partenaires**. La colonne **Certifiés** indique combien de membres ont un certificat valide sur le total — par exemple **4 / 10**.

### Un certificat est-il authentique ?

Ouvrez l’adresse de votre site + `/certificates/` + le numéro du certificat (ou scannez le QR code du PDF). La page affiche le nom, le cours, la date et le statut — **Valide** ou **Révoqué**. Tout le monde peut l’utiliser, même sans se connecter.

---

## 6. Que faire si…

| Situation | Que faire |
|---|---|
| L’apprenant n’a pas reçu l’e-mail | **Certificats** → la ligne → **Renvoyer l’e-mail**. Il peut aussi le télécharger depuis son compte. |
| Un certificat a été délivré par erreur | **Certificats** → **Révoquer**. La vérification publique affiche « Révoqué ». Vous changez d’avis ? **Restaurer**. |
| Le PDF est vide ou ne se télécharge pas | **Certificats** → **Régénérer le PDF**, puis **Télécharger** de nouveau. |
| Le nom sur un certificat est faux | **Certificats** → la ligne → **Modifier le nom**. Le PDF est réimprimé avec le même numéro. Utilisez **Renvoyer l’e-mail** si l’apprenant doit recevoir la copie corrigée. |
| Les e-mails de certificat n’arrivent jamais | **Paramètres → E-mail** indique si l’académie envoie vraiment des e-mails. Vérifiez avec **Envoyer un e-mail de test**. Les réglages eux-mêmes se trouvent dans le fichier `.env` du serveur. |
| Il vous faut une liste pour un rapport | **Certificats** → **Exporter en CSV** (ouvrez-le dans Excel ou Google Sheets). |
| Il vous faut la progression de tous, pas seulement les certificats | **Utilisateurs** → **Exporter la progression des apprenants**. Une ligne par apprenant : leçons faites, certificats, dernière activité. |
| Un apprenant n’a plus de tentatives | **Résultats → Tentatives de quiz** → activez le filtre **Plus de tentatives, non réussi** → **Accorder une autre tentative** sur sa ligne. Seul cet apprenant obtient un essai de plus. Pour le changer pour tout le monde, augmentez **Tentatives max.** sur le cours ou la leçon. |
| Les apprenants ne trouvent pas un cours | Vérifiez la colonne **Statut** dans **Cours**. Seuls les cours **Publiés** apparaissent sur le site — cliquez sur **Publier** sur la ligne. |
| Il manque une leçon dans un cours en ligne | **Leçons** → vérifiez son **Statut** → **Publier**. Une leçon n’apparaît que si elle *et* son cours sont publiés. |
| Un créateur dit que son cours a disparu | Vérifiez le champ **Produit / module** du cours. Un créateur ne voit que les cours rangés sous un produit qui lui est attribué dans **Utilisateurs**. |
| Vous devez mettre hors ligne un cours ou une leçon | La ligne → **Dépublier**. Rien n’est perdu ; republiez quand vous voulez. |
| Quelque chose est cassé pour les apprenants | **Contenu → État du contenu** → **Corriger** sur la ligne. |

---

## 7. Questions fréquentes

**Les certificats expirent-ils ?**
Non. Ils sont permanents et ne sont jamais supprimés quand un cours change.

**Un apprenant peut-il repasser le quiz final après l’avoir réussi ?**
Non. Une fois réussi, le certificat est délivré et le quiz final est verrouillé pour lui.

**D’où vient le nom sur le certificat ?**
L’apprenant tape son nom complet en commençant le quiz final, et il est imprimé exactement tel quel — il peut aussi le définir à l’avance dans **Profil** sur le site. Si un certificat porte déjà un nom erroné, ouvrez **Certificats** → **Modifier le nom** : le PDF est réimprimé avec le même numéro, et vous pouvez aussi enregistrer le nom corrigé pour ses futurs certificats.

**Et si l’e-mail n’est pas configuré sur le serveur ?**
Le certificat est quand même créé. L’apprenant peut le télécharger depuis son compte, et vous pouvez utiliser **Télécharger** ou **Renvoyer l’e-mail** depuis Certificats une fois l’e-mail opérationnel.

**Puis-je changer la note de réussite ?**
Oui — par cours, dans le bloc **Quiz final et certificat**.

**Combien de certificats un apprenant peut-il obtenir pour un cours ?**
Un certificat valide par cours.

**Puis-je tester le quiz final sans terminer toutes les leçons ?**
Oui. Les administrateurs peuvent ouvrir et passer directement le quiz final de n’importe quel cours (depuis la page du cours ou la barre latérale de la leçon), même sans avoir terminé les leçons — pratique pour vérifier vos questions et le certificat. Cela délivre un vrai certificat à votre compte administrateur, que vous pouvez révoquer ensuite dans **Certificats** si ce n’était qu’un test. Les apprenants doivent toujours terminer toutes les leçons d’abord.

---

## 8. Termes utilisés ici

| Terme | Sens |
|---|---|
| Quiz final | Le test portant sur tout le cours, passé après toutes les leçons. |
| Banque de questions | L’ensemble de questions dans lequel le quiz final puise. |
| Note de réussite | Le score dont un apprenant a besoin pour réussir, par exemple 80 %. |
| Tentative | Un essai au quiz final. |
| Certificat | Le PDF qui prouve qu’un apprenant a réussi un cours. |
| Révoquer | Annuler un certificat pour qu’il apparaisse comme non valide. |
| Partenaire | Une entreprise à laquelle appartiennent vos apprenants. |
| Administrateur | Gère toute la plateforme. |
| Créateur | Un responsable produit qui rédige la formation de son ou ses propres produits uniquement. |
| Apprenant | Une personne qui suit des cours. |
| Produit / module | Ce dont traite votre formation (GARM, PTM, …). Un cours appartient à un produit, et c’est ce qui décide quel Créateur en est responsable. |
| Brouillon | Un cours ou une leçon sur lequel vous travaillez encore. Seuls les administrateurs le voient. |
| Publié | En ligne sur le site des apprenants. Cours et leçons y arrivent par l’action **Publier** de leur liste. |
| Archivé | Retiré. Caché aux apprenants, mais son contenu, la progression et les certificats restent. |

## Traduction vidéo avec Descript

La traduction avec Descript est distincte du bouton **Traduire** habituel. Le
bouton habituel reste le moyen de traduire à la main les titres, les résumés des
cours et des leçons et le texte des leçons.

Un administrateur ouvre d’abord **Personnes → Utilisateurs**, ouvre le compte du
membre de l’équipe, coche **Utiliser la traduction vidéo de Descript** dans
**Autorisations supplémentaires** et enregistre. Le serveur doit aussi avoir
l’intégration facultative Descript activée. Ce droit n’est inclus automatiquement
ni dans le rôle Administrateur ni dans le rôle Créateur.

Sur une leçon avec une vidéo importée, l’éditeur autorisé peut cliquer sur
**Traduire la vidéo** (l’icône du haut-parleur, à côté de **Traduire**), choisir la vidéo et les langues, puis cocher la confirmation
que la vidéo sera envoyée à Descript et peut consommer des minutes média et des
crédits d’IA. Rien n’est envoyé avant le clic sur **Démarrer la traduction**. Les
liens YouTube ne peuvent pas être envoyés. Utilisez **Vérifier l’avancement**
jusqu’à ce que le résultat soit enregistré ; une langue terminée ne peut pas être
facturée deux fois.

Sur un **cours**, **Traduire les vidéos des leçons** fait la même chose pour toutes
les vidéos importées de ses leçons à la fois : cochez les langues et la
confirmation, et les traductions sont mises en file d’attente — rien ne démarre
avant que **Vérifier l’avancement** (ou la vérification planifiée du serveur) ne les
fasse avancer quelques-unes à la fois. Les vidéos ou langues déjà traduites ou en
cours sont ignorées et ne coûtent rien. Une traduction terminée donne aussi au
lecteur de la leçon des sous-titres dans cette langue (le bouton **CC**).

**Voix et sous-titres ensemble.** **Traduire la vidéo** produit toujours les
deux : Descript traduit la vidéo, la fait parler dans cette langue (avec la voix
de l’intervenant lui-même quand il peut la copier, sinon avec une voix standard)
et l’académie conserve la vidéo doublée et ses sous-titres. Un apprenant qui
consulte l’académie dans cette langue l’entend automatiquement, avec **Écouter
l’audio d’origine** à côté du lecteur et les sous-titres **CC** disponibles. Une
langue déjà produite n’est jamais refacturée. Chaque langue doublée est un fichier
vidéo supplémentaire complet ; surveillez donc le disque du serveur sur les
longues leçons.

## Permettre de télécharger les vidéos des leçons

Les administrateurs voient **Télécharger la vidéo** sous chaque vidéo importée d’une
leçon sur le site. Pour qu’un apprenant puisse aussi télécharger, ouvrez
**Personnes → Utilisateurs**, ouvrez son compte, cochez **Télécharger les vidéos
des leçons** dans **Autorisations supplémentaires** et enregistrez. Sans ce droit,
le bouton est absent et l’option de téléchargement du lecteur lui-même est masquée.
Les fichiers importés sont tout de même envoyés au navigateur pour être lus : cela
dissuade les gens honnêtes de télécharger, mais ce n’est pas une protection contre
la copie. Les vidéos YouTube ne peuvent pas être téléchargées.

## Supprimer et la corbeille

Supprimer un cours, une leçon, une question du quiz final, une étude de cas, un
tutoriel, un webinaire, un produit ou un média le **masque** ; il n’est pas effacé.
Il disparaît aussitôt du panneau et du site, et peut être récupéré.

- **Un cours supprimé** conserve les certificats, tentatives et avis de ses
  apprenants. Ses leçons ne sont pas supprimées : une leçon qui est aussi dans un
  autre cours y reste, et les autres deviennent **Dans aucun cours** dans
  **Contenu → Leçons**.
- **Une leçon supprimée** retire ses questions de tous les quiz finaux.
- **Un produit ou une image supprimés** sont retirés des cours et leçons qui les
  utilisaient, comme avant.
- **Les utilisateurs et les partenaires, c’est différent : leur suppression est
  définitive.** Rien n’est conservé, pour qu’une personne qui demande l’effacement
  de ses données les voie effacées.

**Pour restaurer quelque chose**, ouvrez **Paramètres → Général → Corbeille**,
choisissez le type de contenu dans la rangée d’onglets et appuyez sur **Restaurer**
sur sa ligne. Un cours restauré récupère ses leçons ; une leçon restaurée récupère
ses questions. Un produit ou une image restaurés reviennent seuls — remettez-les à
la main sur les cours ou les leçons.

La Corbeille est réservée aux administrateurs à qui l’on a donné ce droit. Un
administrateur ouvre le compte de la personne dans **Personnes → Utilisateurs**,
coche **Ouvrir la corbeille et restaurer le contenu supprimé (administrateurs
uniquement)** dans **Autorisations supplémentaires** et enregistre. Être
administrateur ne suffit pas à lui seul.

Rien ne peut être supprimé définitivement depuis le panneau. Un cours, une étude de
cas, un tutoriel, un webinaire ou un produit supprimé garde aussi son adresse web
(slug) réservée, si bien qu’un nouveau ne peut pas la réutiliser tant que l’ancien
n’est pas restauré ou retiré de la base de données.

## Traduire du texte avec DeepL, ChatGPT ou DeepSeek

Un administrateur ajoute le jeton d’API de chaque fournisseur dans **Paramètres →
Intégrations** et l’active. Les administrateurs peuvent s’en servir tout de suite ;
les autres ont besoin du droit correspondant (**Générer des brouillons de traduction
avec DeepL**, ou **… avec ChatGPT ou DeepSeek**, dans **Personnes → Utilisateurs →
Autorisations supplémentaires**). Ils voient alors **Traduire** sur une
leçon, une étude de cas, un tutoriel ou un webinaire devenir un menu : **Traduire à
la main**, ou traduire avec chaque fournisseur activé. Après une confirmation, la
fenêtre de traduction s’ouvre avec les cases vides pré-rédigées ; lisez-les et
corrigez-les, puis appuyez sur **Enregistrer les traductions**. Rien n’est enregistré
avant, et un texte déjà écrit n’est jamais remplacé.

Sur un **cours**, **Traduire** demande le fournisseur à sa première étape, avec
ce qu’il faut traduire (**Le cours seul** ou **Le cours entier**, avec chaque leçon
et chaque quiz) et la langue. Une seule langue passe par une étape de relecture
avant l’enregistrement. **Toutes les langues à la fois** n’est proposé qu’avec un
fournisseur : chaque langue est traduite et enregistrée aussitôt, dans les cases
vides uniquement — sauf si vous cochez **Remplacer** —, rouvrez donc **Traduire**
pour une langue afin de relire le résultat. Le texte de tout le cours est envoyé
plusieurs fois, cela coûte donc plus cher.

---

*Ce guide est également affiché dans le panneau d’administration, dans **Documentation → Guide**. Quand les fonctions d’administration changent, nous mettons à jour la section concernée de ce fichier.*
