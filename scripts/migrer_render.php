<?php

declare(strict_types=1);

define('CHEMIN_RACINE', dirname(__DIR__));

require CHEMIN_RACINE . '/app/Aides/fonctions.php';
require CHEMIN_RACINE . '/app/Noyau/Autoload.php';

charger_variables_env(CHEMIN_RACINE . '/.env');

$connexion = Application\Noyau\BaseDeDonnees::connexion();
$fichiers = glob(CHEMIN_RACINE . '/base_de_donnees/migrations/*.sql') ?: [];
sort($fichiers, SORT_STRING);

$donneesInitiales = CHEMIN_RACINE . '/base_de_donnees/donnees_initiales/001_facultes_promotions.sql';
if (is_file($donneesInitiales)) {
    $fichiers[] = $donneesInitiales;
}

foreach ($fichiers as $fichier) {
    $sql = file_get_contents($fichier);

    if ($sql === false) {
        throw new RuntimeException('Impossible de lire le fichier SQL : ' . $fichier);
    }

    echo 'Execution SQL : ' . basename($fichier) . PHP_EOL;
    $connexion->exec($sql);
}

echo 'Base de donnees prete.' . PHP_EOL;
