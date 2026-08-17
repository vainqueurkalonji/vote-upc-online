<?php

declare(strict_types=1);

namespace Application\Controleurs;

use Application\Modeles\Candidat;
use Application\Modeles\Election;
use Application\Modeles\Etudiant;
use Application\Modeles\JournalActivite;
use Application\Modeles\PersonnelAdministratif;
use Application\Modeles\Utilisateur;
use Application\Noyau\Controleur;
use Application\Services\ServiceAuthentification;
use Application\Services\ServiceEmail;
use Application\Services\ServiceReglesMetier;
use Application\Services\ServiceSecurite;
use Application\Validateurs\ValidateurPersonnelAdministratif;
use DateTimeImmutable;

class ControleurModule extends Controleur
{
    public function superAdminEtudiants(): void
    {
        $utilisateur = ServiceAuthentification::exigerRole(ServiceReglesMetier::ROLE_SUPER_ADMINISTRATEUR);
        $modele = new Etudiant();

        $recherche = trim((string) ($_GET['q'] ?? ''));
        $faculte = strtoupper(trim((string) ($_GET['faculte'] ?? '')));
        $promotion = strtoupper(trim((string) ($_GET['promotion'] ?? '')));
        $statut = trim((string) ($_GET['statut'] ?? ''));
        $filtresActifs = $recherche !== '' || $faculte !== '' || $promotion !== '' || $statut !== '';

        $this->vue('super_administrateur/etudiants', [
            'titre' => 'Gestion des etudiants',
            'utilisateur' => $utilisateur,
            'facultes' => (new PersonnelAdministratif())->listerFacultes(),
            'recherche' => $recherche,
            'faculte_selectionnee' => $faculte,
            'promotion_selectionnee' => $promotion,
            'statut_selectionne' => $statut,
            'filtres_actifs' => $filtresActifs,
            'statistiques' => $modele->statistiquesGlobales(),
            'repartition_academique' => $modele->statistiquesAcademiquesHierarchiques(),
            'etudiants' => $filtresActifs ? $modele->listerPourSuperAdministrateur($recherche, $faculte, $statut, $promotion) : [],
            'message_succes' => $_SESSION['etudiant_message_succes'] ?? null,
            'message_erreur' => $_SESSION['etudiant_message_erreur'] ?? null,
        ], 'application');

        unset($_SESSION['etudiant_message_succes'], $_SESSION['etudiant_message_erreur']);
    }

    public function superAdminImportEtudiants(): void
    {
        $utilisateur = ServiceAuthentification::exigerRole(ServiceReglesMetier::ROLE_SUPER_ADMINISTRATEUR);

        $this->vue('super_administrateur/import_etudiants', [
            'titre' => 'Importer les etudiants',
            'utilisateur' => $utilisateur,
            'message_succes' => $_SESSION['import_message_succes'] ?? null,
            'message_erreur' => $_SESSION['import_message_erreur'] ?? null,
        ], 'application');

        unset($_SESSION['import_message_succes'], $_SESSION['import_message_erreur']);
    }

    public function traiterImportEtudiants(): void
    {
        $utilisateur = ServiceAuthentification::exigerRole(ServiceReglesMetier::ROLE_SUPER_ADMINISTRATEUR);

        if (empty($_FILES['fichier_etudiants']) || !is_uploaded_file($_FILES['fichier_etudiants']['tmp_name'])) {
            $_SESSION['import_message_erreur'] = 'Selectionne d abord un fichier Excel avant de lancer l import.';
            rediriger('/super-administrateur/etudiants/importer');
        }

        $fichier = $_FILES['fichier_etudiants'];
        $extension = strtolower(pathinfo((string) $fichier['name'], PATHINFO_EXTENSION));

        if (!in_array($extension, ['xlsx', 'xls'], true)) {
            $_SESSION['import_message_erreur'] = 'Le fichier doit etre au format Excel .xlsx ou .xls.';
            rediriger('/super-administrateur/etudiants/importer');
        }

        $dossier = CHEMIN_RACINE . '/stockage/imports';
        if (!is_dir($dossier)) {
            mkdir($dossier, 0775, true);
        }

        $nomOriginal = preg_replace('/[^A-Za-z0-9_.-]/', '_', (string) $fichier['name']);
        $nomFichier = date('Ymd_His') . '_' . $nomOriginal;
        $destination = $dossier . '/' . $nomFichier;

        if (!move_uploaded_file($fichier['tmp_name'], $destination)) {
            $_SESSION['import_message_erreur'] = 'Impossible de deposer le fichier. Verifie les permissions du dossier stockage/imports.';
            rediriger('/super-administrateur/etudiants/importer');
        }

        try {
            $rapport = (new Etudiant())->importerDepuisExcel($destination, (int) $utilisateur['id']);
            $resume = sprintf(
                'Fichier recu : %s. Lignes lues : %d. Nouveaux : %d. Modifies : %d. Erreurs : %d.',
                $nomOriginal,
                $rapport['total_lignes'],
                $rapport['total_importes'],
                $rapport['total_modifies'],
                $rapport['total_erreurs']
            );

            if (!empty($rapport['erreurs'])) {
                $resume .= ' Premieres erreurs : ' . implode(' | ', $rapport['erreurs']);
            }

            $_SESSION['import_message_succes'] = $resume;
        } catch (\Throwable $exception) {
            $_SESSION['import_message_erreur'] = 'Fichier depose, mais l import en base a echoue : ' . $exception->getMessage();
        }

        rediriger('/super-administrateur/etudiants/importer');
    }

    public function superAdminAjouterEtudiant(): void
    {
        $this->afficherSuperAdmin([
            'surtitre' => 'Ajout exceptionnel',
            'titre' => 'Ajouter un etudiant',
            'description' => 'Ajouter manuellement un vrai etudiant absent du fichier Excel apres verification administrative.',
            'badge' => 'Exception controlee',
            'actions' => [
                ['libelle' => 'Retour etudiants', 'lien' => '/super-administrateur/etudiants'],
                ['libelle' => 'Importer Excel', 'lien' => '/super-administrateur/etudiants/importer'],
            ],
            'statistiques' => [
                ['valeur' => '1', 'libelle' => 'Faculte unique'],
                ['valeur' => '1', 'libelle' => 'Promotion unique'],
                ['valeur' => '0', 'libelle' => 'Vote cree'],
                ['valeur' => 'Audit', 'libelle' => 'Trace obligatoire'],
            ],
            'regles' => [
                'L ajout manuel doit rester exceptionnel.',
                'Les preuves administratives doivent etre verifiees.',
                'Le compte reste non active tant que l appariteur ne valide pas.',
                'Le super administrateur garde une trace de l action.',
            ],
            'colonnes' => ['Section', 'Champs prevus', 'Controle'],
            'lignes' => [
                ['Identite', 'Nom, post-nom, prenom, sexe', 'Lettres et valeurs valides'],
                ['Academique', 'Faculte, departement, promotion', 'Listes deroulantes'],
                ['Contact', 'Email, matricule', 'Valeurs uniques'],
            ],
        ]);
    }

    public function superAdminAppariteurs(): void
    {
        $utilisateur = ServiceAuthentification::exigerRole(ServiceReglesMetier::ROLE_SUPER_ADMINISTRATEUR);
        $personnel = new PersonnelAdministratif();

        $this->vue('super_administrateur/appariteurs', [
            'titre' => 'Gestion des appariteurs',
            'utilisateur' => $utilisateur,
            'facultes' => $personnel->listerFacultes(),
            'appariteurs' => $personnel->listerAppariteurs(),
            'erreurs' => $_SESSION['appariteur_erreurs'] ?? [],
            'anciennes_donnees' => $_SESSION['appariteur_anciennes_donnees'] ?? [],
            'message_succes' => $_SESSION['appariteur_message_succes'] ?? null,
            'message_erreur' => $_SESSION['appariteur_message_erreur'] ?? null,
        ], 'application');

        unset(
            $_SESSION['appariteur_erreurs'],
            $_SESSION['appariteur_anciennes_donnees'],
            $_SESSION['appariteur_message_succes'],
            $_SESSION['appariteur_message_erreur']
        );
    }

    public function enregistrerAppariteur(): void
    {
        $utilisateur = ServiceAuthentification::exigerRole(ServiceReglesMetier::ROLE_SUPER_ADMINISTRATEUR);
        $donnees = $this->donneesPersonnelDepuisPost(true);
        $erreurs = ValidateurPersonnelAdministratif::valider($donnees, true);

        if (!empty($erreurs)) {
            $_SESSION['appariteur_erreurs'] = $erreurs;
            $_SESSION['appariteur_anciennes_donnees'] = $donnees;
            rediriger('/super-administrateur/appariteurs');
        }

        $motDePasseTemporaire = ServiceSecurite::genererMotDePasseTemporaire();

        try {
            $compte = (new PersonnelAdministratif())->creerAppariteur($donnees, (int) $utilisateur['id'], $motDePasseTemporaire);
            $emailEnvoye = (new ServiceEmail())->envoyerMotDePasseTemporaireUtilisateur($compte, $motDePasseTemporaire);
            $_SESSION['appariteur_message_succes'] = $emailEnvoye
                ? 'Appariteur cree avec succes. Le mot de passe temporaire a ete envoye par email.'
                : 'Appariteur cree, mais email non envoye. Mot de passe temporaire : ' . $motDePasseTemporaire;
        } catch (\Throwable $exception) {
            $_SESSION['appariteur_message_erreur'] = 'Impossible de creer l appariteur : ' . $exception->getMessage();
            $_SESSION['appariteur_anciennes_donnees'] = $donnees;
        }

        rediriger('/super-administrateur/appariteurs');
    }

    public function superAdminModifierAppariteur(): void
    {
        $utilisateur = ServiceAuthentification::exigerRole(ServiceReglesMetier::ROLE_SUPER_ADMINISTRATEUR);
        $appariteurId = (int) ($_GET['id'] ?? 0);
        $personnel = new PersonnelAdministratif();
        $appariteur = $personnel->trouverAppariteur($appariteurId);

        if (!$appariteur) {
            $_SESSION['appariteur_message_erreur'] = 'Appariteur introuvable.';
            rediriger('/super-administrateur/appariteurs');
        }

        $this->vue('super_administrateur/modifier_appariteur', [
            'titre' => 'Modifier un appariteur',
            'utilisateur' => $utilisateur,
            'appariteur' => $appariteur,
            'facultes' => $personnel->listerFacultes(),
            'erreurs' => $_SESSION['appariteur_modification_erreurs'] ?? [],
            'anciennes_donnees' => $_SESSION['appariteur_modification_anciennes_donnees'] ?? [],
        ], 'application');

        unset($_SESSION['appariteur_modification_erreurs'], $_SESSION['appariteur_modification_anciennes_donnees']);
    }

    public function mettreAJourAppariteur(): void
    {
        $utilisateur = ServiceAuthentification::exigerRole(ServiceReglesMetier::ROLE_SUPER_ADMINISTRATEUR);
        $appariteurId = (int) ($_POST['appariteur_id'] ?? 0);
        $donnees = $this->donneesPersonnelDepuisPost(true);
        $erreurs = ValidateurPersonnelAdministratif::valider($donnees, true);

        if (!empty($erreurs)) {
            $_SESSION['appariteur_modification_erreurs'] = $erreurs;
            $_SESSION['appariteur_modification_anciennes_donnees'] = $donnees;
            rediriger('/super-administrateur/appariteurs/modifier?id=' . $appariteurId);
        }

        try {
            (new PersonnelAdministratif())->modifierAppariteur($appariteurId, $donnees, (int) $utilisateur['id']);
            $_SESSION['appariteur_message_succes'] = 'Appariteur modifie avec succes.';
        } catch (\Throwable $exception) {
            $_SESSION['appariteur_modification_erreurs'] = ['general' => $exception->getMessage()];
            $_SESSION['appariteur_modification_anciennes_donnees'] = $donnees;
            rediriger('/super-administrateur/appariteurs/modifier?id=' . $appariteurId);
        }

        rediriger('/super-administrateur/appariteurs');
    }

    public function superAdminPresidents(): void
    {
        $utilisateur = ServiceAuthentification::exigerRole(ServiceReglesMetier::ROLE_SUPER_ADMINISTRATEUR);
        $personnel = new PersonnelAdministratif();

        $this->vue('super_administrateur/presidents', [
            'titre' => 'Gestion du president electoral',
            'utilisateur' => $utilisateur,
            'presidents' => $personnel->listerPresidentsElectoraux(),
            'erreurs' => $_SESSION['president_erreurs'] ?? [],
            'anciennes_donnees' => $_SESSION['president_anciennes_donnees'] ?? [],
            'message_succes' => $_SESSION['president_message_succes'] ?? null,
            'message_erreur' => $_SESSION['president_message_erreur'] ?? null,
        ], 'application');

        unset(
            $_SESSION['president_erreurs'],
            $_SESSION['president_anciennes_donnees'],
            $_SESSION['president_message_succes'],
            $_SESSION['president_message_erreur']
        );
    }

    public function enregistrerPresidentElectoral(): void
    {
        $utilisateur = ServiceAuthentification::exigerRole(ServiceReglesMetier::ROLE_SUPER_ADMINISTRATEUR);
        $donnees = $this->donneesPersonnelDepuisPost(false);
        $erreurs = ValidateurPersonnelAdministratif::valider($donnees, false);

        if (!empty($erreurs)) {
            $_SESSION['president_erreurs'] = $erreurs;
            $_SESSION['president_anciennes_donnees'] = $donnees;
            rediriger('/super-administrateur/presidents-electoraux');
        }

        $motDePasseTemporaire = ServiceSecurite::genererMotDePasseTemporaire();

        try {
            $compte = (new PersonnelAdministratif())->creerPresidentElectoral($donnees, (int) $utilisateur['id'], $motDePasseTemporaire);
            $emailEnvoye = (new ServiceEmail())->envoyerMotDePasseTemporaireUtilisateur($compte, $motDePasseTemporaire);
            $_SESSION['president_message_succes'] = $emailEnvoye
                ? 'President electoral cree avec succes. Le mot de passe temporaire a ete envoye par email.'
                : 'President electoral cree, mais email non envoye. Mot de passe temporaire : ' . $motDePasseTemporaire;
        } catch (\Throwable $exception) {
            $_SESSION['president_message_erreur'] = 'Impossible de creer le president electoral : ' . $exception->getMessage();
            $_SESSION['president_anciennes_donnees'] = $donnees;
        }

        rediriger('/super-administrateur/presidents-electoraux');
    }

    public function superAdminModifierPresidentElectoral(): void
    {
        $utilisateur = ServiceAuthentification::exigerRole(ServiceReglesMetier::ROLE_SUPER_ADMINISTRATEUR);
        $presidentId = (int) ($_GET['id'] ?? 0);
        $personnel = new PersonnelAdministratif();
        $president = $personnel->trouverPresidentElectoral($presidentId);

        if (!$president) {
            $_SESSION['president_message_erreur'] = 'President electoral introuvable.';
            rediriger('/super-administrateur/presidents-electoraux');
        }

        $this->vue('super_administrateur/modifier_president', [
            'titre' => 'Modifier le president electoral',
            'utilisateur' => $utilisateur,
            'president' => $president,
            'erreurs' => $_SESSION['president_modification_erreurs'] ?? [],
            'anciennes_donnees' => $_SESSION['president_modification_anciennes_donnees'] ?? [],
        ], 'application');

        unset($_SESSION['president_modification_erreurs'], $_SESSION['president_modification_anciennes_donnees']);
    }

    public function mettreAJourPresidentElectoral(): void
    {
        $utilisateur = ServiceAuthentification::exigerRole(ServiceReglesMetier::ROLE_SUPER_ADMINISTRATEUR);
        $presidentId = (int) ($_POST['president_id'] ?? 0);
        $donnees = $this->donneesPersonnelDepuisPost(false);
        $erreurs = ValidateurPersonnelAdministratif::valider($donnees, false);

        if (!empty($erreurs)) {
            $_SESSION['president_modification_erreurs'] = $erreurs;
            $_SESSION['president_modification_anciennes_donnees'] = $donnees;
            rediriger('/super-administrateur/presidents-electoraux/modifier?id=' . $presidentId);
        }

        try {
            (new PersonnelAdministratif())->modifierPresidentElectoral($presidentId, $donnees, (int) $utilisateur['id']);
            $_SESSION['president_message_succes'] = 'President electoral modifie avec succes.';
        } catch (\Throwable $exception) {
            $_SESSION['president_modification_erreurs'] = ['general' => $exception->getMessage()];
            $_SESSION['president_modification_anciennes_donnees'] = $donnees;
            rediriger('/super-administrateur/presidents-electoraux/modifier?id=' . $presidentId);
        }

        rediriger('/super-administrateur/presidents-electoraux');
    }

    public function superAdminCandidats(): void
    {
        $utilisateur = ServiceAuthentification::exigerRole(ServiceReglesMetier::ROLE_SUPER_ADMINISTRATEUR);
        $modele = new Candidat();
        $anciennesDonnees = $_SESSION['candidat_anciennes_donnees'] ?? [];
        $electionSelectionnee = (int) ($_GET['election_id'] ?? 0);
        $candidatModification = null;
        $candidatModificationId = (int) ($_GET['modifier_id'] ?? 0);

        if ($electionSelectionnee > 0 && empty($anciennesDonnees['election_id'])) {
            $anciennesDonnees['election_id'] = (string) $electionSelectionnee;
        }

        if ($candidatModificationId > 0) {
            $candidatModification = $modele->trouverPourModification($candidatModificationId);

            if (!$candidatModification) {
                $_SESSION['candidat_message_erreur'] = 'Candidat introuvable.';
                rediriger('/super-administrateur/candidats');
            }

            if (($candidatModification['election_statut'] ?? '') !== 'brouillon') {
                $_SESSION['candidat_message_erreur'] = "Ce candidat n'est plus modifiable parce que son election n'est plus en preparation.";
                rediriger('/super-administrateur/candidats');
            }

            if (empty($anciennesDonnees)) {
                $anciennesDonnees = [
                    'nom' => (string) $candidatModification['nom'],
                    'post_nom' => (string) $candidatModification['post_nom'],
                    'prenom' => (string) $candidatModification['prenom'],
                    'sexe' => (string) ($candidatModification['sexe'] ?? ''),
                    'faculte_id' => (string) $candidatModification['faculte_id'],
                    'promotion_id' => (string) $candidatModification['promotion_id'],
                    'departement_id' => (string) ($candidatModification['departement_id'] ?? ''),
                    'election_id' => (string) ($candidatModification['election_id'] ?? ''),
                    'biographie' => (string) ($candidatModification['biographie'] ?? ''),
                    'slogan' => (string) ($candidatModification['slogan'] ?? ''),
                    'vision' => (string) ($candidatModification['vision'] ?? ''),
                ];
            }
        }

        $this->vue('super_administrateur/candidats', [
            'titre' => 'Gestion des candidats',
            'utilisateur' => $utilisateur,
            'catalogue_academique' => $modele->catalogueAcademique(),
            'departements' => $modele->listerDepartements(),
            'elections' => $modele->listerElectionsDisponibles(),
            'statistiques_candidats' => $modele->statistiques(),
            'candidats' => $modele->lister(),
            'candidat_modification' => $candidatModification,
            'erreurs' => $_SESSION['candidat_erreurs'] ?? [],
            'anciennes_donnees' => $anciennesDonnees,
            'message_succes' => $_SESSION['candidat_message_succes'] ?? null,
            'message_erreur' => $_SESSION['candidat_message_erreur'] ?? null,
        ], 'application');

        unset(
            $_SESSION['candidat_erreurs'],
            $_SESSION['candidat_anciennes_donnees'],
            $_SESSION['candidat_message_succes'],
            $_SESSION['candidat_message_erreur']
        );
    }

    public function enregistrerCandidat(): void
    {
        $utilisateur = ServiceAuthentification::exigerRole(ServiceReglesMetier::ROLE_SUPER_ADMINISTRATEUR);
        $modele = new Candidat();
        $donnees = $this->donneesCandidatDepuisPost();
        $erreurs = $this->validerDonneesCandidat($donnees);
        $photoMedia = null;
        $imageVisionMedia = null;

        if (empty($erreurs)) {
            try {
                $modele->verifierPorteeElectionCandidat($donnees);
            } catch (\Throwable $exception) {
                $erreurs['election_id'] = $exception->getMessage();
            }
        }

        if (empty($erreurs)) {
            $photoMedia = $this->deposerImageCandidat('photo_candidat', 'candidats', $erreurs);
            $imageVisionMedia = $this->deposerImageCandidat('image_vision', 'visions-candidats', $erreurs);
        }

        if (!empty($erreurs)) {
            $_SESSION['candidat_erreurs'] = $erreurs;
            $_SESSION['candidat_anciennes_donnees'] = $donnees;
            rediriger('/super-administrateur/candidats?election_id=' . urlencode((string) ($donnees['election_id'] ?? '')));
        }

        if ($photoMedia !== null) {
            $donnees['photo_base64'] = $photoMedia['base64'];
            $donnees['photo_type_mime'] = $photoMedia['type_mime'];
            $donnees['photo_nom'] = $photoMedia['nom'];
        }

        if ($imageVisionMedia !== null) {
            $donnees['image_vision_base64'] = $imageVisionMedia['base64'];
            $donnees['image_vision_type_mime'] = $imageVisionMedia['type_mime'];
            $donnees['image_vision_nom'] = $imageVisionMedia['nom'];
        }

        try {
            $modele->creer($donnees, (int) $utilisateur['id']);
            $_SESSION['candidat_message_succes'] = "Candidat cree avec succes et rattache a l'election choisie.";
        } catch (\Throwable $exception) {
            $_SESSION['candidat_message_erreur'] = 'Creation du candidat impossible : ' . $exception->getMessage();
            $this->retirerMediasCandidatDesDonnees($donnees);
            $_SESSION['candidat_anciennes_donnees'] = $donnees;
        }

        rediriger('/super-administrateur/candidats?election_id=' . urlencode((string) ($donnees['election_id'] ?? '')));
    }

    public function modifierCandidat(): void
    {
        $utilisateur = ServiceAuthentification::exigerRole(ServiceReglesMetier::ROLE_SUPER_ADMINISTRATEUR);
        $modele = new Candidat();
        $candidatId = (int) ($_POST['candidat_id'] ?? 0);
        $candidat = $modele->trouverPourModification($candidatId);

        if (!$candidat) {
            $_SESSION['candidat_message_erreur'] = 'Candidat introuvable.';
            rediriger('/super-administrateur/candidats');
        }

        $donnees = $this->donneesCandidatDepuisPost();
        $erreurs = $this->validerDonneesCandidat($donnees);
        $photoMedia = null;
        $imageVisionMedia = null;

        if (empty($erreurs)) {
            try {
                if (($candidat['election_statut'] ?? '') !== 'brouillon') {
                    throw new \RuntimeException("Ce candidat n'est plus modifiable parce que son election n'est plus en preparation.");
                }

                $modele->verifierPorteeElectionCandidat($donnees);
            } catch (\Throwable $exception) {
                $erreurs['election_id'] = $exception->getMessage();
            }
        }

        if (empty($erreurs)) {
            $photoMedia = $this->deposerImageCandidat('photo_candidat', 'candidats', $erreurs);
            $imageVisionMedia = $this->deposerImageCandidat('image_vision', 'visions-candidats', $erreurs);
        }

        if (!empty($erreurs)) {
            $_SESSION['candidat_erreurs'] = $erreurs;
            $_SESSION['candidat_anciennes_donnees'] = $donnees;
            rediriger('/super-administrateur/candidats?modifier_id=' . $candidatId);
        }

        if ($photoMedia !== null) {
            $donnees['photo_base64'] = $photoMedia['base64'];
            $donnees['photo_type_mime'] = $photoMedia['type_mime'];
            $donnees['photo_nom'] = $photoMedia['nom'];
        }

        if ($imageVisionMedia !== null) {
            $donnees['image_vision_base64'] = $imageVisionMedia['base64'];
            $donnees['image_vision_type_mime'] = $imageVisionMedia['type_mime'];
            $donnees['image_vision_nom'] = $imageVisionMedia['nom'];
        }

        try {
            $modele->modifier($candidatId, $donnees, (int) $utilisateur['id']);
            $_SESSION['candidat_message_succes'] = 'Candidat modifie avec succes.';
        } catch (\Throwable $exception) {
            $_SESSION['candidat_message_erreur'] = 'Modification du candidat impossible : ' . $exception->getMessage();
            $this->retirerMediasCandidatDesDonnees($donnees);
            $_SESSION['candidat_anciennes_donnees'] = $donnees;
            rediriger('/super-administrateur/candidats?modifier_id=' . $candidatId);
        }

        rediriger('/super-administrateur/candidats?election_id=' . urlencode((string) ($donnees['election_id'] ?? '')));
    }

    public function remplacerPhotoCandidat(): void
    {
        $utilisateur = ServiceAuthentification::exigerRole(ServiceReglesMetier::ROLE_SUPER_ADMINISTRATEUR);
        $candidatId = (int) ($_POST['candidat_id'] ?? 0);
        $erreurs = [];

        if ($candidatId <= 0) {
            $_SESSION['candidat_message_erreur'] = 'Candidat invalide.';
            rediriger('/super-administrateur/candidats');
        }

        $photoMedia = $this->deposerImageCandidat('photo_candidat', 'candidats', $erreurs);

        if ($photoMedia === null && empty($erreurs)) {
            $erreurs['photo_candidat'] = 'Choisis une photo avant de valider.';
        }

        if (!empty($erreurs)) {
            $_SESSION['candidat_message_erreur'] = $erreurs['photo_candidat'] ?? "La photo n'a pas pu etre enregistree.";
            rediriger('/super-administrateur/candidats');
        }

        try {
            (new Candidat())->remplacerPhoto($candidatId, $photoMedia, (int) $utilisateur['id']);
            $_SESSION['candidat_message_succes'] = 'Photo du candidat mise a jour.';
        } catch (\Throwable $exception) {
            $_SESSION['candidat_message_erreur'] = 'Mise a jour de la photo impossible : ' . $exception->getMessage();
        }

        rediriger('/super-administrateur/candidats');
    }

    public function afficherPhotoCandidat(): void
    {
        $this->afficherMediaCandidat('photo');
    }

    public function afficherImageVisionCandidat(): void
    {
        $this->afficherMediaCandidat('image_vision');
    }

    public function superAdminModifierEtudiant(): void
    {
        $utilisateur = ServiceAuthentification::exigerRole(ServiceReglesMetier::ROLE_SUPER_ADMINISTRATEUR);
        $etudiantId = (int) ($_GET['id'] ?? 0);
        $modeleEtudiant = new Etudiant();
        $etudiant = $modeleEtudiant->trouverPourSuperAdministrateur($etudiantId);

        if (!$etudiant) {
            $_SESSION['etudiant_message_erreur'] = 'Etudiant introuvable.';
            rediriger('/super-administrateur/etudiants');
        }

        $modeleCandidat = new Candidat();
        $this->vue('super_administrateur/modifier_etudiant', [
            'titre' => 'Modifier un etudiant',
            'utilisateur' => $utilisateur,
            'etudiant' => $etudiant,
            'catalogue_academique' => $modeleCandidat->catalogueAcademique(),
            'departements' => $modeleCandidat->listerDepartements(),
            'erreurs' => $_SESSION['etudiant_modification_erreurs'] ?? [],
            'anciennes_donnees' => $_SESSION['etudiant_modification_anciennes_donnees'] ?? [],
        ], 'application');

        unset($_SESSION['etudiant_modification_erreurs'], $_SESSION['etudiant_modification_anciennes_donnees']);
    }

    public function mettreAJourEtudiant(): void
    {
        $utilisateur = ServiceAuthentification::exigerRole(ServiceReglesMetier::ROLE_SUPER_ADMINISTRATEUR);
        $etudiantId = (int) ($_POST['etudiant_id'] ?? 0);
        $modele = new Etudiant();
        $etudiant = $modele->trouverPourSuperAdministrateur($etudiantId);

        if (!$etudiant) {
            $_SESSION['etudiant_message_erreur'] = 'Etudiant introuvable.';
            rediriger('/super-administrateur/etudiants');
        }

        $donnees = $this->donneesEtudiantDepuisPost();
        $erreurs = $this->validerDonneesEtudiant($donnees, $etudiant);

        if (!empty($erreurs)) {
            $_SESSION['etudiant_modification_erreurs'] = $erreurs;
            $_SESSION['etudiant_modification_anciennes_donnees'] = $donnees;
            rediriger('/super-administrateur/etudiants/modifier?id=' . $etudiantId);
        }

        try {
            $modele->modifierParSuperAdministrateur($etudiantId, $donnees, (int) $utilisateur['id']);
            $_SESSION['etudiant_message_succes'] = 'Etudiant modifie avec succes.';
        } catch (\Throwable $exception) {
            $_SESSION['etudiant_modification_erreurs'] = ['general' => $exception->getMessage()];
            $_SESSION['etudiant_modification_anciennes_donnees'] = $donnees;
            rediriger('/super-administrateur/etudiants/modifier?id=' . $etudiantId);
        }

        rediriger('/super-administrateur/etudiants?q=' . urlencode($donnees['matricule']));
    }

    public function superAdminElections(): void
    {
        $utilisateur = ServiceAuthentification::exigerRole(ServiceReglesMetier::ROLE_SUPER_ADMINISTRATEUR);
        $modeleElection = new Election();
        $modeleCandidat = new Candidat();

        $this->vue('super_administrateur/elections', [
            'titre' => 'Gestion des elections',
            'utilisateur' => $utilisateur,
            'catalogue_academique' => $modeleCandidat->catalogueAcademique(),
            'departements' => $modeleCandidat->listerDepartements(),
            'statistiques_elections' => $modeleElection->statistiques(),
            'elections' => $modeleElection->listerRecents(50),
            'erreurs' => $_SESSION['election_erreurs'] ?? [],
            'anciennes_donnees' => $_SESSION['election_anciennes_donnees'] ?? [],
            'message_succes' => $_SESSION['election_message_succes'] ?? null,
            'message_erreur' => $_SESSION['election_message_erreur'] ?? null,
        ], 'application');

        unset(
            $_SESSION['election_erreurs'],
            $_SESSION['election_anciennes_donnees'],
            $_SESSION['election_message_succes'],
            $_SESSION['election_message_erreur']
        );
    }

    public function enregistrerElection(): void
    {
        $utilisateur = ServiceAuthentification::exigerRole(ServiceReglesMetier::ROLE_SUPER_ADMINISTRATEUR);
        $donnees = $this->donneesElectionDepuisPost();
        $erreurs = $this->validerDonneesElection($donnees);

        if (!empty($erreurs)) {
            $_SESSION['election_erreurs'] = $erreurs;
            $_SESSION['election_anciennes_donnees'] = $donnees;
            rediriger('/super-administrateur/elections');
        }

        try {
            $modele = new Election();
            $modele->creer($donnees, (int) $utilisateur['id']);
            $_SESSION['election_message_succes'] = "Election creee en preparation. Affiliez les candidats, puis demandez le lancement au president electoral.";
        } catch (\Throwable $exception) {
            $_SESSION['election_message_erreur'] = 'Creation impossible : ' . $exception->getMessage();
            $_SESSION['election_anciennes_donnees'] = $donnees;
        }

        rediriger('/super-administrateur/elections');
    }

    public function demanderLancementElection(): void
    {
        $utilisateur = ServiceAuthentification::exigerRole(ServiceReglesMetier::ROLE_SUPER_ADMINISTRATEUR);
        $electionId = (int) ($_POST['election_id'] ?? 0);

        try {
            (new Election())->demanderValidationLancement($electionId, (int) $utilisateur['id']);
            $_SESSION['election_message_succes'] = "Demande de lancement envoyee au president electoral.";
        } catch (\Throwable $exception) {
            $_SESSION['election_message_erreur'] = $exception->getMessage();
        }

        rediriger('/super-administrateur/elections');
    }

    public function superAdminCodeQrElection(): void
    {
        $utilisateur = ServiceAuthentification::exigerRole(ServiceReglesMetier::ROLE_SUPER_ADMINISTRATEUR);
        $electionId = (int) ($_GET['id'] ?? 0);
        $modele = new Election();
        $election = $modele->trouver($electionId);

        if (!$election) {
            $_SESSION['election_message_erreur'] = 'Election introuvable pour le code QR.';
            rediriger('/super-administrateur/elections');
        }

        $baseUrl = rtrim((string) env_valeur('APP_URL', env_valeur('RENDER_EXTERNAL_URL', 'http://vote-upc-online.cd')), '/');
        $urlAcces = $baseUrl . '/election/acces?id=' . $electionId;

        $this->vue('super_administrateur/code_qr_election', [
            'titre' => 'Code QR election',
            'utilisateur' => $utilisateur,
            'election' => $election,
            'url_acces' => $urlAcces,
            'url_qr' => 'https://api.qrserver.com/v1/create-qr-code/?size=420x420&margin=18&data=' . rawurlencode($urlAcces),
            'portee_libelle' => $this->libellePorteeElection($election),
        ], 'application');
    }

    public function superAdminStatistiques(): void
    {
        $utilisateur = ServiceAuthentification::exigerRole(ServiceReglesMetier::ROLE_SUPER_ADMINISTRATEUR);
        $modele = new Etudiant();

        $this->vue('super_administrateur/statistiques', [
            'titre' => 'Statistiques globales',
            'utilisateur' => $utilisateur,
            'statistiques' => $modele->statistiquesGlobales(),
            'repartition_academique' => $modele->statistiquesAcademiquesHierarchiques(),
        ], 'application');
    }

    public function superAdminJournalActivations(): void
    {
        $this->afficherSuperAdmin([
            'surtitre' => 'Audit',
            'titre' => 'Journal des activations',
            'description' => 'Voir toutes les activations et reactivations realisees par les appariteurs.',
            'badge' => 'Tracabilite',
            'actions' => [
                ['libelle' => 'Appariteurs', 'lien' => '/super-administrateur/appariteurs'],
                ['libelle' => 'Etudiants', 'lien' => '/super-administrateur/etudiants'],
            ],
            'statistiques' => [
                ['valeur' => '0', 'libelle' => 'Activations'],
                ['valeur' => '0', 'libelle' => 'Reactivations'],
                ['valeur' => '0', 'libelle' => 'Emails envoyes'],
                ['valeur' => '5', 'libelle' => 'Facultes suivies'],
            ],
            'regles' => [
                'Chaque activation indique l appariteur responsable.',
                'Un appariteur ne peut activer que dans sa faculte.',
                'Le mot de passe temporaire est envoye par email.',
                'Les comptes bloques sont reactives apres verification.',
            ],
            'colonnes' => ['Evenement', 'Donnees visibles', 'Controle'],
            'lignes' => [
                ['Activation', 'Appariteur, etudiant, faculte, date', 'Trace visible par le super administrateur'],
                ['Reactivation', 'Compte bloque, nouvel email, nouveau mot de passe', 'Controle apres verification'],
                ['Envoi email', 'Destinataire, statut d envoi, date', 'Suivi des mots de passe temporaires'],
            ],
        ]);
    }

    public function superAdminActivites(): void
    {
        $utilisateur = ServiceAuthentification::exigerRole(ServiceReglesMetier::ROLE_SUPER_ADMINISTRATEUR);
        $role = (string) ($_GET['role'] ?? '');
        $action = (string) ($_GET['action'] ?? '');
        $date = (string) ($_GET['date'] ?? '');
        $journal = new JournalActivite();

        $this->vue('super_administrateur/activites', [
            'titre' => 'Activite du personnel',
            'utilisateur' => $utilisateur,
            'role_selectionne' => $role,
            'action_selectionnee' => $action,
            'date_selectionnee' => $date,
            'statistiques' => $journal->statistiques($date),
            'actions_disponibles' => $journal->actionsDisponibles(),
            'activites' => $journal->listerPourSuperAdministrateur($role, $action, $date),
        ], 'application');
    }

    public function presidentTableauDeBord(): void
    {
        $utilisateur = ServiceAuthentification::exigerRole(ServiceReglesMetier::ROLE_PRESIDENT_ELECTORAL);
        $modeleEtudiant = new Etudiant();
        $modeleCandidat = new Candidat();
        $modeleElection = new Election();

        $this->vue('president_electoral/tableau_de_bord', [
            'titre' => 'Tableau de bord president electoral',
            'utilisateur' => $utilisateur,
            'statistiques_etudiants' => $modeleEtudiant->statistiquesGlobales(),
            'repartition_academique' => $modeleEtudiant->statistiquesAcademiquesHierarchiques(),
            'statistiques_candidats' => $modeleCandidat->statistiques(),
            'statistiques_elections' => $modeleElection->statistiques(),
            'candidats_recents' => $modeleCandidat->lister(6),
            'elections_recentes' => $modeleElection->listerRecents(6),
        ], 'application');
    }

    public function presidentValidations(): void
    {
        $utilisateur = ServiceAuthentification::exigerRole(ServiceReglesMetier::ROLE_PRESIDENT_ELECTORAL);
        $modele = new Election();

        $this->vue('president_electoral/validations', [
            'titre' => 'Validation du lancement',
            'utilisateur' => $utilisateur,
            'elections' => $modele->listerAValider(),
            'message_succes' => $_SESSION['president_validation_succes'] ?? null,
            'message_erreur' => $_SESSION['president_validation_erreur'] ?? null,
        ], 'application');

        unset($_SESSION['president_validation_succes'], $_SESSION['president_validation_erreur']);
    }

    public function traiterValidationPresident(): void
    {
        $utilisateur = ServiceAuthentification::exigerRole(ServiceReglesMetier::ROLE_PRESIDENT_ELECTORAL);
        $electionId = (int) ($_POST['election_id'] ?? 0);
        $decision = (string) ($_POST['decision'] ?? '');
        $commentaire = trim((string) ($_POST['commentaire'] ?? ''));

        if (!in_array($decision, ['valide', 'refuse'], true)) {
            $_SESSION['president_validation_erreur'] = 'Decision invalide.';
            rediriger('/president-electoral/elections/validations');
        }

        try {
            (new Election())->validerLancement($electionId, (int) $utilisateur['id'], $decision, $commentaire);
            (new JournalActivite())->enregistrerPourRole($utilisateur, $decision === 'valide' ? 'validation_lancement_validee' : 'validation_lancement_refusee', 'elections', $electionId, [
                'decision' => $decision,
                'commentaire' => $commentaire,
            ]);
            $_SESSION['president_validation_succes'] = $decision === 'valide'
                ? 'Election validee. Les etudiants pourront voter pendant la periode definie.'
                : 'Election refusee et renvoyee en preparation.';
        } catch (\Throwable $exception) {
            $_SESSION['president_validation_erreur'] = $exception->getMessage();
        }

        rediriger('/president-electoral/elections/validations');
    }

    public function presidentStatistiques(): void
    {
        $utilisateur = ServiceAuthentification::exigerRole(ServiceReglesMetier::ROLE_PRESIDENT_ELECTORAL);
        $modele = new Etudiant();
        $modeleElection = new Election();

        $this->vue('president_electoral/statistiques', [
            'titre' => 'Statistiques presidentielles',
            'utilisateur' => $utilisateur,
            'statistiques' => $modele->statistiquesGlobales(),
            'repartition_academique' => $modele->statistiquesAcademiquesHierarchiques(),
            'elections_rapport' => $modeleElection->listerPourRapportsParticipation(),
        ], 'application');
    }

    public function presidentRapportParticipation(): void
    {
        $utilisateur = ServiceAuthentification::exigerRole(ServiceReglesMetier::ROLE_PRESIDENT_ELECTORAL);
        $modele = new Election();
        $elections = $modele->listerPourRapportsParticipation();
        $electionId = (int) ($_GET['election_id'] ?? 0);

        if ($electionId <= 0 && !empty($elections)) {
            $electionId = (int) $elections[0]['id'];
        }

        $rapport = null;
        $messageErreur = null;

        if ($electionId > 0) {
            try {
                $rapport = $modele->rapportParticipation($electionId);
            } catch (\Throwable $exception) {
                $messageErreur = $exception->getMessage();
            }
        }

        $this->vue('president_electoral/rapport_participation', [
            'titre' => 'Rapport de participation',
            'utilisateur' => $utilisateur,
            'elections' => $elections,
            'election_id' => $electionId,
            'rapport' => $rapport,
            'message_erreur' => $messageErreur,
        ], 'application');
    }

    public function presidentPublication(): void
    {
        $utilisateur = ServiceAuthentification::exigerRole(ServiceReglesMetier::ROLE_PRESIDENT_ELECTORAL);
        $modele = new Election();

        $this->vue('president_electoral/publication', [
            'titre' => 'Publication des resultats',
            'utilisateur' => $utilisateur,
            'elections' => $modele->listerAPublier(),
            'message_succes' => $_SESSION['president_publication_succes'] ?? null,
            'message_erreur' => $_SESSION['president_publication_erreur'] ?? null,
        ], 'application');

        unset($_SESSION['president_publication_succes'], $_SESSION['president_publication_erreur']);
    }

    public function traiterPublicationPresident(): void
    {
        $utilisateur = ServiceAuthentification::exigerRole(ServiceReglesMetier::ROLE_PRESIDENT_ELECTORAL);
        $electionId = (int) ($_POST['election_id'] ?? 0);
        $commentaire = trim((string) ($_POST['commentaire'] ?? ''));

        try {
            (new Election())->publierResultats($electionId, (int) $utilisateur['id'], $commentaire);
            (new JournalActivite())->enregistrerPourRole($utilisateur, 'publication_resultats', 'elections', $electionId, [
                'commentaire' => $commentaire,
            ]);
            $_SESSION['president_publication_succes'] = 'Resultats publies. Les etudiants peuvent maintenant les consulter.';
        } catch (\Throwable $exception) {
            $_SESSION['president_publication_erreur'] = $exception->getMessage();
        }

        rediriger('/president-electoral/resultats/publication');
    }

    public function presidentAnnulations(): void
    {
        $utilisateur = ServiceAuthentification::exigerRole(ServiceReglesMetier::ROLE_PRESIDENT_ELECTORAL);

        $this->vue('president_electoral/annulations', [
            'titre' => 'Annulation par faculte',
            'utilisateur' => $utilisateur,
            'elections' => (new Election())->listerPourAnnulation(),
            'facultes' => (new PersonnelAdministratif())->listerFacultes(),
            'message_succes' => $_SESSION['president_annulation_succes'] ?? null,
            'message_erreur' => $_SESSION['president_annulation_erreur'] ?? null,
        ], 'application');

        unset($_SESSION['president_annulation_succes'], $_SESSION['president_annulation_erreur']);
    }

    public function traiterAnnulationPresident(): void
    {
        $utilisateur = ServiceAuthentification::exigerRole(ServiceReglesMetier::ROLE_PRESIDENT_ELECTORAL);
        $electionId = (int) ($_POST['election_id'] ?? 0);
        $faculteId = (int) ($_POST['faculte_id'] ?? 0);
        $raison = trim((string) ($_POST['raison'] ?? ''));

        try {
            (new Election())->annulerFaculte($electionId, $faculteId, (int) $utilisateur['id'], $raison);
            (new JournalActivite())->enregistrerPourRole($utilisateur, 'annulation_faculte', 'elections', $electionId, [
                'faculte_id' => $faculteId,
                'raison' => $raison,
            ]);
            $_SESSION['president_annulation_succes'] = 'Annulation enregistree pour la faculte choisie.';
        } catch (\Throwable $exception) {
            $_SESSION['president_annulation_erreur'] = $exception->getMessage();
        }

        rediriger('/president-electoral/elections/annulations');
    }

    public function presidentCandidats(): void
    {
        $utilisateur = ServiceAuthentification::exigerRole(ServiceReglesMetier::ROLE_PRESIDENT_ELECTORAL);
        $modele = new Candidat();

        $this->vue('president_electoral/candidats', [
            'titre' => 'Candidats',
            'utilisateur' => $utilisateur,
            'statistiques_candidats' => $modele->statistiques(),
            'candidats' => $modele->lister(),
        ], 'application');
    }

    public function appariteurTableauDeBord(): void
    {
        $utilisateur = ServiceAuthentification::exigerRole(ServiceReglesMetier::ROLE_APPARITEUR);
        $modele = new Etudiant();

        $this->vue('appariteur/tableau_de_bord', [
            'titre' => 'Tableau de bord appariteur',
            'utilisateur' => $utilisateur,
            'affectation' => $modele->affectationAppariteur((int) $utilisateur['id']),
            'statistiques' => $modele->statistiquesPourAppariteur((int) $utilisateur['id']),
            'repartition_promotions' => $modele->repartitionPromotionsPourAppariteur((int) $utilisateur['id']),
            'etudiants' => $modele->listerPourAppariteur((int) $utilisateur['id'], '', 12),
        ], 'application');
    }

    public function appariteurRecherche(): void
    {
        $this->appariteurEtudiants();
    }

    public function appariteurEtudiants(): void
    {
        $utilisateur = ServiceAuthentification::exigerRole(ServiceReglesMetier::ROLE_APPARITEUR);
        $recherche = trim((string) ($_GET['q'] ?? ''));
        $modele = new Etudiant();

        $this->vue('appariteur/etudiants', [
            'titre' => 'Etudiants de ma faculte',
            'utilisateur' => $utilisateur,
            'affectation' => $modele->affectationAppariteur((int) $utilisateur['id']),
            'recherche' => $recherche,
            'etudiants' => $modele->listerPourAppariteur((int) $utilisateur['id'], $recherche),
            'statistiques' => $modele->statistiquesPourAppariteur((int) $utilisateur['id']),
            'message_succes' => $_SESSION['activation_message_succes'] ?? null,
            'message_erreur' => $_SESSION['activation_message_erreur'] ?? null,
        ], 'application');

        unset($_SESSION['activation_message_succes'], $_SESSION['activation_message_erreur']);
    }

    public function activerEtudiantParAppariteur(): void
    {
        $utilisateur = ServiceAuthentification::exigerRole(ServiceReglesMetier::ROLE_APPARITEUR);
        $etudiantId = (int) ($_POST['etudiant_id'] ?? 0);

        if ($etudiantId <= 0) {
            $_SESSION['activation_message_erreur'] = 'Etudiant invalide.';
            rediriger('/appariteur/etudiants');
        }

        $motDePasseTemporaire = ServiceSecurite::genererMotDePasseTemporaire(14);
        $modele = new Etudiant();

        try {
            $etudiant = $modele->activerParAppariteur((int) $utilisateur['id'], $etudiantId, $motDePasseTemporaire);
            $emailEnvoye = (new ServiceEmail())->envoyerMotDePasseTemporaireEtudiant($etudiant, $motDePasseTemporaire);
            $modele->marquerMotDePasseTemporaireEnvoye((int) $etudiant['activation_id'], $emailEnvoye);
            (new JournalActivite())->enregistrerPourRole($utilisateur, (($etudiant['action_activation'] ?? '') === 'reactivation') ? 'reactivation_etudiant' : 'activation_etudiant', 'etudiants', $etudiantId, [
                'matricule' => $etudiant['matricule'] ?? '',
                'faculte' => $etudiant['faculte_code'] ?? '',
                'promotion' => $etudiant['promotion_code'] ?? '',
                'email_envoye' => $emailEnvoye,
            ]);

            $_SESSION['activation_message_succes'] = $emailEnvoye
                ? 'Etudiant active avec succes. Le mot de passe temporaire a ete envoye par email.'
                : 'Etudiant active, mais email non envoye. Mot de passe temporaire : ' . $motDePasseTemporaire;
        } catch (\Throwable $exception) {
            $_SESSION['activation_message_erreur'] = 'Activation impossible : ' . $exception->getMessage();
        }

        rediriger('/appariteur/etudiants');
    }

    public function appariteurActivations(): void
    {
        $this->afficherRole(ServiceReglesMetier::ROLE_APPARITEUR, $this->pageAppariteur('Activations', 'Envoyer un mot de passe temporaire apres verification des preuves.'));
    }

    public function appariteurComptesBloques(): void
    {
        $this->afficherRole(ServiceReglesMetier::ROLE_APPARITEUR, $this->pageAppariteur('Comptes bloques', 'Reactiver un compte bloque apres 4 mauvaises tentatives.'));
    }

    public function etudiantTableauDeBord(): void
    {
        $donnees = $this->donneesEspaceEtudiant();

        $this->vue('etudiant/tableau_de_bord', array_merge($donnees, [
            'titre' => 'Tableau de bord etudiant',
        ]), 'application');
    }

    public function etudiantCandidats(): void
    {
        $donnees = $this->donneesEspaceEtudiant();

        $this->vue('etudiant/candidats', array_merge($donnees, [
            'titre' => 'Candidats',
        ]), 'application');
    }

    public function etudiantVote(): void
    {
        $donnees = $this->donneesEspaceEtudiant();

        $this->vue('etudiant/vote', array_merge($donnees, [
            'titre' => 'Vote',
            'message_succes' => $_SESSION['vote_message_succes'] ?? null,
            'message_erreur' => $_SESSION['vote_message_erreur'] ?? null,
        ]), 'application');

        unset($_SESSION['vote_message_succes'], $_SESSION['vote_message_erreur']);
    }

    public function enregistrerVoteEtudiant(): void
    {
        $utilisateur = ServiceAuthentification::exigerRole(ServiceReglesMetier::ROLE_ETUDIANT);
        $etudiant = (new Etudiant())->trouverParUtilisateurId((int) $utilisateur['id']);

        if (!$etudiant) {
            $_SESSION['vote_message_erreur'] = 'Profil etudiant introuvable.';
            rediriger('/etudiant/vote');
        }

        try {
            (new Election())->voter((int) ($_POST['election_id'] ?? 0), (int) ($_POST['candidat_id'] ?? 0), $etudiant);
            $_SESSION['vote_message_succes'] = 'Votre vote a ete enregistre avec succes.';
        } catch (\Throwable $exception) {
            $_SESSION['vote_message_erreur'] = $exception->getMessage();
        }

        rediriger('/etudiant/vote');
    }

    public function etudiantResultats(): void
    {
        $donnees = $this->donneesEspaceEtudiant();
        $sessionsResultats = [];
        $modele = new Election();

        foreach (($donnees['sessions_vote'] ?? []) as $session) {
            $election = $session['election'] ?? [];
            if (($election['statut'] ?? '') !== ServiceReglesMetier::STATUT_ELECTION_PUBLIEE) {
                continue;
            }

            $electionId = (int) $election['id'];
            $session['resultats'] = $modele->resultatsPourElection($electionId);
            $session['participation'] = $modele->statistiquesParticipation($electionId);
            $sessionsResultats[] = $session;
        }

        $this->vue('etudiant/resultats', array_merge($donnees, [
            'titre' => 'Resultats',
            'sessions_resultats' => $sessionsResultats,
        ]), 'application');
    }

    public function etudiantMotDePasse(): void
    {
        $this->afficherRole(ServiceReglesMetier::ROLE_ETUDIANT, $this->pageEtudiant('Profil et mot de passe', 'Changer son mot de passe personnel de facon securisee.'));
    }

    public function motDePasseTemporaire(): void
    {
        $utilisateur = ServiceAuthentification::exigerConnexion();

        if (empty($utilisateur['mot_de_passe_temporaire'])) {
            rediriger($this->destinationApresChangementMotDePasse((string) $utilisateur['role_code']));
        }

        $this->vue('authentification/changer_mot_de_passe_temporaire', [
            'utilisateur' => $utilisateur,
            'titre' => 'Changer le mot de passe',
            'erreurs' => [],
        ], 'application');
    }

    public function changerMotDePasseTemporaire(): void
    {
        $utilisateur = ServiceAuthentification::exigerConnexion();
        $donnees = [
            'mot_de_passe' => (string) ($_POST['mot_de_passe'] ?? ''),
            'confirmation_mot_de_passe' => (string) ($_POST['confirmation_mot_de_passe'] ?? ''),
        ];
        $erreurs = $this->validerNouveauMotDePasse($donnees);

        if (!empty($erreurs)) {
            $this->vue('authentification/changer_mot_de_passe_temporaire', [
                'utilisateur' => $utilisateur,
                'titre' => 'Changer le mot de passe',
                'erreurs' => $erreurs,
            ], 'application');
            return;
        }

        try {
            $modeleUtilisateur = new Utilisateur();
            $modeleUtilisateur->changerMotDePasse((int) $utilisateur['id'], $donnees['mot_de_passe'], false);
            $utilisateurActualise = $modeleUtilisateur->trouverParId((int) $utilisateur['id']);

            if ($utilisateurActualise) {
                ServiceAuthentification::connecter($utilisateurActualise);
            }

            rediriger($this->destinationApresChangementMotDePasse((string) $utilisateur['role_code']));
        } catch (\Throwable) {
            $this->vue('authentification/changer_mot_de_passe_temporaire', [
                'utilisateur' => $utilisateur,
                'titre' => 'Changer le mot de passe',
                'erreurs' => ['general' => 'Changement impossible pour le moment.'],
            ], 'application');
        }
    }

    private function afficherSuperAdmin(array $page): void
    {
        $this->afficherRole(ServiceReglesMetier::ROLE_SUPER_ADMINISTRATEUR, $page);
    }

    private function validerNouveauMotDePasse(array $donnees): array
    {
        $erreurs = [];
        $motDePasse = (string) ($donnees['mot_de_passe'] ?? '');

        if ($motDePasse === '') {
            $erreurs['mot_de_passe'] = 'Le nouveau mot de passe est obligatoire.';
        } elseif (strlen($motDePasse) < 8) {
            $erreurs['mot_de_passe'] = 'Le mot de passe doit contenir au moins 8 caracteres.';
        } elseif (
            !preg_match('/[A-Z]/', $motDePasse)
            || !preg_match('/[a-z]/', $motDePasse)
            || !preg_match('/[0-9]/', $motDePasse)
            || !preg_match('/[^A-Za-z0-9]/', $motDePasse)
        ) {
            $erreurs['mot_de_passe'] = 'Le mot de passe doit contenir une majuscule, une minuscule, un chiffre et un symbole.';
        }

        if ((string) ($donnees['confirmation_mot_de_passe'] ?? '') !== $motDePasse) {
            $erreurs['confirmation_mot_de_passe'] = 'La confirmation doit etre identique.';
        }

        return $erreurs;
    }

    private function destinationApresChangementMotDePasse(string $role): string
    {
        if ($role === ServiceReglesMetier::ROLE_ETUDIANT) {
            $electionId = (int) ($_SESSION['election_cible_id'] ?? 0);

            if ($electionId > 0) {
                unset($_SESSION['election_cible_id']);

                return '/etudiant/vote#election-' . $electionId;
            }
        }

        return ServiceAuthentification::cheminApresConnexion($role);
    }

    private function afficherRole(string $role, array $page): void
    {
        $utilisateur = ServiceAuthentification::exigerRole($role);

        $this->vue('modules/page_module', array_merge([
            'utilisateur' => $utilisateur,
        ], $page), 'application');
    }

    private function pagePresident(string $titre, string $description): array
    {
        return [
            'surtitre' => 'President electoral',
            'titre' => $titre,
            'description' => $description,
            'badge' => 'Supervision',
            'compact' => true,
            'actions' => [
                ['libelle' => 'Validations', 'lien' => '/president-electoral/elections/validations'],
                ['libelle' => 'Publication', 'lien' => '/president-electoral/resultats/publication'],
                ['libelle' => 'Candidats', 'lien' => '/president-electoral/candidats'],
                ['libelle' => 'Statistiques', 'lien' => '/president-electoral/statistiques'],
                ['libelle' => 'Rapport votes', 'lien' => '/president-electoral/rapports/participation'],
            ],
            'statistiques' => [
                ['valeur' => '0', 'libelle' => 'Elections a valider'],
                ['valeur' => '0', 'libelle' => 'Publications'],
                ['valeur' => '0', 'libelle' => 'Annulations'],
                ['valeur' => 'Audit', 'libelle' => 'Decisions'],
            ],
            'regles' => [
                'Le president donne le dernier mot pour le lancement.',
                'Les resultats restent invisibles avant son aval.',
                'Les statistiques restent globales et anonymes.',
                'Une annulation par faculte invalide les votes de cette faculte.',
            ],
            'colonnes' => ['Element', 'Responsabilite', 'Statut'],
            'lignes' => [
                ['Lancement', 'Valider ou refuser', 'A traiter'],
                ['Publication', 'Accord final', 'A traiter'],
                ['Annulation', 'Decision motivee', 'A traiter'],
            ],
        ];
    }

    private function pageAppariteur(string $titre, string $description): array
    {
        return [
            'surtitre' => 'Appariteur',
            'titre' => $titre,
            'description' => $description,
            'badge' => 'Faculte unique',
            'compact' => true,
            'actions' => [
                ['libelle' => 'Recherche', 'lien' => '/appariteur/etudiants/rechercher'],
                ['libelle' => 'Activations', 'lien' => '/appariteur/activations'],
                ['libelle' => 'Comptes bloques', 'lien' => '/appariteur/comptes-bloques'],
            ],
            'statistiques' => [
                ['valeur' => '0', 'libelle' => 'A activer'],
                ['valeur' => '0', 'libelle' => 'Actives'],
                ['valeur' => '0', 'libelle' => 'Bloques'],
                ['valeur' => 'Email', 'libelle' => 'MDP temporaire'],
            ],
            'regles' => [
                'L appariteur ne voit que sa faculte.',
                'Il recherche par nom, promotion, matricule ou email.',
                'Il active apres verification des preuves.',
                'Il reactive apres blocage de 4 mauvaises tentatives.',
            ],
            'colonnes' => ['Recherche', 'Condition', 'Action'],
            'lignes' => [
                ['Matricule', 'Existe dans la base', 'Activer'],
                ['Email', 'Unique', 'Envoyer MDP'],
                ['Compte bloque', '4 echecs', 'Reactiver'],
            ],
        ];
    }

    private function pageEtudiant(string $titre, string $description): array
    {
        return [
            'surtitre' => 'Etudiant',
            'titre' => $titre,
            'description' => $description,
            'badge' => 'Portee academique',
            'actions' => [
                ['libelle' => 'Candidats', 'lien' => '/etudiant/candidats'],
                ['libelle' => 'Vote', 'lien' => '/etudiant/vote'],
                ['libelle' => 'Resultats', 'lien' => '/etudiant/resultats'],
            ],
            'statistiques' => [
                ['valeur' => '1', 'libelle' => 'Vote maximum'],
                ['valeur' => 'Chrono', 'libelle' => 'Session'],
                ['valeur' => 'Publication', 'libelle' => 'Resultats'],
                ['valeur' => 'Portee', 'libelle' => 'Faculte/promo'],
            ],
            'regles' => [
                'L etudiant voit seulement sa faculte, son departement et sa promotion.',
                'Il vote une seule fois.',
                'Il garde acces au site apres le vote.',
                'Les resultats apparaissent seulement apres publication officielle.',
            ],
            'colonnes' => ['Menu', 'Condition', 'Resultat'],
            'lignes' => [
                ['Candidats', 'Portee correspondante', 'Consultation'],
                ['Vote', 'Election ouverte', 'Bulletin unique'],
                ['Resultats', 'Publication validee', 'Animation vainqueur'],
            ],
        ];
    }

    private function donneesPersonnelDepuisPost(bool $avecFaculte): array
    {
        $donnees = [
            'nom' => $this->majuscule((string) ($_POST['nom'] ?? '')),
            'post_nom' => $this->majuscule((string) ($_POST['post_nom'] ?? '')),
            'prenom' => $this->majuscule((string) ($_POST['prenom'] ?? '')),
            'nom_utilisateur' => trim((string) ($_POST['nom_utilisateur'] ?? '')),
            'email' => strtolower(trim((string) ($_POST['email'] ?? ''))),
        ];

        if ($avecFaculte) {
            $donnees['faculte_id'] = (string) ($_POST['faculte_id'] ?? '');
        }

        return $donnees;
    }

    private function majuscule(string $valeur): string
    {
        $valeur = trim($valeur);

        return function_exists('mb_strtoupper') ? mb_strtoupper($valeur, 'UTF-8') : strtoupper($valeur);
    }

    private function donneesCandidatDepuisPost(): array
    {
        return [
            'nom' => $this->majuscule((string) ($_POST['nom'] ?? '')),
            'post_nom' => $this->majuscule((string) ($_POST['post_nom'] ?? '')),
            'prenom' => $this->majuscule((string) ($_POST['prenom'] ?? '')),
            'sexe' => strtoupper(trim((string) ($_POST['sexe'] ?? ''))),
            'faculte_id' => (string) ($_POST['faculte_id'] ?? ''),
            'promotion_id' => (string) ($_POST['promotion_id'] ?? ''),
            'departement_id' => trim((string) ($_POST['departement_id'] ?? '')),
            'election_id' => trim((string) ($_POST['election_id'] ?? '')),
            'biographie' => trim((string) ($_POST['biographie'] ?? '')),
            'slogan' => trim((string) ($_POST['slogan'] ?? '')),
            'vision' => trim((string) ($_POST['vision'] ?? '')),
        ];
    }

    private function validerDonneesCandidat(array $donnees): array
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

        if ((int) ($donnees['faculte_id'] ?? 0) <= 0) {
            $erreurs['faculte_id'] = 'La faculte est obligatoire.';
        }

        if (!in_array((string) ($donnees['sexe'] ?? ''), ['M', 'F'], true)) {
            $erreurs['sexe'] = 'Choisissez le sexe du candidat.';
        }

        if ((int) ($donnees['promotion_id'] ?? 0) <= 0) {
            $erreurs['promotion_id'] = 'La promotion est obligatoire.';
        }

        if ((int) ($donnees['election_id'] ?? 0) <= 0) {
            $erreurs['election_id'] = "Creez d'abord une election, puis choisissez-la ici.";
        }

        if (trim((string) ($donnees['slogan'] ?? '')) === '') {
            $erreurs['slogan'] = 'Le slogan est obligatoire.';
        } elseif (strlen((string) $donnees['slogan']) > 255) {
            $erreurs['slogan'] = 'Le slogan doit rester court.';
        }

        if (trim((string) ($donnees['biographie'] ?? '')) === '') {
            $erreurs['biographie'] = 'La biographie est obligatoire.';
        }

        if (trim((string) ($donnees['vision'] ?? '')) === '') {
            $erreurs['vision'] = 'La vision est obligatoire.';
        }

        return $erreurs;
    }

    private function afficherMediaCandidat(string $type): void
    {
        $candidatId = (int) ($_GET['id'] ?? 0);
        if ($candidatId <= 0) {
            http_response_code(404);
            return;
        }

        $media = (new Candidat())->trouverMedia($candidatId, $type);
        if (!$media) {
            http_response_code(404);
            return;
        }

        $contenu = base64_decode((string) $media['base64'], true);
        $typeMime = (string) ($media['type_mime'] ?? '');
        $typesAutorises = ['image/jpeg', 'image/png', 'image/webp'];

        if ($contenu === false || !in_array($typeMime, $typesAutorises, true)) {
            http_response_code(404);
            return;
        }

        header('Content-Type: ' . $typeMime);
        header('Content-Length: ' . strlen($contenu));
        header('Cache-Control: public, max-age=86400');
        header('X-Content-Type-Options: nosniff');
        echo $contenu;
        exit;
    }

    private function retirerMediasCandidatDesDonnees(array &$donnees): void
    {
        unset(
            $donnees['photo_base64'],
            $donnees['photo_type_mime'],
            $donnees['photo_nom'],
            $donnees['image_vision_base64'],
            $donnees['image_vision_type_mime'],
            $donnees['image_vision_nom']
        );
    }

    private function deposerImageCandidat(string $champ, string $_dossier, array &$erreurs): ?array
    {
        if (empty($_FILES[$champ]) || ($_FILES[$champ]['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            return null;
        }

        $fichier = $_FILES[$champ];

        if (($fichier['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
            $erreurs[$champ] = "L'image n'a pas pu etre deposee.";
            return null;
        }

        if ((int) ($fichier['size'] ?? 0) > 2 * 1024 * 1024) {
            $erreurs[$champ] = "L'image ne doit pas depasser 2 Mo.";
            return null;
        }

        $extension = strtolower(pathinfo((string) ($fichier['name'] ?? ''), PATHINFO_EXTENSION));
        if (!in_array($extension, ['jpg', 'jpeg', 'png', 'webp'], true)) {
            $erreurs[$champ] = 'Format autorise : JPG, PNG ou WEBP.';
            return null;
        }

        $typeMime = mime_content_type((string) $fichier['tmp_name']) ?: '';
        if (!in_array($typeMime, ['image/jpeg', 'image/png', 'image/webp'], true)) {
            $erreurs[$champ] = 'Le fichier choisi ne semble pas etre une image valide.';
            return null;
        }

        $contenu = file_get_contents((string) $fichier['tmp_name']);
        if ($contenu === false) {
            $erreurs[$champ] = "Impossible de lire l'image.";
            return null;
        }

        $nomOriginal = preg_replace('/[^A-Za-z0-9_.-]/', '_', (string) ($fichier['name'] ?? 'image.' . $extension));

        return [
            'base64' => base64_encode($contenu),
            'type_mime' => $typeMime,
            'nom' => $nomOriginal !== '' ? $nomOriginal : 'image.' . $extension,
        ];
    }

    private function donneesEtudiantDepuisPost(): array
    {
        return [
            'nom' => $this->majuscule((string) ($_POST['nom'] ?? '')),
            'post_nom' => $this->majuscule((string) ($_POST['post_nom'] ?? '')),
            'prenom' => $this->majuscule((string) ($_POST['prenom'] ?? '')),
            'matricule' => $this->majuscule((string) ($_POST['matricule'] ?? '')),
            'email' => strtolower(trim((string) ($_POST['email'] ?? ''))),
            'faculte_id' => (string) ($_POST['faculte_id'] ?? ''),
            'promotion_id' => (string) ($_POST['promotion_id'] ?? ''),
            'departement_id' => trim((string) ($_POST['departement_id'] ?? '')),
            'date_naissance' => trim((string) ($_POST['date_naissance'] ?? '')),
            'lieu_naissance' => $this->majuscule((string) ($_POST['lieu_naissance'] ?? '')),
            'sexe' => $this->majuscule((string) ($_POST['sexe'] ?? '')),
        ];
    }

    private function validerDonneesEtudiant(array $donnees, array $etudiant): array
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

        if (trim((string) ($donnees['matricule'] ?? '')) === '') {
            $erreurs['matricule'] = 'Le matricule est obligatoire.';
        }

        $email = strtolower(trim((string) ($donnees['email'] ?? '')));
        if ($email === '' && !empty($etudiant['utilisateur_id'])) {
            $erreurs['email'] = 'Un etudiant deja active doit garder une adresse email.';
        } elseif ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $erreurs['email'] = "L'adresse email est invalide.";
        }

        if ((int) ($donnees['faculte_id'] ?? 0) <= 0) {
            $erreurs['faculte_id'] = 'La faculte est obligatoire.';
        }

        if ((int) ($donnees['promotion_id'] ?? 0) <= 0) {
            $erreurs['promotion_id'] = 'La promotion est obligatoire.';
        }

        if (!in_array((string) ($donnees['sexe'] ?? ''), ['', 'M', 'F'], true)) {
            $erreurs['sexe'] = 'Le sexe doit etre M ou F.';
        }

        if ((string) ($donnees['date_naissance'] ?? '') !== '') {
            $date = (string) $donnees['date_naissance'];
            $parties = explode('-', $date);
            if (count($parties) !== 3 || !checkdate((int) $parties[1], (int) $parties[2], (int) $parties[0])) {
                $erreurs['date_naissance'] = 'La date de naissance est invalide.';
            }
        }

        return $erreurs;
    }

    private function donneesEspaceEtudiant(): array
    {
        $utilisateur = ServiceAuthentification::exigerRole(ServiceReglesMetier::ROLE_ETUDIANT);
        $modeleEtudiant = new Etudiant();
        $etudiant = $modeleEtudiant->trouverParUtilisateurId((int) $utilisateur['id']);

        if (!$etudiant) {
            ServiceAuthentification::deconnecter();
            rediriger('/etudiant/connexion');
        }

        $modeleElection = new Election();
        $modeleCandidat = new Candidat();
        $sessions = [];
        $tousLesCandidats = [];
        $resume = [
            'sessions' => 0,
            'ouvertes' => 0,
            'votes_faits' => 0,
            'votes_a_faire' => 0,
            'candidats' => 0,
            'publiees' => 0,
        ];

        foreach ($modeleElection->listerPourEtudiant($etudiant) as $election) {
            $candidats = $modeleCandidat->listerPourEtudiant($etudiant, $election);
            $aDejaVote = $modeleElection->etudiantADejaVote((int) $election['id'], (int) $etudiant['id']);
            $peutVoter = ServiceReglesMetier::etudiantPeutVoter($etudiant, $election, $aDejaVote);

            foreach ($candidats as $candidat) {
                $candidat['election_id'] = $election['id'];
                $candidat['election_nom'] = $election['nom'];
                $tousLesCandidats[] = $candidat;
            }

            $resume['sessions']++;
            $resume['candidats'] += count($candidats);
            $resume['ouvertes'] += $peutVoter ? 1 : 0;
            $resume['votes_faits'] += $aDejaVote ? 1 : 0;
            $resume['votes_a_faire'] += ($peutVoter && !$aDejaVote) ? 1 : 0;
            $resume['publiees'] += (($election['statut'] ?? '') === ServiceReglesMetier::STATUT_ELECTION_PUBLIEE) ? 1 : 0;

            $sessions[] = [
                'election' => $election,
                'portee_libelle' => $this->libellePorteeElection($election),
                'candidats' => $candidats,
                'a_deja_vote' => $aDejaVote,
                'peut_voter' => $peutVoter,
                'nombre_candidats' => count($candidats),
            ];
        }

        $premiereSession = $sessions[0] ?? null;

        return [
            'utilisateur' => $utilisateur,
            'etudiant' => $etudiant,
            'sessions_vote' => $sessions,
            'resume_sessions' => $resume,
            'election' => $premiereSession['election'] ?? null,
            'candidats' => $tousLesCandidats,
            'a_deja_vote' => $premiereSession['a_deja_vote'] ?? false,
            'peut_voter' => $premiereSession['peut_voter'] ?? false,
        ];
    }

    private function libellePorteeElection(array $election): string
    {
        return match ((string) ($election['portee_type'] ?? '')) {
            ServiceReglesMetier::PORTEE_UNIVERSITE => 'Toute l universite',
            ServiceReglesMetier::PORTEE_FACULTE => 'Faculte ' . (string) ($election['faculte_code'] ?? ''),
            ServiceReglesMetier::PORTEE_PROMOTION => trim('Promotion ' . (string) ($election['faculte_code'] ?? '') . ' ' . (string) ($election['promotion_code'] ?? '')),
            ServiceReglesMetier::PORTEE_DEPARTEMENT => trim('Departement ' . (string) ($election['faculte_code'] ?? '') . ' ' . (string) ($election['departement_nom'] ?? '')),
            default => 'Portee academique',
        };
    }

    private function donneesElectionDepuisPost(): array
    {
        $portee = (string) ($_POST['portee_type'] ?? ServiceReglesMetier::PORTEE_UNIVERSITE);
        $faculteId = trim((string) ($_POST['faculte_id'] ?? ''));
        $departementId = trim((string) ($_POST['departement_id'] ?? ''));
        $promotionId = trim((string) ($_POST['promotion_id'] ?? ''));

        if ($portee === ServiceReglesMetier::PORTEE_UNIVERSITE) {
            $faculteId = '';
            $departementId = '';
            $promotionId = '';
        } elseif ($portee === ServiceReglesMetier::PORTEE_FACULTE) {
            $departementId = '';
            $promotionId = '';
        } elseif ($portee === ServiceReglesMetier::PORTEE_PROMOTION) {
            $departementId = '';
        }

        return [
            'nom' => trim((string) ($_POST['nom'] ?? '')),
            'description' => trim((string) ($_POST['description'] ?? '')),
            'portee_type' => $portee,
            'faculte_id' => $faculteId,
            'departement_id' => $departementId,
            'promotion_id' => $promotionId,
            'date_debut' => $this->normaliserDateHeure((string) ($_POST['date_debut'] ?? '')),
            'date_fin' => $this->normaliserDateHeure((string) ($_POST['date_fin'] ?? '')),
        ];
    }

    private function validerDonneesElection(array $donnees): array
    {
        $erreurs = [];

        if ($donnees['nom'] === '') {
            $erreurs['nom'] = 'Le nom de l election est obligatoire.';
        }

        if (!in_array($donnees['portee_type'], [
            ServiceReglesMetier::PORTEE_UNIVERSITE,
            ServiceReglesMetier::PORTEE_FACULTE,
            ServiceReglesMetier::PORTEE_DEPARTEMENT,
            ServiceReglesMetier::PORTEE_PROMOTION,
        ], true)) {
            $erreurs['portee_type'] = 'La portee est invalide.';
        }

        if (in_array($donnees['portee_type'], [ServiceReglesMetier::PORTEE_FACULTE, ServiceReglesMetier::PORTEE_DEPARTEMENT, ServiceReglesMetier::PORTEE_PROMOTION], true)
            && (int) $donnees['faculte_id'] <= 0
        ) {
            $erreurs['faculte_id'] = 'La faculte est obligatoire pour cette portee.';
        }

        if ($donnees['portee_type'] === ServiceReglesMetier::PORTEE_PROMOTION && (int) $donnees['promotion_id'] <= 0) {
            $erreurs['promotion_id'] = 'La promotion est obligatoire.';
        }

        if ($donnees['portee_type'] === ServiceReglesMetier::PORTEE_DEPARTEMENT && (int) $donnees['departement_id'] <= 0) {
            $erreurs['departement_id'] = 'Le departement est obligatoire.';
        }

        $debut = $this->dateDepuisTexte($donnees['date_debut']);
        $fin = $this->dateDepuisTexte($donnees['date_fin']);

        if (!$debut) {
            $erreurs['date_debut'] = 'La date de debut est obligatoire.';
        }

        if (!$fin) {
            $erreurs['date_fin'] = 'La date de fin est obligatoire.';
        }

        if ($debut && $fin && $fin <= $debut) {
            $erreurs['date_fin'] = 'La date de fin doit etre apres la date de debut.';
        }

        if ($fin && $fin <= new DateTimeImmutable()) {
            $erreurs['date_fin'] = "La date de fin doit etre dans le futur pour permettre l'ajout des candidats et la validation du president.";
        }

        return $erreurs;
    }

    private function normaliserDateHeure(string $valeur): string
    {
        $valeur = trim($valeur);

        if ($valeur === '') {
            return '';
        }

        return str_replace('T', ' ', $valeur);
    }

    private function dateDepuisTexte(string $valeur): ?DateTimeImmutable
    {
        if (trim($valeur) === '') {
            return null;
        }

        try {
            return new DateTimeImmutable($valeur);
        } catch (\Throwable) {
            return null;
        }
    }
}

