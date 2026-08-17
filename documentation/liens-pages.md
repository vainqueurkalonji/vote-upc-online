# Liens des pages - VOTE UPC ONLINE

Domaine local ServBay:

```text
https://vote-upc-online.cd
```

Les liens ci-dessous sont les liens officiels du projet. Les pages marquees `pret` existent deja. Les pages marquees `structure prete` affichent deja une interface et leurs regles, puis seront branchees progressivement a la logique complete.

## Public et authentification

| Page | Lien | Statut |
| --- | --- | --- |
| Accueil | `https://vote-upc-online.cd/` | pret |
| Premiere inscription super administrateur | `https://vote-upc-online.cd/installation/super-administrateur` | pret |
| Connexion administration | `https://vote-upc-online.cd/administration/connexion` | pret avec OTP |
| Connexion etudiant | `https://vote-upc-online.cd/etudiant/connexion` | pret avec OTP |
| Verification OTP | `https://vote-upc-online.cd/otp/connexion` | pret apres mot de passe |
| Ancien lien connexion | `https://vote-upc-online.cd/connexion` | alias administration |
| Mot de passe oublie | `https://vote-upc-online.cd/mot-de-passe-oublie` | structure prete |
| Changement du mot de passe temporaire | `https://vote-upc-online.cd/mot-de-passe-temporaire/changer` | structure prete |
| Langue francaise | `https://vote-upc-online.cd/langue?choix=fr` | pret |
| Langue anglaise | `https://vote-upc-online.cd/langue?choix=en` | pret |

## Super administrateur

| Page | Lien | Regle |
| --- | --- | --- |
| Tableau de bord | `https://vote-upc-online.cd/super-administrateur/tableau-de-bord` | stats globales anonymes |
| Import Excel etudiants | `https://vote-upc-online.cd/super-administrateur/etudiants/importer` | avant periode electorale |
| Gestion etudiants | `https://vote-upc-online.cd/super-administrateur/etudiants` | ajout/modification exceptionnelle |
| Ajout etudiant | `https://vote-upc-online.cd/super-administrateur/etudiants/ajouter` | seulement si vrai etudiant absent |
| Gestion appariteurs | `https://vote-upc-online.cd/super-administrateur/appariteurs` | un appariteur = une faculte |
| Gestion president electoral | `https://vote-upc-online.cd/super-administrateur/presidents-electoraux` | creation du president |
| Gestion candidats | `https://vote-upc-online.cd/super-administrateur/candidats` | faculte/departement/promotion par listes |
| Gestion elections | `https://vote-upc-online.cd/super-administrateur/elections` | portee universite/faculte/departement/promotion |
| Statistiques | `https://vote-upc-online.cd/super-administrateur/statistiques` | noms etudiants masques |
| Journal des activations | `https://vote-upc-online.cd/super-administrateur/journal-activations` | voit toutes les activations |

## President electoral

| Page | Lien | Regle |
| --- | --- | --- |
| Tableau de bord | `https://vote-upc-online.cd/president-electoral/tableau-de-bord` | supervision globale |
| Validation du lancement | `https://vote-upc-online.cd/president-electoral/elections/validations` | obligatoire avant le vote |
| Statistiques | `https://vote-upc-online.cd/president-electoral/statistiques` | vision globale |
| Publication resultats | `https://vote-upc-online.cd/president-electoral/resultats/publication` | aval final du president |
| Annulation par faculte | `https://vote-upc-online.cd/president-electoral/elections/annulations` | invalide les votes de la faculte concernee |

## Appariteur

| Page | Lien | Regle |
| --- | --- | --- |
| Tableau de bord | `https://vote-upc-online.cd/appariteur/tableau-de-bord` | faculte de l'appariteur seulement |
| Recherche etudiants | `https://vote-upc-online.cd/appariteur/etudiants/rechercher` | nom, promotion, matricule ou email |
| Liste etudiants | `https://vote-upc-online.cd/appariteur/etudiants` | etudiants de sa faculte uniquement |
| Activations | `https://vote-upc-online.cd/appariteur/activations` | envoi MDP temporaire |
| Comptes bloques | `https://vote-upc-online.cd/appariteur/comptes-bloques` | reactivation apres 4 echecs |

## Etudiant

| Page | Lien | Regle |
| --- | --- | --- |
| Tableau de bord | `https://vote-upc-online.cd/etudiant/tableau-de-bord` | contenu selon sa faculte/promotion |
| Candidats | `https://vote-upc-online.cd/etudiant/candidats` | candidats visibles selon portee |
| Vote | `https://vote-upc-online.cd/etudiant/vote` | une seule fois, si election ouverte |
| Resultats | `https://vote-upc-online.cd/etudiant/resultats` | seulement apres publication |
| Profil et mot de passe | `https://vote-upc-online.cd/etudiant/profil/mot-de-passe` | changement personnel |

## Regle importante des pages etudiants

Un etudiant voit uniquement les pages, candidats, elections et resultats correspondant a sa faculte, son departement et sa promotion.

Exemples:

- Un etudiant FASE reste dans l'espace FASE.
- Un etudiant Medecine reste dans l'espace Medecine.
- Un etudiant Theologie reste dans l'espace Theologie.
- Un etudiant FASI ne vote pas dans une election FASE.
- Si l'election est pour toute l'universite, tous les etudiants actifs peuvent voter.
