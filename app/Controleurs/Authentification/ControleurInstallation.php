<?php

declare(strict_types=1);

namespace Application\Controleurs\Authentification;

use Application\Modeles\Utilisateur;
use Application\Noyau\Controleur;
use Application\Services\ServiceAuthentification;
use Application\Validateurs\ValidateurSuperAdministrateur;

class ControleurInstallation extends Controleur
{
    public function accueil(): void
    {
        if (ServiceAuthentification::estConnecte()) {
            $utilisateur = ServiceAuthentification::utilisateur();
            rediriger(ServiceAuthentification::cheminApresConnexion($utilisateur['role_code'] ?? ''));
        }

        [$basePrete, $superAdminExiste] = $this->etatInstallation();

        if ($basePrete && $superAdminExiste) {
            rediriger('/administration/connexion');
        }

        rediriger('/installation/super-administrateur');
    }

    public function afficherCreationSuperAdministrateur(): void
    {
        [$basePrete, $superAdminExiste] = $this->etatInstallation();

        if ($basePrete && $superAdminExiste) {
            rediriger('/administration/connexion');
        }

        $this->vue('authentification/installation_super_admin', [
            'titre' => 'Première inscription super administrateur',
            'base_prete' => $basePrete,
            'erreurs' => [],
            'anciennes_donnees' => [],
        ]);
    }

    public function enregistrerSuperAdministrateur(): void
    {
        [$basePrete, $superAdminExiste] = $this->etatInstallation();

        if ($basePrete && $superAdminExiste) {
            rediriger('/administration/connexion');
        }

        $donnees = [
            'nom' => trim((string) ($_POST['nom'] ?? '')),
            'post_nom' => trim((string) ($_POST['post_nom'] ?? '')),
            'prenom' => trim((string) ($_POST['prenom'] ?? '')),
            'nom_utilisateur' => trim((string) ($_POST['nom_utilisateur'] ?? '')),
            'email' => strtolower(trim((string) ($_POST['email'] ?? ''))),
            'mot_de_passe' => (string) ($_POST['mot_de_passe'] ?? ''),
            'confirmation_mot_de_passe' => (string) ($_POST['confirmation_mot_de_passe'] ?? ''),
        ];

        $erreurs = ValidateurSuperAdministrateur::valider($donnees);

        if (!$basePrete) {
            $erreurs['base_de_donnees'] = "La base de données n'est pas encore prête. Exécute d'abord la migration SQL initiale.";
        }

        if (!empty($erreurs)) {
            $this->afficherFormulaireAvecErreurs($basePrete, $erreurs, $donnees);
            return;
        }

        try {
            (new Utilisateur())->creerSuperAdministrateur($donnees);
            rediriger('/administration/connexion?installation=ok');
        } catch (\Throwable $exception) {
            $this->afficherFormulaireAvecErreurs($basePrete, [
                'general' => "Impossible de créer le super administrateur : " . $exception->getMessage(),
            ], $donnees);
        }
    }

    private function etatInstallation(): array
    {
        try {
            return [true, (new Utilisateur())->existeSuperAdministrateur()];
        } catch (\Throwable) {
            return [false, false];
        }
    }

    private function afficherFormulaireAvecErreurs(bool $basePrete, array $erreurs, array $donnees): void
    {
        unset($donnees['mot_de_passe'], $donnees['confirmation_mot_de_passe']);

        $this->vue('authentification/installation_super_admin', [
            'titre' => 'Première inscription super administrateur',
            'base_prete' => $basePrete,
            'erreurs' => $erreurs,
            'anciennes_donnees' => $donnees,
        ]);
    }
}
