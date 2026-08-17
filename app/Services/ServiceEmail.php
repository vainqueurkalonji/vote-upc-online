<?php

declare(strict_types=1);

namespace Application\Services;

final class ServiceEmail
{
    private array $configuration;

    public function __construct()
    {
        $this->configuration = require CHEMIN_RACINE . '/configuration/email.php';
    }

    public function envoyer(string $destinataire, string $sujet, string $messageHtml, ?string $messageTexte = null): bool
    {
        if (class_exists(\PHPMailer\PHPMailer\PHPMailer::class)) {
            return $this->envoyerAvecPhpMailer($destinataire, $sujet, $messageHtml, $messageTexte);
        }

        return $this->envoyerAvecMailPhp($destinataire, $sujet, $messageHtml);
    }

    public function envoyerMotDePasseTemporaireEtudiant(array $etudiant, string $motDePasseTemporaire): bool
    {
        $nomComplet = trim(($etudiant['prenom'] ?? '') . ' ' . ($etudiant['nom'] ?? '') . ' ' . ($etudiant['post_nom'] ?? ''));
        $message = $this->modeleMessage(
            'Activation de votre compte VOTE UPC ONLINE',
            'Bienvenue ' . $nomComplet,
            'Votre compte etudiant est active. Voici votre mot de passe temporaire:',
            $motDePasseTemporaire,
            'Vous devrez changer ce mot de passe lors de votre premiere connexion.'
        );

        return $this->envoyer((string) $etudiant['email'], 'Activation de votre compte VOTE UPC ONLINE', $message);
    }

    public function envoyerMotDePasseTemporaireUtilisateur(array $utilisateur, string $motDePasseTemporaire): bool
    {
        $nomComplet = trim(($utilisateur['prenom'] ?? '') . ' ' . ($utilisateur['nom'] ?? '') . ' ' . ($utilisateur['post_nom'] ?? ''));
        $role = (string) ($utilisateur['role_nom'] ?? 'Utilisateur');
        $message = $this->modeleMessage(
            'Creation de votre compte VOTE UPC ONLINE',
            'Bonjour ' . $nomComplet,
            'Votre compte ' . $role . ' est cree. Voici votre mot de passe temporaire:',
            $motDePasseTemporaire,
            'Vous devrez changer ce mot de passe lors de votre premiere connexion.'
        );

        return $this->envoyer((string) $utilisateur['email'], 'Votre compte VOTE UPC ONLINE', $message);
    }

    public function envoyerCodeSecurite(string $destinataire, string $code, string $contexte): bool
    {
        $message = $this->modeleMessage(
            'Code de securite VOTE UPC ONLINE',
            'Verification de securite',
            'Voici votre code de securite pour ' . $contexte . ':',
            $code,
            'Ce code expire rapidement. Ne le partagez avec personne.'
        );

        return $this->envoyer($destinataire, 'Code de securite VOTE UPC ONLINE', $message);
    }

    private function envoyerAvecPhpMailer(string $destinataire, string $sujet, string $messageHtml, ?string $messageTexte): bool
    {
        $mail = new \PHPMailer\PHPMailer\PHPMailer(true);

        try {
            if (($this->configuration['transport'] ?? 'smtp') === 'smtp') {
                $mail->isSMTP();
                $mail->Host = (string) $this->configuration['hote'];
                $mail->Port = (int) $this->configuration['port'];
                $mail->SMTPAuth = true;
                $mail->Username = (string) $this->configuration['utilisateur'];
                $mail->Password = (string) $this->configuration['mot_de_passe'];

                $chiffrement = strtolower((string) $this->configuration['chiffrement']);
                if ($chiffrement === 'ssl') {
                    $mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS;
                } elseif ($chiffrement === 'tls') {
                    $mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
                }
            }

            $mail->CharSet = 'UTF-8';
            $mail->setFrom(
                (string) $this->configuration['adresse_expediteur'],
                (string) $this->configuration['nom_expediteur']
            );

            if (!empty($this->configuration['reponse_a'])) {
                $mail->addReplyTo((string) $this->configuration['reponse_a']);
            }

            $mail->addAddress($destinataire);
            $mail->isHTML(true);
            $mail->Subject = $sujet;
            $mail->Body = $messageHtml;
            $mail->AltBody = $messageTexte ?? strip_tags(str_replace(['<br>', '<br/>', '<br />'], "\n", $messageHtml));

            return $mail->send();
        } catch (\Throwable) {
            return false;
        }
    }

    private function envoyerAvecMailPhp(string $destinataire, string $sujet, string $messageHtml): bool
    {
        $entetes = [
            'MIME-Version: 1.0',
            'Content-type: text/html; charset=UTF-8',
            'From: ' . $this->configuration['nom_expediteur'] . ' <' . $this->configuration['adresse_expediteur'] . '>',
        ];

        return mail($destinataire, $sujet, $messageHtml, implode("\r\n", $entetes));
    }

    private function modeleMessage(string $titre, string $salutation, string $texte, string $code, string $note): string
    {
        return '
            <div style="font-family:Arial,sans-serif;line-height:1.55;color:#152033">
                <h2 style="margin:0 0 12px;color:#1f5eff">' . e($titre) . '</h2>
                <p>' . e($salutation) . '</p>
                <p>' . e($texte) . '</p>
                <p style="font-size:26px;font-weight:700;letter-spacing:3px;background:#f3f7ff;border:1px solid #cfe0ff;padding:12px;border-radius:6px;display:inline-block">' . e($code) . '</p>
                <p>' . e($note) . '</p>
                <p style="color:#5d6b82">VOTE UPC ONLINE</p>
            </div>
        ';
    }
}
