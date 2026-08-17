# VOTE UPC ONLINE - Version ServBay

Application de vote universitaire en ligne en PHP pur avec MVC personnalise.

## Choix techniques

- PHP pur oriente objet.
- Architecture MVC personnalisee.
- PostgreSQL.
- Noms metier en francais.
- ServBay pour l'environnement local.
- Langues prevues: francais et anglais.

## Installation avec ServBay

ServBay utilise generalement ce dossier pour les projets Windows:

```text
C:\ServBay\www
```

Le projet doit se trouver ici:

```text
C:\ServBay\www\vote-upc-online
```

Dans ServBay, creer un site:

```text
Nom : VOTE UPC ONLINE
Domaine : vote-upc-online.cd
Type : PHP
Version PHP : 8.3 ou 8.4
Document Root : C:\ServBay\www\vote-upc-online\public
```

Important: le `Document Root` doit pointer sur `public`, pas sur la racine du projet.

## Base de donnees

Creer une base PostgreSQL:

```text
vote_upc
```

Copier `.env.example` vers `.env`, puis completer:

```text
DB_HOTE=127.0.0.1
DB_PORT=5432
DB_NOM=vote_upc
DB_UTILISATEUR=...
DB_MOT_DE_PASSE=...
```

Executer les fichiers SQL dans cet ordre:

```sql
\i base_de_donnees/migrations/001_initiale_roles_utilisateurs.sql
\i base_de_donnees/migrations/002_academique_elections_vote.sql
\i base_de_donnees/migrations/003_emails_codes_securite.sql
\i base_de_donnees/donnees_initiales/001_facultes_promotions.sql
```

## Premiere page

Ouvrir:

```text
https://vote-upc-online.cd/installation/super-administrateur
```

Cette page cree le premier super administrateur.

## Langues

Le site demarre en francais et permet deja de basculer vers l'anglais avec la route:

```text
/langue?choix=fr
/langue?choix=en
```

Les textes sont dans:

```text
app/Langues/fr.php
app/Langues/en.php
```

## Regles metier importantes

Le document officiel des regles est ici:

```text
documentation/regles-metier.md
```

La liste officielle des liens de pages est ici:

```text
documentation/liens-pages.md
```

La configuration des emails est expliquee ici:

```text
documentation/configuration-emails.md
```

La logique d'import Excel des etudiants est expliquee ici:

```text
documentation/import-etudiants.md
```

Points critiques:

- Un appariteur ne peut gerer que les etudiants de sa faculte.
- Une election FASI ne concerne que les etudiants FASI.
- Chaque page etudiant respecte sa faculte, son departement et sa promotion.
- Une election universite concerne tous les etudiants actifs.
- Le president electoral valide le lancement avant le vote.
- Le president electoral donne l'aval avant publication des resultats.
- Un etudiant vote une seule fois, mais garde acces au site apres son vote.
- Un non-votant est une abstention, pas une voix nulle.
- Une annulation de faculte invalide les votes de cette faculte seulement.

## Ordre de construction

1. Super administrateur.
2. Connexion securisee.
3. President electoral.
4. Appariteur.
5. Etudiant.
6. Import Excel.
7. Activation etudiant par appariteur.
8. Candidats.
9. Elections.
10. Vote.
11. Statistiques.
12. Publication des resultats.
