<?php

declare(strict_types=1);

namespace Application\Controleurs\SuperAdministrateur;

use Application\Modeles\StatistiquesSuperAdministrateur;
use Application\Noyau\Controleur;
use Application\Services\ServiceAuthentification;
use Application\Services\ServiceReglesMetier;

class ControleurTableauDeBord extends Controleur
{
    public function afficher(): void
    {
        $utilisateur = ServiceAuthentification::exigerRole(ServiceReglesMetier::ROLE_SUPER_ADMINISTRATEUR);
        $statistiques = [
            'facultes' => 0,
            'promotions' => 0,
            'etudiants' => 0,
            'utilisateurs' => 0,
        ];

        try {
            $statistiques = (new StatistiquesSuperAdministrateur())->resume();
        } catch (\Throwable) {
            // La page reste accessible meme si un compteur echoue.
        }

        $this->vue('super_administrateur/tableau_de_bord', [
            'titre' => traduire('super_admin.tableau_de_bord.titre'),
            'utilisateur' => $utilisateur,
            'statistiques' => $statistiques,
        ], 'application');
    }
}
