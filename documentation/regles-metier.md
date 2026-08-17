# Regles Metier - VOTE UPC ONLINE

Ce document fixe les regles officielles de l'application. Le code doit respecter ces points pendant toute la construction.

## Acteurs

### Super administrateur

- Fait la premiere inscription securisee.
- Se connecte avec email ou nom d'utilisateur.
- Cree les candidats.
- Importe les etudiants par Excel.
- Ajoute ou modifie un etudiant avant la periode des elections.
- Cree les appariteurs et le president electoral.
- Lance ou ferme les elections.
- Lance une election pour toute l'universite, une faculte, un departement ou une promotion.
- Voit les statistiques globales anonymes.
- Voit chaque activation faite par un appariteur.
- Ne publie pas les resultats sans aval du president electoral.

### Appariteur

- Voit uniquement les etudiants de sa faculte.
- Recherche par nom, promotion, matricule ou email.
- Active un etudiant apres verification des preuves.
- Envoie un mot de passe temporaire par email.
- Reactive un compte bloque apres 4 mauvaises tentatives.
- Ne peut pas activer un etudiant d'une autre faculte.

### Etudiant

- Se connecte avec email ou matricule.
- Utilise un mot de passe temporaire apres activation.
- Change obligatoirement son mot de passe a la premiere connexion.
- Consulte les candidats avec photo, biographie, slogan et vision.
- Vote seulement si l'election est ouverte.
- Vote une seule fois.
- Garde acces au site apres son vote pour consulter les autres menus.
- Voit les resultats seulement apres publication officielle.
- Voit une animation de suspense pour l'annonce du vainqueur.

### President electoral

- Consulte les effectifs et statistiques globales.
- Valide le lancement d'une election avant que les etudiants puissent voter.
- Donne l'accord final pour publier les resultats.
- Peut annuler une election dans une faculte si necessaire.
- Supervise le deroulement general.

## Cycle de vie d'un etudiant

1. Import ou ajout manuel exceptionnel: statut `non_active`.
2. Activation par l'appariteur de sa faculte: generation et envoi du mot de passe temporaire.
3. Premiere connexion: changement obligatoire du mot de passe.
4. Vote: participation marquee `a_vote`.
5. 4 echecs de connexion: compte `bloque`.
6. Reactivation par l'appariteur de sa faculte.

## Cycle de vie d'une election

1. Creation: nom, date debut, date fin et portee.
2. Demande de validation du lancement par le president electoral.
3. Apres validation: les etudiants concernes peuvent voir le passage au vote.
4. Session ouverte: popup chrono visible jusqu'a la fin.
5. A `00:00:00`: plus aucun vote possible.
6. Les non-votants deviennent des abstentions.
7. Cloture: calcul des resultats, mais resultats caches.
8. Aval du president electoral: publication officielle.
9. Apres publication: resultats, animation du vainqueur et statistiques pour la valve.
10. Annulation possible par faculte: les votes de cette faculte sont invalides.

Important: une abstention n'est pas une voix nulle. Une annulation d'election est encore une autre notion.

## Portee des elections

- `universite`: tous les etudiants actifs peuvent voter.
- `faculte`: seuls les etudiants de la faculte choisie peuvent voter.
- `departement`: seuls les etudiants du departement choisi peuvent voter.
- `promotion`: seuls les etudiants de la promotion choisie peuvent voter.

Exemple: si une election est creee pour la FASI, seuls les etudiants FASI peuvent voter.

## Visibilite des pages etudiants

Chaque page etudiant doit respecter la faculte, le departement et la promotion de l'etudiant connecte.

- Un etudiant FASE reste dans l'espace FASE.
- Un etudiant Medecine reste dans l'espace Medecine.
- Un etudiant Theologie reste dans l'espace Theologie.
- Un etudiant FASI reste dans l'espace FASI.
- Un etudiant Droit reste dans l'espace Droit.
- Les candidats, elections, resultats et statistiques visibles doivent correspondre a sa portee academique.

Cette regle evite qu'un etudiant voie ou utilise une page qui ne concerne pas sa faculte, son departement ou sa promotion.

## Promotions officielles

### FASE, Theologie et Droit

- L1
- L2
- L3
- M1
- M2

### FASI

- L1
- L2
- L3
- L4
- M1
- M2

### Medecine

- G0
- L1
- L2
- L3
- PR BACH1
- BACH2
- BACH3
- D4

## Modules

- Authentification: setup super administrateur, connexion, mot de passe oublie, mot de passe temporaire, changement force, blocage apres 4 essais.
- Etudiants: import Excel, recherche, statuts, ajout exceptionnel.
- Facultes, departements et promotions: catalogues academiques.
- Appariteurs: un appariteur appartient a une seule faculte.
- Candidats: listes deroulantes, photo passeport, biographie, slogan, vision, image optionnelle.
- Elections: nom, dates, chrono, portee, validation, publication.
- Vote: vote unique, securise, acces conserve apres vote.
- Statistiques et resultats: participation, graphiques, publication controlee.
