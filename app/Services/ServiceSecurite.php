<?php

declare(strict_types=1);

namespace Application\Services;

final class ServiceSecurite
{
    public static function genererCodeSixChiffres(): string
    {
        return (string) random_int(100000, 999999);
    }

    public static function genererMotDePasseTemporaire(int $longueur = 12): string
    {
        $majuscules = 'ABCDEFGHJKLMNPQRSTUVWXYZ';
        $minuscules = 'abcdefghijkmnopqrstuvwxyz';
        $chiffres = '23456789';
        $symboles = '@#$%&*!?';

        $motDePasse = [
            self::caractereAleatoire($majuscules),
            self::caractereAleatoire($minuscules),
            self::caractereAleatoire($chiffres),
            self::caractereAleatoire($symboles),
        ];

        $tous = $majuscules . $minuscules . $chiffres . $symboles;
        while (count($motDePasse) < max(8, $longueur)) {
            $motDePasse[] = self::caractereAleatoire($tous);
        }

        shuffle($motDePasse);

        return implode('', $motDePasse);
    }

    public static function hashCode(string $code): string
    {
        return password_hash($code, PASSWORD_DEFAULT);
    }

    public static function verifierCode(string $code, string $hash): bool
    {
        return password_verify($code, $hash);
    }

    private static function caractereAleatoire(string $alphabet): string
    {
        return $alphabet[random_int(0, strlen($alphabet) - 1)];
    }
}
