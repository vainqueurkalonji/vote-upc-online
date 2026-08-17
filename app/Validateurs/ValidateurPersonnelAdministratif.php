<?php

declare(strict_types=1);

namespace Application\Validateurs;

class ValidateurPersonnelAdministratif
{
    public static function valider(array $donnees, bool $faculteRequise = false): array
    {
        $erreurs = [];

        foreach (['nom', 'post_nom', 'prenom'] as $champ) {
            $valeur = trim((string) ($donnees[$champ] ?? ''));

            if ($valeur === '') {
                $erreurs[$champ] = 'Ce champ est obligatoire.';
                continue;
            }

            if (!preg_match("/^[\\p{L}\\s'-]+$/u", $valeur)) {
                $erreurs[$champ] = 'Ce champ doit contenir uniquement des lettres.';
            }
        }

        $nomUtilisateur = trim((string) ($donnees['nom_utilisateur'] ?? ''));
        if ($nomUtilisateur === '') {
            $erreurs['nom_utilisateur'] = 'Le nom utilisateur est obligatoire.';
        } elseif (!preg_match('/^[A-Za-z0-9]+$/', $nomUtilisateur)) {
            $erreurs['nom_utilisateur'] = 'Le nom utilisateur doit contenir uniquement des lettres et chiffres.';
        }

        $email = strtolower(trim((string) ($donnees['email'] ?? '')));
        if ($email === '') {
            $erreurs['email'] = "L'adresse email est obligatoire.";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $erreurs['email'] = "L'adresse email est invalide.";
        }

        if ($faculteRequise && empty($donnees['faculte_id'])) {
            $erreurs['faculte_id'] = 'La faculte de rattachement est obligatoire.';
        }

        return $erreurs;
    }
}
