<?php

declare(strict_types=1);

return [
    'transport' => env_valeur('MAIL_TRANSPORT', 'smtp'),
    'hote' => env_valeur('MAIL_HOTE', 'smtp.gmail.com'),
    'port' => (int) env_valeur('MAIL_PORT', '587'),
    'chiffrement' => env_valeur('MAIL_CHIFFREMENT', 'tls'),
    'utilisateur' => env_valeur('MAIL_UTILISATEUR', ''),
    'mot_de_passe' => env_valeur('MAIL_MOT_DE_PASSE', ''),
    'adresse_expediteur' => env_valeur('MAIL_ADRESSE_EXPEDITEUR', 'noreply@vote-upc-online.cd'),
    'nom_expediteur' => env_valeur('MAIL_NOM_EXPEDITEUR', 'VOTE UPC ONLINE'),
    'reponse_a' => env_valeur('MAIL_REPONSE_A', ''),
];
