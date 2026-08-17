<?php

declare(strict_types=1);

namespace Application\Modeles;

use Application\Services\ServiceReglesMetier;
use PDO;

class JournalActivite extends Modele
{
    private const ROLES_SUIVIS = [
        ServiceReglesMetier::ROLE_APPARITEUR,
        ServiceReglesMetier::ROLE_PRESIDENT_ELECTORAL,
    ];

    public function enregistrerPourRole(array $utilisateur, string $action, ?string $entite = null, ?int $entiteId = null, array $details = []): void
    {
        $role = (string) ($utilisateur['role_code'] ?? '');

        if (!in_array($role, self::ROLES_SUIVIS, true)) {
            return;
        }

        $this->enregistrer((int) $utilisateur['id'], $action, $entite, $entiteId, $details);
    }

    public function enregistrer(int $utilisateurId, string $action, ?string $entite = null, ?int $entiteId = null, array $details = []): void
    {
        if ($utilisateurId <= 0) {
            return;
        }

        try {
            $requete = $this->db->prepare(
                "INSERT INTO journaux_actions (utilisateur_id, action, entite, entite_id, adresse_ip, details, cree_le)
                 VALUES (:utilisateur_id, :action, :entite, :entite_id, :adresse_ip, :details, NOW())"
            );
            $requete->execute([
                'utilisateur_id' => $utilisateurId,
                'action' => $action,
                'entite' => $entite,
                'entite_id' => $entiteId,
                'adresse_ip' => (string) ($_SERVER['REMOTE_ADDR'] ?? ''),
                'details' => json_encode($details, JSON_THROW_ON_ERROR),
            ]);
        } catch (\Throwable) {
            // Le journal ne doit jamais bloquer une action metier.
        }
    }

    public function listerPourSuperAdministrateur(string $role = '', string $action = '', string $date = '', int $limite = 120): array
    {
        $conditions = ["r.code IN ('appariteur', 'president_electoral')"];
        $parametres = [];

        if (in_array($role, self::ROLES_SUIVIS, true)) {
            $conditions[] = 'r.code = :role';
            $parametres['role'] = $role;
        }

        if ($action !== '') {
            $conditions[] = 'j.action = :action';
            $parametres['action'] = $action;
        }

        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            $conditions[] = 'DATE(j.cree_le) = :date_journal';
            $parametres['date_journal'] = $date;
        }

        $requete = $this->db->prepare(
            "SELECT
                j.id,
                j.action,
                j.entite,
                j.entite_id,
                j.adresse_ip,
                j.details,
                j.cree_le,
                u.nom,
                u.post_nom,
                u.prenom,
                u.nom_utilisateur,
                u.email,
                r.code AS role_code,
                r.nom AS role_nom,
                f.code AS faculte_code,
                f.nom AS faculte_nom
             FROM journaux_actions j
             INNER JOIN utilisateurs u ON u.id = j.utilisateur_id
             INNER JOIN roles r ON r.id = u.role_id
             LEFT JOIN appariteurs a ON a.utilisateur_id = u.id
             LEFT JOIN facultes f ON f.id = a.faculte_id
             WHERE " . implode(' AND ', $conditions) . "
             ORDER BY j.cree_le DESC, j.id DESC
             LIMIT :limite"
        );

        foreach ($parametres as $cle => $valeur) {
            $requete->bindValue($cle, $valeur);
        }
        $requete->bindValue('limite', max(20, min(300, $limite)), PDO::PARAM_INT);
        $requete->execute();

        return array_map(function (array $ligne): array {
            $details = json_decode((string) ($ligne['details'] ?? '{}'), true);
            $ligne['details'] = is_array($details) ? $details : [];

            return $ligne;
        }, $requete->fetchAll());
    }

    public function statistiques(string $date = ''): array
    {
        $conditions = ["r.code IN ('appariteur', 'president_electoral')"];
        $parametres = [];

        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            $conditions[] = 'DATE(j.cree_le) = :date_journal';
            $parametres['date_journal'] = $date;
        }

        $requete = $this->db->prepare(
            "SELECT
                COUNT(j.id) AS total,
                SUM(CASE WHEN j.action = 'connexion_reussie' THEN 1 ELSE 0 END) AS connexions,
                SUM(CASE WHEN j.action = 'deconnexion' THEN 1 ELSE 0 END) AS deconnexions,
                SUM(CASE WHEN r.code = 'appariteur' THEN 1 ELSE 0 END) AS appariteurs,
                SUM(CASE WHEN r.code = 'president_electoral' THEN 1 ELSE 0 END) AS presidents
             FROM journaux_actions j
             INNER JOIN utilisateurs u ON u.id = j.utilisateur_id
             INNER JOIN roles r ON r.id = u.role_id
             WHERE " . implode(' AND ', $conditions)
        );
        $requete->execute($parametres);
        $ligne = $requete->fetch() ?: [];

        return [
            'total' => (int) ($ligne['total'] ?? 0),
            'connexions' => (int) ($ligne['connexions'] ?? 0),
            'deconnexions' => (int) ($ligne['deconnexions'] ?? 0),
            'appariteurs' => (int) ($ligne['appariteurs'] ?? 0),
            'presidents' => (int) ($ligne['presidents'] ?? 0),
        ];
    }

    public function actionsDisponibles(): array
    {
        return [
            'connexion_reussie',
            'deconnexion',
            'activation_etudiant',
            'reactivation_etudiant',
            'validation_lancement_validee',
            'validation_lancement_refusee',
            'publication_resultats',
            'annulation_faculte',
        ];
    }
}
