<?php

// Shipped with the code — see the note in lang/en/academy.php. A row in
// the translations table with the same key overrides a line.

return [
    'common' => [
        'thanks' => 'Merci,',
    ],

    'course_reminder' => [
        'subject' => 'Reprenez là où vous vous êtes arrêté - Pilot Academy',
        'heading' => 'Toujours là, :name ?',
        'intro' => 'Vous avez commencé sur Pilot Academy, et il ne reste plus grand-chose à rattraper.',
        'intro_progress' => 'Vous avez commencé sur Pilot Academy — :count leçon terminée jusqu’ici — et il ne reste plus grand-chose à rattraper.|Vous avez commencé sur Pilot Academy — :count leçons terminées jusqu’ici — et il ne reste plus grand-chose à rattraper.',
        'button' => 'Reprendre là où vous en étiez',
        'personal' => 'Ce lien vous connecte directement et vous emmène à la leçon suivante : aucun mot de passe à retenir. Il vous est personnel — merci de ne pas le transférer.',
    ],

    'certificate_issued' => [
        'subject' => 'Votre certificat « :course »',
        'heading' => 'Félicitations, :name !',
        'passed' => 'Vous avez réussi le quiz final de **:course** avec un score de **:score %** et obtenu votre certificat.',
        'attached' => 'Votre certificat est joint à cet e-mail au format PDF. Son numéro unique est **:number**.',
        'button' => 'Vérifier le certificat',
        'anyone' => 'Tout le monde peut vérifier l’authenticité de ce certificat grâce au lien ci-dessus.',
    ],

    'mail_check' => [
        'subject' => 'Pilot Academy : e-mail de test',
        'heading' => 'Ceci est un e-mail de test',
        'sent_by' => ':name l’a envoyé depuis **Paramètres → E-mail** dans le panneau d’administration de Pilot Academy le :date.',
        'works' => 'Si vous lisez ceci, l’académie peut envoyer des e-mails : les certificats et les rappels parviendront aux apprenants.',
        'links' => 'Les liens des e-mails de l’académie commencent par :url. Si ce n’est pas l’adresse utilisée pour ouvrir l’académie, le logo et les liens des e-mails de certificat seront cassés.',
    ],

    'certificate_pdf' => [
        'title' => 'Certificat de réussite',
        'certifies' => 'Nous certifions que',
        'completed' => 'a suivi avec succès le cours',
        'scan' => 'Scanner pour vérifier',
        'number' => 'Certificat n°',
        'issued' => 'Délivré le',
    ],
];
