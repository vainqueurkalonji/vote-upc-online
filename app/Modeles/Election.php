<?php

declare(strict_types=1);

namespace Application\Modeles;

use Application\Services\ServiceReglesMetier;
use PDO;
use RuntimeException;

class Election extends Modele
{
    public function normaliserStatuts(): void
    {
        $maintenant = date('Y-m-d H:i:s');

        $requeteOuverture = $this->db->prepare(
            "UPDATE elections
             SET statut = 'ouverte',
                 modifie_le = NOW()
             WHERE statut = 'validee'
             AND :maintenant BETWEEN date_debut AND date_fin"
        );
        $requeteOuverture->execute(['maintenant' => $maintenant]);

        $requeteFermeture = $this->db->prepare(
            "UPDATE elections
             SET statut = 'en_attente_publication',
                 modifie_le = NOW()
             WHERE statut = 'ouverte'
             AND :maintenant > date_fin"
        );
        $requeteFermeture->execute(['maintenant' => $maintenant]);
    }

    public function statistiques(): array
    {
        $this->normaliserStatuts();
        $requete = $this->db->query(
            "SELECT
                COUNT(id) AS total,
                SUM(
                    CASE
                        WHEN statut = 'en_attente_validation_lancement'
                            OR (
                                statut = 'brouillon'
                                AND date_fin > NOW()
                                AND EXISTS (
                                    SELECT 1
                                    FROM candidats c
                                    WHERE c.election_id = elections.id
                                    AND c.statut = 'actif'
                                )
                            )
                        THEN 1
                        ELSE 0
                    END
                ) AS a_valider,
                SUM(CASE WHEN statut = 'ouverte' THEN 1 ELSE 0 END) AS ouvertes,
                SUM(CASE WHEN statut = 'en_attente_publication' THEN 1 ELSE 0 END) AS a_publier,
                SUM(CASE WHEN statut = 'publiee' THEN 1 ELSE 0 END) AS publiees,
                SUM(CASE WHEN statut = 'annulee' THEN 1 ELSE 0 END) AS annulees
             FROM elections"
        );
        $ligne = $requete->fetch() ?: [];

        return [
            'total' => (int) ($ligne['total'] ?? 0),
            'a_valider' => (int) ($ligne['a_valider'] ?? 0),
            'ouvertes' => (int) ($ligne['ouvertes'] ?? 0),
            'a_publier' => (int) ($ligne['a_publier'] ?? 0),
            'publiees' => (int) ($ligne['publiees'] ?? 0),
            'annulees' => (int) ($ligne['annulees'] ?? 0),
        ];
    }

    public function listerRecents(int $limite = 8): array
    {
        $this->normaliserStatuts();
        $requete = $this->db->prepare(
            "SELECT
                e.id,
                e.nom,
                e.description,
                e.portee_type,
                e.faculte_id,
                e.departement_id,
                e.promotion_id,
                e.date_debut,
                e.date_fin,
                e.statut,
                f.code AS faculte_code,
                p.code AS promotion_code,
                d.code AS departement_code,
                d.nom AS departement_nom,
                COUNT(c.id) AS total_candidats
             FROM elections e
             LEFT JOIN facultes f ON f.id = e.faculte_id
             LEFT JOIN promotions p ON p.id = e.promotion_id
             LEFT JOIN departements d ON d.id = e.departement_id
             LEFT JOIN candidats c ON c.election_id = e.id AND c.statut = 'actif'
             GROUP BY e.id, f.code, p.code, d.code, d.nom
             ORDER BY e.cree_le DESC, e.id DESC
             LIMIT :limite"
        );
        $requete->bindValue('limite', $limite, PDO::PARAM_INT);
        $requete->execute();

        return $requete->fetchAll();
    }

    public function listerPourRapportsParticipation(int $limite = 80): array
    {
        $this->normaliserStatuts();
        $requete = $this->db->prepare(
            "SELECT
                e.id,
                e.nom,
                e.description,
                e.portee_type,
                e.faculte_id,
                e.departement_id,
                e.promotion_id,
                e.date_debut,
                e.date_fin,
                e.statut,
                f.code AS faculte_code,
                p.code AS promotion_code,
                d.code AS departement_code,
                d.nom AS departement_nom,
                COUNT(c.id) AS total_candidats
             FROM elections e
             LEFT JOIN facultes f ON f.id = e.faculte_id
             LEFT JOIN promotions p ON p.id = e.promotion_id
             LEFT JOIN departements d ON d.id = e.departement_id
             LEFT JOIN candidats c ON c.election_id = e.id AND c.statut = 'actif'
             WHERE e.statut <> 'brouillon'
             GROUP BY e.id, f.code, p.code, d.code, d.nom
             ORDER BY e.date_debut DESC, e.id DESC
             LIMIT :limite"
        );
        $requete->bindValue('limite', $limite, PDO::PARAM_INT);
        $requete->execute();

        return $requete->fetchAll();
    }

    public function creer(array $donnees, int $creePar): int
    {
        $porteeType = (string) ($donnees['portee_type'] ?? ServiceReglesMetier::PORTEE_UNIVERSITE);
        $faculteId = !empty($donnees['faculte_id']) ? (int) $donnees['faculte_id'] : null;
        $promotionId = !empty($donnees['promotion_id']) ? (int) $donnees['promotion_id'] : null;
        $departementId = !empty($donnees['departement_id']) ? (int) $donnees['departement_id'] : null;

        $this->verifierPortee($porteeType, $faculteId, $departementId, $promotionId);

        $this->db->beginTransaction();

        try {
            $requete = $this->db->prepare(
                "INSERT INTO elections (
                    nom,
                    description,
                    portee_type,
                    faculte_id,
                    departement_id,
                    promotion_id,
                    date_debut,
                    date_fin,
                    statut,
                    cree_par,
                    cree_le
                 ) VALUES (
                    :nom,
                    :description,
                    :portee_type,
                    :faculte_id,
                    :departement_id,
                    :promotion_id,
                    :date_debut,
                    :date_fin,
                    'brouillon',
                    :cree_par,
                    NOW()
                 )
                 RETURNING id"
            );
            $requete->execute([
                'nom' => $donnees['nom'],
                'description' => $donnees['description'] !== '' ? $donnees['description'] : null,
                'portee_type' => $porteeType,
                'faculte_id' => $faculteId,
                'departement_id' => $departementId,
                'promotion_id' => $promotionId,
                'date_debut' => $donnees['date_debut'],
                'date_fin' => $donnees['date_fin'],
                'cree_par' => $creePar,
            ]);

            $electionId = (int) $requete->fetch()['id'];
            $this->journaliser($creePar, 'creation_election', 'elections', $electionId, [
                'portee_type' => $porteeType,
            ]);

            $this->db->commit();

            return $electionId;
        } catch (\Throwable $exception) {
            $this->db->rollBack();
            throw $exception;
        }
    }

    public function listerAValider(): array
    {
        $this->normaliserStatuts();

        $requete = $this->db->prepare(
            "SELECT
                e.id,
                e.nom,
                e.description,
                e.portee_type,
                e.date_debut,
                e.date_fin,
                e.statut,
                f.code AS faculte_code,
                p.code AS promotion_code,
                COUNT(c.id) AS total_candidats
             FROM elections e
             LEFT JOIN facultes f ON f.id = e.faculte_id
             LEFT JOIN promotions p ON p.id = e.promotion_id
             LEFT JOIN candidats c ON c.election_id = e.id AND c.statut = 'actif'
             WHERE e.statut = 'en_attente_validation_lancement'
                OR (
                    e.statut = 'brouillon'
                    AND e.date_fin > :maintenant
                    AND EXISTS (
                        SELECT 1
                        FROM candidats c2
                        WHERE c2.election_id = e.id
                        AND c2.statut = 'actif'
                    )
                )
             GROUP BY e.id, f.code, p.code
             ORDER BY
                CASE WHEN e.statut = 'en_attente_validation_lancement' THEN 0 ELSE 1 END,
                e.date_debut DESC,
                e.id DESC"
        );
        $requete->execute(['maintenant' => date('Y-m-d H:i:s')]);

        return $requete->fetchAll();
    }

    public function demanderValidationLancement(int $electionId, int $utilisateurId): void
    {
        $election = $this->trouver($electionId);

        if (!$election || ($election['statut'] ?? '') !== 'brouillon') {
            throw new RuntimeException("Seule une election en preparation peut etre envoyee au president.");
        }

        if (strtotime((string) $election['date_fin']) <= time()) {
            throw new RuntimeException("La demande de lancement doit etre faite avant la fin prevue de l'election.");
        }

        $totalCandidats = $this->compterCandidatsElection($electionId);
        if ($totalCandidats <= 0) {
            throw new RuntimeException("Ajoutez au moins un candidat avant de demander le lancement.");
        }

        $requete = $this->db->prepare(
            "UPDATE elections
             SET statut = 'en_attente_validation_lancement',
                 modifie_le = NOW()
             WHERE id = :id"
        );
        $requete->execute(['id' => $electionId]);

        $this->journaliser($utilisateurId, 'demande_validation_lancement', 'elections', $electionId, [
            'total_candidats' => $totalCandidats,
        ]);
    }

    public function listerAPublier(): array
    {
        $this->normaliserStatuts();

        return $this->listerParStatuts(['en_attente_publication', 'fermee']);
    }

    public function listerPourAnnulation(): array
    {
        $this->normaliserStatuts();

        return $this->listerParStatuts(['en_attente_validation_lancement', 'validee', 'ouverte', 'en_attente_publication']);
    }

    public function trouver(int $electionId): ?array
    {
        $this->normaliserStatuts();
        $requete = $this->db->prepare(
            "SELECT
                e.*,
                f.code AS faculte_code,
                f.nom AS faculte_nom,
                p.code AS promotion_code,
                p.nom AS promotion_nom,
                d.code AS departement_code,
                d.nom AS departement_nom
             FROM elections e
             LEFT JOIN facultes f ON f.id = e.faculte_id
             LEFT JOIN promotions p ON p.id = e.promotion_id
             LEFT JOIN departements d ON d.id = e.departement_id
             WHERE e.id = :id
             LIMIT 1"
        );
        $requete->execute(['id' => $electionId]);
        $ligne = $requete->fetch();

        return $ligne ?: null;
    }

    public function electionCourantePourEtudiant(array $etudiant): ?array
    {
        return $this->listerPourEtudiant($etudiant, 1)[0] ?? null;
    }

    public function listerPourEtudiant(array $etudiant, int $limite = 20): array
    {
        $this->normaliserStatuts();
        $requete = $this->db->prepare(
            "SELECT
                e.*,
                f.code AS faculte_code,
                f.nom AS faculte_nom,
                p.code AS promotion_code,
                p.nom AS promotion_nom,
                d.code AS departement_code,
                d.nom AS departement_nom,
                COUNT(c.id) AS total_candidats
             FROM elections e
             LEFT JOIN facultes f ON f.id = e.faculte_id
             LEFT JOIN promotions p ON p.id = e.promotion_id
             LEFT JOIN departements d ON d.id = e.departement_id
             LEFT JOIN candidats c ON c.election_id = e.id AND c.statut = 'actif'
             WHERE e.statut IN ('validee', 'ouverte', 'fermee', 'en_attente_publication', 'publiee')
             AND (
                e.portee_type = 'universite'
                OR (e.portee_type = 'faculte' AND e.faculte_id = :faculte_id)
                OR (e.portee_type = 'promotion' AND e.faculte_id = :faculte_id AND e.promotion_id = :promotion_id)
                OR (e.portee_type = 'departement' AND e.faculte_id = :faculte_id AND e.departement_id = :departement_id)
             )
             GROUP BY e.id, f.code, f.nom, p.code, p.nom, d.code, d.nom
             ORDER BY
                CASE e.statut
                    WHEN 'ouverte' THEN 1
                    WHEN 'validee' THEN 2
                    WHEN 'en_attente_publication' THEN 3
                    WHEN 'fermee' THEN 4
                    WHEN 'publiee' THEN 5
                    ELSE 9
                END,
                CASE e.portee_type
                    WHEN 'universite' THEN 1
                    WHEN 'faculte' THEN 2
                    WHEN 'departement' THEN 3
                    WHEN 'promotion' THEN 4
                    ELSE 9
                END,
                e.date_debut ASC,
                e.id ASC
             LIMIT :limite"
        );
        $requete->bindValue('faculte_id', (int) $etudiant['faculte_id'], PDO::PARAM_INT);
        $requete->bindValue('promotion_id', (int) $etudiant['promotion_id'], PDO::PARAM_INT);
        $requete->bindValue('departement_id', !empty($etudiant['departement_id']) ? (int) $etudiant['departement_id'] : 0, PDO::PARAM_INT);
        $requete->bindValue('limite', $limite, PDO::PARAM_INT);
        $requete->execute();

        return $requete->fetchAll();
    }

    public function etudiantADejaVote(int $electionId, int $etudiantId): bool
    {
        $requete = $this->db->prepare(
            "SELECT id
             FROM participations_etudiants
             WHERE election_id = :election_id
             AND etudiant_id = :etudiant_id
             AND statut_participation = 'a_vote'
             LIMIT 1"
        );
        $requete->execute([
            'election_id' => $electionId,
            'etudiant_id' => $etudiantId,
        ]);

        return (bool) $requete->fetch();
    }

    public function voter(int $electionId, int $candidatId, array $etudiant): void
    {
        $election = $this->trouver($electionId);
        if (!$election || !ServiceReglesMetier::etudiantPeutVoter($etudiant, $election, $this->etudiantADejaVote($electionId, (int) $etudiant['id']))) {
            throw new RuntimeException("Le vote n'est pas ouvert pour votre compte.");
        }

        if (!$this->candidatCompatible($candidatId, $election)) {
            throw new RuntimeException("Ce candidat n'est pas disponible pour cette election.");
        }

        $jeton = hash('sha256', $electionId . ':' . (int) $etudiant['id'] . ':' . env_valeur('APP_NOM', 'VOTE UPC ONLINE'));

        $this->db->beginTransaction();

        try {
            $requeteVote = $this->db->prepare(
                "INSERT INTO bulletins_votes (election_id, candidat_id, jeton_anonyme, statut, cree_le)
                 VALUES (:election_id, :candidat_id, :jeton_anonyme, 'valide', NOW())"
            );
            $requeteVote->execute([
                'election_id' => $electionId,
                'candidat_id' => $candidatId,
                'jeton_anonyme' => $jeton,
            ]);

            $requeteParticipation = $this->db->prepare(
                "INSERT INTO participations_etudiants (election_id, etudiant_id, statut_participation, a_vote_le, cree_le)
                 VALUES (:election_id, :etudiant_id, 'a_vote', NOW(), NOW())
                 ON CONFLICT (election_id, etudiant_id) DO UPDATE
                 SET statut_participation = 'a_vote',
                     a_vote_le = NOW()"
            );
            $requeteParticipation->execute([
                'election_id' => $electionId,
                'etudiant_id' => (int) $etudiant['id'],
            ]);

            $this->db->commit();
        } catch (\Throwable $exception) {
            $this->db->rollBack();
            throw new RuntimeException('Votre vote est deja enregistre ou ne peut pas etre accepte.');
        }
    }

    public function presidentIdPourUtilisateur(int $utilisateurId): int
    {
        $requete = $this->db->prepare(
            "SELECT id
             FROM presidents_electoraux
             WHERE utilisateur_id = :utilisateur_id
             AND statut = 'actif'
             LIMIT 1"
        );
        $requete->execute(['utilisateur_id' => $utilisateurId]);
        $ligne = $requete->fetch();

        if (!$ligne) {
            throw new RuntimeException('President electoral introuvable.');
        }

        return (int) $ligne['id'];
    }

    public function validerLancement(int $electionId, int $presidentUtilisateurId, string $decision, string $commentaire): void
    {
        $presidentId = $this->presidentIdPourUtilisateur($presidentUtilisateurId);
        $election = $this->trouver($electionId);

        if (!$election || !ServiceReglesMetier::presidentPeutValiderLancement($election)) {
            throw new RuntimeException("Cette election n'est pas en attente de validation.");
        }

        if ($decision === 'valide') {
            if (strtotime((string) $election['date_fin']) <= time()) {
                throw new RuntimeException("Cette election est deja terminee et ne peut plus etre lancee.");
            }

            if ($this->compterCandidatsElection($electionId) <= 0) {
                throw new RuntimeException("Ajoutez au moins un candidat avant de valider le lancement.");
            }
        }

        $nouveauStatut = $decision === 'valide'
            ? ($this->maintenantDansPeriode($election) ? 'ouverte' : 'validee')
            : 'brouillon';

        $this->db->beginTransaction();

        try {
            $this->insererValidation($electionId, $presidentId, 'lancement', $decision, $commentaire);

            if ($decision === 'valide') {
                $requete = $this->db->prepare(
                    "UPDATE elections
                     SET statut = :statut,
                         validee_par_president_id = :president_id,
                         validee_le = NOW(),
                         modifie_le = NOW()
                     WHERE id = :id"
                );
                $requete->execute([
                    'statut' => $nouveauStatut,
                    'president_id' => $presidentId,
                    'id' => $electionId,
                ]);
            } else {
                $requete = $this->db->prepare(
                    "UPDATE elections
                     SET statut = :statut,
                         modifie_le = NOW()
                     WHERE id = :id"
                );
                $requete->execute([
                    'statut' => $nouveauStatut,
                    'id' => $electionId,
                ]);
            }

            $this->db->commit();
        } catch (\Throwable $exception) {
            $this->db->rollBack();
            throw $exception;
        }
    }

    public function publierResultats(int $electionId, int $presidentUtilisateurId, string $commentaire): void
    {
        $this->normaliserStatuts();
        $presidentId = $this->presidentIdPourUtilisateur($presidentUtilisateurId);
        $election = $this->trouver($electionId);

        if (!$election || !in_array((string) $election['statut'], ['en_attente_publication', 'fermee'], true)) {
            throw new RuntimeException("Cette election n'est pas prete pour publication.");
        }

        $this->db->beginTransaction();

        try {
            $this->insererValidation($electionId, $presidentId, 'publication', 'valide', $commentaire);

            $requetePublication = $this->db->prepare(
                "INSERT INTO publications_resultats (election_id, president_electoral_id, commentaire, cree_le)
                 VALUES (:election_id, :president_id, :commentaire, NOW())
                 ON CONFLICT (election_id) DO UPDATE
                 SET president_electoral_id = EXCLUDED.president_electoral_id,
                     commentaire = EXCLUDED.commentaire,
                     cree_le = NOW()"
            );
            $requetePublication->execute([
                'election_id' => $electionId,
                'president_id' => $presidentId,
                'commentaire' => $commentaire !== '' ? $commentaire : null,
            ]);

            $requete = $this->db->prepare(
                "UPDATE elections
                 SET statut = 'publiee',
                     publiee_par_president_id = :president_id,
                     publiee_le = NOW(),
                     modifie_le = NOW()
                 WHERE id = :id"
            );
            $requete->execute([
                'president_id' => $presidentId,
                'id' => $electionId,
            ]);

            $this->db->commit();
        } catch (\Throwable $exception) {
            $this->db->rollBack();
            throw $exception;
        }
    }

    public function annulerFaculte(int $electionId, int $faculteId, int $presidentUtilisateurId, string $raison): void
    {
        if (trim($raison) === '') {
            throw new RuntimeException('La raison est obligatoire.');
        }

        $presidentId = $this->presidentIdPourUtilisateur($presidentUtilisateurId);

        $requete = $this->db->prepare(
            "INSERT INTO annulations_elections_facultes (election_id, faculte_id, president_electoral_id, raison, cree_le)
             VALUES (:election_id, :faculte_id, :president_id, :raison, NOW())
             ON CONFLICT (election_id, faculte_id) DO UPDATE
             SET president_electoral_id = EXCLUDED.president_electoral_id,
                 raison = EXCLUDED.raison,
                 cree_le = NOW()"
        );
        $requete->execute([
            'election_id' => $electionId,
            'faculte_id' => $faculteId,
            'president_id' => $presidentId,
            'raison' => $raison,
        ]);
    }

    public function resultatsPourElection(int $electionId): array
    {
        $requete = $this->db->prepare(
            "SELECT
                c.id,
                c.nom,
                c.post_nom,
                c.prenom,
                CASE
                    WHEN c.photo_base64 IS NOT NULL AND c.photo_base64 <> ''
                    THEN '/candidats/photo?id=' || c.id::text
                    ELSE c.photo_chemin
                END AS photo_chemin,
                c.slogan,
                f.code AS faculte_code,
                p.code AS promotion_code,
                COUNT(b.id) AS voix
             FROM candidats c
             LEFT JOIN bulletins_votes b ON b.candidat_id = c.id
                AND b.election_id = :election_id
                AND b.statut = 'valide'
             LEFT JOIN facultes f ON f.id = c.faculte_id
             LEFT JOIN promotions p ON p.id = c.promotion_id
             WHERE c.election_id = :election_id
             AND c.statut = 'actif'
             GROUP BY c.id, f.code, p.code
             ORDER BY voix DESC, c.nom, c.post_nom, c.prenom"
        );
        $requete->execute(['election_id' => $electionId]);

        return $requete->fetchAll();
    }

    public function rapportParticipation(int $electionId): array
    {
        $election = $this->trouver($electionId);

        if (!$election) {
            throw new RuntimeException('Election introuvable pour le rapport.');
        }

        $conditions = ["et.statut <> 'supprime'"];
        $parametres = ['election_id' => $electionId];

        if (($election['portee_type'] ?? '') === ServiceReglesMetier::PORTEE_FACULTE) {
            $conditions[] = 'et.faculte_id = :faculte_id';
            $parametres['faculte_id'] = (int) $election['faculte_id'];
        }

        if (($election['portee_type'] ?? '') === ServiceReglesMetier::PORTEE_PROMOTION) {
            $conditions[] = 'et.faculte_id = :faculte_id';
            $conditions[] = 'et.promotion_id = :promotion_id';
            $parametres['faculte_id'] = (int) $election['faculte_id'];
            $parametres['promotion_id'] = (int) $election['promotion_id'];
        }

        if (($election['portee_type'] ?? '') === ServiceReglesMetier::PORTEE_DEPARTEMENT) {
            $conditions[] = 'et.faculte_id = :faculte_id';
            $conditions[] = 'et.departement_id = :departement_id';
            $parametres['faculte_id'] = (int) $election['faculte_id'];
            $parametres['departement_id'] = (int) $election['departement_id'];
        }

        $requete = $this->db->prepare(
            "SELECT
                f.code AS faculte_code,
                f.nom AS faculte_nom,
                p.code AS promotion_code,
                p.nom AS promotion_nom,
                COUNT(et.id) AS total_etudiants,
                SUM(CASE WHEN et.statut = 'actif' THEN 1 ELSE 0 END) AS actifs,
                SUM(CASE WHEN et.statut = 'bloque' THEN 1 ELSE 0 END) AS bloques,
                SUM(CASE WHEN pe.statut_participation = 'a_vote' THEN 1 ELSE 0 END) AS votants,
                SUM(CASE WHEN pe.statut_participation = 'annule' THEN 1 ELSE 0 END) AS annules
             FROM etudiants et
             INNER JOIN facultes f ON f.id = et.faculte_id
             INNER JOIN promotions p ON p.id = et.promotion_id
             LEFT JOIN participations_etudiants pe ON pe.etudiant_id = et.id
                AND pe.election_id = :election_id
             WHERE " . implode(' AND ', $conditions) . "
             GROUP BY f.id, f.code, f.nom, p.id, p.code, p.nom, p.ordre
             ORDER BY f.code, p.ordre, p.code"
        );
        $requete->execute($parametres);
        $lignes = $requete->fetchAll();

        $totaux = [
            'etudiants' => 0,
            'actifs' => 0,
            'bloques' => 0,
            'votants' => 0,
            'annules' => 0,
            'non_votants' => 0,
            'votes_valides' => 0,
            'candidats' => $this->compterCandidatsElection($electionId),
            'taux_participation' => 0.0,
        ];

        foreach ($lignes as &$ligne) {
            $ligne['total_etudiants'] = (int) ($ligne['total_etudiants'] ?? 0);
            $ligne['actifs'] = (int) ($ligne['actifs'] ?? 0);
            $ligne['bloques'] = (int) ($ligne['bloques'] ?? 0);
            $ligne['votants'] = (int) ($ligne['votants'] ?? 0);
            $ligne['annules'] = (int) ($ligne['annules'] ?? 0);
            $ligne['non_votants'] = max(0, $ligne['total_etudiants'] - $ligne['votants']);
            $ligne['taux_participation'] = $ligne['total_etudiants'] > 0
                ? round(($ligne['votants'] / $ligne['total_etudiants']) * 100, 2)
                : 0.0;

            $totaux['etudiants'] += $ligne['total_etudiants'];
            $totaux['actifs'] += $ligne['actifs'];
            $totaux['bloques'] += $ligne['bloques'];
            $totaux['votants'] += $ligne['votants'];
            $totaux['annules'] += $ligne['annules'];
            $totaux['non_votants'] += $ligne['non_votants'];
        }
        unset($ligne);

        $requeteVotes = $this->db->prepare(
            "SELECT COUNT(id) AS total
             FROM bulletins_votes
             WHERE election_id = :election_id
             AND statut = 'valide'"
        );
        $requeteVotes->execute(['election_id' => $electionId]);
        $totalVotes = $requeteVotes->fetch() ?: [];
        $totaux['votes_valides'] = (int) ($totalVotes['total'] ?? 0);
        $totaux['taux_participation'] = $totaux['etudiants'] > 0
            ? round(($totaux['votants'] / $totaux['etudiants']) * 100, 2)
            : 0.0;

        return [
            'election' => $election,
            'lignes' => $lignes,
            'totaux' => $totaux,
        ];
    }

    public function statistiquesParticipation(int $electionId): array
    {
        $requete = $this->db->prepare(
            "SELECT
                COUNT(*) AS total,
                SUM(CASE WHEN statut_participation = 'a_vote' THEN 1 ELSE 0 END) AS votants,
                SUM(CASE WHEN statut_participation = 'abstention' THEN 1 ELSE 0 END) AS abstentions,
                SUM(CASE WHEN statut_participation = 'annule' THEN 1 ELSE 0 END) AS annules
             FROM participations_etudiants
             WHERE election_id = :election_id"
        );
        $requete->execute(['election_id' => $electionId]);
        $ligne = $requete->fetch() ?: [];

        return [
            'total' => (int) ($ligne['total'] ?? 0),
            'votants' => (int) ($ligne['votants'] ?? 0),
            'abstentions' => (int) ($ligne['abstentions'] ?? 0),
            'annules' => (int) ($ligne['annules'] ?? 0),
        ];
    }

    public function compterCandidatsElection(int $electionId): int
    {
        $requete = $this->db->prepare(
            "SELECT COUNT(id) AS total
             FROM candidats
             WHERE election_id = :election_id
             AND statut = 'actif'"
        );
        $requete->execute(['election_id' => $electionId]);
        $ligne = $requete->fetch() ?: [];

        return (int) ($ligne['total'] ?? 0);
    }

    private function listerParStatuts(array $statuts): array
    {
        $marqueurs = [];
        $parametres = [];
        foreach ($statuts as $index => $statut) {
            $cle = 'statut_' . $index;
            $marqueurs[] = ':' . $cle;
            $parametres[$cle] = $statut;
        }

        $sql = "SELECT
                    e.id,
                    e.nom,
                    e.description,
                    e.portee_type,
                    e.date_debut,
                    e.date_fin,
                    e.statut,
                    f.code AS faculte_code,
                    p.code AS promotion_code,
                    COUNT(c.id) AS total_candidats
                FROM elections e
                LEFT JOIN facultes f ON f.id = e.faculte_id
                LEFT JOIN promotions p ON p.id = e.promotion_id
                LEFT JOIN candidats c ON c.election_id = e.id AND c.statut = 'actif'
                WHERE e.statut IN (" . implode(',', $marqueurs) . ")
                GROUP BY e.id, f.code, p.code
                ORDER BY e.date_debut DESC, e.id DESC";

        $requete = $this->db->prepare($sql);
        $requete->execute($parametres);

        return $requete->fetchAll();
    }

    private function verifierPortee(string $porteeType, ?int $faculteId, ?int $departementId, ?int $promotionId): void
    {
        if (!in_array($porteeType, ['universite', 'faculte', 'departement', 'promotion'], true)) {
            throw new RuntimeException('Portee electorale invalide.');
        }

        if ($porteeType === 'universite' && ($faculteId !== null || $departementId !== null || $promotionId !== null)) {
            throw new RuntimeException("Une election universite ne doit pas avoir de faculte ou promotion.");
        }

        if ($porteeType === 'faculte' && $faculteId === null) {
            throw new RuntimeException('La faculte est obligatoire pour cette portee.');
        }

        if ($porteeType === 'promotion' && ($faculteId === null || $promotionId === null)) {
            throw new RuntimeException('La faculte et la promotion sont obligatoires.');
        }

        if ($porteeType === 'departement' && ($faculteId === null || $departementId === null)) {
            throw new RuntimeException('La faculte et le departement sont obligatoires.');
        }
    }

    private function candidatCompatible(int $candidatId, array $election): bool
    {
        $requete = $this->db->prepare(
            "SELECT id
             FROM candidats
             WHERE id = :id
             AND statut = 'actif'
             AND election_id = :election_id
             LIMIT 1"
        );
        $requete->execute([
            'id' => $candidatId,
            'election_id' => (int) $election['id'],
        ]);

        return (bool) $requete->fetch();
    }

    private function maintenantDansPeriode(array $election): bool
    {
        return ServiceReglesMetier::electionEstOuverte(array_merge($election, [
            'statut' => 'ouverte',
        ]));
    }

    private function insererValidation(int $electionId, int $presidentId, string $type, string $decision, string $commentaire): void
    {
        $requete = $this->db->prepare(
            "INSERT INTO validations_elections (election_id, president_electoral_id, type_validation, decision, commentaire, cree_le)
             VALUES (:election_id, :president_id, :type_validation, :decision, :commentaire, NOW())"
        );
        $requete->execute([
            'election_id' => $electionId,
            'president_id' => $presidentId,
            'type_validation' => $type,
            'decision' => $decision,
            'commentaire' => $commentaire !== '' ? $commentaire : null,
        ]);
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
