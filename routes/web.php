<?php

declare(strict_types=1);

use Application\Controleurs\Authentification\ControleurAuthentification;
use Application\Controleurs\Authentification\ControleurInstallation;
use Application\Controleurs\ControleurLangue;
use Application\Controleurs\ControleurModule;
use Application\Controleurs\SuperAdministrateur\ControleurTableauDeBord;

$routeur->get('/', [ControleurInstallation::class, 'accueil']);

$routeur->get('/langue', [ControleurLangue::class, 'changer']);

$routeur->get('/installation/super-administrateur', [ControleurInstallation::class, 'afficherCreationSuperAdministrateur']);
$routeur->post('/installation/super-administrateur', [ControleurInstallation::class, 'enregistrerSuperAdministrateur']);

$routeur->get('/connexion', [ControleurAuthentification::class, 'afficherConnexion']);
$routeur->post('/connexion', [ControleurAuthentification::class, 'connecter']);
$routeur->get('/administration/connexion', [ControleurAuthentification::class, 'afficherConnexionAdministration']);
$routeur->post('/administration/connexion', [ControleurAuthentification::class, 'connecterAdministration']);
$routeur->get('/etudiant/connexion', [ControleurAuthentification::class, 'afficherConnexionEtudiant']);
$routeur->post('/etudiant/connexion', [ControleurAuthentification::class, 'connecterEtudiant']);
$routeur->get('/election/acces', [ControleurAuthentification::class, 'accesElectionEtudiant']);
$routeur->get('/otp/connexion', [ControleurAuthentification::class, 'afficherVerificationOtp']);
$routeur->post('/otp/connexion', [ControleurAuthentification::class, 'verifierOtp']);
$routeur->post('/otp/renvoyer', [ControleurAuthentification::class, 'renvoyerOtp']);
$routeur->get('/deconnexion', [ControleurAuthentification::class, 'deconnecter']);
$routeur->post('/deconnexion', [ControleurAuthentification::class, 'deconnecter']);
$routeur->get('/mot-de-passe-oublie', [ControleurAuthentification::class, 'afficherMotDePasseOublie']);
$routeur->post('/mot-de-passe-oublie', [ControleurAuthentification::class, 'envoyerCodeMotDePasseOublie']);
$routeur->get('/mot-de-passe-oublie/reinitialiser', [ControleurAuthentification::class, 'afficherReinitialisationMotDePasse']);
$routeur->post('/mot-de-passe-oublie/reinitialiser', [ControleurAuthentification::class, 'reinitialiserMotDePasse']);
$routeur->get('/mot-de-passe-temporaire/changer', [ControleurModule::class, 'motDePasseTemporaire']);
$routeur->post('/mot-de-passe-temporaire/changer', [ControleurModule::class, 'changerMotDePasseTemporaire']);

$routeur->get('/super-administrateur/tableau-de-bord', [ControleurTableauDeBord::class, 'afficher']);
$routeur->get('/super-administrateur/etudiants', [ControleurModule::class, 'superAdminEtudiants']);
$routeur->get('/super-administrateur/etudiants/importer', [ControleurModule::class, 'superAdminImportEtudiants']);
$routeur->post('/super-administrateur/etudiants/importer', [ControleurModule::class, 'traiterImportEtudiants']);
$routeur->get('/super-administrateur/etudiants/ajouter', [ControleurModule::class, 'superAdminAjouterEtudiant']);
$routeur->get('/super-administrateur/etudiants/modifier', [ControleurModule::class, 'superAdminModifierEtudiant']);
$routeur->post('/super-administrateur/etudiants/modifier', [ControleurModule::class, 'mettreAJourEtudiant']);
$routeur->get('/super-administrateur/appariteurs', [ControleurModule::class, 'superAdminAppariteurs']);
$routeur->post('/super-administrateur/appariteurs', [ControleurModule::class, 'enregistrerAppariteur']);
$routeur->get('/super-administrateur/appariteurs/modifier', [ControleurModule::class, 'superAdminModifierAppariteur']);
$routeur->post('/super-administrateur/appariteurs/modifier', [ControleurModule::class, 'mettreAJourAppariteur']);
$routeur->get('/super-administrateur/presidents-electoraux', [ControleurModule::class, 'superAdminPresidents']);
$routeur->post('/super-administrateur/presidents-electoraux', [ControleurModule::class, 'enregistrerPresidentElectoral']);
$routeur->get('/super-administrateur/presidents-electoraux/modifier', [ControleurModule::class, 'superAdminModifierPresidentElectoral']);
$routeur->post('/super-administrateur/presidents-electoraux/modifier', [ControleurModule::class, 'mettreAJourPresidentElectoral']);
$routeur->get('/super-administrateur/candidats', [ControleurModule::class, 'superAdminCandidats']);
$routeur->post('/super-administrateur/candidats', [ControleurModule::class, 'enregistrerCandidat']);
$routeur->post('/super-administrateur/candidats/modifier', [ControleurModule::class, 'modifierCandidat']);
$routeur->get('/super-administrateur/elections', [ControleurModule::class, 'superAdminElections']);
$routeur->post('/super-administrateur/elections', [ControleurModule::class, 'enregistrerElection']);
$routeur->post('/super-administrateur/elections/lancer', [ControleurModule::class, 'demanderLancementElection']);
$routeur->get('/super-administrateur/elections/code-qr', [ControleurModule::class, 'superAdminCodeQrElection']);
$routeur->get('/super-administrateur/statistiques', [ControleurModule::class, 'superAdminStatistiques']);
$routeur->get('/super-administrateur/journal-activations', [ControleurModule::class, 'superAdminJournalActivations']);
$routeur->get('/super-administrateur/activites', [ControleurModule::class, 'superAdminActivites']);

$routeur->get('/president-electoral/tableau-de-bord', [ControleurModule::class, 'presidentTableauDeBord']);
$routeur->get('/president-electoral/candidats', [ControleurModule::class, 'presidentCandidats']);
$routeur->get('/president-electoral/elections/validations', [ControleurModule::class, 'presidentValidations']);
$routeur->post('/president-electoral/elections/validations', [ControleurModule::class, 'traiterValidationPresident']);
$routeur->get('/president-electoral/statistiques', [ControleurModule::class, 'presidentStatistiques']);
$routeur->get('/president-electoral/rapports/participation', [ControleurModule::class, 'presidentRapportParticipation']);
$routeur->get('/president-electoral/resultats/publication', [ControleurModule::class, 'presidentPublication']);
$routeur->post('/president-electoral/resultats/publication', [ControleurModule::class, 'traiterPublicationPresident']);
$routeur->get('/president-electoral/elections/annulations', [ControleurModule::class, 'presidentAnnulations']);
$routeur->post('/president-electoral/elections/annulations', [ControleurModule::class, 'traiterAnnulationPresident']);

$routeur->get('/appariteur/tableau-de-bord', [ControleurModule::class, 'appariteurTableauDeBord']);
$routeur->get('/appariteur/etudiants/rechercher', [ControleurModule::class, 'appariteurRecherche']);
$routeur->get('/appariteur/etudiants', [ControleurModule::class, 'appariteurEtudiants']);
$routeur->post('/appariteur/etudiants/activer', [ControleurModule::class, 'activerEtudiantParAppariteur']);
$routeur->get('/appariteur/activations', [ControleurModule::class, 'appariteurActivations']);
$routeur->get('/appariteur/comptes-bloques', [ControleurModule::class, 'appariteurComptesBloques']);

$routeur->get('/etudiant/tableau-de-bord', [ControleurModule::class, 'etudiantTableauDeBord']);
$routeur->get('/etudiant/candidats', [ControleurModule::class, 'etudiantCandidats']);
$routeur->get('/etudiant/vote', [ControleurModule::class, 'etudiantVote']);
$routeur->post('/etudiant/vote', [ControleurModule::class, 'enregistrerVoteEtudiant']);
$routeur->get('/etudiant/resultats', [ControleurModule::class, 'etudiantResultats']);
$routeur->get('/etudiant/profil/mot-de-passe', [ControleurModule::class, 'etudiantMotDePasse']);
