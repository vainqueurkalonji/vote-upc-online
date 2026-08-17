<?php

declare(strict_types=1);

namespace Application\Services;

final class ServiceCatalogueAcademique
{
    public const FACULTES = [
        'FASI' => 'Faculte des Sciences Informatiques',
        'FASE' => 'Faculte des Sciences Economiques',
        'MEDECINE' => 'Faculte de Medecine',
        'DROIT' => 'Faculte de Droit',
        'THEOLOGIE' => 'Faculte de Theologie',
    ];

    public const PROMOTIONS_PAR_FACULTE = [
        'FASE' => ['L1', 'L2', 'L3', 'M1', 'M2'],
        'THEOLOGIE' => ['L1', 'L2', 'L3', 'M1', 'M2'],
        'DROIT' => ['L1', 'L2', 'L3', 'M1', 'M2'],
        'FASI' => ['L1', 'L2', 'L3', 'L4', 'M1', 'M2'],
        'MEDECINE' => ['G0', 'L1', 'L2', 'L3', 'PR_BACH1', 'BACH2', 'BACH3', 'D4'],
    ];

    public static function facultes(): array
    {
        return self::FACULTES;
    }

    public static function promotionsParFaculte(string $codeFaculte): array
    {
        $codeFaculte = strtoupper(trim($codeFaculte));

        return self::PROMOTIONS_PAR_FACULTE[$codeFaculte] ?? [];
    }

    public static function faculteExiste(string $codeFaculte): bool
    {
        return array_key_exists(strtoupper(trim($codeFaculte)), self::FACULTES);
    }

    public static function promotionExisteDansFaculte(string $codeFaculte, string $codePromotion): bool
    {
        $promotions = self::promotionsParFaculte($codeFaculte);

        return in_array(strtoupper(trim($codePromotion)), $promotions, true);
    }
}
