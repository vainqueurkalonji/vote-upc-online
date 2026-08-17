<?php

declare(strict_types=1);

spl_autoload_register(function (string $classe): void {
    $prefixe = 'Application\\';

    if (!str_starts_with($classe, $prefixe)) {
        return;
    }

    $cheminRelatif = substr($classe, strlen($prefixe));
    $cheminFichier = CHEMIN_RACINE . '/app/' . str_replace('\\', '/', $cheminRelatif) . '.php';

    if (is_file($cheminFichier)) {
        require $cheminFichier;
    }
});
