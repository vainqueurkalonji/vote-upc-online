<section class="panneau-authentification panneau-large">
    <div class="entete-authentification">
        <p class="surtitre"><?= e(traduire('installation.surtitre')) ?></p>
        <h1><?= e(traduire('installation.titre')) ?></h1>
        <p>
            <?= e(traduire('installation.description')) ?>
        </p>
    </div>

    <?php if (!$base_prete): ?>
        <div class="alerte alerte-attention">
            <?= e(traduire('installation.base_attention')) ?>
            <strong>base_de_donnees/migrations/001_initiale_roles_utilisateurs.sql</strong>,
            <strong>base_de_donnees/migrations/002_academique_elections_vote.sql</strong>,
            <strong>base_de_donnees/migrations/003_emails_codes_securite.sql</strong>,
            <strong>base_de_donnees/donnees_initiales/001_facultes_promotions.sql</strong>.
        </div>
    <?php endif; ?>

    <?php foreach (['general', 'base_de_donnees'] as $cleErreur): ?>
        <?php if (!empty($erreurs[$cleErreur])): ?>
            <div class="alerte alerte-erreur"><?= e($erreurs[$cleErreur]) ?></div>
        <?php endif; ?>
    <?php endforeach; ?>

    <form method="post" action="/installation/super-administrateur" class="formulaire" novalidate>
        <div class="grille-formulaire">
            <label>
                <span><?= e(traduire('champ.nom')) ?></span>
                <input type="text" name="nom" value="<?= e(ancien('nom', $anciennes_donnees ?? [])) ?>" required>
                <?php if (!empty($erreurs['nom'])): ?><small><?= e($erreurs['nom']) ?></small><?php endif; ?>
            </label>

            <label>
                <span><?= e(traduire('champ.post_nom')) ?></span>
                <input type="text" name="post_nom" value="<?= e(ancien('post_nom', $anciennes_donnees ?? [])) ?>" required>
                <?php if (!empty($erreurs['post_nom'])): ?><small><?= e($erreurs['post_nom']) ?></small><?php endif; ?>
            </label>

            <label>
                <span><?= e(traduire('champ.prenom')) ?></span>
                <input type="text" name="prenom" value="<?= e(ancien('prenom', $anciennes_donnees ?? [])) ?>" required>
                <?php if (!empty($erreurs['prenom'])): ?><small><?= e($erreurs['prenom']) ?></small><?php endif; ?>
            </label>

            <label>
                <span><?= e(traduire('champ.nom_utilisateur')) ?></span>
                <input type="text" name="nom_utilisateur" value="<?= e(ancien('nom_utilisateur', $anciennes_donnees ?? [])) ?>" required>
                <?php if (!empty($erreurs['nom_utilisateur'])): ?><small><?= e($erreurs['nom_utilisateur']) ?></small><?php endif; ?>
            </label>

            <label class="champ-large">
                <span><?= e(traduire('champ.email_gmail')) ?></span>
                <input type="email" name="email" value="<?= e(ancien('email', $anciennes_donnees ?? [])) ?>" placeholder="exemple@gmail.com" required>
                <?php if (!empty($erreurs['email'])): ?><small><?= e($erreurs['email']) ?></small><?php endif; ?>
            </label>

            <label>
                <span><?= e(traduire('champ.mot_de_passe')) ?></span>
                <input type="password" name="mot_de_passe" required>
                <?php if (!empty($erreurs['mot_de_passe'])): ?><small><?= e($erreurs['mot_de_passe']) ?></small><?php endif; ?>
            </label>

            <label>
                <span><?= e(traduire('champ.confirmation')) ?></span>
                <input type="password" name="confirmation_mot_de_passe" required>
                <?php if (!empty($erreurs['confirmation_mot_de_passe'])): ?><small><?= e($erreurs['confirmation_mot_de_passe']) ?></small><?php endif; ?>
            </label>
        </div>

        <div class="regles-mot-de-passe">
            <strong><?= e(traduire('mot_de_passe.libelle')) ?></strong>
            <?= e(traduire('mot_de_passe.regle')) ?>
        </div>

        <button type="submit" class="bouton-principal"><?= e(traduire('bouton.creer_super_admin')) ?></button>
    </form>
</section>
