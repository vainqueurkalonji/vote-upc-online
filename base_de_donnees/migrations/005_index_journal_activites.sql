BEGIN;

CREATE INDEX IF NOT EXISTS journaux_actions_utilisateur_date_idx
    ON journaux_actions (utilisateur_id, cree_le DESC);

CREATE INDEX IF NOT EXISTS journaux_actions_action_date_idx
    ON journaux_actions (action, cree_le DESC);

CREATE INDEX IF NOT EXISTS journaux_actions_date_idx
    ON journaux_actions (cree_le DESC);

COMMIT;
