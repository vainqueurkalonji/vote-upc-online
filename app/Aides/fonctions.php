<?php

declare(strict_types=1);

function charger_variables_env(string $chemin): void
{
    if (!is_file($chemin)) {
        return;
    }

    $lignes = file($chemin, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

    foreach ($lignes as $ligne) {
        $ligne = trim($ligne);

        if ($ligne === '' || str_starts_with($ligne, '#') || !str_contains($ligne, '=')) {
            continue;
        }

        [$cle, $valeur] = explode('=', $ligne, 2);
        $cle = trim($cle);
        $valeur = trim($valeur, " \t\n\r\0\x0B\"'");

        $_ENV[$cle] = $valeur;
        putenv($cle . '=' . $valeur);
    }
}

function env_valeur(string $cle, mixed $defaut = null): mixed
{
    $valeur = getenv($cle);

    if ($valeur === false) {
        return $_ENV[$cle] ?? $defaut;
    }

    return $valeur;
}

function e(mixed $valeur): string
{
    return htmlspecialchars((string) $valeur, ENT_QUOTES, 'UTF-8');
}

function rediriger(string $chemin): never
{
    header('Location: ' . $chemin);
    exit;
}

function ancien(string $cle, array $donnees, string $defaut = ''): string
{
    return (string) ($donnees[$cle] ?? $defaut);
}

function langue_actuelle(): string
{
    $langue = $_SESSION['langue'] ?? env_valeur('APP_LANGUE', 'fr');

    return in_array($langue, ['fr', 'en'], true) ? $langue : 'fr';
}

function traduire(string $cle, array $remplacements = []): string
{
    static $traductions = [];

    $langue = langue_actuelle();

    if (!isset($traductions[$langue])) {
        $fichier = CHEMIN_RACINE . '/app/Langues/' . $langue . '.php';
        $traductions[$langue] = is_file($fichier) ? require $fichier : [];
    }

    $texte = $traductions[$langue][$cle] ?? $cle;

    foreach ($remplacements as $nom => $valeur) {
        $texte = str_replace(':' . $nom, (string) $valeur, $texte);
    }

    return $texte;
}

function traductions_interface(): array
{
    static $traductions = [];

    $langue = langue_actuelle();

    if ($langue === 'fr') {
        return [];
    }

    if (!isset($traductions[$langue])) {
        $fichier = CHEMIN_RACINE . '/app/Langues/' . $langue . '.php';
        $donnees = is_file($fichier) ? require $fichier : [];
        $traductions[$langue] = is_array($donnees['interface'] ?? null) ? $donnees['interface'] : [];

        uksort($traductions[$langue], static fn(string $a, string $b): int => strlen($b) <=> strlen($a));
    }

    return $traductions[$langue];
}

function traduire_html_interface(string $html): string
{
    $traductions = traductions_interface();

    if (empty($traductions)) {
        return $html;
    }

    return strtr($html, $traductions);
}
