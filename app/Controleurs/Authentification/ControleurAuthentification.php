<?php

declare(strict_types=1);

namespace Application\Controleurs\Authentification;

use Application\Modeles\CodeSecuriteEmail;
use Application\Modeles\JournalActivite;
use Application\Modeles\Utilisateur;
use Application\Noyau\Controleur;
use Application\Services\ServiceAuthentification;
use Application\Services\ServiceEmail;
use Application\Services\ServiceReglesMetier;
use Application\Services\ServiceSecurite;

class ControleurAuthentification extends Controleur
{
    private const TYPE_ADMINISTRATION = 'administration';
    private const TYPE_ETUDIANT = 'etudiant';

    public function afficherConnexion(): void
    {
        $this->afficherConnexionAdministration();
    }

    public function connecter(): void
    {
        $this->connecterAdministration();
    }

    public function afficherConnexionAdministration(): void
    {
        $this->afficherPageConnexion(self::TYPE_ADMINISTRATION);
    }

    public function connecterAdministration(): void
    {
        $this->connecterParType(self::TYPE_ADMINISTRATION);
    }

    public function afficherConnexionEtudiant(): void
    {
        $this->afficherPageConnexion(self::TYPE_ETUDIANT);
    }

    public function connecterEtudiant(): void
    {
        $this->connecterParType(self::TYPE_ETUDIANT);
    }

    public function accesElectionEtudiant(): void
    {
        $electionId = (int) ($_GET['id'] ?? 0);

        if ($electionId > 0) {
            $_SESSION['election_cible_id'] = $electionId;
        }

        rediriger('/etudiant/connexion' . ($electionId > 0 ? '?election_id=' . $electionId : ''));
    }

    public function afficherVerificationOtp(array $erreurs = [], string $message = ''): void
    {
        $verification = $_SESSION['otp_connexion'] ?? null;

        if (!$verification) {
            rediriger('/administration/connexion');
        }

        if ($message === '' && ($_GET['renvoye'] ?? '') === '1') {
            $message = 'Un nouveau code OTP a ete envoye.';
        }

        $this->vue('authentification/verification_otp', [
            'titre' => 'Verification OTP',
            'erreurs' => $erreurs,
            'message' => $message,
            'type_connexion' => (string) $verification['type'],
            'email_masque' => $this->masquerEmail((string) $verification['email']),
            'description_otp' => $this->descriptionOtp((string) $verification['type']),
            'expiration_otp_minutes' => ServiceAuthentification::dureeExpirationOtpMinutes(),
        ]);
    }

    public function verifierOtp(): void
    {
        $verification = $_SESSION['otp_connexion'] ?? null;

        if (!$verification) {
            rediriger('/administration/connexion');
        }

        $code = preg_replace('/\s+/', '', (string) ($_POST['code_otp'] ?? ''));

        if (!preg_match('/^\d{6}$/', $code)) {
            $this->afficherVerificationOtp([
                'code_otp' => 'Le code OTP doit contenir exactement 6 chiffres.',
            ]);
            return;
        }

        try {
            $modeleCode = new CodeSecuriteEmail();
            $codeValide = $modeleCode->verifier(
                (int) $verification['utilisateur_id'],
                CodeSecuriteEmail::CONTEXTE_CONNEXION_OTP,
                $code
            );

            if (!$codeValide) {
                $this->afficherVerificationOtp([
                    'general' => 'Code OTP incorrect, expiré ou déjà utilisé.',
                ]);
                return;
            }

            $modeleUtilisateur = new Utilisateur();
            $utilisateur = $modeleUtilisateur->trouverParId((int) $verification['utilisateur_id']);

            if (!$utilisateur || (string) $utilisateur['role_code'] !== (string) $verification['role_code']) {
                ServiceAuthentification::deconnecter();
                rediriger('/administration/connexion');
            }

            $modeleUtilisateur->enregistrerConnexionReussie((int) $utilisateur['id']);
            $utilisateur['tentatives_connexion'] = 0;
            ServiceAuthentification::connecter($utilisateur);
            ServiceAuthentification::marquerOtpValide($utilisateur, (string) $verification['type']);
            (new JournalActivite())->enregistrerPourRole($utilisateur, 'connexion_reussie', 'utilisateurs', (int) $utilisateur['id'], [
                'type_connexion' => (string) $verification['type'],
                'methode' => 'otp',
            ]);

            if ((bool) $utilisateur['mot_de_passe_temporaire']) {
                rediriger('/mot-de-passe-temporaire/changer');
            }

            rediriger($this->destinationApresConnexion((string) $utilisateur['role_code']));
        } catch (\Throwable) {
            $this->afficherVerificationOtp([
                'general' => 'Verification OTP impossible pour le moment. Reessayez dans quelques instants.',
            ]);
        }
    }

    public function renvoyerOtp(): void
    {
        $verification = $_SESSION['otp_connexion'] ?? null;

        if (!$verification) {
            rediriger('/administration/connexion');
        }

        try {
            $modeleUtilisateur = new Utilisateur();
            $utilisateur = $modeleUtilisateur->trouverParId((int) $verification['utilisateur_id']);

            if (!$utilisateur) {
                ServiceAuthentification::deconnecter();
                rediriger('/administration/connexion');
            }

            $this->envoyerOtpEtRediriger($utilisateur, (string) $verification['type'], 'Un nouveau code OTP a ete envoye.');
        } catch (\Throwable) {
            $this->afficherVerificationOtp([
                'general' => "Le nouveau code OTP n'a pas pu etre envoye. Verifiez la configuration email.",
            ]);
        }
    }

    public function deconnecter(): void
    {
        $utilisateur = ServiceAuthentification::utilisateur();
        $destination = (($utilisateur['role_code'] ?? '') === ServiceReglesMetier::ROLE_ETUDIANT)
            ? '/etudiant/connexion'
            : '/administration/connexion';

        if ($utilisateur) {
            (new JournalActivite())->enregistrerPourRole($utilisateur, 'deconnexion', 'utilisateurs', (int) $utilisateur['id'], [
                'destination' => $destination,
            ]);
        }

        ServiceAuthentification::deconnecter();
        rediriger($destination);
    }

    public function afficherMotDePasseOublie(): void
    {
        $this->vue('authentification/mot_de_passe_oublie', [
            'titre' => traduire('mot_de_passe_oublie.titre'),
            'erreurs' => [],
            'anciennes_donnees' => [],
        ]);
    }

    public function envoyerCodeMotDePasseOublie(): void
    {
        $identifiant = trim((string) ($_POST['identifiant'] ?? ''));

        if ($identifiant === '') {
            $this->vue('authentification/mot_de_passe_oublie', [
                'titre' => traduire('mot_de_passe_oublie.titre'),
                'erreurs' => ['identifiant' => "L'email ou le nom utilisateur est obligatoire."],
                'anciennes_donnees' => ['identifiant' => $identifiant],
            ]);
            return;
        }

        try {
            $modeleUtilisateur = new Utilisateur();
            $utilisateur = $modeleUtilisateur->trouverParIdentifiant($identifiant);

            if (!$utilisateur || (string) $utilisateur['statut'] !== ServiceReglesMetier::STATUT_UTILISATEUR_ACTIF) {
                $this->vue('authentification/mot_de_passe_oublie', [
                    'titre' => traduire('mot_de_passe_oublie.titre'),
                    'erreurs' => ['general' => 'Compte introuvable ou non actif.'],
                    'anciennes_donnees' => ['identifiant' => $identifiant],
                ]);
                return;
            }

            $code = ServiceSecurite::genererCodeSixChiffres();
            (new CodeSecuriteEmail())->creer(
                (int) $utilisateur['id'],
                (string) $utilisateur['email'],
                CodeSecuriteEmail::CONTEXTE_MOT_DE_PASSE_OUBLIE,
                $code,
                10,
                (string) ($_SERVER['REMOTE_ADDR'] ?? '')
            );

            $emailEnvoye = (new ServiceEmail())->envoyerCodeSecurite(
                (string) $utilisateur['email'],
                $code,
                'la reinitialisation de votre mot de passe'
            );

            if (!$emailEnvoye) {
                throw new \RuntimeException("Le code n'a pas pu etre envoye par email.");
            }

            $_SESSION['mot_de_passe_oublie'] = [
                'utilisateur_id' => (int) $utilisateur['id'],
                'role_code' => (string) $utilisateur['role_code'],
                'email' => (string) $utilisateur['email'],
                'cree_le' => time(),
            ];

            rediriger('/mot-de-passe-oublie/reinitialiser');
        } catch (\Throwable $exception) {
            $this->vue('authentification/mot_de_passe_oublie', [
                'titre' => traduire('mot_de_passe_oublie.titre'),
                'erreurs' => ['general' => $exception->getMessage()],
                'anciennes_donnees' => ['identifiant' => $identifiant],
            ]);
        }
    }

    public function afficherReinitialisationMotDePasse(array $erreurs = []): void
    {
        $demande = $_SESSION['mot_de_passe_oublie'] ?? null;

        if (!$demande) {
            rediriger('/mot-de-passe-oublie');
        }

        $this->vue('authentification/reinitialiser_mot_de_passe', [
            'titre' => 'Nouveau mot de passe',
            'erreurs' => $erreurs,
            'email_masque' => $this->masquerEmail((string) $demande['email']),
        ]);
    }

    public function reinitialiserMotDePasse(): void
    {
        $demande = $_SESSION['mot_de_passe_oublie'] ?? null;

        if (!$demande) {
            rediriger('/mot-de-passe-oublie');
        }

        $donnees = [
            'code_otp' => preg_replace('/\s+/', '', (string) ($_POST['code_otp'] ?? '')),
            'mot_de_passe' => (string) ($_POST['mot_de_passe'] ?? ''),
            'confirmation_mot_de_passe' => (string) ($_POST['confirmation_mot_de_passe'] ?? ''),
        ];
        $erreurs = $this->validerNouveauMotDePasse($donnees);

        if (!preg_match('/^\d{6}$/', $donnees['code_otp'])) {
            $erreurs['code_otp'] = 'Le code doit contenir exactement 6 chiffres.';
        }

        if (!empty($erreurs)) {
            $this->afficherReinitialisationMotDePasse($erreurs);
            return;
        }

        try {
            $codeValide = (new CodeSecuriteEmail())->verifier(
                (int) $demande['utilisateur_id'],
                CodeSecuriteEmail::CONTEXTE_MOT_DE_PASSE_OUBLIE,
                $donnees['code_otp']
            );

            if (!$codeValide) {
                $this->afficherReinitialisationMotDePasse([
                    'general' => 'Code incorrect, expire ou deja utilise.',
                ]);
                return;
            }

            $modeleUtilisateur = new Utilisateur();
            $modeleUtilisateur->changerMotDePasse((int) $demande['utilisateur_id'], $donnees['mot_de_passe'], false);
            $destination = ((string) ($demande['role_code'] ?? '') === ServiceReglesMetier::ROLE_ETUDIANT)
                ? '/etudiant/connexion?mot_de_passe=change'
                : '/administration/connexion?mot_de_passe=change';
            unset($_SESSION['mot_de_passe_oublie']);

            rediriger($destination);
        } catch (\Throwable) {
            $this->afficherReinitialisationMotDePasse([
                'general' => 'Reinitialisation impossible pour le moment.',
            ]);
        }
    }

    private function afficherPageConnexion(string $typeConnexion, array $erreurs = [], array $donnees = []): void
    {
        if ($typeConnexion === self::TYPE_ETUDIANT) {
            $this->memoriserElectionCible();
        }

        if (($_GET['changer'] ?? '') === '1') {
            ServiceAuthentification::deconnecter();
        } elseif (ServiceAuthentification::estConnecte()) {
            $utilisateur = ServiceAuthentification::utilisateur();
            $role = (string) ($utilisateur['role_code'] ?? '');

            if ($this->roleAutorisePourType($role, $typeConnexion)) {
                rediriger($this->destinationApresConnexion($role));
            }

            ServiceAuthentification::deconnecter();
        }

        $this->vue('authentification/connexion', $this->donneesVueConnexion($typeConnexion, $erreurs, $donnees));
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

    private function connecterParType(string $typeConnexion): void
    {
        $donnees = [
            'identifiant' => trim((string) ($_POST['identifiant'] ?? '')),
            'mot_de_passe' => (string) ($_POST['mot_de_passe'] ?? ''),
        ];

        $erreurs = [];

        if ($donnees['identifiant'] === '') {
            $erreurs['identifiant'] = $typeConnexion === self::TYPE_ETUDIANT
                ? "L'email ou le matricule est obligatoire."
                : traduire('connexion.erreur_identifiant_obligatoire');
        }

        if ($donnees['mot_de_passe'] === '') {
            $erreurs['mot_de_passe'] = traduire('connexion.erreur_mot_de_passe_obligatoire');
        }

        if (!empty($erreurs)) {
            $this->afficherPageConnexion($typeConnexion, $erreurs, $donnees);
            return;
        }

        try {
            $modeleUtilisateur = new Utilisateur();
            $utilisateur = $modeleUtilisateur->trouverParIdentifiant($donnees['identifiant']);

            if (!$utilisateur) {
                $message = $typeConnexion === self::TYPE_ETUDIANT
                    ? "Compte etudiant introuvable ou pas encore active. L'appariteur doit d'abord activer l'etudiant."
                    : traduire('connexion.erreur_identifiants');

                $this->afficherPageConnexion($typeConnexion, ['general' => $message], $donnees);
                return;
            }

            if (!$this->roleAutorisePourType((string) $utilisateur['role_code'], $typeConnexion)) {
                $this->afficherPageConnexion($typeConnexion, [
                    'general' => $this->messageRoleNonAutorise((string) $utilisateur['role_code'], $typeConnexion),
                ], $donnees);
                return;
            }

            if ($utilisateur['statut'] === ServiceReglesMetier::STATUT_UTILISATEUR_BLOQUE) {
                $this->afficherPageConnexion($typeConnexion, [
                    'general' => traduire('connexion.erreur_compte_bloque'),
                ], $donnees);
                return;
            }

            if ($utilisateur['statut'] !== ServiceReglesMetier::STATUT_UTILISATEUR_ACTIF) {
                $this->afficherPageConnexion($typeConnexion, [
                    'general' => traduire('connexion.erreur_compte_inactif'),
                ], $donnees);
                return;
            }

            if (!password_verify($donnees['mot_de_passe'], (string) $utilisateur['mot_de_passe'])) {
                $tentatives = $modeleUtilisateur->enregistrerTentativeEchouee(
                    (int) $utilisateur['id'],
                    ServiceReglesMetier::NOMBRE_MAX_TENTATIVES_CONNEXION
                );

                $message = $tentatives >= ServiceReglesMetier::NOMBRE_MAX_TENTATIVES_CONNEXION
                    ? traduire('connexion.erreur_compte_bloque')
                    : traduire('connexion.erreur_identifiants');

                $this->afficherPageConnexion($typeConnexion, ['general' => $message], $donnees);
                return;
            }

            if (!ServiceAuthentification::otpConnexionActif()) {
                $this->connecterApresMotDePasse($modeleUtilisateur, $utilisateur, $typeConnexion, 'otp_desactive_par_configuration');
            }

            if (ServiceAuthentification::otpValideRecemment($utilisateur, $typeConnexion)) {
                $this->connecterApresMotDePasse($modeleUtilisateur, $utilisateur, $typeConnexion);
            }

            $this->envoyerOtpEtRediriger($utilisateur, $typeConnexion);
        } catch (\RuntimeException $exception) {
            $this->afficherPageConnexion($typeConnexion, [
                'general' => $exception->getMessage(),
            ], $donnees);
        } catch (\Throwable) {
            $this->afficherPageConnexion($typeConnexion, [
                'general' => traduire('connexion.erreur_technique'),
            ], $donnees);
        }
    }

    private function connecterApresMotDePasse(Utilisateur $modeleUtilisateur, array $utilisateur, string $typeConnexion, string $methode = 'session_otp_valide'): never
    {
        $modeleUtilisateur->enregistrerConnexionReussie((int) $utilisateur['id']);
        $utilisateur['tentatives_connexion'] = 0;
        ServiceAuthentification::connecter($utilisateur);
        ServiceAuthentification::marquerOtpValide($utilisateur, $typeConnexion);
        (new JournalActivite())->enregistrerPourRole($utilisateur, 'connexion_reussie', 'utilisateurs', (int) $utilisateur['id'], [
            'type_connexion' => $typeConnexion,
            'methode' => $methode,
        ]);

        if ((bool) $utilisateur['mot_de_passe_temporaire']) {
            rediriger('/mot-de-passe-temporaire/changer');
        }

        rediriger($this->destinationApresConnexion((string) $utilisateur['role_code']));
    }

    private function envoyerOtpEtRediriger(array $utilisateur, string $typeConnexion, string $message = ''): never
    {
        $code = ServiceSecurite::genererCodeSixChiffres();
        $modeleCode = new CodeSecuriteEmail();
        $modeleCode->creer(
            (int) $utilisateur['id'],
            (string) $utilisateur['email'],
            CodeSecuriteEmail::CONTEXTE_CONNEXION_OTP,
            $code,
            ServiceAuthentification::dureeExpirationOtpMinutes(),
            (string) ($_SERVER['REMOTE_ADDR'] ?? '')
        );

        $emailEnvoye = (new ServiceEmail())->envoyerCodeSecurite(
            (string) $utilisateur['email'],
            $code,
            $this->libelleContexteOtp($typeConnexion)
        );

        if (!$emailEnvoye) {
            throw new \RuntimeException("Le code OTP n'a pas pu etre envoye par email.");
        }

        $_SESSION['otp_connexion'] = [
            'utilisateur_id' => (int) $utilisateur['id'],
            'role_code' => (string) $utilisateur['role_code'],
            'type' => $typeConnexion,
            'email' => (string) $utilisateur['email'],
            'cree_le' => time(),
        ];

        $parametre = $message !== '' ? '?renvoye=1' : '';
        rediriger('/otp/connexion' . $parametre);
    }

    private function roleAutorisePourType(string $role, string $typeConnexion): bool
    {
        if ($typeConnexion === self::TYPE_ETUDIANT) {
            return $role === ServiceReglesMetier::ROLE_ETUDIANT;
        }

        return in_array($role, [
            ServiceReglesMetier::ROLE_SUPER_ADMINISTRATEUR,
            ServiceReglesMetier::ROLE_PRESIDENT_ELECTORAL,
            ServiceReglesMetier::ROLE_APPARITEUR,
        ], true);
    }

    private function messageRoleNonAutorise(string $role, string $typeConnexion): string
    {
        if ($typeConnexion === self::TYPE_ETUDIANT) {
            return 'Cette page est reservee aux etudiants. Utilisez la connexion administration pour les comptes administratifs.';
        }

        if ($role === ServiceReglesMetier::ROLE_ETUDIANT) {
            return 'Ce compte est un compte etudiant. Utilisez la connexion etudiant.';
        }

        return "Ce role n'est pas autorise sur cette page de connexion.";
    }

    private function memoriserElectionCible(): void
    {
        $electionId = (int) ($_GET['election_id'] ?? $_GET['id'] ?? 0);

        if ($electionId > 0) {
            $_SESSION['election_cible_id'] = $electionId;
        }
    }

    private function destinationApresConnexion(string $role): string
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

    private function donneesVueConnexion(string $typeConnexion, array $erreurs = [], array $donnees = []): array
    {
        unset($donnees['mot_de_passe']);

        $otpActif = ServiceAuthentification::otpConnexionActif();

        if ($typeConnexion === self::TYPE_ETUDIANT) {
            return [
                'titre' => 'Connexion etudiant',
                'installation_reussie' => false,
                'message_succes' => ($_GET['mot_de_passe'] ?? '') === 'change' ? 'Mot de passe change avec succes. Connectez-vous avec le nouveau mot de passe.' : '',
                'erreurs' => $erreurs,
                'anciennes_donnees' => $donnees,
                'action_connexion' => '/etudiant/connexion',
                'surtitre_connexion' => 'Espace etudiant',
                'titre_connexion' => 'Connexion etudiant',
                'description_connexion' => $otpActif
                    ? "Connectez-vous avec votre email ou votre matricule. L'OTP est demande a la connexion puis apres deconnexion."
                    : 'Connectez-vous avec votre email ou votre matricule.',
                'identifiant_libelle' => 'Email ou matricule',
                'identifiant_placeholder' => 'email@gmail.com ou matricule',
                'bouton_connexion' => 'Se connecter',
            ];
        }

        return [
            'titre' => 'Connexion administration',
            'installation_reussie' => ($_GET['installation'] ?? '') === 'ok',
            'message_succes' => ($_GET['mot_de_passe'] ?? '') === 'change' ? 'Mot de passe change avec succes. Connectez-vous avec le nouveau mot de passe.' : '',
            'erreurs' => $erreurs,
            'anciennes_donnees' => $donnees,
            'action_connexion' => '/administration/connexion',
            'surtitre_connexion' => 'Administration VOTE UPC ONLINE',
            'titre_connexion' => 'Connexion administration',
            'description_connexion' => $otpActif
                ? "Super administrateur, president electoral et appariteur utilisent cette entree. L'OTP est demande a la connexion puis apres deconnexion."
                : 'Super administrateur, president electoral et appariteur utilisent cette entree.',
            'identifiant_libelle' => 'Email ou nom utilisateur',
            'identifiant_placeholder' => 'email@gmail.com ou nom utilisateur',
            'bouton_connexion' => 'Se connecter',
        ];
    }

    private function libelleContexteOtp(string $typeConnexion): string
    {
        return $typeConnexion === self::TYPE_ETUDIANT
            ? 'la connexion a votre espace etudiant'
            : 'la connexion a votre espace administration';
    }

    private function descriptionOtp(string $typeConnexion): string
    {
        return $typeConnexion === self::TYPE_ETUDIANT
            ? 'Entrez le code a 6 chiffres envoye a votre email pour ouvrir votre espace etudiant.'
            : 'Entrez le code a 6 chiffres envoye a votre email pour ouvrir votre espace administratif.';
    }

    private function masquerEmail(string $email): string
    {
        if (!str_contains($email, '@')) {
            return $email;
        }

        [$nom, $domaine] = explode('@', $email, 2);
        $debut = substr($nom, 0, min(2, strlen($nom)));
        $masque = str_repeat('*', max(3, strlen($nom) - strlen($debut)));

        return $debut . $masque . '@' . $domaine;
    }
}
