<?php

declare(strict_types=1);

return [
    'nom' => env_valeur('APP_NOM', 'VOTE UPC ONLINE'),
    'url' => env_valeur('APP_URL', env_valeur('RENDER_EXTERNAL_URL', 'https://vote-upc-online.cd')),
    'environnement' => env_valeur('APP_ENV', 'local'),
];

