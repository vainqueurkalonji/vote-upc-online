<?php

declare(strict_types=1);

namespace Application\Validateurs;

class ValidateurSuperAdministrateur
{
    public static function valider(array $donnees): array
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
        } elseif (!str_ends_with($email, '@gmail.com')) {
            $erreurs['email'] = "L'adresse email du super administrateur doit se terminer par @gmail.com.";
        }

        $motDePasse = (string) ($donnees['mot_de_passe'] ?? '');
        if ($motDePasse === '') {
            $erreurs['mot_de_passe'] = 'Le mot de passe est obligatoire.';
        } elseif (strlen($motDePasse) < 8) {
            $erreurs['mot_de_passe'] = 'Le mot de passe doit contenir au moins 8 caractères.';
        } elseif (
            !preg_match('/[A-Z]/', $motDePasse)
            || !preg_match('/[a-z]/', $motDePasse)
            || !preg_match('/[0-9]/', $motDePasse)
            || !preg_match('/[^A-Za-z0-9]/', $motDePasse)
        ) {
            $erreurs['mot_de_passe'] = 'Le mot de passe doit contenir une majuscule, une minuscule, un chiffre et un symbole.';
        }

        $confirmation = (string) ($donnees['confirmation_mot_de_passe'] ?? '');
        if ($confirmation !== $motDePasse) {
            $erreurs['confirmation_mot_de_passe'] = 'La confirmation doit être identique au mot de passe.';
        }

        return $erreurs;
    }
}
