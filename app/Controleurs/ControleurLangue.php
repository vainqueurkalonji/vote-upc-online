<?php

declare(strict_types=1);

namespace Application\Controleurs;

class ControleurLangue
{
    public function changer(): void
    {
        $choix = (string) ($_GET['choix'] ?? 'fr');

        if (in_array($choix, ['fr', 'en'], true)) {
            $_SESSION['langue'] = $choix;
        }

        $retour = (string) ($_GET['retour'] ?? '/');

        if (!str_starts_with($retour, '/')) {
            $retour = '/';
        }

        rediriger($retour);
    }
}
