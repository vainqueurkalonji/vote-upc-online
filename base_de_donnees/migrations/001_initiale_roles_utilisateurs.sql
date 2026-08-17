BEGIN;

CREATE TABLE IF NOT EXISTS roles (
    id BIGSERIAL PRIMARY KEY,
    code VARCHAR(80) NOT NULL UNIQUE,
    nom VARCHAR(120) NOT NULL,
    description TEXT,
    cree_le TIMESTAMP WITHOUT TIME ZONE NOT NULL DEFAULT NOW()
);

INSERT INTO roles (code, nom, description)
VALUES
    ('super_administrateur', 'Super administrateur', 'Responsable global de la plateforme.'),
    ('president_electoral', 'President electoral', 'Responsable de la validation electorale et de la publication.'),
    ('appariteur', 'Appariteur', 'Agent charge de l''activation des etudiants de sa faculte.'),
    ('etudiant', 'Etudiant', 'Utilisateur autorise a consulter les candidats et voter.')
ON CONFLICT (code) DO NOTHING;

CREATE TABLE IF NOT EXISTS utilisateurs (
    id BIGSERIAL PRIMARY KEY,
    role_id BIGINT NOT NULL REFERENCES roles(id),
    nom VARCHAR(120) NOT NULL,
    post_nom VARCHAR(120),
    prenom VARCHAR(120) NOT NULL,
    nom_utilisateur VARCHAR(120) NOT NULL,
    email VARCHAR(180) NOT NULL,
    mot_de_passe VARCHAR(255) NOT NULL,
    statut VARCHAR(40) NOT NULL DEFAULT 'actif',
    mot_de_passe_temporaire BOOLEAN NOT NULL DEFAULT FALSE,
    tentatives_connexion INTEGER NOT NULL DEFAULT 0,
    derniere_connexion_le TIMESTAMP WITHOUT TIME ZONE,
    cree_le TIMESTAMP WITHOUT TIME ZONE NOT NULL DEFAULT NOW(),
    modifie_le TIMESTAMP WITHOUT TIME ZONE,
    CONSTRAINT utilisateurs_statut_valide CHECK (statut IN ('actif', 'inactif', 'bloque', 'supprime'))
);

CREATE UNIQUE INDEX IF NOT EXISTS utilisateurs_email_unique
    ON utilisateurs (LOWER(email));

CREATE UNIQUE INDEX IF NOT EXISTS utilisateurs_nom_utilisateur_unique
    ON utilisateurs (LOWER(nom_utilisateur));

CREATE TABLE IF NOT EXISTS journaux_actions (
    id BIGSERIAL PRIMARY KEY,
    utilisateur_id BIGINT REFERENCES utilisateurs(id),
    action VARCHAR(160) NOT NULL,
    entite VARCHAR(160),
    entite_id BIGINT,
    adresse_ip VARCHAR(80),
    details JSONB,
    cree_le TIMESTAMP WITHOUT TIME ZONE NOT NULL DEFAULT NOW()
);

COMMIT;
