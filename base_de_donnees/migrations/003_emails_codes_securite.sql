BEGIN;

CREATE TABLE IF NOT EXISTS codes_securite_email (
    id BIGSERIAL PRIMARY KEY,
    utilisateur_id BIGINT REFERENCES utilisateurs(id) ON DELETE CASCADE,
    email_destinataire VARCHAR(180) NOT NULL,
    contexte VARCHAR(80) NOT NULL,
    code_hash VARCHAR(255) NOT NULL,
    expire_le TIMESTAMP WITHOUT TIME ZONE NOT NULL,
    consomme_le TIMESTAMP WITHOUT TIME ZONE,
    tentatives_verification INTEGER NOT NULL DEFAULT 0,
    adresse_ip VARCHAR(80),
    cree_le TIMESTAMP WITHOUT TIME ZONE NOT NULL DEFAULT NOW(),
    CONSTRAINT codes_securite_email_contexte_valide CHECK (
        contexte IN (
            'mot_de_passe_oublie',
            'activation_etudiant',
            'reactivation_etudiant',
            'verification_email',
            'connexion_otp'
        )
    )
);

CREATE INDEX IF NOT EXISTS codes_securite_email_recherche_idx
    ON codes_securite_email (LOWER(email_destinataire), contexte, expire_le DESC);

CREATE TABLE IF NOT EXISTS emails_envoyes (
    id BIGSERIAL PRIMARY KEY,
    utilisateur_id BIGINT REFERENCES utilisateurs(id) ON DELETE SET NULL,
    email_destinataire VARCHAR(180) NOT NULL,
    sujet VARCHAR(180) NOT NULL,
    contexte VARCHAR(80) NOT NULL,
    statut VARCHAR(40) NOT NULL DEFAULT 'en_attente',
    erreur TEXT,
    cree_le TIMESTAMP WITHOUT TIME ZONE NOT NULL DEFAULT NOW(),
    envoye_le TIMESTAMP WITHOUT TIME ZONE,
    CONSTRAINT emails_envoyes_statut_valide CHECK (statut IN ('en_attente', 'envoye', 'echec'))
);

CREATE INDEX IF NOT EXISTS emails_envoyes_statistiques_idx
    ON emails_envoyes (contexte, statut, cree_le DESC);

COMMIT;
