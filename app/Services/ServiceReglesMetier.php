<?php

declare(strict_types=1);

namespace Application\Services;

use DateTimeImmutable;

final class ServiceReglesMetier
{
    public const ROLE_SUPER_ADMINISTRATEUR = 'super_administrateur';
    public const ROLE_PRESIDENT_ELECTORAL = 'president_electoral';
    public const ROLE_APPARITEUR = 'appariteur';
    public const ROLE_ETUDIANT = 'etudiant';

    public const STATUT_UTILISATEUR_ACTIF = 'actif';
    public const STATUT_UTILISATEUR_BLOQUE = 'bloque';

    public const STATUT_ETUDIANT_NON_ACTIVE = 'non_active';
    public const STATUT_ETUDIANT_ACTIF = 'actif';
    public const STATUT_ETUDIANT_BLOQUE = 'bloque';

    public const STATUT_ELECTION_BROUILLON = 'brouillon';
    public const STATUT_ELECTION_ATTENTE_LANCEMENT = 'en_attente_validation_lancement';
    public const STATUT_ELECTION_VALIDEE = 'validee';
    public const STATUT_ELECTION_OUVERTE = 'ouverte';
    public const STATUT_ELECTION_FERMEE = 'fermee';
    public const STATUT_ELECTION_ATTENTE_PUBLICATION = 'en_attente_publication';
    public const STATUT_ELECTION_PUBLIEE = 'publiee';
    public const STATUT_ELECTION_ANNULEE = 'annulee';

    public const PORTEE_UNIVERSITE = 'universite';
    public const PORTEE_FACULTE = 'faculte';
    public const PORTEE_DEPARTEMENT = 'departement';
    public const PORTEE_PROMOTION = 'promotion';

    public const PARTICIPATION_A_VOTE = 'a_vote';
    public const PARTICIPATION_ABSTENTION = 'abstention';
    public const PARTICIPATION_ANNULEE = 'annule';

    public const NOMBRE_MAX_TENTATIVES_CONNEXION = 4;

    public static function appariteurPeutGererEtudiant(array $appariteur, array $etudiant): bool
    {
        if (!isset($appariteur['faculte_id'], $etudiant['faculte_id'])) {
            return false;
        }

        return (int) $appariteur['faculte_id'] === (int) $etudiant['faculte_id'];
    }

    public static function utilisateurDoitEtreBloque(int $tentativesConnexion): bool
    {
        return $tentativesConnexion >= self::NOMBRE_MAX_TENTATIVES_CONNEXION;
    }

    public static function etudiantCorrespondALaPortee(array $etudiant, array $election): bool
    {
        $portee = $election['portee_type'] ?? self::PORTEE_UNIVERSITE;

        return match ($portee) {
            self::PORTEE_UNIVERSITE => true,
            self::PORTEE_FACULTE => self::memeValeur($etudiant['faculte_id'] ?? null, $election['faculte_id'] ?? null),
            self::PORTEE_DEPARTEMENT => self::memeValeur($etudiant['faculte_id'] ?? null, $election['faculte_id'] ?? null)
                && self::memeValeur($etudiant['departement_id'] ?? null, $election['departement_id'] ?? null),
            self::PORTEE_PROMOTION => self::memeValeur($etudiant['faculte_id'] ?? null, $election['faculte_id'] ?? null)
                && self::memeValeur($etudiant['promotion_id'] ?? null, $election['promotion_id'] ?? null),
            default => false,
        };
    }

    public static function etudiantPeutVoirContenuAcademique(array $etudiant, array $contenu): bool
    {
        if (isset($contenu['faculte_id']) && !self::memeValeur($etudiant['faculte_id'] ?? null, $contenu['faculte_id'])) {
            return false;
        }

        if (isset($contenu['departement_id']) && !self::memeValeur($etudiant['departement_id'] ?? null, $contenu['departement_id'])) {
            return false;
        }

        if (isset($contenu['promotion_id']) && !self::memeValeur($etudiant['promotion_id'] ?? null, $contenu['promotion_id'])) {
            return false;
        }

        return true;
    }

    public static function etudiantPeutVoirCandidat(array $etudiant, array $candidat): bool
    {
        if (($etudiant['statut'] ?? null) !== self::STATUT_ETUDIANT_ACTIF) {
            return false;
        }

        if (($candidat['statut'] ?? self::STATUT_UTILISATEUR_ACTIF) !== self::STATUT_UTILISATEUR_ACTIF) {
            return false;
        }

        return self::etudiantPeutVoirContenuAcademique($etudiant, $candidat);
    }

    public static function etudiantPeutVoirResultats(array $etudiant, array $election): bool
    {
        return ($etudiant['statut'] ?? null) === self::STATUT_ETUDIANT_ACTIF
            && ($election['statut'] ?? null) === self::STATUT_ELECTION_PUBLIEE
            && self::etudiantCorrespondALaPortee($etudiant, $election);
    }

    public static function electionEstOuverte(array $election, ?DateTimeImmutable $maintenant = null): bool
    {
        if (($election['statut'] ?? null) !== self::STATUT_ELECTION_OUVERTE) {
            return false;
        }

        $maintenant ??= new DateTimeImmutable();
        $dateDebut = self::dateDepuisValeur($election['date_debut'] ?? null);
        $dateFin = self::dateDepuisValeur($election['date_fin'] ?? null);

        if (!$dateDebut || !$dateFin) {
            return false;
        }

        return $maintenant >= $dateDebut && $maintenant <= $dateFin;
    }

    public static function etudiantPeutVoter(
        array $etudiant,
        array $election,
        bool $aDejaVote,
        bool $faculteAnnulee = false,
        ?DateTimeImmutable $maintenant = null
    ): bool {
        if (($etudiant['statut'] ?? null) !== self::STATUT_ETUDIANT_ACTIF) {
            return false;
        }

        if ($aDejaVote || $faculteAnnulee) {
            return false;
        }

        return self::electionEstOuverte($election, $maintenant)
            && self::etudiantCorrespondALaPortee($etudiant, $election);
    }

    public static function presidentPeutValiderLancement(array $election): bool
    {
        return in_array($election['statut'] ?? null, [
            self::STATUT_ELECTION_ATTENTE_LANCEMENT,
            self::STATUT_ELECTION_BROUILLON,
        ], true);
    }

    public static function presidentPeutPublierResultats(array $election, bool $resultatsCalcules): bool
    {
        return $resultatsCalcules
            && ($election['statut'] ?? null) === self::STATUT_ELECTION_ATTENTE_PUBLICATION;
    }

    public static function superAdminPeutModifierEtudiants(array $electionActiveOuFuture): bool
    {
        $statut = $electionActiveOuFuture['statut'] ?? null;

        return !in_array($statut, [
            self::STATUT_ELECTION_ATTENTE_LANCEMENT,
            self::STATUT_ELECTION_VALIDEE,
            self::STATUT_ELECTION_OUVERTE,
        ], true);
    }

    public static function statutNonVotantApresCloture(): string
    {
        return self::PARTICIPATION_ABSTENTION;
    }

    public static function messageAbstention(): string
    {
        return 'Un non-votant est une abstention, pas une voix nulle.';
    }

    private static function memeValeur(mixed $gauche, mixed $droite): bool
    {
        if ($gauche === null || $droite === null) {
            return false;
        }

        return (string) $gauche === (string) $droite;
    }

    private static function dateDepuisValeur(mixed $valeur): ?DateTimeImmutable
    {
        if ($valeur instanceof DateTimeImmutable) {
            return $valeur;
        }

        if (!is_string($valeur) || trim($valeur) === '') {
            return null;
        }

        try {
            return new DateTimeImmutable($valeur);
        } catch (\Exception) {
            return null;
        }
    }
}
