<?php

declare(strict_types=1);

namespace Application\Noyau;

class Controleur
{
    protected function vue(string $cheminVue, array $donnees = [], string $layout = 'authentification'): void
    {
        $fichierVue = CHEMIN_RACINE . '/app/Vues/' . $cheminVue . '.php';
        $fichierLayout = CHEMIN_RACINE . '/app/Vues/layouts/' . $layout . '.php';

        if (!is_file($fichierVue)) {
            throw new \RuntimeException('Vue introuvable : ' . $cheminVue);
        }

        extract($donnees, EXTR_SKIP);

        ob_start();
        require $fichierVue;
        $contenu = ob_get_clean();

        if (is_file($fichierLayout)) {
            require $fichierLayout;
            return;
        }

        echo $contenu;
    }
}
