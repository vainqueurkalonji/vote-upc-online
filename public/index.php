<?php

declare(strict_types=1);

define('CHEMIN_RACINE', dirname(__DIR__));

require CHEMIN_RACINE . '/app/Aides/fonctions.php';

$autoloadComposer = CHEMIN_RACINE . '/vendor/autoload.php';
if (is_file($autoloadComposer)) {
    require $autoloadComposer;
}

require CHEMIN_RACINE . '/app/Noyau/Autoload.php';

charger_variables_env(CHEMIN_RACINE . '/.env');

$fuseauHoraire = (string) env_valeur('APP_FUSEAU_HORAIRE', 'America/Los_Angeles');
if (in_array($fuseauHoraire, timezone_identifiers_list(), true)) {
    date_default_timezone_set($fuseauHoraire);
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$routeur = new Application\Noyau\Routeur();

require CHEMIN_RACINE . '/routes/web.php';

$routeur->resoudre($_SERVER['REQUEST_METHOD'], $_SERVER['REQUEST_URI']);
