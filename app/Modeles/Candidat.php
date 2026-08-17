<?php

declare(strict_types=1);

namespace Application\Modeles;

use PDO;
use RuntimeException;

class Candidat extends Modele
{
    public function statistiques(): array
    {
        $requete = $this->db->query(
            "SELECT
                COUNT(id) AS total,
                SUM(CASE WHEN statut = 'actif' THEN 1 ELSE 0 END) AS actifs,
                SUM(CASE WHEN statut = 'inactif' THEN 1 ELSE 0 END) AS inactifs
             FROM candidats
             WHERE statut <> 'supprime'"
        );
        $ligne = $requete->fetch() ?: [];

        return [
            'total' => (int) ($ligne['total'] ?? 0),
            'actifs' => (int) ($ligne['actifs'] ?? 0),
            'inactifs' => (int) ($ligne['inactifs'] ?? 0),
        ];
    }

    public function catalogueAcademique(): array
    {
        $requete = $this->db->query(
            "SELECT
                f.id AS faculte_id,
                f.code AS faculte_code,
                f.nom AS faculte_nom,
                p.id AS promotion_id,
                p.code AS promotion_code,
                p.nom AS promotion_nom,
                p.ordre AS promotion_ordre
             FROM facultes f
             LEFT JOIN promotions p ON p.faculte_id = f.id AND p.active = TRUE
             WHERE f.active = TRUE
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
            $faculteId = (int) $ligne['faculte_id'];

            if (!isset($facultes[$faculteId])) {
                $facultes[$faculteId] = [
                    'id' => $faculteId,
                    'code' => (string) $ligne['faculte_code'],
                    'nom' => (string) $ligne['faculte_nom'],
                    'promotions' => [],
                ];
            }

            if (!empty($ligne['promotion_id'])) {
                $facultes[$faculteId]['promotions'][] = [
                    'id' => (int) $ligne['promotion_id'],
                    'code' => (string) $ligne['promotion_code'],
                    'nom' => (string) $ligne['promotion_nom'],
                ];
            }
        }

        return array_values($facultes);
    }

    public function listerDepartements(): array
    {
        $requete = $this->db->query(
            "SELECT
                d.id,
                d.faculte_id,
                d.code,
                d.nom,
                f.code AS faculte_code
             FROM departements d
             INNER JOIN facultes f ON f.id = d.faculte_id
             WHERE d.actif = TRUE
             ORDER BY f.code, d.nom"
        );

        return $requete->fetchAll();
    }

    public function listerElectionsDisponibles(): array
    {
        $requete = $this->db->prepare(
            "SELECT
                e.id,
                e.nom,
                e.portee_type,
                e.faculte_id,
                e.departement_id,
                e.promotion_id,
                e.date_debut,
                e.date_fin,
                e.statut,
                f.code AS faculte_code,
                p.code AS promotion_code,
                d.nom AS departement_nom
             FROM elections e
             LEFT JOIN facultes f ON f.id = e.faculte_id
             LEFT JOIN promotions p ON p.id = e.promotion_id
             LEFT JOIN departements d ON d.id = e.departement_id
             WHERE e.statut = 'brouillon'
             AND e.date_fin > :maintenant
             ORDER BY e.cree_le DESC, e.id DESC
             LIMIT 80"
        );
        $requete->execute(['maintenant' => date('Y-m-d H:i:s')]);

        return $requete->fetchAll();
    }

    public function lister(int $limite = 120): array
    {
        $requete = $this->db->prepare(
            "SELECT
                c.id,
                c.nom,
                c.post_nom,
                c.prenom,
                c.sexe,
                CASE
                    WHEN c.photo_base64 IS NOT NULL AND c.photo_base64 <> ''
                    THEN '/candidats/photo?id=' || c.id::text
                    ELSE c.photo_chemin
                END AS photo_chemin,
                c.biographie,
                c.slogan,
                c.vision,
                CASE
                    WHEN c.image_vision_base64 IS NOT NULL AND c.image_vision_base64 <> ''
                    THEN '/candidats/image-vision?id=' || c.id::text
                    ELSE c.image_vision_chemin
                END AS image_vision_chemin,
                c.statut,
                c.election_id,
                c.cree_le,
                f.code AS faculte_code,
                f.nom AS faculte_nom,
                p.code AS promotion_code,
                p.nom AS promotion_nom,
                d.nom AS departement_nom,
                e.nom AS election_nom,
                e.statut AS election_statut
             FROM candidats c
             LEFT JOIN facultes f ON f.id = c.faculte_id
             LEFT JOIN promotions p ON p.id = c.promotion_id
             LEFT JOIN departements d ON d.id = c.departement_id
             LEFT JOIN elections e ON e.id = c.election_id
             WHERE c.statut <> 'supprime'
             ORDER BY c.cree_le DESC, c.id DESC
             LIMIT :limite"
        );
        $requete->bindValue('limite', $limite, PDO::PARAM_INT);
        $requete->execute();

        return $requete->fetchAll();
    }

    public function listerPourEtudiant(array $etudiant, ?array $election = null, int $limite = 80): array
    {
        $conditions = ["c.statut = 'actif'"];
        $parametres = [];

        if ($election !== null) {
            $conditions[] = 'c.election_id = :election_id';
            $parametres['election_id'] = (int) $election['id'];

            if (($election['portee_type'] ?? 'universite') === 'faculte') {
                $conditions[] = 'c.faculte_id = :faculte_election_id';
                $parametres['faculte_election_id'] = (int) $election['faculte_id'];
            } elseif (($election['portee_type'] ?? 'universite') === 'promotion') {
                $conditions[] = 'c.faculte_id = :faculte_election_id';
                $conditions[] = 'c.promotion_id = :promotion_election_id';
                $parametres['faculte_election_id'] = (int) $election['faculte_id'];
                $parametres['promotion_election_id'] = (int) $election['promotion_id'];
            } elseif (($election['portee_type'] ?? 'universite') === 'departement') {
                $conditions[] = 'c.faculte_id = :faculte_election_id';
                $conditions[] = 'c.departement_id = :departement_election_id';
                $parametres['faculte_election_id'] = (int) $election['faculte_id'];
                $parametres['departement_election_id'] = (int) $election['departement_id'];
            }
        } else {
            $conditions[] = 'c.faculte_id = :faculte_id';
            $parametres['faculte_id'] = (int) $etudiant['faculte_id'];
        }

        $sql = "SELECT
                    c.id,
                    c.nom,
                    c.post_nom,
                    c.prenom,
                    c.sexe,
                    CASE
                        WHEN c.photo_base64 IS NOT NULL AND c.photo_base64 <> ''
                        THEN '/candidats/photo?id=' || c.id::text
                        ELSE c.photo_chemin
                    END AS photo_chemin,
                    c.biographie,
                    c.slogan,
                    c.vision,
                    CASE
                        WHEN c.image_vision_base64 IS NOT NULL AND c.image_vision_base64 <> ''
                        THEN '/candidats/image-vision?id=' || c.id::text
                        ELSE c.image_vision_chemin
                    END AS image_vision_chemin,
                    c.statut,
                    f.code AS faculte_code,
                    p.code AS promotion_code,
                    d.nom AS departement_nom,
                    e.nom AS election_nom
                FROM candidats c
                LEFT JOIN facultes f ON f.id = c.faculte_id
                LEFT JOIN promotions p ON p.id = c.promotion_id
                LEFT JOIN departements d ON d.id = c.departement_id
                LEFT JOIN elections e ON e.id = c.election_id
                WHERE " . implode(' AND ', $conditions) . "
                ORDER BY c.cree_le DESC, c.id DESC
                LIMIT :limite";

        $requete = $this->db->prepare($sql);
        foreach ($parametres as $cle => $valeur) {
            $requete->bindValue($cle, $valeur, PDO::PARAM_INT);
        }
        $requete->bindValue('limite', $limite, PDO::PARAM_INT);
        $requete->execute();

        return $requete->fetchAll();
    }

    public function creer(array $donnees, int $creePar): int
    {
        $faculteId = (int) ($donnees['faculte_id'] ?? 0);
        $promotionId = (int) ($donnees['promotion_id'] ?? 0);
        $departementId = !empty($donnees['departement_id']) ? (int) $donnees['departement_id'] : null;
        $electionId = !empty($donnees['election_id']) ? (int) $donnees['election_id'] : 0;

        $this->verifierPorteeAcademique($faculteId, $promotionId, $departementId);
        $election = $this->verifierPorteeElectionCandidat($donnees);

        $this->db->beginTransaction();

        try {
            $requete = $this->db->prepare(
                "INSERT INTO candidats (
                    election_id,
                    faculte_id,
                    departement_id,
                    promotion_id,
                    nom,
                    post_nom,
                    prenom,
                    sexe,
                    photo_chemin,
                    photo_base64,
                    photo_type_mime,
                    photo_nom,
                    biographie,
                    slogan,
                    vision,
                    image_vision_chemin,
                    image_vision_base64,
                    image_vision_type_mime,
                    image_vision_nom,
                    statut,
                    cree_par,
                    cree_le
                 ) VALUES (
                    :election_id,
                    :faculte_id,
                    :departement_id,
                    :promotion_id,
                    :nom,
                    :post_nom,
                    :prenom,
                    :sexe,
                    :photo_chemin,
                    :photo_base64,
                    :photo_type_mime,
                    :photo_nom,
                    :biographie,
                    :slogan,
                    :vision,
                    :image_vision_chemin,
                    :image_vision_base64,
                    :image_vision_type_mime,
                    :image_vision_nom,
                    'actif',
                    :cree_par,
                    NOW()
                 )
                 RETURNING id"
            );

            $requete->execute([
                'election_id' => $electionId,
                'faculte_id' => $faculteId,
                'departement_id' => $departementId,
                'promotion_id' => $promotionId,
                'nom' => $donnees['nom'],
                'post_nom' => $donnees['post_nom'],
                'prenom' => $donnees['prenom'],
                'sexe' => $donnees['sexe'],
                'photo_chemin' => $donnees['photo_chemin'] ?? null,
                'photo_base64' => $donnees['photo_base64'] ?? null,
                'photo_type_mime' => $donnees['photo_type_mime'] ?? null,
                'photo_nom' => $donnees['photo_nom'] ?? null,
                'biographie' => $donnees['biographie'],
                'slogan' => $donnees['slogan'],
                'vision' => $donnees['vision'],
                'image_vision_chemin' => $donnees['image_vision_chemin'] ?? null,
                'image_vision_base64' => $donnees['image_vision_base64'] ?? null,
                'image_vision_type_mime' => $donnees['image_vision_type_mime'] ?? null,
                'image_vision_nom' => $donnees['image_vision_nom'] ?? null,
                'cree_par' => $creePar,
            ]);

            $candidatId = (int) $requete->fetch()['id'];
            $this->mettreAJourCheminsMedias(
                $candidatId,
                !empty($donnees['photo_base64']),
                !empty($donnees['image_vision_base64'])
            );

            $this->journaliser($creePar, $candidatId, [
                'faculte_id' => $faculteId,
                'promotion_id' => $promotionId,
                'election_id' => $electionId,
                'portee_election' => $election['portee_type'],
            ]);

            $this->db->commit();

            return $candidatId;
        } catch (\Throwable $exception) {
            $this->db->rollBack();
            throw $exception;
        }
    }

    public function trouverPourModification(int $candidatId): ?array
    {
        $requete = $this->db->prepare(
            "SELECT
                c.*,
                e.statut AS election_statut,
                e.nom AS election_nom,
                e.portee_type AS election_portee_type,
                e.faculte_id AS election_faculte_id,
                e.departement_id AS election_departement_id,
                e.promotion_id AS election_promotion_id,
                f.code AS faculte_code,
                p.code AS promotion_code,
                d.nom AS departement_nom
             FROM candidats c
             LEFT JOIN elections e ON e.id = c.election_id
             LEFT JOIN facultes f ON f.id = c.faculte_id
             LEFT JOIN promotions p ON p.id = c.promotion_id
             LEFT JOIN departements d ON d.id = c.departement_id
             WHERE c.id = :id
             AND c.statut <> 'supprime'
             LIMIT 1"
        );
        $requete->execute(['id' => $candidatId]);
        $ligne = $requete->fetch();

        return $ligne ?: null;
    }

    public function modifier(int $candidatId, array $donnees, int $modifiePar): void
    {
        $candidat = $this->trouverPourModification($candidatId);

        if (!$candidat) {
            throw new RuntimeException('Candidat introuvable.');
        }

        if (($candidat['election_statut'] ?? '') !== 'brouillon') {
            throw new RuntimeException("Ce candidat n'est plus modifiable parce que son election n'est plus en preparation.");
        }

        $faculteId = (int) ($donnees['faculte_id'] ?? 0);
        $promotionId = (int) ($donnees['promotion_id'] ?? 0);
        $departementId = !empty($donnees['departement_id']) ? (int) $donnees['departement_id'] : null;
        $electionId = !empty($donnees['election_id']) ? (int) $donnees['election_id'] : 0;

        $this->verifierPorteeAcademique($faculteId, $promotionId, $departementId);
        $election = $this->verifierPorteeElectionCandidat($donnees);

        $photoEstRemplacee = array_key_exists('photo_base64', $donnees);
        $imageVisionEstRemplacee = array_key_exists('image_vision_base64', $donnees);
        $photoChemin = $photoEstRemplacee ? '/candidats/photo?id=' . $candidatId : ($candidat['photo_chemin'] ?? null);
        $imageVisionChemin = $imageVisionEstRemplacee ? '/candidats/image-vision?id=' . $candidatId : ($candidat['image_vision_chemin'] ?? null);
        $photoBase64 = $photoEstRemplacee ? ($donnees['photo_base64'] ?? null) : ($candidat['photo_base64'] ?? null);
        $photoTypeMime = $photoEstRemplacee ? ($donnees['photo_type_mime'] ?? null) : ($candidat['photo_type_mime'] ?? null);
        $photoNom = $photoEstRemplacee ? ($donnees['photo_nom'] ?? null) : ($candidat['photo_nom'] ?? null);
        $imageVisionBase64 = $imageVisionEstRemplacee ? ($donnees['image_vision_base64'] ?? null) : ($candidat['image_vision_base64'] ?? null);
        $imageVisionTypeMime = $imageVisionEstRemplacee ? ($donnees['image_vision_type_mime'] ?? null) : ($candidat['image_vision_type_mime'] ?? null);
        $imageVisionNom = $imageVisionEstRemplacee ? ($donnees['image_vision_nom'] ?? null) : ($candidat['image_vision_nom'] ?? null);

        $requete = $this->db->prepare(
            "UPDATE candidats
             SET election_id = :election_id,
                 faculte_id = :faculte_id,
                 departement_id = :departement_id,
                 promotion_id = :promotion_id,
                 nom = :nom,
                 post_nom = :post_nom,
                 prenom = :prenom,
                 sexe = :sexe,
                 photo_chemin = :photo_chemin,
                 photo_base64 = :photo_base64,
                 photo_type_mime = :photo_type_mime,
                 photo_nom = :photo_nom,
                 biographie = :biographie,
                 slogan = :slogan,
                 vision = :vision,
                 image_vision_chemin = :image_vision_chemin,
                 image_vision_base64 = :image_vision_base64,
                 image_vision_type_mime = :image_vision_type_mime,
                 image_vision_nom = :image_vision_nom,
                 modifie_le = NOW()
             WHERE id = :id"
        );
        $requete->execute([
            'id' => $candidatId,
            'election_id' => $electionId,
            'faculte_id' => $faculteId,
            'departement_id' => $departementId,
            'promotion_id' => $promotionId,
            'nom' => $donnees['nom'],
            'post_nom' => $donnees['post_nom'],
            'prenom' => $donnees['prenom'],
            'sexe' => $donnees['sexe'],
            'photo_chemin' => $photoChemin,
            'photo_base64' => $photoBase64,
            'photo_type_mime' => $photoTypeMime,
            'photo_nom' => $photoNom,
            'biographie' => $donnees['biographie'],
            'slogan' => $donnees['slogan'],
            'vision' => $donnees['vision'],
            'image_vision_chemin' => $imageVisionChemin,
            'image_vision_base64' => $imageVisionBase64,
            'image_vision_type_mime' => $imageVisionTypeMime,
            'image_vision_nom' => $imageVisionNom,
        ]);

        $this->journaliser($modifiePar, $candidatId, [
            'faculte_id' => $faculteId,
            'promotion_id' => $promotionId,
            'election_id' => $electionId,
            'portee_election' => $election['portee_type'],
            'action' => 'modification_candidat',
        ], 'modification_candidat');
    }

    public function verifierPorteeElectionCandidat(array $donnees): array
    {
        $electionId = (int) ($donnees['election_id'] ?? 0);
        if ($electionId <= 0) {
            throw new RuntimeException("Avant de creer un candidat, choisissez d'abord une election.");
        }

        $election = $this->trouverElectionPourCandidat($electionId);
        if (!$election) {
            throw new RuntimeException('Election introuvable.');
        }

        if (($election['statut'] ?? '') !== 'brouillon') {
            throw new RuntimeException("Les candidatures sont verrouillees pour cette election. Aucun candidat ne peut etre ajoute apres la demande de lancement.");
        }

        if (strtotime((string) $election['date_fin']) <= time()) {
            throw new RuntimeException("Les candidats doivent etre ajoutes avant la fin prevue de l'election.");
        }

        $faculteId = (int) ($donnees['faculte_id'] ?? 0);
        $promotionId = (int) ($donnees['promotion_id'] ?? 0);
        $departementId = !empty($donnees['departement_id']) ? (int) $donnees['departement_id'] : null;

        if ($election['portee_type'] === 'faculte' && $faculteId !== (int) $election['faculte_id']) {
            throw new RuntimeException('Ce candidat doit appartenir a la faculte definie dans cette election.');
        }

        if ($election['portee_type'] === 'promotion') {
            if ($faculteId !== (int) $election['faculte_id'] || $promotionId !== (int) $election['promotion_id']) {
                throw new RuntimeException('Ce candidat doit appartenir a la promotion definie dans cette election.');
            }
        }

        if ($election['portee_type'] === 'departement') {
            if ($faculteId !== (int) $election['faculte_id'] || $departementId !== (int) $election['departement_id']) {
                throw new RuntimeException('Ce candidat doit appartenir au departement defini dans cette election.');
            }
        }

        return $election;
    }

    public function remplacerPhoto(int $candidatId, array $media, int $modifiePar): void
    {
        $candidat = $this->trouverPourModification($candidatId);

        if (!$candidat) {
            throw new RuntimeException('Candidat introuvable.');
        }

        $requete = $this->db->prepare(
            "UPDATE candidats
             SET photo_chemin = :photo_chemin,
                 photo_base64 = :photo_base64,
                 photo_type_mime = :photo_type_mime,
                 photo_nom = :photo_nom,
                 modifie_le = NOW()
             WHERE id = :id
             AND statut <> 'supprime'"
        );
        $requete->execute([
            'id' => $candidatId,
            'photo_chemin' => '/candidats/photo?id=' . $candidatId,
            'photo_base64' => $media['base64'] ?? null,
            'photo_type_mime' => $media['type_mime'] ?? null,
            'photo_nom' => $media['nom'] ?? null,
        ]);

        $this->journaliser($modifiePar, $candidatId, [
            'action' => 'remplacement_photo_candidat',
        ], 'modification_candidat');
    }

    public function trouverMedia(int $candidatId, string $type): ?array
    {
        $champs = match ($type) {
            'photo' => [
                'base64' => 'photo_base64',
                'type_mime' => 'photo_type_mime',
                'nom' => 'photo_nom',
            ],
            'image_vision' => [
                'base64' => 'image_vision_base64',
                'type_mime' => 'image_vision_type_mime',
                'nom' => 'image_vision_nom',
            ],
            default => null,
        };

        if ($champs === null) {
            return null;
        }

        $requete = $this->db->prepare(
            "SELECT
                {$champs['base64']} AS base64,
                {$champs['type_mime']} AS type_mime,
                {$champs['nom']} AS nom
             FROM candidats
             WHERE id = :id
             AND statut <> 'supprime'
             LIMIT 1"
        );
        $requete->execute(['id' => $candidatId]);
        $media = $requete->fetch();

        if (!$media || empty($media['base64'])) {
            return null;
        }

        return $media;
    }

    private function trouverElectionPourCandidat(int $electionId): ?array
    {
        $requete = $this->db->prepare(
            "SELECT
                id,
                nom,
                portee_type,
                faculte_id,
                departement_id,
                promotion_id,
                date_debut,
                date_fin,
                statut
             FROM elections
             WHERE id = :id
             LIMIT 1"
        );
        $requete->execute(['id' => $electionId]);
        $ligne = $requete->fetch();

        return $ligne ?: null;
    }

    private function verifierPorteeAcademique(int $faculteId, int $promotionId, ?int $departementId): void
    {
        $requete = $this->db->prepare(
            "SELECT p.id
             FROM promotions p
             WHERE p.id = :promotion_id
             AND p.faculte_id = :faculte_id
             AND p.active = TRUE
             LIMIT 1"
        );
        $requete->execute([
            'promotion_id' => $promotionId,
            'faculte_id' => $faculteId,
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

    private function journaliser(int $utilisateurId, int $candidatId, array $details, string $action = 'creation_candidat'): void
    {
        $requete = $this->db->prepare(
            "INSERT INTO journaux_actions (utilisateur_id, action, entite, entite_id, details, cree_le)
             VALUES (:utilisateur_id, :action, 'candidats', :entite_id, :details, NOW())"
        );
        $requete->execute([
            'utilisateur_id' => $utilisateurId,
            'action' => $action,
            'entite_id' => $candidatId,
            'details' => json_encode($details, JSON_THROW_ON_ERROR),
        ]);
    }

    private function mettreAJourCheminsMedias(int $candidatId, bool $aPhoto, bool $aImageVision): void
    {
        $champs = [];
        $parametres = ['id' => $candidatId];

        if ($aPhoto) {
            $champs[] = 'photo_chemin = :photo_chemin';
            $parametres['photo_chemin'] = '/candidats/photo?id=' . $candidatId;
        }

        if ($aImageVision) {
            $champs[] = 'image_vision_chemin = :image_vision_chemin';
            $parametres['image_vision_chemin'] = '/candidats/image-vision?id=' . $candidatId;
        }

        if (empty($champs)) {
            return;
        }

        $requete = $this->db->prepare(
            'UPDATE candidats SET ' . implode(', ', $champs) . ', modifie_le = NOW() WHERE id = :id'
        );
        $requete->execute($parametres);
    }
}
