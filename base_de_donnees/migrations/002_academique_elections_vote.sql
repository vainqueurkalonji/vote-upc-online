BEGIN;

CREATE TABLE IF NOT EXISTS facultes (
    id BIGSERIAL PRIMARY KEY,
    code VARCHAR(40) NOT NULL UNIQUE,
    nom VARCHAR(180) NOT NULL,
    active BOOLEAN NOT NULL DEFAULT TRUE,
    cree_le TIMESTAMP WITHOUT TIME ZONE NOT NULL DEFAULT NOW(),
    modifie_le TIMESTAMP WITHOUT TIME ZONE
);

CREATE TABLE IF NOT EXISTS departements (
    id BIGSERIAL PRIMARY KEY,
    faculte_id BIGINT NOT NULL REFERENCES facultes(id) ON DELETE RESTRICT,
    code VARCHAR(60) NOT NULL,
    nom VARCHAR(180) NOT NULL,
    actif BOOLEAN NOT NULL DEFAULT TRUE,
    cree_le TIMESTAMP WITHOUT TIME ZONE NOT NULL DEFAULT NOW(),
    modifie_le TIMESTAMP WITHOUT TIME ZONE,
    CONSTRAINT departements_code_faculte_unique UNIQUE (faculte_id, code)
);

CREATE TABLE IF NOT EXISTS promotions (
    id BIGSERIAL PRIMARY KEY,
    faculte_id BIGINT NOT NULL REFERENCES facultes(id) ON DELETE RESTRICT,
    code VARCHAR(60) NOT NULL,
    nom VARCHAR(180) NOT NULL,
    active BOOLEAN NOT NULL DEFAULT TRUE,
    ordre INTEGER NOT NULL DEFAULT 0,
    cree_le TIMESTAMP WITHOUT TIME ZONE NOT NULL DEFAULT NOW(),
    modifie_le TIMESTAMP WITHOUT TIME ZONE,
    CONSTRAINT promotions_code_faculte_unique UNIQUE (faculte_id, code)
);

CREATE TABLE IF NOT EXISTS etudiants (
    id BIGSERIAL PRIMARY KEY,
    utilisateur_id BIGINT REFERENCES utilisateurs(id) ON DELETE SET NULL,
    faculte_id BIGINT NOT NULL REFERENCES facultes(id) ON DELETE RESTRICT,
    departement_id BIGINT REFERENCES departements(id) ON DELETE RESTRICT,
    promotion_id BIGINT NOT NULL REFERENCES promotions(id) ON DELETE RESTRICT,
    nom VARCHAR(120) NOT NULL,
    post_nom VARCHAR(120) NOT NULL,
    prenom VARCHAR(120) NOT NULL,
    date_naissance DATE,
    lieu_naissance VARCHAR(160),
    sexe VARCHAR(20),
    email VARCHAR(180),
    matricule VARCHAR(80) NOT NULL,
    statut VARCHAR(40) NOT NULL DEFAULT 'non_active',
    source_creation VARCHAR(40) NOT NULL DEFAULT 'import_excel',
    cree_par BIGINT REFERENCES utilisateurs(id) ON DELETE SET NULL,
    cree_le TIMESTAMP WITHOUT TIME ZONE NOT NULL DEFAULT NOW(),
    modifie_le TIMESTAMP WITHOUT TIME ZONE,
    CONSTRAINT etudiants_statut_valide CHECK (statut IN ('non_active', 'actif', 'bloque', 'supprime')),
    CONSTRAINT etudiants_source_creation_valide CHECK (source_creation IN ('import_excel', 'ajout_manuel'))
);

CREATE UNIQUE INDEX IF NOT EXISTS etudiants_matricule_unique
    ON etudiants (LOWER(matricule));

CREATE UNIQUE INDEX IF NOT EXISTS etudiants_email_unique
    ON etudiants (LOWER(email))
    WHERE email IS NOT NULL;

CREATE INDEX IF NOT EXISTS etudiants_recherche_idx
    ON etudiants (faculte_id, promotion_id, statut);

CREATE TABLE IF NOT EXISTS imports_etudiants (
    id BIGSERIAL PRIMARY KEY,
    fichier_nom VARCHAR(255) NOT NULL,
    fichier_chemin VARCHAR(255),
    total_lignes INTEGER NOT NULL DEFAULT 0,
    total_importes INTEGER NOT NULL DEFAULT 0,
    total_modifies INTEGER NOT NULL DEFAULT 0,
    total_erreurs INTEGER NOT NULL DEFAULT 0,
    importe_par BIGINT REFERENCES utilisateurs(id) ON DELETE SET NULL,
    cree_le TIMESTAMP WITHOUT TIME ZONE NOT NULL DEFAULT NOW()
);

CREATE TABLE IF NOT EXISTS appariteurs (
    id BIGSERIAL PRIMARY KEY,
    utilisateur_id BIGINT NOT NULL UNIQUE REFERENCES utilisateurs(id) ON DELETE CASCADE,
    faculte_id BIGINT NOT NULL REFERENCES facultes(id) ON DELETE RESTRICT,
    statut VARCHAR(40) NOT NULL DEFAULT 'actif',
    cree_par BIGINT REFERENCES utilisateurs(id) ON DELETE SET NULL,
    cree_le TIMESTAMP WITHOUT TIME ZONE NOT NULL DEFAULT NOW(),
    modifie_le TIMESTAMP WITHOUT TIME ZONE,
    CONSTRAINT appariteurs_statut_valide CHECK (statut IN ('actif', 'inactif', 'bloque', 'supprime'))
);

CREATE INDEX IF NOT EXISTS appariteurs_faculte_idx
    ON appariteurs (faculte_id, statut);

CREATE TABLE IF NOT EXISTS presidents_electoraux (
    id BIGSERIAL PRIMARY KEY,
    utilisateur_id BIGINT NOT NULL UNIQUE REFERENCES utilisateurs(id) ON DELETE CASCADE,
    statut VARCHAR(40) NOT NULL DEFAULT 'actif',
    cree_par BIGINT REFERENCES utilisateurs(id) ON DELETE SET NULL,
    cree_le TIMESTAMP WITHOUT TIME ZONE NOT NULL DEFAULT NOW(),
    modifie_le TIMESTAMP WITHOUT TIME ZONE,
    CONSTRAINT presidents_electoraux_statut_valide CHECK (statut IN ('actif', 'inactif', 'bloque', 'supprime'))
);

CREATE TABLE IF NOT EXISTS activations_etudiants (
    id BIGSERIAL PRIMARY KEY,
    etudiant_id BIGINT NOT NULL REFERENCES etudiants(id) ON DELETE CASCADE,
    appariteur_id BIGINT NOT NULL REFERENCES appariteurs(id) ON DELETE RESTRICT,
    action VARCHAR(40) NOT NULL,
    preuves_verifiees BOOLEAN NOT NULL DEFAULT TRUE,
    mot_de_passe_temporaire_envoye BOOLEAN NOT NULL DEFAULT FALSE,
    adresse_ip VARCHAR(80),
    commentaire TEXT,
    cree_le TIMESTAMP WITHOUT TIME ZONE NOT NULL DEFAULT NOW(),
    CONSTRAINT activations_etudiants_action_valide CHECK (action IN ('activation', 'reactivation'))
);

CREATE INDEX IF NOT EXISTS activations_etudiants_journal_idx
    ON activations_etudiants (appariteur_id, etudiant_id, cree_le DESC);

CREATE TABLE IF NOT EXISTS elections (
    id BIGSERIAL PRIMARY KEY,
    nom VARCHAR(180) NOT NULL,
    description TEXT,
    portee_type VARCHAR(40) NOT NULL DEFAULT 'universite',
    faculte_id BIGINT REFERENCES facultes(id) ON DELETE RESTRICT,
    departement_id BIGINT REFERENCES departements(id) ON DELETE RESTRICT,
    promotion_id BIGINT REFERENCES promotions(id) ON DELETE RESTRICT,
    date_debut TIMESTAMP WITHOUT TIME ZONE NOT NULL,
    date_fin TIMESTAMP WITHOUT TIME ZONE NOT NULL,
    statut VARCHAR(60) NOT NULL DEFAULT 'brouillon',
    cree_par BIGINT REFERENCES utilisateurs(id) ON DELETE SET NULL,
    validee_par_president_id BIGINT REFERENCES presidents_electoraux(id) ON DELETE SET NULL,
    validee_le TIMESTAMP WITHOUT TIME ZONE,
    publiee_par_president_id BIGINT REFERENCES presidents_electoraux(id) ON DELETE SET NULL,
    publiee_le TIMESTAMP WITHOUT TIME ZONE,
    cree_le TIMESTAMP WITHOUT TIME ZONE NOT NULL DEFAULT NOW(),
    modifie_le TIMESTAMP WITHOUT TIME ZONE,
    CONSTRAINT elections_dates_valides CHECK (date_fin > date_debut),
    CONSTRAINT elections_portee_type_valide CHECK (portee_type IN ('universite', 'faculte', 'departement', 'promotion')),
    CONSTRAINT elections_statut_valide CHECK (
        statut IN (
            'brouillon',
            'en_attente_validation_lancement',
            'validee',
            'ouverte',
            'fermee',
            'en_attente_publication',
            'publiee',
            'annulee'
        )
    ),
    CONSTRAINT elections_portee_coherente CHECK (
        (portee_type = 'universite' AND faculte_id IS NULL AND departement_id IS NULL AND promotion_id IS NULL)
        OR (portee_type = 'faculte' AND faculte_id IS NOT NULL AND departement_id IS NULL AND promotion_id IS NULL)
        OR (portee_type = 'departement' AND faculte_id IS NOT NULL AND departement_id IS NOT NULL AND promotion_id IS NULL)
        OR (portee_type = 'promotion' AND faculte_id IS NOT NULL AND promotion_id IS NOT NULL)
    )
);

CREATE INDEX IF NOT EXISTS elections_periode_idx
    ON elections (statut, date_debut, date_fin);

CREATE INDEX IF NOT EXISTS elections_portee_idx
    ON elections (portee_type, faculte_id, departement_id, promotion_id);

CREATE TABLE IF NOT EXISTS candidats (
    id BIGSERIAL PRIMARY KEY,
    election_id BIGINT REFERENCES elections(id) ON DELETE SET NULL,
    faculte_id BIGINT REFERENCES facultes(id) ON DELETE RESTRICT,
    departement_id BIGINT REFERENCES departements(id) ON DELETE RESTRICT,
    promotion_id BIGINT REFERENCES promotions(id) ON DELETE RESTRICT,
    nom VARCHAR(120) NOT NULL,
    post_nom VARCHAR(120) NOT NULL,
    prenom VARCHAR(120) NOT NULL,
    sexe VARCHAR(20),
    photo_chemin VARCHAR(255),
    biographie TEXT,
    slogan VARCHAR(255),
    vision TEXT,
    image_vision_chemin VARCHAR(255),
    statut VARCHAR(40) NOT NULL DEFAULT 'actif',
    cree_par BIGINT REFERENCES utilisateurs(id) ON DELETE SET NULL,
    cree_le TIMESTAMP WITHOUT TIME ZONE NOT NULL DEFAULT NOW(),
    modifie_le TIMESTAMP WITHOUT TIME ZONE,
    CONSTRAINT candidats_sexe_valide CHECK (sexe IS NULL OR sexe IN ('M', 'F')),
    CONSTRAINT candidats_statut_valide CHECK (statut IN ('actif', 'inactif', 'supprime'))
);

CREATE INDEX IF NOT EXISTS candidats_election_idx
    ON candidats (election_id, statut);

CREATE INDEX IF NOT EXISTS candidats_portee_idx
    ON candidats (faculte_id, departement_id, promotion_id);

CREATE TABLE IF NOT EXISTS validations_elections (
    id BIGSERIAL PRIMARY KEY,
    election_id BIGINT NOT NULL REFERENCES elections(id) ON DELETE CASCADE,
    president_electoral_id BIGINT NOT NULL REFERENCES presidents_electoraux(id) ON DELETE RESTRICT,
    type_validation VARCHAR(60) NOT NULL,
    decision VARCHAR(40) NOT NULL,
    commentaire TEXT,
    cree_le TIMESTAMP WITHOUT TIME ZONE NOT NULL DEFAULT NOW(),
    CONSTRAINT validations_elections_type_valide CHECK (
        type_validation IN ('lancement', 'publication', 'annulation_faculte')
    ),
    CONSTRAINT validations_elections_decision_valide CHECK (decision IN ('valide', 'refuse'))
);

CREATE INDEX IF NOT EXISTS validations_elections_idx
    ON validations_elections (election_id, type_validation, cree_le DESC);

CREATE TABLE IF NOT EXISTS participations_etudiants (
    id BIGSERIAL PRIMARY KEY,
    election_id BIGINT NOT NULL REFERENCES elections(id) ON DELETE CASCADE,
    etudiant_id BIGINT NOT NULL REFERENCES etudiants(id) ON DELETE CASCADE,
    statut_participation VARCHAR(40) NOT NULL,
    a_vote_le TIMESTAMP WITHOUT TIME ZONE,
    cree_le TIMESTAMP WITHOUT TIME ZONE NOT NULL DEFAULT NOW(),
    CONSTRAINT participations_etudiants_unique UNIQUE (election_id, etudiant_id),
    CONSTRAINT participations_etudiants_statut_valide CHECK (
        statut_participation IN ('a_vote', 'abstention', 'annule')
    )
);

CREATE INDEX IF NOT EXISTS participations_statistiques_idx
    ON participations_etudiants (election_id, statut_participation);

CREATE TABLE IF NOT EXISTS bulletins_votes (
    id BIGSERIAL PRIMARY KEY,
    election_id BIGINT NOT NULL REFERENCES elections(id) ON DELETE CASCADE,
    candidat_id BIGINT NOT NULL REFERENCES candidats(id) ON DELETE RESTRICT,
    jeton_anonyme VARCHAR(120) NOT NULL,
    statut VARCHAR(40) NOT NULL DEFAULT 'valide',
    cree_le TIMESTAMP WITHOUT TIME ZONE NOT NULL DEFAULT NOW(),
    CONSTRAINT bulletins_votes_unique UNIQUE (election_id, jeton_anonyme),
    CONSTRAINT bulletins_votes_statut_valide CHECK (statut IN ('valide', 'annule'))
);

CREATE INDEX IF NOT EXISTS bulletins_votes_resultats_idx
    ON bulletins_votes (election_id, candidat_id, statut);

CREATE TABLE IF NOT EXISTS annulations_elections_facultes (
    id BIGSERIAL PRIMARY KEY,
    election_id BIGINT NOT NULL REFERENCES elections(id) ON DELETE CASCADE,
    faculte_id BIGINT NOT NULL REFERENCES facultes(id) ON DELETE RESTRICT,
    president_electoral_id BIGINT NOT NULL REFERENCES presidents_electoraux(id) ON DELETE RESTRICT,
    raison TEXT NOT NULL,
    cree_le TIMESTAMP WITHOUT TIME ZONE NOT NULL DEFAULT NOW(),
    CONSTRAINT annulations_elections_facultes_unique UNIQUE (election_id, faculte_id)
);

CREATE TABLE IF NOT EXISTS publications_resultats (
    id BIGSERIAL PRIMARY KEY,
    election_id BIGINT NOT NULL UNIQUE REFERENCES elections(id) ON DELETE CASCADE,
    president_electoral_id BIGINT NOT NULL REFERENCES presidents_electoraux(id) ON DELETE RESTRICT,
    publie_par_super_admin_id BIGINT REFERENCES utilisateurs(id) ON DELETE SET NULL,
    commentaire TEXT,
    cree_le TIMESTAMP WITHOUT TIME ZONE NOT NULL DEFAULT NOW()
);

COMMENT ON TABLE participations_etudiants IS 'Garde la trace du droit de vote sans stocker le candidat choisi. Un non-votant devient abstention apres cloture.';
COMMENT ON TABLE bulletins_votes IS 'Stocke le choix vote avec un jeton anonyme. Les statistiques restent anonymes cote etudiant.';
COMMENT ON TABLE annulations_elections_facultes IS 'Annulation decidee par le president electoral pour une faculte precise; ce n''est pas une abstention.';

COMMIT;
