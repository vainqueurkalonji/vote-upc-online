<?php
$valeur = static fn(string $cle, string $defaut = ''): string => ancien($cle, $anciennes_donnees ?? [], (string) ($etudiant[$cle] ?? $defaut));
?>

<section class="entete-page entete-page-module">
    <div>
        <p class="surtitre">Super administrateur</p>
        <h1>Modifier un etudiant</h1>
        <p>Corriger une erreur d'identite, email, matricule ou rattachement academique.</p>
    </div>
    <span class="badge-page"><?= e($etudiant['statut']) ?></span>
</section>

<?php if (!empty($erreurs['general'])): ?>
    <div class="alerte alerte-erreur"><?= e($erreurs['general']) ?></div>
<?php endif; ?>

<section class="bloc-module">
    <div class="titre-bloc">
        <div>
            <p class="surtitre">Fiche etudiant</p>
            <h2><?= e(trim($etudiant['nom'] . ' ' . $etudiant['post_nom'] . ' ' . $etudiant['prenom'])) ?></h2>
        </div>
        <a class="lien-action" href="/super-administrateur/etudiants?q=<?= e(urlencode((string) $etudiant['matricule'])) ?>">Retour</a>
    </div>

    <form method="post" action="/super-administrateur/etudiants/modifier" class="formulaire">
        <input type="hidden" name="etudiant_id" value="<?= e($etudiant['id']) ?>">
        <div class="grille-formulaire">
            <label>
                <span>Nom</span>
                <input type="text" name="nom" data-majuscules autocapitalize="characters" value="<?= e($valeur('nom')) ?>" required>
                <?php if (!empty($erreurs['nom'])): ?><small><?= e($erreurs['nom']) ?></small><?php endif; ?>
            </label>
            <label>
                <span>Post-nom</span>
                <input type="text" name="post_nom" data-majuscules autocapitalize="characters" value="<?= e($valeur('post_nom')) ?>" required>
                <?php if (!empty($erreurs['post_nom'])): ?><small><?= e($erreurs['post_nom']) ?></small><?php endif; ?>
            </label>
            <label>
                <span>Prenom</span>
                <input type="text" name="prenom" data-majuscules autocapitalize="characters" value="<?= e($valeur('prenom')) ?>" required>
                <?php if (!empty($erreurs['prenom'])): ?><small><?= e($erreurs['prenom']) ?></small><?php endif; ?>
            </label>
            <label>
                <span>Matricule</span>
                <input type="text" name="matricule" data-majuscules autocapitalize="characters" value="<?= e($valeur('matricule')) ?>" required>
                <?php if (!empty($erreurs['matricule'])): ?><small><?= e($erreurs['matricule']) ?></small><?php endif; ?>
            </label>
            <label>
                <span>Email</span>
                <input type="email" name="email" value="<?= e($valeur('email')) ?>">
                <?php if (!empty($erreurs['email'])): ?><small><?= e($erreurs['email']) ?></small><?php endif; ?>
            </label>
            <label>
                <span>Sexe</span>
                <select name="sexe">
                    <option value="">Non precise</option>
                    <option value="M" <?= $valeur('sexe') === 'M' ? 'selected' : '' ?>>Masculin</option>
                    <option value="F" <?= $valeur('sexe') === 'F' ? 'selected' : '' ?>>Feminin</option>
                </select>
                <?php if (!empty($erreurs['sexe'])): ?><small><?= e($erreurs['sexe']) ?></small><?php endif; ?>
            </label>
            <label>
                <span>Date de naissance</span>
                <input type="date" name="date_naissance" value="<?= e($valeur('date_naissance')) ?>">
                <?php if (!empty($erreurs['date_naissance'])): ?><small><?= e($erreurs['date_naissance']) ?></small><?php endif; ?>
            </label>
            <label>
                <span>Lieu de naissance</span>
                <input type="text" name="lieu_naissance" data-majuscules autocapitalize="characters" value="<?= e($valeur('lieu_naissance')) ?>">
            </label>
            <label>
                <span>Faculte</span>
                <select name="faculte_id" data-select-faculte required>
                    <option value="">Choisir une faculte</option>
                    <?php foreach (($catalogue_academique ?? []) as $faculte): ?>
                        <option value="<?= e($faculte['id']) ?>" <?= $valeur('faculte_id') === (string) $faculte['id'] ? 'selected' : '' ?>>
                            <?= e($faculte['code'] . ' - ' . $faculte['nom']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <?php if (!empty($erreurs['faculte_id'])): ?><small><?= e($erreurs['faculte_id']) ?></small><?php endif; ?>
            </label>
            <label>
                <span>Promotion</span>
                <select name="promotion_id" data-faculte-cible required>
                    <option value="">Choisir une promotion</option>
                    <?php foreach (($catalogue_academique ?? []) as $faculte): ?>
                        <?php foreach (($faculte['promotions'] ?? []) as $promotion): ?>
                            <option
                                value="<?= e($promotion['id']) ?>"
                                data-faculte-id="<?= e($faculte['id']) ?>"
                                <?= $valeur('promotion_id') === (string) $promotion['id'] ? 'selected' : '' ?>
                            >
                                <?= e($faculte['code'] . ' - ' . $promotion['code']) ?>
                            </option>
                        <?php endforeach; ?>
                    <?php endforeach; ?>
                </select>
                <?php if (!empty($erreurs['promotion_id'])): ?><small><?= e($erreurs['promotion_id']) ?></small><?php endif; ?>
            </label>
            <label class="champ-large">
                <span>Departement</span>
                <select name="departement_id" data-faculte-cible>
                    <option value="">Aucun departement precis</option>
                    <?php foreach (($departements ?? []) as $departement): ?>
                        <option
                            value="<?= e($departement['id']) ?>"
                            data-faculte-id="<?= e($departement['faculte_id']) ?>"
                            <?= $valeur('departement_id') === (string) $departement['id'] ? 'selected' : '' ?>
                        >
                            <?= e($departement['faculte_code'] . ' - ' . $departement['nom']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </label>
        </div>

        <button type="submit" class="bouton-principal">Enregistrer les modifications</button>
    </form>
</section>
