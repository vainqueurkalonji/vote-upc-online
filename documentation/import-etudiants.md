# Import des etudiants - VOTE UPC ONLINE

## Objectif

L'import permet d'enregistrer les etudiants en masse a partir d'un fichier Excel officiel, sans saisir les etudiants un par un.

## Modele officiel

Le modele est disponible dans l'application:

```text
/modeles/modele_import_etudiants_vote_upc.xlsx
```

Un jeu de demonstration avec 200 etudiants fictifs est aussi disponible:

```text
/modeles/jeu_demo_200_etudiants_vote_upc.xlsx
```

Colonnes principales:

- MATRICULE
- NOM
- POST_NOM
- PRENOM
- DATE_NAISSANCE
- LIEU_NAISSANCE
- SEXE
- FACULTE
- DEPARTEMENT
- PROMOTION
- EMAIL
- TELEPHONE
- OBSERVATION

## Majuscules automatiques

Dans l'interface web, les champs d'identite et academiques peuvent utiliser `data-majuscules`.

Effet:

- le texte s'affiche en majuscules pendant la saisie;
- la valeur est convertie en majuscules avant envoi du formulaire;
- sur telephone, `autocapitalize="characters"` demande au clavier de proposer les majuscules quand le navigateur le supporte.

Important: le serveur devra aussi normaliser les donnees importees, parce qu'un fichier Excel peut venir d'une autre machine.

## Listes deroulantes et dates

Le fichier Excel utilise des validations pour faciliter la saisie:

- `SEXE`: choix entre `M` et `F`;
- `FACULTE`: choix entre `FASI`, `FASE`, `MEDECINE`, `DROIT`, `THEOLOGIE`;
- `PROMOTION`: choix dans les promotions officielles;
- `DATE_NAISSANCE`: cellule au format date, avec controle entre 1990-01-01 et 2010-12-31.

## Controles avant insertion

Le serveur verifie:

- matricule unique;
- email unique;
- faculte existante;
- promotion compatible avec la faculte;
- date valide;
- sexe M ou F;
- etudiant non active apres import.

Si un matricule existe deja, l'import peut mettre a jour les informations administratives de l'etudiant sans creer un doublon.

## Cycle apres import

1. L'etudiant est importe avec le statut `non_active`.
2. L'appariteur de sa faculte verifie ses preuves.
3. L'appariteur active le compte.
4. Le systeme envoie un mot de passe temporaire par email.
5. L'etudiant change son mot de passe a la premiere connexion.
