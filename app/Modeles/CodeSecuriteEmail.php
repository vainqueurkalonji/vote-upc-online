<?php

declare(strict_types=1);

namespace Application\Modeles;

use Application\Services\ServiceSecurite;

class CodeSecuriteEmail extends Modele
{
    public const CONTEXTE_CONNEXION_OTP = 'connexion_otp';
    public const CONTEXTE_MOT_DE_PASSE_OUBLIE = 'mot_de_passe_oublie';

    public function creer(
        int $utilisateurId,
        string $emailDestinataire,
        string $contexte,
        string $code,
        int $minutesValidite = 10,
        ?string $adresseIp = null
    ): int {
        $this->db->beginTransaction();

        try {
            $this->consommerAnciensCodes($utilisateurId, $contexte);

            $requete = $this->db->prepare(
                "INSERT INTO codes_securite_email (
                    utilisateur_id,
                    email_destinataire,
                    contexte,
                    code_hash,
                    expire_le,
                    adresse_ip,
                    cree_le
                ) VALUES (
                    :utilisateur_id,
                    :email_destinataire,
                    :contexte,
                    :code_hash,
                    NOW() + (:minutes_validite || ' minutes')::interval,
                    :adresse_ip,
                    NOW()
                ) RETURNING id"
            );

            $requete->execute([
                'utilisateur_id' => $utilisateurId,
                'email_destinataire' => strtolower(trim($emailDestinataire)),
                'contexte' => $contexte,
                'code_hash' => ServiceSecurite::hashCode($code),
                'minutes_validite' => $minutesValidite,
                'adresse_ip' => $adresseIp,
            ]);

            $id = (int) $requete->fetch()['id'];
            $this->db->commit();

            return $id;
        } catch (\Throwable $exception) {
            $this->db->rollBack();
            throw $exception;
        }
    }

    public function verifier(int $utilisateurId, string $contexte, string $code, int $maxTentatives = 5): bool
    {
        $this->db->beginTransaction();

        try {
            $requete = $this->db->prepare(
                "SELECT id, code_hash, tentatives_verification
                 FROM codes_securite_email
                 WHERE utilisateur_id = :utilisateur_id
                 AND contexte = :contexte
                 AND consomme_le IS NULL
                 AND expire_le >= NOW()
                 ORDER BY expire_le DESC
                 LIMIT 1
                 FOR UPDATE"
            );

            $requete->execute([
                'utilisateur_id' => $utilisateurId,
                'contexte' => $contexte,
            ]);

            $codeSecurite = $requete->fetch();

            if (!$codeSecurite) {
                $this->db->commit();
                return false;
            }

            $tentatives = (int) $codeSecurite['tentatives_verification'] + 1;
            $verificationReussie = ServiceSecurite::verifierCode($code, (string) $codeSecurite['code_hash']);

            if ($verificationReussie) {
                $requeteConsommation = $this->db->prepare(
                    "UPDATE codes_securite_email
                     SET tentatives_verification = :tentatives,
                         consomme_le = NOW()
                     WHERE id = :id"
                );
                $requeteConsommation->execute([
                    'tentatives' => $tentatives,
                    'id' => (int) $codeSecurite['id'],
                ]);

                $this->db->commit();
                return true;
            }

            $requeteTentative = $this->db->prepare(
                "UPDATE codes_securite_email
                 SET tentatives_verification = :tentatives,
                     consomme_le = :consomme_le
                 WHERE id = :id"
            );
            $requeteTentative->execute([
                'tentatives' => $tentatives,
                'consomme_le' => $tentatives >= $maxTentatives ? date('Y-m-d H:i:s') : null,
                'id' => (int) $codeSecurite['id'],
            ]);

            $this->db->commit();
            return false;
        } catch (\Throwable $exception) {
            $this->db->rollBack();
            throw $exception;
        }
    }

    private function consommerAnciensCodes(int $utilisateurId, string $contexte): void
    {
        $requete = $this->db->prepare(
            "UPDATE codes_securite_email
             SET consomme_le = NOW()
             WHERE utilisateur_id = :utilisateur_id
             AND contexte = :contexte
             AND consomme_le IS NULL"
        );

        $requete->execute([
            'utilisateur_id' => $utilisateurId,
            'contexte' => $contexte,
        ]);
    }
}
