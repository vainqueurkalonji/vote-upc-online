<?php

declare(strict_types=1);

namespace Application\Services;

final class ServiceAuthentification
{
    private const TYPE_ADMINISTRATION = 'administration';
    private const TYPE_ETUDIANT = 'etudiant';
    private const EXPIRATION_OTP_MINUTES_DEFAUT = 5;

    public static function connecter(array $utilisateur): void
    {
        session_regenerate_id(true);

        unset($_SESSION['otp_connexion']);

        $_SESSION['utilisateur'] = [
            'id' => (int) $utilisateur['id'],
            'role_code' => (string) $utilisateur['role_code'],
            'nom' => (string) $utilisateur['nom'],
            'post_nom' => (string) ($utilisateur['post_nom'] ?? ''),
            'prenom' => (string) $utilisateur['prenom'],
            'nom_utilisateur' => (string) $utilisateur['nom_utilisateur'],
            'email' => (string) $utilisateur['email'],
            'mot_de_passe_temporaire' => (bool) $utilisateur['mot_de_passe_temporaire'],
            'derniere_activite' => time(),
        ];
    }

    public static function utilisateur(): ?array
    {
        return $_SESSION['utilisateur'] ?? null;
    }

    public static function id(): ?int
    {
        $utilisateur = self::utilisateur();

        return $utilisateur ? (int) $utilisateur['id'] : null;
    }

    public static function estConnecte(): bool
    {
        return self::utilisateur() !== null;
    }

    public static function deconnecter(): void
    {
        $utilisateur = self::utilisateur();

        if ($utilisateur) {
            unset($_SESSION['otp_confiance'][(string) ((int) $utilisateur['id'])]);
        }

        unset($_SESSION['utilisateur']);
        unset($_SESSION['otp_connexion']);
        session_regenerate_id(true);
    }

    public static function exigerConnexion(): array
    {
        $utilisateur = self::utilisateur();

        if (!$utilisateur) {
            $cheminCourant = (string) ($_SERVER['REQUEST_URI'] ?? '');
            $destination = str_starts_with($cheminCourant, '/etudiant')
                ? '/etudiant/connexion'
                : '/administration/connexion';

            rediriger($destination);
        }

        $_SESSION['utilisateur']['derniere_activite'] = time();
        self::marquerOtpValide($utilisateur, self::typeConnexionPourRole((string) ($utilisateur['role_code'] ?? '')));

        return $_SESSION['utilisateur'];
    }

    public static function exigerRole(string $role): array
    {
        $utilisateur = self::exigerConnexion();

        if (($utilisateur['role_code'] ?? '') !== $role) {
            rediriger(self::cheminApresConnexion($utilisateur['role_code'] ?? ''));
        }

        return $utilisateur;
    }

    public static function cheminApresConnexion(string $role): string
    {
        return match ($role) {
            ServiceReglesMetier::ROLE_SUPER_ADMINISTRATEUR => '/super-administrateur/tableau-de-bord',
            ServiceReglesMetier::ROLE_PRESIDENT_ELECTORAL => '/president-electoral/tableau-de-bord',
            ServiceReglesMetier::ROLE_APPARITEUR => '/appariteur/tableau-de-bord',
            ServiceReglesMetier::ROLE_ETUDIANT => '/etudiant/tableau-de-bord',
            default => '/administration/connexion',
        };
    }

    public static function otpValideRecemment(array $utilisateur, string $typeConnexion): bool
    {
        $utilisateurId = (int) ($utilisateur['id'] ?? 0);
        if ($utilisateurId <= 0) {
            return false;
        }

        $cle = (string) $utilisateurId;
        $instant = (int) ($_SESSION['otp_confiance'][$cle][$typeConnexion] ?? 0);

        return $instant > 0;
    }

    public static function marquerOtpValide(array $utilisateur, string $typeConnexion): void
    {
        $utilisateurId = (int) ($utilisateur['id'] ?? 0);
        if ($utilisateurId <= 0) {
            return;
        }

        $_SESSION['otp_confiance'][(string) $utilisateurId][$typeConnexion] = time();
    }

    public static function typeConnexionPourRole(string $role): string
    {
        return $role === ServiceReglesMetier::ROLE_ETUDIANT
            ? self::TYPE_ETUDIANT
            : self::TYPE_ADMINISTRATION;
    }

    public static function dureeExpirationOtpMinutes(): int
    {
        $minutes = (int) env_valeur('APP_OTP_EXPIRATION_MINUTES', self::EXPIRATION_OTP_MINUTES_DEFAUT);

        return max(1, $minutes);
    }
}
