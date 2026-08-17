<?php

declare(strict_types=1);

$databaseUrl = (string) env_valeur('DATABASE_URL', '');

if ($databaseUrl !== '') {
    $composants = parse_url($databaseUrl);

    if (is_array($composants) && !empty($composants['host']) && !empty($composants['path'])) {
        $parametres = [];

        if (!empty($composants['query'])) {
            parse_str((string) $composants['query'], $parametres);
        }

        return [
            'pilote' => 'pgsql',
            'hote' => (string) $composants['host'],
            'port' => (string) ($composants['port'] ?? '5432'),
            'nom' => ltrim((string) $composants['path'], '/'),
            'utilisateur' => rawurldecode((string) ($composants['user'] ?? '')),
            'mot_de_passe' => rawurldecode((string) ($composants['pass'] ?? '')),
            'sslmode' => (string) ($parametres['sslmode'] ?? ''),
        ];
    }
}

return [
    'pilote' => 'pgsql',
    'hote' => env_valeur('DB_HOTE', '127.0.0.1'),
    'port' => env_valeur('DB_PORT', '5432'),
    'nom' => env_valeur('DB_NOM', 'vote_upc'),
    'utilisateur' => env_valeur('DB_UTILISATEUR', 'postgres'),
    'mot_de_passe' => env_valeur('DB_MOT_DE_PASSE', ''),
    'sslmode' => env_valeur('DB_SSLMODE', ''),
];
