BEGIN;

INSERT INTO facultes (code, nom)
VALUES
    ('FASI', 'Faculte des Sciences Informatiques'),
    ('FASE', 'Faculte des Sciences Economiques'),
    ('MEDECINE', 'Faculte de Medecine'),
    ('DROIT', 'Faculte de Droit'),
    ('THEOLOGIE', 'Faculte de Theologie')
ON CONFLICT (code) DO UPDATE
SET nom = EXCLUDED.nom,
    active = TRUE,
    modifie_le = NOW();

INSERT INTO promotions (faculte_id, code, nom, ordre)
SELECT f.id, p.code, p.nom, p.ordre
FROM facultes f
JOIN (
    VALUES
        ('FASE', 'L1', 'Licence 1', 10),
        ('FASE', 'L2', 'Licence 2', 20),
        ('FASE', 'L3', 'Licence 3', 30),
        ('FASE', 'M1', 'Master 1', 40),
        ('FASE', 'M2', 'Master 2', 50),
        ('THEOLOGIE', 'L1', 'Licence 1', 10),
        ('THEOLOGIE', 'L2', 'Licence 2', 20),
        ('THEOLOGIE', 'L3', 'Licence 3', 30),
        ('THEOLOGIE', 'M1', 'Master 1', 40),
        ('THEOLOGIE', 'M2', 'Master 2', 50),
        ('DROIT', 'L1', 'Licence 1', 10),
        ('DROIT', 'L2', 'Licence 2', 20),
        ('DROIT', 'L3', 'Licence 3', 30),
        ('DROIT', 'M1', 'Master 1', 40),
        ('DROIT', 'M2', 'Master 2', 50),
        ('FASI', 'L1', 'Licence 1', 10),
        ('FASI', 'L2', 'Licence 2', 20),
        ('FASI', 'L3', 'Licence 3', 30),
        ('FASI', 'L4', 'Licence 4', 40),
        ('FASI', 'M1', 'Master 1', 50),
        ('FASI', 'M2', 'Master 2', 60),
        ('MEDECINE', 'G0', 'Graduat 0', 10),
        ('MEDECINE', 'L1', 'Licence 1', 20),
        ('MEDECINE', 'L2', 'Licence 2', 30),
        ('MEDECINE', 'L3', 'Licence 3', 40),
        ('MEDECINE', 'PR_BACH1', 'Pre-Bachelier 1', 50),
        ('MEDECINE', 'BACH2', 'Bachelier 2', 60),
        ('MEDECINE', 'BACH3', 'Bachelier 3', 70),
        ('MEDECINE', 'D4', 'Doctorat 4', 80)
) AS p(faculte_code, code, nom, ordre)
    ON p.faculte_code = f.code
ON CONFLICT (faculte_id, code) DO UPDATE
SET nom = EXCLUDED.nom,
    ordre = EXCLUDED.ordre,
    active = TRUE,
    modifie_le = NOW();

COMMIT;
