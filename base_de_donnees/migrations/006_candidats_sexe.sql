BEGIN;

ALTER TABLE candidats
    ADD COLUMN IF NOT EXISTS sexe VARCHAR(20);

DO $$
BEGIN
    IF NOT EXISTS (
        SELECT 1
        FROM pg_constraint
        WHERE conname = 'candidats_sexe_valide'
    ) THEN
        ALTER TABLE candidats
            ADD CONSTRAINT candidats_sexe_valide CHECK (sexe IS NULL OR sexe IN ('M', 'F'));
    END IF;
END $$;

COMMIT;
