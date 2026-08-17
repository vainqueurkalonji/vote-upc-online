<?php

declare(strict_types=1);

namespace Application\Modeles;

use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as DateExcel;
use RuntimeException;

class Etudiant extends Modele
{
    public function affectationAppariteur(int $utilisateurId): ?array
    {
        $requete = $this->db->prepare(
            "SELECT
                a.id AS appariteur_id,
                a.statut AS appariteur_statut,
                f.id AS faculte_id,
                f.code AS faculte_code,
                f.nom AS faculte_nom
             FROM appariteurs a
             INNER JOIN facultes f ON f.id = a.faculte_id
             WHERE a.utilisateur_id = :utilisateur_id
             AND a.statut = 'actif'
             LIMIT 1"
        );
        $requete->execute(['utilisateur_id' => $utilisateurId]);
        $affectation = $requete->fetch();

        return $affectation ?: null;
    }

    public function listerPourAppariteur(int $utilisateurId, string $recherche = '', int $limite = 300): array
    {
        $parametres = [
            'utilisateur_id' => $utilisateurId,
        ];
        $conditionRecherche = '';

        if (trim($recherche) !== '') {
            $parametres['recherche'] = '%' . strtolower(trim($recherche)) . '%';
            $conditionRecherche = "
                AND (
                    LOWER(e.nom) LIKE :recherche
                    OR LOWER(e.post_nom) LIKE :recherche
                    OR LOWER(e.prenom) LIKE :recherche
                    OR LOWER(e.matricule) LIKE :recherche
                    OR LOWER(COALESCE(e.email, '')) LIKE :recherche
                    OR LOWER(p.code) LIKE :recherche
                )";
        }

        $requete = $this->db->prepare(
            "SELECT
                e.id,
                e.nom,
                e.post_nom,
                e.prenom,
                e.matricule,
                e.email,
                e.sexe,
                e.statut,
                f.code AS faculte_code,
                p.code AS promotion_code,
                d.nom AS departement_nom
             FROM appariteurs a
             INNER JOIN etudiants e ON e.faculte_id = a.faculte_id
             INNER JOIN facultes f ON f.id = e.faculte_id
             INNER JOIN promotions p ON p.id = e.promotion_id
             LEFT JOIN departements d ON d.id = e.departement_id
             WHERE a.utilisateur_id = :utilisateur_id
             AND a.statut = 'actif'
             AND e.statut <> 'supprime'
             {$conditionRecherche}
             ORDER BY e.nom, e.post_nom, e.prenom
             LIMIT :limite"
        );

        $requete->bindValue('utilisateur_id', $utilisateurId, \PDO::PARAM_INT);
        $requete->bindValue('limite', max(1, min(500, $limite)), \PDO::PARAM_INT);
        if (isset($parametres['recherche'])) {
            $requete->bindValue('recherche', $parametres['recherche']);
        }
        $requete->execute();

        return $requete->fetchAll();
    }

    public function statistiquesPourAppariteur(int $utilisateurId): array
    {
        $requete = $this->db->prepare(
            "SELECT
                COUNT(e.id) AS total,
                SUM(CASE WHEN e.statut = 'non_active' THEN 1 ELSE 0 END) AS non_actives,
                SUM(CASE WHEN e.statut = 'actif' THEN 1 ELSE 0 END) AS actives,
                SUM(CASE WHEN e.statut = 'bloque' THEN 1 ELSE 0 END) AS bloques
             FROM appariteurs a
             LEFT JOIN etudiants e ON e.faculte_id = a.faculte_id AND e.statut <> 'supprime'
             WHERE a.utilisateur_id = :utilisateur_id
             AND a.statut = 'actif'"
        );
        $requete->execute(['utilisateur_id' => $utilisateurId]);
        $ligne = $requete->fetch() ?: [];

        return [
            'total' => (int) ($ligne['total'] ?? 0),
            'non_actives' => (int) ($ligne['non_actives'] ?? 0),
            'actives' => (int) ($ligne['actives'] ?? 0),
            'bloques' => (int) ($ligne['bloques'] ?? 0),
        ];
    }

    public function repartitionPromotionsPourAppariteur(int $utilisateurId): array
    {
        $requete = $this->db->prepare(
            "SELECT
                p.code AS promotion_code,
                COUNT(e.id) AS total,
                SUM(CASE WHEN e.statut = 'non_active' THEN 1 ELSE 0 END) AS non_actives,
                SUM(CASE WHEN e.statut = 'actif' THEN 1 ELSE 0 END) AS actives
             FROM appariteurs a
             INNER JOIN etudiants e ON e.faculte_id = a.faculte_id
             INNER JOIN promotions p ON p.id = e.promotion_id
             WHERE a.utilisateur_id = :utilisateur_id
             AND a.statut = 'actif'
             AND e.statut <> 'supprime'
             GROUP BY p.code, p.ordre
             ORDER BY p.ordre, p.code"
        );
        $requete->execute(['utilisateur_id' => $utilisateurId]);

        return $requete->fetchAll();
    }

    public function statistiquesGlobales(): array
    {
        $requete = $this->db->query(
            "SELECT
                COUNT(id) AS total,
                SUM(CASE WHEN statut = 'non_active' THEN 1 ELSE 0 END) AS non_actives,
                SUM(CASE WHEN statut = 'actif' THEN 1 ELSE 0 END) AS actives,
                SUM(CASE WHEN statut = 'bloque' THEN 1 ELSE 0 END) AS bloques
             FROM etudiants
             WHERE statut <> 'supprime'"
        );
        $ligne = $requete->fetch() ?: [];

        return [
            'total' => (int) ($ligne['total'] ?? 0),
            'non_actives' => (int) ($ligne['non_actives'] ?? 0),
            'actives' => (int) ($ligne['actives'] ?? 0),
            'bloques' => (int) ($ligne['bloques'] ?? 0),
        ];
    }

    public function statistiquesAcademiquesHierarchiques(): array
    {
        $requete = $this->db->query(
            "SELECT
                f.id AS faculte_id,
                f.code AS faculte_code,
                f.nom AS faculte_nom,
                p.id AS promotion_id,
                p.code AS promotion_code,
                p.nom AS promotion_nom,
                p.ordre AS promotion_ordre,
                COUNT(e.id) AS total,
                SUM(CASE WHEN e.statut = 'non_active' THEN 1 ELSE 0 END) AS non_actives,
                SUM(CASE WHEN e.statut = 'actif' THEN 1 ELSE 0 END) AS actives,
                SUM(CASE WHEN e.statut = 'bloque' THEN 1 ELSE 0 END) AS bloques
             FROM facultes f
             LEFT JOIN promotions p ON p.faculte_id = f.id AND p.active = TRUE
             LEFT JOIN etudiants e ON e.faculte_id = f.id
                AND e.promotion_id = p.id
                AND e.statut <> 'supprime'
             WHERE f.active = TRUE
             GROUP BY f.id, f.code, f.nom, p.id, p.code, p.nom, p.ordre
             ORDER BY
                CASE f.code
                    WHEN 'FASI' THEN 1
                    WHEN 'FASE' THEN 2
                    WHEN 'MEDECINE' THEN 3
                    WHEN 'DROIT' THEN 4
                    WHEN 'THEOLOGIE' THEN 5
                    ELSE 99
                END,
                p.ordre,
                p.code"
        );

        $facultes = [];

        foreach ($requete->fetchAll() as $ligne) {
            $codeFaculte = (string) $ligne['faculte_code'];

            if (!isset($facultes[$codeFaculte])) {
                $facultes[$codeFaculte] = [
                    'id' => (int) $ligne['faculte_id'],
                    'code' => $codeFaculte,
                    'nom' => (string) $ligne['faculte_nom'],
                    'total' => 0,
                    'non_actives' => 0,
                    'actives' => 0,
                    'bloques' => 0,
                    'promotions' => [],
                ];
            }

            $promotion = [
                'id' => (int) ($ligne['promotion_id'] ?? 0),
                'code' => (string) ($ligne['promotion_code'] ?? ''),
                'nom' => (string) ($ligne['promotion_nom'] ?? ''),
                'total' => (int) ($ligne['total'] ?? 0),
                'non_actives' => (int) ($ligne['non_actives'] ?? 0),
                'actives' => (int) ($ligne['actives'] ?? 0),
                'bloques' => (int) ($ligne['bloques'] ?? 0),
            ];

            if ($promotion['code'] !== '') {
                $facultes[$codeFaculte]['promotions'][] = $promotion;
                $facultes[$codeFaculte]['total'] += $promotion['total'];
                $facultes[$codeFaculte]['non_actives'] += $promotion['non_actives'];
                $facultes[$codeFaculte]['actives'] += $promotion['actives'];
                $facultes[$codeFaculte]['bloques'] += $promotion['bloques'];
            }
        }

        return array_values($facultes);
    }

    public function listerPourSuperAdministrateur(
        string $recherche = '',
        string $faculte = '',
        string $statut = '',
        string $promotion = '',
        int $limite = 500
    ): array {
        $conditions = ["e.statut <> 'supprime'"];
        $parametres = [];

        if (trim($recherche) !== '') {
            $conditions[] = "(
                LOWER(e.nom) LIKE :recherche
                OR LOWER(e.post_nom) LIKE :recherche
                OR LOWER(e.prenom) LIKE :recherche
                OR LOWER(e.matricule) LIKE :recherche
                OR LOWER(COALESCE(e.email, '')) LIKE :recherche
                OR LOWER(p.code) LIKE :recherche
            )";
            $parametres['recherche'] = '%' . strtolower(trim($recherche)) . '%';
        }

        if (trim($faculte) !== '') {
            $conditions[] = 'f.code = :faculte';
            $parametres['faculte'] = strtoupper(trim($faculte));
        }

        if (trim($promotion) !== '') {
            $conditions[] = 'p.code = :promotion';
            $parametres['promotion'] = strtoupper(trim($promotion));
        }

        if (in_array($statut, ['non_active', 'actif', 'bloque'], true)) {
            $conditions[] = 'e.statut = :statut';
            $parametres['statut'] = $statut;
        }

        $sql = "SELECT
                    e.id,
                    e.nom,
                    e.post_nom,
                    e.prenom,
                    e.matricule,
                    e.email,
                    e.sexe,
                    e.date_naissance,
                    e.lieu_naissance,
                    e.statut,
                    f.code AS faculte_code,
                    f.nom AS faculte_nom,
                    p.code AS promotion_code,
                    d.nom AS departement_nom
                FROM etudiants e
                INNER JOIN facultes f ON f.id = e.faculte_id
                INNER JOIN promotions p ON p.id = e.promotion_id
                LEFT JOIN departements d ON d.id = e.departement_id
                WHERE " . implode(' AND ', $conditions) . "
                ORDER BY f.code, p.ordre, e.nom, e.post_nom, e.prenom
                LIMIT :limite";

        $requete = $this->db->prepare($sql);
        foreach ($parametres as $cle => $valeur) {
            $requete->bindValue($cle, $valeur);
        }
        $requete->bindValue('limite', max(1, min(1000, $limite)), \PDO::PARAM_INT);
        $requete->execute();

        return $requete->fetchAll();
    }

    public function trouverPourSuperAdministrateur(int $etudiantId): ?array
    {
        $requete = $this->db->prepare(
            "SELECT
                e.id,
                e.utilisateur_id,
                e.faculte_id,
                e.departement_id,
                e.promotion_id,
                e.nom,
                e.post_nom,
                e.prenom,
                e.matricule,
                e.email,
                e.sexe,
                e.date_naissance,
                e.lieu_naissance,
                e.statut,
                f.code AS faculte_code,
                f.nom AS faculte_nom,
                p.code AS promotion_code,
                p.nom AS promotion_nom,
                d.nom AS departement_nom
             FROM etudiants e
             INNER JOIN facultes f ON f.id = e.faculte_id
             INNER JOIN promotions p ON p.id = e.promotion_id
             LEFT JOIN departements d ON d.id = e.departement_id
             WHERE e.id = :id
             AND e.statut <> 'supprime'
             LIMIT 1"
        );
        $requete->execute(['id' => $etudiantId]);
        $ligne = $requete->fetch();

        return $ligne ?: null;
    }

    public function trouverParUtilisateurId(int $utilisateurId): ?array
    {
        $requete = $this->db->prepare(
            "SELECT
                e.id,
                e.utilisateur_id,
                e.faculte_id,
                e.departement_id,
                e.promotion_id,
                e.nom,
                e.post_nom,
                e.prenom,
                e.matricule,
                e.email,
                e.sexe,
                e.date_naissance,
                e.lieu_naissance,
                e.statut,
                f.code AS faculte_code,
                f.nom AS faculte_nom,
                p.code AS promotion_code,
                p.nom AS promotion_nom,
                d.nom AS departement_nom
             FROM etudiants e
             INNER JOIN facultes f ON f.id = e.faculte_id
             INNER JOIN promotions p ON p.id = e.promotion_id
             LEFT JOIN departements d ON d.id = e.departement_id
             WHERE e.utilisateur_id = :utilisateur_id
             AND e.statut <> 'supprime'
             LIMIT 1"
        );
        $requete->execute(['utilisateur_id' => $utilisateurId]);
        $ligne = $requete->fetch();

        return $ligne ?: null;
    }

    public function modifierParSuperAdministrateur(int $etudiantId, array $donnees, int $modifieParId): void
    {
        $etudiant = $this->trouverPourSuperAdministrateur($etudiantId);
        if (!$etudiant) {
            throw new RuntimeException('Etudiant introuvable.');
        }

        $matricule = $this->majuscule((string) $donnees['matricule']);
        $email = strtolower(trim((string) ($donnees['email'] ?? '')));
        $utilisateurId = !empty($etudiant['utilisateur_id']) ? (int) $etudiant['utilisateur_id'] : null;
        $faculteId = (int) $donnees['faculte_id'];
        $promotionId = (int) $donnees['promotion_id'];
        $departementId = !empty($donnees['departement_id']) ? (int) $donnees['departement_id'] : null;

        $this->verifierMatriculeDisponible($matricule, $etudiantId);
        $this->verifierEmailDisponible($email, $etudiantId);
        $this->verifierIdentifiantsUtilisateurDisponibles($email, $matricule, $utilisateurId);
        $this->verifierAcademiqueParIds($faculteId, $promotionId, $departementId);

        $this->db->beginTransaction();

        try {
            $requete = $this->db->prepare(
                "UPDATE etudiants
                 SET faculte_id = :faculte_id,
                     departement_id = :departement_id,
                     promotion_id = :promotion_id,
                     nom = :nom,
                     post_nom = :post_nom,
                     prenom = :prenom,
                     matricule = :matricule,
                     email = :email,
                     date_naissance = :date_naissance,
                     lieu_naissance = :lieu_naissance,
                     sexe = :sexe,
                     modifie_le = NOW()
                 WHERE id = :id"
            );
            $requete->execute([
                'id' => $etudiantId,
                'faculte_id' => $faculteId,
                'departement_id' => $departementId,
                'promotion_id' => $promotionId,
                'nom' => $donnees['nom'],
                'post_nom' => $donnees['post_nom'],
                'prenom' => $donnees['prenom'],
                'matricule' => $matricule,
                'email' => $email !== '' ? $email : null,
                'date_naissance' => $donnees['date_naissance'] !== '' ? $donnees['date_naissance'] : null,
                'lieu_naissance' => $donnees['lieu_naissance'] !== '' ? $donnees['lieu_naissance'] : null,
                'sexe' => $donnees['sexe'] !== '' ? $donnees['sexe'] : null,
            ]);

            if ($utilisateurId !== null) {
                $requeteUtilisateur = $this->db->prepare(
                    "UPDATE utilisateurs
                     SET nom = :nom,
                         post_nom = :post_nom,
                         prenom = :prenom,
                         nom_utilisateur = :nom_utilisateur,
                         email = :email,
                         modifie_le = NOW()
                     WHERE id = :id"
                );
                $requeteUtilisateur->execute([
                    'id' => $utilisateurId,
                    'nom' => $donnees['nom'],
                    'post_nom' => $donnees['post_nom'],
                    'prenom' => $donnees['prenom'],
                    'nom_utilisateur' => $matricule,
                    'email' => $email,
                ]);
            }

            $this->journaliserModificationEtudiant($modifieParId, $etudiantId, [
                'matricule' => $matricule,
                'email' => $email,
                'faculte_id' => $faculteId,
                'promotion_id' => $promotionId,
            ]);

            $this->db->commit();
        } catch (\Throwable $exception) {
            $this->db->rollBack();
            throw $exception;
        }
    }

    public function activerParAppariteur(int $utilisateurId, int $etudiantId, string $motDePasseTemporaire): array
    {
        $affectation = $this->affectationAppariteur($utilisateurId);
        if (!$affectation) {
            throw new RuntimeException('Ce compte appariteur n est rattache a aucune faculte active.');
        }

        $this->db->beginTransaction();

        try {
            $requete = $this->db->prepare(
                "SELECT
                    e.id,
                    e.utilisateur_id,
                    e.faculte_id,
                    e.nom,
                    e.post_nom,
                    e.prenom,
                    e.email,
                    e.matricule,
                    e.statut,
                    f.code AS faculte_code,
                    p.code AS promotion_code
                 FROM etudiants e
                 INNER JOIN facultes f ON f.id = e.faculte_id
                 INNER JOIN promotions p ON p.id = e.promotion_id
                 WHERE e.id = :id
                 AND e.faculte_id = :faculte_id
                 AND e.statut <> 'supprime'
                 LIMIT 1"
            );
            $requete->execute([
                'id' => $etudiantId,
                'faculte_id' => (int) $affectation['faculte_id'],
            ]);
            $etudiant = $requete->fetch();

            if (!$etudiant) {
                throw new RuntimeException('Etudiant introuvable dans votre faculte.');
            }

            if (($etudiant['statut'] ?? '') === 'actif') {
                throw new RuntimeException('Cet etudiant est deja active.');
            }

            if (empty($etudiant['email'])) {
                throw new RuntimeException('Cet etudiant n a pas d email. Ajoute son email avant activation.');
            }

            $utilisateurEtudiantId = $this->creerOuMettreAJourUtilisateurEtudiant($etudiant, $motDePasseTemporaire);
            $action = ($etudiant['statut'] ?? '') === 'bloque' ? 'reactivation' : 'activation';

            $requete = $this->db->prepare(
                "UPDATE etudiants
                 SET utilisateur_id = :utilisateur_id,
                     statut = 'actif',
                     modifie_le = NOW()
                 WHERE id = :id"
            );
            $requete->execute([
                'utilisateur_id' => $utilisateurEtudiantId,
                'id' => $etudiantId,
            ]);

            $requete = $this->db->prepare(
                "INSERT INTO activations_etudiants (
                    etudiant_id,
                    appariteur_id,
                    action,
                    preuves_verifiees,
                    mot_de_passe_temporaire_envoye,
                    commentaire,
                    cree_le
                 ) VALUES (
                    :etudiant_id,
                    :appariteur_id,
                    :action,
                    TRUE,
                    FALSE,
                    :commentaire,
                    NOW()
                 )
                 RETURNING id"
            );
            $requete->execute([
                'etudiant_id' => $etudiantId,
                'appariteur_id' => (int) $affectation['appariteur_id'],
                'action' => $action,
                'commentaire' => 'Activation depuis la page appariteur.',
            ]);
            $activationId = (int) $requete->fetch()['id'];

            $this->db->commit();

            $etudiant['utilisateur_id'] = $utilisateurEtudiantId;
            $etudiant['statut'] = 'actif';
            $etudiant['activation_id'] = $activationId;
            $etudiant['action_activation'] = $action;

            return $etudiant;
        } catch (\Throwable $exception) {
            $this->db->rollBack();
            throw $exception;
        }
    }

    public function marquerMotDePasseTemporaireEnvoye(int $activationId, bool $envoye): void
    {
        $requete = $this->db->prepare(
            "UPDATE activations_etudiants
             SET mot_de_passe_temporaire_envoye = :envoye
             WHERE id = :id"
        );
        $requete->execute([
            'envoye' => $envoye,
            'id' => $activationId,
        ]);
    }

    public function importerDepuisExcel(string $cheminFichier, int $importeParId): array
    {
        if (!class_exists(IOFactory::class)) {
            throw new RuntimeException('PhpSpreadsheet n est pas installe. Installe phpoffice/phpspreadsheet pour lire les fichiers Excel.');
        }

        $classeur = IOFactory::load($cheminFichier);
        $feuille = $classeur->getSheet(0);
        $plusGrandeLigne = $feuille->getHighestDataRow();
        $plusGrandeColonne = Coordinate::columnIndexFromString($feuille->getHighestDataColumn());
        $ligneEntete = $this->trouverLigneEntete($feuille, $plusGrandeColonne);

        if ($ligneEntete === null) {
            throw new RuntimeException('Entetes introuvables. Le fichier doit contenir MATRICULE, NOM, POST_NOM, PRENOM, FACULTE et PROMOTION.');
        }

        $colonnes = $this->colonnesParEntete($feuille, $ligneEntete, $plusGrandeColonne);
        foreach (['MATRICULE', 'NOM', 'POST_NOM', 'PRENOM', 'FACULTE', 'PROMOTION'] as $obligatoire) {
            if (!isset($colonnes[$obligatoire])) {
                throw new RuntimeException('Colonne obligatoire manquante : ' . $obligatoire);
            }
        }

        $rapport = [
            'total_lignes' => 0,
            'total_importes' => 0,
            'total_modifies' => 0,
            'total_erreurs' => 0,
            'erreurs' => [],
        ];

        $this->db->beginTransaction();

        try {
            for ($ligne = $ligneEntete + 1; $ligne <= $plusGrandeLigne; $ligne += 1) {
                $donnees = $this->lireLigne($feuille, $ligne, $colonnes);

                if ($this->ligneVide($donnees)) {
                    continue;
                }

                $rapport['total_lignes']++;

                try {
                    $resultat = $this->enregistrerLigneImportee($donnees, $importeParId);
                    if ($resultat === 'importe') {
                        $rapport['total_importes']++;
                    } else {
                        $rapport['total_modifies']++;
                    }
                } catch (\Throwable $exception) {
                    $rapport['total_erreurs']++;
                    if (count($rapport['erreurs']) < 8) {
                        $rapport['erreurs'][] = 'Ligne ' . $ligne . ' : ' . $exception->getMessage();
                    }
                }
            }

            $this->db->commit();
        } catch (\Throwable $exception) {
            $this->db->rollBack();
            throw $exception;
        }

        return $rapport;
    }

    private function trouverLigneEntete(object $feuille, int $plusGrandeColonne): ?int
    {
        for ($ligne = 1; $ligne <= 12; $ligne += 1) {
            $entetes = [];
            for ($colonne = 1; $colonne <= $plusGrandeColonne; $colonne += 1) {
                $entetes[] = $this->normaliserEntete((string) $this->valeurCellule($feuille, $colonne, $ligne));
            }

            if (in_array('MATRICULE', $entetes, true) && in_array('NOM', $entetes, true)) {
                return $ligne;
            }
        }

        return null;
    }

    private function colonnesParEntete(object $feuille, int $ligneEntete, int $plusGrandeColonne): array
    {
        $colonnes = [];

        for ($colonne = 1; $colonne <= $plusGrandeColonne; $colonne += 1) {
            $entete = $this->normaliserEntete((string) $this->valeurCellule($feuille, $colonne, $ligneEntete));
            if ($entete !== '') {
                $colonnes[$entete] = $colonne;
            }
        }

        return $colonnes;
    }

    private function lireLigne(object $feuille, int $ligne, array $colonnes): array
    {
        $donnees = [];
        foreach ($colonnes as $entete => $colonne) {
            $donnees[$entete] = $this->valeurCellule($feuille, $colonne, $ligne);
        }

        return $donnees;
    }

    private function valeurCellule(object $feuille, int $colonne, int $ligne): mixed
    {
        $reference = Coordinate::stringFromColumnIndex($colonne) . $ligne;
        $cellule = $feuille->getCell($reference);
        $valeur = $cellule->getValue();

        if ($valeur !== null && DateExcel::isDateTime($cellule)) {
            if (is_numeric($valeur)) {
                return DateExcel::excelToDateTimeObject((float) $valeur)->format('Y-m-d');
            }
        }

        return is_string($valeur) ? trim($valeur) : $valeur;
    }

    private function enregistrerLigneImportee(array $donnees, int $importeParId): string
    {
        $matricule = $this->majuscule((string) ($donnees['MATRICULE'] ?? ''));
        $nom = $this->majuscule((string) ($donnees['NOM'] ?? ''));
        $postNom = $this->majuscule((string) ($donnees['POST_NOM'] ?? ''));
        $prenom = $this->majuscule((string) ($donnees['PRENOM'] ?? ''));
        $faculte = $this->majuscule((string) ($donnees['FACULTE'] ?? ''));
        $promotion = $this->majuscule((string) ($donnees['PROMOTION'] ?? ''));
        $departement = $this->majuscule((string) ($donnees['DEPARTEMENT'] ?? ''));
        $lieuNaissance = $this->majuscule((string) ($donnees['LIEU_NAISSANCE'] ?? ''));
        $sexe = $this->majuscule((string) ($donnees['SEXE'] ?? ''));
        $dateNaissance = $this->dateOuNull($donnees['DATE_NAISSANCE'] ?? null);
        $email = strtolower(trim((string) ($donnees['EMAIL'] ?? '')));

        if ($matricule === '' || $nom === '' || $postNom === '' || $prenom === '' || $faculte === '' || $promotion === '') {
            throw new RuntimeException('Identite, matricule, faculte et promotion sont obligatoires.');
        }

        if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new RuntimeException('Email invalide : ' . $email);
        }

        if ($sexe !== '' && !in_array($sexe, ['M', 'F'], true)) {
            throw new RuntimeException('Sexe invalide : ' . $sexe);
        }

        $academique = $this->identifiantsAcademiques($faculte, $promotion);
        $departementId = $this->trouverOuCreerDepartement($academique['faculte_id'], $departement);
        $existant = $this->trouverParMatricule($matricule);
        $this->verifierEmailDisponible($email, $existant['id'] ?? null);

        if ($existant) {
            $requete = $this->db->prepare(
                "UPDATE etudiants
                 SET faculte_id = :faculte_id,
                     departement_id = :departement_id,
                     promotion_id = :promotion_id,
                     nom = :nom,
                     post_nom = :post_nom,
                     prenom = :prenom,
                     date_naissance = :date_naissance,
                     lieu_naissance = :lieu_naissance,
                     sexe = :sexe,
                     email = :email,
                     modifie_le = NOW()
                 WHERE id = :id"
            );
            $requete->execute([
                'id' => (int) $existant['id'],
                'faculte_id' => $academique['faculte_id'],
                'departement_id' => $departementId,
                'promotion_id' => $academique['promotion_id'],
                'nom' => $nom,
                'post_nom' => $postNom,
                'prenom' => $prenom,
                'date_naissance' => $dateNaissance,
                'lieu_naissance' => $lieuNaissance !== '' ? $lieuNaissance : null,
                'sexe' => $sexe !== '' ? $sexe : null,
                'email' => $email !== '' ? $email : null,
            ]);

            return 'modifie';
        }

        $requete = $this->db->prepare(
            "INSERT INTO etudiants (
                faculte_id,
                departement_id,
                promotion_id,
                nom,
                post_nom,
                prenom,
                date_naissance,
                lieu_naissance,
                sexe,
                email,
                matricule,
                statut,
                source_creation,
                cree_par,
                cree_le
             ) VALUES (
                :faculte_id,
                :departement_id,
                :promotion_id,
                :nom,
                :post_nom,
                :prenom,
                :date_naissance,
                :lieu_naissance,
                :sexe,
                :email,
                :matricule,
                'non_active',
                'import_excel',
                :cree_par,
                NOW()
             )"
        );
        $requete->execute([
            'faculte_id' => $academique['faculte_id'],
            'departement_id' => $departementId,
            'promotion_id' => $academique['promotion_id'],
            'nom' => $nom,
            'post_nom' => $postNom,
            'prenom' => $prenom,
            'date_naissance' => $dateNaissance,
            'lieu_naissance' => $lieuNaissance !== '' ? $lieuNaissance : null,
            'sexe' => $sexe !== '' ? $sexe : null,
            'email' => $email !== '' ? $email : null,
            'matricule' => $matricule,
            'cree_par' => $importeParId,
        ]);

        return 'importe';
    }

    private function identifiantsAcademiques(string $faculte, string $promotion): array
    {
        $requete = $this->db->prepare(
            "SELECT f.id AS faculte_id, p.id AS promotion_id
             FROM facultes f
             INNER JOIN promotions p ON p.faculte_id = f.id
             WHERE f.code = :faculte
             AND p.code = :promotion
             AND f.active = TRUE
             AND p.active = TRUE
             LIMIT 1"
        );
        $requete->execute([
            'faculte' => $faculte,
            'promotion' => $promotion,
        ]);
        $ligne = $requete->fetch();

        if (!$ligne) {
            throw new RuntimeException('Promotion ' . $promotion . ' invalide pour la faculte ' . $faculte . '.');
        }

        return [
            'faculte_id' => (int) $ligne['faculte_id'],
            'promotion_id' => (int) $ligne['promotion_id'],
        ];
    }

    private function trouverOuCreerDepartement(int $faculteId, string $nom): ?int
    {
        if ($nom === '') {
            return null;
        }

        $code = $this->codeDepartement($nom);
        $requete = $this->db->prepare(
            "INSERT INTO departements (faculte_id, code, nom, actif, cree_le)
             VALUES (:faculte_id, :code, :nom, TRUE, NOW())
             ON CONFLICT (faculte_id, code) DO UPDATE
             SET nom = EXCLUDED.nom,
                 actif = TRUE,
                 modifie_le = NOW()
             RETURNING id"
        );
        $requete->execute([
            'faculte_id' => $faculteId,
            'code' => $code,
            'nom' => $nom,
        ]);

        return (int) $requete->fetch()['id'];
    }

    private function creerOuMettreAJourUtilisateurEtudiant(array $etudiant, string $motDePasseTemporaire): int
    {
        $roleId = $this->roleId('etudiant');
        $motDePasseHash = password_hash($motDePasseTemporaire, PASSWORD_DEFAULT);
        $utilisateurId = $etudiant['utilisateur_id'] ? (int) $etudiant['utilisateur_id'] : null;

        if ($utilisateurId !== null) {
            $requete = $this->db->prepare(
                "UPDATE utilisateurs
                 SET nom = :nom,
                     post_nom = :post_nom,
                     prenom = :prenom,
                     nom_utilisateur = :nom_utilisateur,
                     email = :email,
                     mot_de_passe = :mot_de_passe,
                     statut = 'actif',
                     mot_de_passe_temporaire = TRUE,
                     tentatives_connexion = 0,
                     modifie_le = NOW()
                 WHERE id = :id"
            );
            $requete->execute([
                'id' => $utilisateurId,
                'nom' => $etudiant['nom'],
                'post_nom' => $etudiant['post_nom'],
                'prenom' => $etudiant['prenom'],
                'nom_utilisateur' => $etudiant['matricule'],
                'email' => strtolower((string) $etudiant['email']),
                'mot_de_passe' => $motDePasseHash,
            ]);

            return $utilisateurId;
        }

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
                tentatives_connexion,
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
                0,
                NOW()
             )
             RETURNING id"
        );
        $requete->execute([
            'role_id' => $roleId,
            'nom' => $etudiant['nom'],
            'post_nom' => $etudiant['post_nom'],
            'prenom' => $etudiant['prenom'],
            'nom_utilisateur' => $etudiant['matricule'],
            'email' => strtolower((string) $etudiant['email']),
            'mot_de_passe' => $motDePasseHash,
        ]);

        return (int) $requete->fetch()['id'];
    }

    private function roleId(string $code): int
    {
        $requete = $this->db->prepare('SELECT id FROM roles WHERE code = :code LIMIT 1');
        $requete->execute(['code' => $code]);
        $role = $requete->fetch();

        if (!$role) {
            throw new RuntimeException('Role introuvable : ' . $code);
        }

        return (int) $role['id'];
    }

    private function trouverParMatricule(string $matricule): ?array
    {
        $requete = $this->db->prepare(
            "SELECT id
             FROM etudiants
             WHERE LOWER(matricule) = LOWER(:matricule)
             LIMIT 1"
        );
        $requete->execute(['matricule' => $matricule]);
        $ligne = $requete->fetch();

        return $ligne ?: null;
    }

    private function verifierEmailDisponible(string $email, ?int $etudiantId): void
    {
        if ($email === '') {
            return;
        }

        if ($etudiantId === null) {
            $requete = $this->db->prepare(
                "SELECT id
                 FROM etudiants
                 WHERE LOWER(email) = LOWER(:email)
                 LIMIT 1"
            );
            $requete->execute(['email' => $email]);
        } else {
            $requete = $this->db->prepare(
                "SELECT id
                 FROM etudiants
                 WHERE LOWER(email) = LOWER(:email)
                 AND id <> :id
                 LIMIT 1"
            );
            $requete->execute([
                'email' => $email,
                'id' => $etudiantId,
            ]);
        }

        if ($requete->fetch()) {
            throw new RuntimeException('Email deja utilise : ' . $email);
        }
    }

    private function verifierMatriculeDisponible(string $matricule, int $etudiantId): void
    {
        $requete = $this->db->prepare(
            "SELECT id
             FROM etudiants
             WHERE LOWER(matricule) = LOWER(:matricule)
             AND id <> :id
             LIMIT 1"
        );
        $requete->execute([
            'matricule' => $matricule,
            'id' => $etudiantId,
        ]);

        if ($requete->fetch()) {
            throw new RuntimeException('Matricule deja utilise : ' . $matricule);
        }
    }

    private function verifierIdentifiantsUtilisateurDisponibles(string $email, string $matricule, ?int $utilisateurIgnoreId): void
    {
        $conditionIgnore = $utilisateurIgnoreId !== null ? ' AND id <> :utilisateur_ignore_id' : '';
        $conditionEmail = $email !== '' ? 'LOWER(email) = LOWER(:email) OR ' : '';
        $requete = $this->db->prepare(
            "SELECT email, nom_utilisateur
             FROM utilisateurs
             WHERE (
                " . $conditionEmail . "
                LOWER(nom_utilisateur) = LOWER(:matricule)
             )
             " . $conditionIgnore . "
             LIMIT 1"
        );
        $parametres = [
            'matricule' => $matricule,
        ];
        if ($email !== '') {
            $parametres['email'] = $email;
        }

        if ($utilisateurIgnoreId !== null) {
            $parametres['utilisateur_ignore_id'] = $utilisateurIgnoreId;
        }

        $requete->execute($parametres);
        $existant = $requete->fetch();

        if (!$existant) {
            return;
        }

        if ($email !== '' && strtolower((string) $existant['email']) === strtolower($email)) {
            throw new RuntimeException('Cet email est deja utilise par un autre compte utilisateur.');
        }

        throw new RuntimeException('Ce matricule est deja utilise comme identifiant utilisateur.');
    }

    private function verifierAcademiqueParIds(int $faculteId, int $promotionId, ?int $departementId): void
    {
        $requete = $this->db->prepare(
            "SELECT p.id
             FROM promotions p
             INNER JOIN facultes f ON f.id = p.faculte_id
             WHERE f.id = :faculte_id
             AND p.id = :promotion_id
             AND f.active = TRUE
             AND p.active = TRUE
             LIMIT 1"
        );
        $requete->execute([
            'faculte_id' => $faculteId,
            'promotion_id' => $promotionId,
        ]);

        if (!$requete->fetch()) {
            throw new RuntimeException('La promotion choisie ne correspond pas a la faculte selectionnee.');
        }

        if ($departementId === null) {
            return;
        }

        $requeteDepartement = $this->db->prepare(
            "SELECT id
             FROM departements
             WHERE id = :departement_id
             AND faculte_id = :faculte_id
             AND actif = TRUE
             LIMIT 1"
        );
        $requeteDepartement->execute([
            'departement_id' => $departementId,
            'faculte_id' => $faculteId,
        ]);

        if (!$requeteDepartement->fetch()) {
            throw new RuntimeException('Le departement choisi ne correspond pas a la faculte selectionnee.');
        }
    }

    private function journaliserModificationEtudiant(int $utilisateurId, int $etudiantId, array $details): void
    {
        $requete = $this->db->prepare(
            "INSERT INTO journaux_actions (utilisateur_id, action, entite, entite_id, details, cree_le)
             VALUES (:utilisateur_id, 'modification_etudiant', 'etudiants', :entite_id, :details, NOW())"
        );
        $requete->execute([
            'utilisateur_id' => $utilisateurId,
            'entite_id' => $etudiantId,
            'details' => json_encode($details, JSON_THROW_ON_ERROR),
        ]);
    }

    private function ligneVide(array $donnees): bool
    {
        foreach ($donnees as $valeur) {
            if (trim((string) $valeur) !== '') {
                return false;
            }
        }

        return true;
    }

    private function dateOuNull(mixed $valeur): ?string
    {
        $valeur = trim((string) $valeur);
        if ($valeur === '') {
            return null;
        }

        $timestamp = strtotime($valeur);
        return $timestamp === false ? null : date('Y-m-d', $timestamp);
    }

    private function normaliserEntete(string $valeur): string
    {
        $valeur = $this->majuscule($valeur);
        $valeur = str_replace(['-', ' '], '_', $valeur);

        return preg_replace('/[^A-Z0-9_]/', '', $valeur) ?: '';
    }

    private function codeDepartement(string $nom): string
    {
        $code = preg_replace('/[^A-Z0-9]+/', '_', $this->majuscule($nom)) ?: 'GENERAL';
        $code = trim($code, '_');

        return substr($code !== '' ? $code : 'GENERAL', 0, 60);
    }

    private function majuscule(string $valeur): string
    {
        $valeur = trim($valeur);

        return function_exists('mb_strtoupper') ? mb_strtoupper($valeur, 'UTF-8') : strtoupper($valeur);
    }
}
