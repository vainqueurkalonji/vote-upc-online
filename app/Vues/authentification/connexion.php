<section class="panneau-authentification">
    <div class="entete-authentification">
        <p class="surtitre"><?= e($surtitre_connexion ?? traduire('connexion.surtitre')) ?></p>
        <h1><?= e($titre_connexion ?? traduire('connexion.titre')) ?></h1>
        <p><?= e($description_connexion ?? traduire('connexion.description')) ?></p>
    </div>

    <?php if (!empty($installation_reussie)): ?>
        <div class="alerte alerte-succes">
            <?= e(traduire('connexion.installation_ok')) ?>
        </div>
    <?php endif; ?>

    <?php if (!empty($message_succes)): ?>
        <div class="alerte alerte-succes"><?= e($message_succes) ?></div>
    <?php endif; ?>

    <?php if (!empty($erreurs['general'])): ?>
        <div class="alerte alerte-erreur"><?= e($erreurs['general']) ?></div>
    <?php endif; ?>

    <form class="formulaire" method="post" action="<?= e($action_connexion ?? '/connexion') ?>" novalidate>
        <label>
            <span><?= e($identifiant_libelle ?? traduire('connexion.identifiant')) ?></span>
            <input
                type="text"
                name="identifiant"
                value="<?= e(ancien('identifiant', $anciennes_donnees ?? [])) ?>"
                placeholder="<?= e($identifiant_placeholder ?? traduire('connexion.identifiant_placeholder')) ?>"
                autocomplete="username"
                required
            >
            <?php if (!empty($erreurs['identifiant'])): ?><small><?= e($erreurs['identifiant']) ?></small><?php endif; ?>
        </label>

        <label>
            <span><?= e(traduire('champ.mot_de_passe')) ?></span>
            <input
                type="password"
                name="mot_de_passe"
                placeholder="<?= e(traduire('connexion.mot_de_passe_placeholder')) ?>"
                autocomplete="current-password"
                required
            >
            <?php if (!empty($erreurs['mot_de_passe'])): ?><small><?= e($erreurs['mot_de_passe']) ?></small><?php endif; ?>
        </label>

        <div class="ligne-actions-formulaire">
            <a href="/mot-de-passe-oublie"><?= e(traduire('connexion.mot_de_passe_oublie')) ?></a>
        </div>

        <button type="submit" class="bouton-principal"><?= e($bouton_connexion ?? traduire('connexion.bouton')) ?></button>

        <?php if (!empty($lien_connexion_secondaire) && !empty($texte_connexion_secondaire)): ?>
            <a class="lien-secondaire-authentification" href="<?= e($lien_connexion_secondaire) ?>">
                <?= e($texte_connexion_secondaire) ?>
            </a>
        <?php endif; ?>
    </form>
</section>
