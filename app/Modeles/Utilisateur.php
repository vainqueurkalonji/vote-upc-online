<?php

declare(strict_types=1);

namespace Application\Modeles;

class Utilisateur extends Modele
{
    public function trouverParIdentifiant(string $identifiant): ?array
    {
        $requete = $this->db->prepare(
            "SELECT
                u.id,
                u.role_id,
                r.code AS role_code,
                r.nom AS role_nom,
                u.nom,
                u.post_nom,
                u.prenom,
                u.nom_utilisateur,
                u.email,
                u.mot_de_passe,
                u.statut,
                u.mot_de_passe_temporaire,
                u.tentatives_connexion,
                u.derniere_connexion_le
             FROM utilisateurs u
             INNER JOIN roles r ON r.id = u.role_id
             WHERE u.statut <> 'supprime'
             AND (
                LOWER(u.email) = LOWER(:identifiant)
                OR LOWER(u.nom_utilisateur) = LOWER(:identifiant)
             )
             LIMIT 1"
        );

        $requete->execute(['identifiant' => trim($identifiant)]);
        $utilisateur = $requete->fetch();

        return $utilisateur ?: null;
    }

    public function trouverParId(int $id): ?array
    {
        $requete = $this->db->prepare(
            "SELECT
                u.id,
                u.role_id,
                r.code AS role_code,
                r.nom AS role_nom,
                u.nom,
                u.post_nom,
                u.prenom,
                u.nom_utilisateur,
                u.email,
                u.mot_de_passe,
                u.statut,
                u.mot_de_passe_temporaire,
                u.tentatives_connexion,
                u.derniere_connexion_le
             FROM utilisateurs u
             INNER JOIN roles r ON r.id = u.role_id
             WHERE u.id = :id
             AND u.statut <> 'supprime'
             LIMIT 1"
        );

        $requete->execute(['id' => $id]);
        $utilisateur = $requete->fetch();

        return $utilisateur ?: null;
    }

    public function existeSuperAdministrateur(): bool
    {
        $requete = $this->db->query(
            "SELECT COUNT(*) AS total
             FROM utilisateurs u
             INNER JOIN roles r ON r.id = u.role_id
             WHERE r.code = 'super_administrateur'
             AND u.statut <> 'supprime'"
        );

        return (int) $requete->fetch()['total'] > 0;
    }

    public function creerSuperAdministrateur(array $donnees): int
    {
        $this->db->beginTransaction();

        try {
            $requeteRole = $this->db->prepare('SELECT id FROM roles WHERE code = :code LIMIT 1');
            $requeteRole->execute(['code' => 'super_administrateur']);
            $role = $requeteRole->fetch();

            if (!$role) {
                throw new \RuntimeException('Le rôle super administrateur est introuvable.');
            }

            $requete = $this->db->prepare(
                "INSERT INTO utilisateurs (
                    role_id, nom, post_nom, prenom, nom_utilisateur,
                    email, mot_de_passe, statut, cree_le
                ) VALUES (
                    :role_id, :nom, :post_nom, :prenom, :nom_utilisateur,
                    :email, :mot_de_passe, 'actif', NOW()
                ) RETURNING id"
            );

            $requete->execute([
                'role_id' => $role['id'],
                'nom' => $donnees['nom'],
                'post_nom' => $donnees['post_nom'],
                'prenom' => $donnees['prenom'],
                'nom_utilisateur' => $donnees['nom_utilisateur'],
                'email' => strtolower($donnees['email']),
                'mot_de_passe' => password_hash($donnees['mot_de_passe'], PASSWORD_DEFAULT),
            ]);

            $id = (int) $requete->fetch()['id'];
            $this->db->commit();

            return $id;
        } catch (\Throwable $exception) {
            $this->db->rollBack();
            throw $exception;
        }
    }

    public function enregistrerConnexionReussie(int $id): void
    {
        $requete = $this->db->prepare(
            "UPDATE utilisateurs
             SET tentatives_connexion = 0,
                 derniere_connexion_le = NOW(),
                 modifie_le = NOW()
             WHERE id = :id"
        );

        $requete->execute(['id' => $id]);
    }

    public function changerMotDePasse(int $id, string $nouveauMotDePasse, bool $motDePasseTemporaire = false): void
    {
        $requete = $this->db->prepare(
            "UPDATE utilisateurs
             SET mot_de_passe = :mot_de_passe,
                 mot_de_passe_temporaire = :mot_de_passe_temporaire,
                 tentatives_connexion = 0,
                 modifie_le = NOW()
             WHERE id = :id
             AND statut <> 'supprime'"
        );

        $requete->bindValue('mot_de_passe', password_hash($nouveauMotDePasse, PASSWORD_DEFAULT));
        $requete->bindValue('mot_de_passe_temporaire', $motDePasseTemporaire, \PDO::PARAM_BOOL);
        $requete->bindValue('id', $id, \PDO::PARAM_INT);
        $requete->execute();
    }

    public function enregistrerTentativeEchouee(int $id, int $nombreMaxTentatives): int
    {
        $this->db->beginTransaction();

        try {
            $requete = $this->db->prepare(
                "UPDATE utilisateurs
                 SET tentatives_connexion = tentatives_connexion + 1,
                     modifie_le = NOW()
                 WHERE id = :id
                 RETURNING tentatives_connexion"
            );

            $requete->execute(['id' => $id]);
            $tentatives = (int) $requete->fetch()['tentatives_connexion'];

            if ($tentatives >= $nombreMaxTentatives) {
                $requeteBlocage = $this->db->prepare(
                    "UPDATE utilisateurs
                     SET statut = 'bloque',
                         modifie_le = NOW()
                     WHERE id = :id"
                );
                $requeteBlocage->execute(['id' => $id]);
            }

            $this->db->commit();

            return $tentatives;
        } catch (\Throwable $exception) {
            $this->db->rollBack();
            throw $exception;
        }
    }
}
