<?php

declare(strict_types=1);

namespace Application\Modeles;

use RuntimeException;

class PersonnelAdministratif extends Modele
{
    public function listerFacultes(): array
    {
        $requete = $this->db->query(
            "SELECT id, code, nom
             FROM facultes
             WHERE active = TRUE
             ORDER BY code"
        );

        return $requete->fetchAll();
    }

    public function listerAppariteurs(): array
    {
        $requete = $this->db->query(
            "SELECT
                a.id,
                a.statut,
                u.nom,
                u.post_nom,
                u.prenom,
                u.nom_utilisateur,
                u.email,
                u.id AS utilisateur_id,
                f.code AS faculte_code,
                f.nom AS faculte_nom,
                a.cree_le
             FROM appariteurs a
             INNER JOIN utilisateurs u ON u.id = a.utilisateur_id
             INNER JOIN facultes f ON f.id = a.faculte_id
             WHERE a.statut <> 'supprime'
             ORDER BY f.code, u.nom, u.post_nom, u.prenom"
        );

        return $requete->fetchAll();
    }

    public function listerPresidentsElectoraux(): array
    {
        $requete = $this->db->query(
            "SELECT
                p.id,
                p.statut,
                u.nom,
                u.post_nom,
                u.prenom,
                u.nom_utilisateur,
                u.email,
                u.id AS utilisateur_id,
                p.cree_le
             FROM presidents_electoraux p
             INNER JOIN utilisateurs u ON u.id = p.utilisateur_id
             WHERE p.statut <> 'supprime'
             ORDER BY u.nom, u.post_nom, u.prenom"
        );

        return $requete->fetchAll();
    }

    public function trouverAppariteur(int $appariteurId): ?array
    {
        $requete = $this->db->prepare(
            "SELECT
                a.id,
                a.faculte_id,
                a.statut,
                u.id AS utilisateur_id,
                u.nom,
                u.post_nom,
                u.prenom,
                u.nom_utilisateur,
                u.email,
                f.code AS faculte_code,
                f.nom AS faculte_nom
             FROM appariteurs a
             INNER JOIN utilisateurs u ON u.id = a.utilisateur_id
             INNER JOIN facultes f ON f.id = a.faculte_id
             WHERE a.id = :id
             AND a.statut <> 'supprime'
             LIMIT 1"
        );
        $requete->execute(['id' => $appariteurId]);
        $ligne = $requete->fetch();

        return $ligne ?: null;
    }

    public function trouverPresidentElectoral(int $presidentId): ?array
    {
        $requete = $this->db->prepare(
            "SELECT
                p.id,
                p.statut,
                u.id AS utilisateur_id,
                u.nom,
                u.post_nom,
                u.prenom,
                u.nom_utilisateur,
                u.email
             FROM presidents_electoraux p
             INNER JOIN utilisateurs u ON u.id = p.utilisateur_id
             WHERE p.id = :id
             AND p.statut <> 'supprime'
             LIMIT 1"
        );
        $requete->execute(['id' => $presidentId]);
        $ligne = $requete->fetch();

        return $ligne ?: null;
    }

    public function modifierAppariteur(int $appariteurId, array $donnees, int $modifieParId): void
    {
        $appariteur = $this->trouverAppariteur($appariteurId);
        if (!$appariteur) {
            throw new RuntimeException('Appariteur introuvable.');
        }

        $this->verifierIdentifiantsDisponibles(
            (string) $donnees['email'],
            (string) $donnees['nom_utilisateur'],
            (int) $appariteur['utilisateur_id']
        );
        $this->verifierFaculteActive((int) $donnees['faculte_id']);

        $this->db->beginTransaction();

        try {
            $this->mettreAJourUtilisateur((int) $appariteur['utilisateur_id'], $donnees);

            $requete = $this->db->prepare(
                "UPDATE appariteurs
                 SET faculte_id = :faculte_id,
                     modifie_le = NOW()
                 WHERE id = :id"
            );
            $requete->execute([
                'faculte_id' => (int) $donnees['faculte_id'],
                'id' => $appariteurId,
            ]);

            $this->journaliser($modifieParId, 'modification_appariteur', 'appariteurs', $appariteurId, [
                'email' => strtolower((string) $donnees['email']),
                'faculte_id' => (int) $donnees['faculte_id'],
            ]);

            $this->db->commit();
        } catch (\Throwable $exception) {
            $this->db->rollBack();
            throw $exception;
        }
    }

    public function modifierPresidentElectoral(int $presidentId, array $donnees, int $modifieParId): void
    {
        $president = $this->trouverPresidentElectoral($presidentId);
        if (!$president) {
            throw new RuntimeException('President electoral introuvable.');
        }

        $this->verifierIdentifiantsDisponibles(
            (string) $donnees['email'],
            (string) $donnees['nom_utilisateur'],
            (int) $president['utilisateur_id']
        );

        $this->db->beginTransaction();

        try {
            $this->mettreAJourUtilisateur((int) $president['utilisateur_id'], $donnees);
            $this->journaliser($modifieParId, 'modification_president_electoral', 'presidents_electoraux', $presidentId, [
                'email' => strtolower((string) $donnees['email']),
            ]);

            $this->db->commit();
        } catch (\Throwable $exception) {
            $this->db->rollBack();
            throw $exception;
        }
    }

    public function creerAppariteur(array $donnees, int $creeParId, string $motDePasseTemporaire): array
    {
        $this->verifierIdentifiantsDisponibles($donnees['email'], $donnees['nom_utilisateur']);

        $this->db->beginTransaction();

        try {
            $utilisateurId = $this->creerUtilisateur('appariteur', $donnees, $motDePasseTemporaire);

            $requete = $this->db->prepare(
                "INSERT INTO appariteurs (utilisateur_id, faculte_id, statut, cree_par, cree_le)
                 VALUES (:utilisateur_id, :faculte_id, 'actif', :cree_par, NOW())
                 RETURNING id"
            );
            $requete->execute([
                'utilisateur_id' => $utilisateurId,
                'faculte_id' => (int) $donnees['faculte_id'],
                'cree_par' => $creeParId,
            ]);

            $appariteurId = (int) $requete->fetch()['id'];
            $this->journaliser($creeParId, 'creation_appariteur', 'appariteurs', $appariteurId, [
                'email' => strtolower((string) $donnees['email']),
                'faculte_id' => (int) $donnees['faculte_id'],
            ]);

            $this->db->commit();

            return $this->trouverUtilisateur($utilisateurId);
        } catch (\Throwable $exception) {
            $this->db->rollBack();
            throw $exception;
        }
    }

    public function creerPresidentElectoral(array $donnees, int $creeParId, string $motDePasseTemporaire): array
    {
        $this->verifierIdentifiantsDisponibles($donnees['email'], $donnees['nom_utilisateur']);

        $this->db->beginTransaction();

        try {
            $utilisateurId = $this->creerUtilisateur('president_electoral', $donnees, $motDePasseTemporaire);

            $requete = $this->db->prepare(
                "INSERT INTO presidents_electoraux (utilisateur_id, statut, cree_par, cree_le)
                 VALUES (:utilisateur_id, 'actif', :cree_par, NOW())
                 RETURNING id"
            );
            $requete->execute([
                'utilisateur_id' => $utilisateurId,
                'cree_par' => $creeParId,
            ]);

            $presidentId = (int) $requete->fetch()['id'];
            $this->journaliser($creeParId, 'creation_president_electoral', 'presidents_electoraux', $presidentId, [
                'email' => strtolower((string) $donnees['email']),
            ]);

            $this->db->commit();

            return $this->trouverUtilisateur($utilisateurId);
        } catch (\Throwable $exception) {
            $this->db->rollBack();
            throw $exception;
        }
    }

    private function creerUtilisateur(string $roleCode, array $donnees, string $motDePasseTemporaire): int
    {
        $roleId = $this->roleId($roleCode);
        $requete = $this->db->prepare(
            "INSERT INTO utilisateurs (
                role_id,
                nom,
                post_nom,
                prenom,
                nom_utilisateur,
                email,
                mot_de_passe,
                statut,
                mot_de_passe_temporaire,
                cree_le
             ) VALUES (
                :role_id,
                :nom,
                :post_nom,
                :prenom,
                :nom_utilisateur,
                :email,
                :mot_de_passe,
                'actif',
                TRUE,
                NOW()
             )
             RETURNING id"
        );

        $requete->execute([
            'role_id' => $roleId,
            'nom' => $donnees['nom'],
            'post_nom' => $donnees['post_nom'],
            'prenom' => $donnees['prenom'],
            'nom_utilisateur' => $donnees['nom_utilisateur'],
            'email' => strtolower((string) $donnees['email']),
            'mot_de_passe' => password_hash($motDePasseTemporaire, PASSWORD_DEFAULT),
        ]);

        return (int) $requete->fetch()['id'];
    }

    private function roleId(string $roleCode): int
    {
        $requete = $this->db->prepare('SELECT id FROM roles WHERE code = :code LIMIT 1');
        $requete->execute(['code' => $roleCode]);
        $role = $requete->fetch();

        if (!$role) {
            throw new RuntimeException('Role introuvable : ' . $roleCode);
        }

        return (int) $role['id'];
    }

    private function verifierIdentifiantsDisponibles(string $email, string $nomUtilisateur, ?int $utilisateurIgnoreId = null): void
    {
        $conditionIgnore = $utilisateurIgnoreId !== null ? ' AND id <> :utilisateur_ignore_id' : '';
        $requete = $this->db->prepare(
            "SELECT email, nom_utilisateur
             FROM utilisateurs
             WHERE (
                LOWER(email) = LOWER(:email)
                OR LOWER(nom_utilisateur) = LOWER(:nom_utilisateur)
             )
             " . $conditionIgnore . "
             LIMIT 1"
        );
        $parametres = [
            'email' => $email,
            'nom_utilisateur' => $nomUtilisateur,
        ];
        if ($utilisateurIgnoreId !== null) {
            $parametres['utilisateur_ignore_id'] = $utilisateurIgnoreId;
        }

        $requete->execute($parametres);
        $existant = $requete->fetch();

        if (!$existant) {
            return;
        }

        if (strtolower((string) $existant['email']) === strtolower($email)) {
            throw new RuntimeException('Cette adresse email existe deja.');
        }

        throw new RuntimeException('Ce nom utilisateur existe deja.');
    }

    private function verifierFaculteActive(int $faculteId): void
    {
        $requete = $this->db->prepare(
            "SELECT id
             FROM facultes
             WHERE id = :id
             AND active = TRUE
             LIMIT 1"
        );
        $requete->execute(['id' => $faculteId]);

        if (!$requete->fetch()) {
            throw new RuntimeException('Faculte de rattachement invalide.');
        }
    }

    private function mettreAJourUtilisateur(int $utilisateurId, array $donnees): void
    {
        $requete = $this->db->prepare(
            "UPDATE utilisateurs
             SET nom = :nom,
                 post_nom = :post_nom,
                 prenom = :prenom,
                 nom_utilisateur = :nom_utilisateur,
                 email = :email,
                 modifie_le = NOW()
             WHERE id = :id"
        );
        $requete->execute([
            'id' => $utilisateurId,
            'nom' => $donnees['nom'],
            'post_nom' => $donnees['post_nom'],
            'prenom' => $donnees['prenom'],
            'nom_utilisateur' => $donnees['nom_utilisateur'],
            'email' => strtolower((string) $donnees['email']),
        ]);
    }

    private function trouverUtilisateur(int $utilisateurId): array
    {
        $requete = $this->db->prepare(
            "SELECT
                u.id,
                u.nom,
                u.post_nom,
                u.prenom,
                u.nom_utilisateur,
                u.email,
                r.code AS role_code,
                r.nom AS role_nom
             FROM utilisateurs u
             INNER JOIN roles r ON r.id = u.role_id
             WHERE u.id = :id
             LIMIT 1"
        );
        $requete->execute(['id' => $utilisateurId]);

        return $requete->fetch() ?: [];
    }

    private function journaliser(int $utilisateurId, string $action, string $entite, int $entiteId, array $details): void
    {
        $requete = $this->db->prepare(
            "INSERT INTO journaux_actions (utilisateur_id, action, entite, entite_id, details, cree_le)
             VALUES (:utilisateur_id, :action, :entite, :entite_id, :details, NOW())"
        );
        $requete->execute([
            'utilisateur_id' => $utilisateurId,
            'action' => $action,
            'entite' => $entite,
            'entite_id' => $entiteId,
            'details' => json_encode($details, JSON_THROW_ON_ERROR),
        ]);
    }
}
