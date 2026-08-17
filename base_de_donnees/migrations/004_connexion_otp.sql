BEGIN;

ALTER TABLE codes_securite_email
    DROP CONSTRAINT IF EXISTS codes_securite_email_contexte_valide;

ALTER TABLE codes_securite_email
    ADD CONSTRAINT codes_securite_email_contexte_valide CHECK (
        contexte IN (
            'mot_de_passe_oublie',
            'activation_etudiant',
            'reactivation_etudiant',
            'verification_email',
            'connexion_otp'
        )
    );

CREATE INDEX IF NOT EXISTS codes_securite_email_utilisateur_contexte_idx
    ON codes_securite_email (utilisateur_id, contexte, expire_le DESC);

COMMIT;
