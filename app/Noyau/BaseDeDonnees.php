<?php

declare(strict_types=1);

namespace Application\Noyau;

use PDO;

class BaseDeDonnees
{
    private static ?PDO $connexion = null;

    public static function connexion(): PDO
    {
        if (self::$connexion !== null) {
            return self::$connexion;
        }

        $configuration = require CHEMIN_RACINE . '/configuration/base_de_donnees.php';
        $dsn = sprintf(
            'pgsql:host=%s;port=%s;dbname=%s',
            $configuration['hote'],
            $configuration['port'],
            $configuration['nom']
        );

        if (!empty($configuration['sslmode'])) {
            $dsn .= ';sslmode=' . $configuration['sslmode'];
        }

        self::$connexion = new PDO(
            $dsn,
            $configuration['utilisateur'],
            $configuration['mot_de_passe'],
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]
        );

        return self::$connexion;
    }
}

